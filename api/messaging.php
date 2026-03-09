<?php
/**
 * api/messaging.php
 *
 * GET  ?action=conversations            → list of DM threads + groups
 * GET  ?action=messages&with=USER_ID    → messages between current user and USER_ID
 * GET  ?action=group_messages&group=staff|parents_staff
 * POST {type:'user',  to_user_id, body} → send DM
 * POST {type:'group', group, body}      → send group message
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['school_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

$school_id   = (int) $_SESSION['school_id'];
$current_uid = (int) $_SESSION['user_id'];
$current_role = $_SESSION['role'] ?? '';
$current_name = $_SESSION['full_name'] ?? '';

$conn = get_db_connection();

if (!$current_role || !$current_name || $current_name === 'User') {
    $us = $conn->prepare("SELECT full_name, role FROM users WHERE id = ? AND school_id = ?");
    $us->bind_param('ii', $current_uid, $school_id);
    $us->execute();
    $u_res = $us->get_result()->fetch_assoc();
    if ($u_res) {
        $current_name = $u_res['full_name'];
        $current_role = $u_res['role'];
    } else {
        $current_name = 'User';
        $current_role = 'admin';
    }
    $us->close();
}

// ── Helper: relative time ─────────────────────────────────────────────
function time_ago(string $ts): string {
    $diff = time() - strtotime($ts);
    if ($diff < 60)      return 'Just now';
    if ($diff < 3600)    return floor($diff / 60) . 'm ago';
    if ($diff < 86400)   return floor($diff / 3600) . 'h ago';
    if ($diff < 604800)  return floor($diff / 86400) . 'd ago';
    return date('d M', strtotime($ts));
}

function make_initials(string $name): string {
    $parts = explode(' ', trim($name));
    $ini = '';
    foreach (array_slice($parts, 0, 2) as $p) $ini .= strtoupper(substr($p, 0, 1));
    return $ini ?: '?';
}

// ── Role → colour map ─────────────────────────────────────────────────
function role_color(string $role): string {
    return match($role) {
        'admin'      => 'indigo',
        'teacher'    => 'emerald',
        'parent'     => 'amber',
        'accountant' => 'sky',
        'driver'     => 'slate',
        default      => 'violet',
    };
}

// ───────────────────────────────────────────────────────────────────────
// GET requests
// ───────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'conversations';

    // ── List conversations ────────────────────────────────────────────
    if ($action === 'conversations') {
        // Get recent DM partners: people current user has messaged or received from
        $stmt = $conn->prepare("
            SELECT 
                CASE
                    WHEN sender_id = ? THEN recipient_user_id
                    ELSE sender_id
                END AS partner_id,
                MAX(created_at) AS last_msg_time
            FROM school_messages
            WHERE school_id = ?
              AND recipient_type = 'user'
              AND (sender_id = ? OR recipient_user_id = ?)
            GROUP BY partner_id
            ORDER BY last_msg_time DESC
            LIMIT 20
        ");
        $stmt->bind_param('iiii', $current_uid, $school_id, $current_uid, $current_uid);
        $stmt->execute();
        $res  = $stmt->get_result();
        $partner_ids = [];
        while ($r = $res->fetch_assoc()) {
            if ($r['partner_id']) $partner_ids[] = (int)$r['partner_id'];
        }
        $stmt->close();

        $conversations = [];
        foreach ($partner_ids as $pid) {
            // Get partner info
            $us = $conn->prepare("SELECT id, full_name, role FROM users WHERE id = ? AND school_id = ?");
            $us->bind_param('ii', $pid, $school_id);
            $us->execute();
            $partner = $us->get_result()->fetch_assoc();
            $us->close();
            if (!$partner) continue;

            // Get latest message
            $lm = $conn->prepare("
                SELECT body, created_at FROM school_messages
                WHERE school_id = ? AND recipient_type='user'
                  AND ((sender_id=? AND recipient_user_id=?) OR (sender_id=? AND recipient_user_id=?))
                ORDER BY created_at DESC LIMIT 1
            ");
            $lm->bind_param('iiiii', $school_id, $current_uid, $pid, $pid, $current_uid);
            $lm->execute();
            $last = $lm->get_result()->fetch_assoc();
            $lm->close();

            // Get unread count
            $uc = $conn->prepare("
                SELECT COUNT(*) as unread FROM school_messages
                WHERE school_id = ? AND recipient_type='user' 
                  AND sender_id = ? AND recipient_user_id = ? AND is_read = 0
            ");
            $uc->bind_param('iii', $school_id, $pid, $current_uid);
            $uc->execute();
            $unread = (int)$uc->get_result()->fetch_assoc()['unread'];
            $uc->close();

            $conversations[] = [
                'type'      => 'dm',
                'partner_id'=> (int)$partner['id'],
                'name'      => $partner['full_name'],
                'role'      => $partner['role'],
                'initials'  => make_initials($partner['full_name']),
                'color'     => role_color($partner['role']),
                'last_msg'  => $last ? mb_substr($last['body'], 0, 60) : '',
                'ago'       => $last ? time_ago($last['created_at']) : '',
                'unread'    => $unread
            ];
        }

        // Get absolute total unread across all DMs
        $tc = $conn->prepare("
            SELECT COUNT(*) as total_unread FROM school_messages
            WHERE school_id = ? AND recipient_type='user' 
              AND recipient_user_id = ? AND is_read = 0
        ");
        $tc->bind_param('ii', $school_id, $current_uid);
        $tc->execute();
        $total_unread = (int)$tc->get_result()->fetch_assoc()['total_unread'];
        $tc->close();

        echo json_encode([
            'conversations' => $conversations,
            'total_unread' => $total_unread
        ]);
        $conn->close();
        exit;
    }

    // ── DM thread ────────────────────────────────────────────────────
    if ($action === 'messages') {
        $with = (int)($_GET['with'] ?? 0);
        if (!$with) { echo json_encode(['messages' => []]); exit; }

        // Mark as read
        $upd = $conn->prepare("
            UPDATE school_messages 
            SET is_read = 1 
            WHERE school_id = ? AND recipient_type='user' AND sender_id = ? AND recipient_user_id = ? AND is_read = 0
        ");
        $upd->bind_param('iii', $school_id, $with, $current_uid);
        $upd->execute();
        $upd->close();

        $stmt = $conn->prepare("
            SELECT m.id, m.sender_id, m.sender_name, m.sender_role, m.body, m.created_at
            FROM school_messages m
            WHERE m.school_id = ?
              AND m.recipient_type = 'user'
              AND (
                  (m.sender_id = ? AND m.recipient_user_id = ?)
               OR (m.sender_id = ? AND m.recipient_user_id = ?)
              )
            ORDER BY m.created_at ASC
            LIMIT 100
        ");
        $stmt->bind_param('iiiii', $school_id, $current_uid, $with, $with, $current_uid);
        $stmt->execute();
        $res = $stmt->get_result();

        $messages = [];
        while ($r = $res->fetch_assoc()) {
            $messages[] = [
                'id'          => (int)$r['id'],
                'sender_id'   => (int)$r['sender_id'],
                'sender_name' => $r['sender_name'],
                'sender_role' => $r['sender_role'],
                'initials'    => make_initials($r['sender_name']),
                'color'       => role_color($r['sender_role']),
                'body'        => $r['body'],
                'ago'         => time_ago($r['created_at']),
                'is_mine'     => (int)$r['sender_id'] === $current_uid,
            ];
        }
        $stmt->close();
        $conn->close();
        echo json_encode(['messages' => $messages]);
        exit;
    }

    // ── Group messages ────────────────────────────────────────────────
    if ($action === 'group_messages') {
        $group = $_GET['group'] ?? '';
        if (!in_array($group, ['staff', 'parents_staff'])) {
            echo json_encode(['messages' => []]); exit;
        }

        // For 'staff' group: only admin/teacher/accountant/driver can see it
        // For 'parents_staff': everyone can see it
        $stmt = $conn->prepare("
            SELECT id, sender_id, sender_name, sender_role, body, created_at
            FROM school_messages
            WHERE school_id = ?
              AND recipient_type = 'group'
              AND recipient_group = ?
            ORDER BY created_at ASC
            LIMIT 100
        ");
        $stmt->bind_param('is', $school_id, $group);
        $stmt->execute();
        $res = $stmt->get_result();

        $messages = [];
        while ($r = $res->fetch_assoc()) {
            $messages[] = [
                'id'          => (int)$r['id'],
                'sender_id'   => (int)$r['sender_id'],
                'sender_name' => $r['sender_name'],
                'sender_role' => $r['sender_role'],
                'initials'    => make_initials($r['sender_name']),
                'color'       => role_color($r['sender_role']),
                'body'        => $r['body'],
                'ago'         => time_ago($r['created_at']),
                'is_mine'     => (int)$r['sender_id'] === $current_uid,
            ];
        }
        $stmt->close();
        $conn->close();
        echo json_encode(['messages' => $messages]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action']);
    $conn->close();
    exit;
}

// ───────────────────────────────────────────────────────────────────────
// POST → send a message
// ───────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) { parse_str(file_get_contents('php://input'), $data); }

    $type = $data['type'] ?? '';
    $body = trim($data['body'] ?? '');

    if (!$body) {
        echo json_encode(['error' => 'Message body is required']); exit;
    }

    if ($type === 'user') {
        $to_user_id = (int)($data['to_user_id'] ?? 0);
        if (!$to_user_id) { echo json_encode(['error' => 'Recipient required']); exit; }

        // Verify recipient is in same school
        $chk = $conn->prepare("SELECT id, full_name, role FROM users WHERE id=? AND school_id=?");
        $chk->bind_param('ii', $to_user_id, $school_id);
        $chk->execute();
        $recipient = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$recipient) { echo json_encode(['error' => 'Recipient not found']); exit; }

        $stmt = $conn->prepare("
            INSERT INTO school_messages
                (school_id, sender_id, sender_role, sender_name, recipient_type, recipient_user_id, body)
            VALUES (?, ?, ?, ?, 'user', ?, ?)
        ");
        $stmt->bind_param('iissis', $school_id, $current_uid, $current_role, $current_name, $to_user_id, $body);
        $stmt->execute();
        $new_id = $conn->insert_id;
        $stmt->close();

        // Insert notification for recipient (new message)
        $notif_title = 'New message from ' . $current_name;
        $notif_body  = mb_substr($body, 0, 80);
        $color = role_color($current_role);
        $target_group = in_array($recipient['role'], ['parent']) ? 'parents_staff' : 'staff';
        $ns = $conn->prepare("
            INSERT INTO school_notifications (school_id, target_group, type, title, body, actor_name, color)
            VALUES (?, ?, 'message', ?, ?, ?, ?)
        ");
        $ns->bind_param('isssss', $school_id, $target_group, $notif_title, $notif_body, $current_name, $color);
        $ns->execute();
        $ns->close();

        $conn->close();
        echo json_encode(['success' => true, 'id' => $new_id]);
        exit;
    }

    if ($type === 'group') {
        $group = $data['group'] ?? '';
        if (!in_array($group, ['staff', 'parents_staff'])) {
            echo json_encode(['error' => 'Invalid group']); exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO school_messages
                (school_id, sender_id, sender_role, sender_name, recipient_type, recipient_group, body)
            VALUES (?, ?, ?, ?, 'group', ?, ?)
        ");
        $stmt->bind_param('iissss', $school_id, $current_uid, $current_role, $current_name, $group, $body);
        $stmt->execute();
        $new_id = $conn->insert_id;
        $stmt->close();

        // Insert notification for the group
        $group_label  = $group === 'staff' ? 'Staff & Admin' : 'Parents, Staff & Admin';
        $notif_title  = $current_name . ' sent a message to ' . $group_label;
        $notif_body   = mb_substr($body, 0, 80);
        $color        = role_color($current_role);
        $target_group = $group; // same group as message
        $ns = $conn->prepare("
            INSERT INTO school_notifications (school_id, target_group, type, title, body, actor_name, color)
            VALUES (?, ?, 'message', ?, ?, ?, ?)
        ");
        $ns->bind_param('isssss', $school_id, $target_group, $notif_title, $notif_body, $current_name, $color);
        $ns->execute();
        $ns->close();

        $conn->close();
        echo json_encode(['success' => true, 'id' => $new_id]);
        exit;
    }

    echo json_encode(['error' => 'Invalid message type']);
    $conn->close();
}

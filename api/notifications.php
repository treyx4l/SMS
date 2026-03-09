<?php
/**
 * api/notifications.php
 *
 * GET                     → returns last 30 activity notifications in 2 groups
 * POST {action:'mark_read'} → clears unread count for this session (client-side localStorage is primary)
 *
 * Groups returned:
 *  parents_staff  → visible to parents + staff + admin
 *  staff          → visible to staff + admin only
 *
 * Types auto-created by other API calls: message, student_added, student_updated,
 *   teacher_added, parent_added, driver_added, accountant_added, general
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['school_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

$school_id    = (int) $_SESSION['school_id'];
$current_role = $_SESSION['role'] ?? 'admin';

function time_ago_notif(string $ts): string {
    $diff = time() - strtotime($ts);
    if ($diff < 60)      return 'Just now';
    if ($diff < 3600)    return floor($diff / 60) . 'm ago';
    if ($diff < 86400)   return floor($diff / 3600) . 'h ago';
    if ($diff < 604800)  return floor($diff / 86400) . 'd ago';
    return date('d M', strtotime($ts));
}

$conn = get_db_connection();

// ── GET ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Determine which groups to show based on role
    $is_parent = ($current_role === 'parent');

    // parents_staff group: everyone sees this
    $stmt1 = $conn->prepare("
        SELECT id, type, title, body, actor_name, color, link, created_at
        FROM school_notifications
        WHERE school_id = ?
          AND target_group IN ('parents_staff','all')
        ORDER BY created_at DESC
        LIMIT 15
    ");
    $stmt1->bind_param('i', $school_id);
    $stmt1->execute();
    $res1 = $stmt1->get_result();
    $group_parents_staff = [];
    while ($r = $res1->fetch_assoc()) {
        $group_parents_staff[] = [
            'id'         => (int)$r['id'],
            'type'       => $r['type'],
            'title'      => $r['title'],
            'body'       => $r['body'],
            'actor_name' => $r['actor_name'],
            'color'      => $r['color'] ?: 'indigo',
            'link'       => $r['link'] ?: '',
            'ago'        => time_ago_notif($r['created_at']),
        ];
    }
    $stmt1->close();

    // staff group: only non-parents see this
    $group_staff = [];
    if (!$is_parent) {
        $stmt2 = $conn->prepare("
            SELECT id, type, title, body, actor_name, color, link, created_at
            FROM school_notifications
            WHERE school_id = ?
              AND target_group IN ('staff','all')
            ORDER BY created_at DESC
            LIMIT 15
        ");
        $stmt2->bind_param('i', $school_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($r = $res2->fetch_assoc()) {
            // Avoid duplicates with 'all' already shown above
            $already = false;
            foreach ($group_parents_staff as $ex) {
                if ($ex['id'] === (int)$r['id']) { $already = true; break; }
            }
            if (!$already) {
                $group_staff[] = [
                    'id'         => (int)$r['id'],
                    'type'       => $r['type'],
                    'title'      => $r['title'],
                    'body'       => $r['body'],
                    'actor_name' => $r['actor_name'],
                    'color'      => $r['color'] ?: 'emerald',
                    'link'       => $r['link'] ?: '',
                    'ago'        => time_ago_notif($r['created_at']),
                ];
            }
        }
        $stmt2->close();
    }

    $conn->close();
    echo json_encode([
        'parents_staff' => $group_parents_staff,
        'staff'         => $group_staff,
    ]);
    exit;
}

// ── POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For now, mark-read is handled client-side via localStorage.
    // This endpoint exists for future server-side read tracking.
    echo json_encode(['success' => true]);
    $conn->close();
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
$conn->close();

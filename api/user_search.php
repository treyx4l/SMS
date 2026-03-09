<?php
/**
 * api/user_search.php
 * Search users within the same school by name.
 * Used by the chat modal's compose / new DM search box.
 *
 * GET  ?q=search_term   → returns [{id, full_name, role, initials}]
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

// Must be authenticated
if (empty($_SESSION['user_id']) || empty($_SESSION['school_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

$school_id   = (int) $_SESSION['school_id'];
$current_uid = (int) $_SESSION['user_id'];
$q           = trim($_GET['q'] ?? '');

if (strlen($q) < 1) {
    echo json_encode(['users' => []]);
    exit;
}

$conn = get_db_connection();

$stmt = null;
if (isset($_GET['id'])) {
    $id_search = (int)$_GET['id'];
    $stmt = $conn->prepare(
        "SELECT id, full_name, role
         FROM users
         WHERE school_id = ?
           AND id = ?"
    );
    $stmt->bind_param('ii', $school_id, $id_search);
} else {
    // Search users in the same school, exclude the current user
    $stmt = $conn->prepare(
        "SELECT id, full_name, role
         FROM users
         WHERE school_id = ?
           AND id != ?
           AND full_name LIKE ?
         ORDER BY full_name
         LIMIT 15"
    );
    $like = '%' . $q . '%';
    $stmt->bind_param('iis', $school_id, $current_uid, $like);
}

$stmt->execute();
$res = $stmt->get_result();

$users = [];
while ($row = $res->fetch_assoc()) {
    // Build initials from name
    $parts    = explode(' ', trim($row['full_name']));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $initials .= strtoupper(substr($p, 0, 1));
    }
    $users[] = [
        'id'        => (int) $row['id'],
        'full_name' => $row['full_name'],
        'role'      => $row['role'],
        'initials'  => $initials ?: '?',
    ];
}

$stmt->close();
$conn->close();

echo json_encode(['users' => $users]);

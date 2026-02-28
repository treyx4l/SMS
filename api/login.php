<?php

require_once __DIR__ . '/firebase_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Method not allowed'], 405);
}

$data    = read_json_input();
$idToken = $data['idToken'] ?? '';
$requestedRole = $data['role'] ?? null;

if ($idToken === '') {
    send_json(['error' => 'Missing token'], 422);
}

$verified = verify_firebase_id_token($idToken);
if (!$verified) {
    send_json(['error' => 'Invalid token'], 401);
}

$uid   = $verified['uid']   ?? null;
$email = $verified['email'] ?? null;

if (!$uid || !$email) {
    send_json(['error' => 'Token missing uid/email'], 400);
}

$conn = get_db_connection();

$stmt = $conn->prepare(
    "SELECT u.id, u.school_id, u.role
     FROM users u
     WHERE u.firebase_uid = ?"
);
$stmt->bind_param('s', $uid);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();

// If not found by UID, try to auto-link by email for teacher, parent, driver, accountant.
if (!$user && in_array($requestedRole, ['teacher', 'parent', 'driver', 'accountant'], true)) {
    $tables = [
        'teacher'    => 'teachers',
        'parent'     => 'parents',
        'driver'     => 'bus_drivers',
        'accountant' => 'accountants',
    ];
    $table = $tables[$requestedRole] ?? null;

    if ($table) {
        $stmt = $conn->prepare("SELECT school_id, full_name FROM {$table} WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $schoolId = (int) $row['school_id'];
            $fullName = $row['full_name'] ?? $verified['name'] ?? $email;

            $stmt = $conn->prepare(
                "INSERT INTO users (school_id, firebase_uid, email, full_name, role)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('issss', $schoolId, $uid, $email, $fullName, $requestedRole);
            $stmt->execute();
            $newUserId = (int) $stmt->insert_id;
            $stmt->close();

            $user = [
                'id'        => $newUserId,
                'school_id' => $schoolId,
                'role'      => $requestedRole,
            ];
        }
    }
}

if (!$user) {
    send_json(['error' => 'User not registered in Axis SMS'], 404);
}

$_SESSION['user_id']   = (int) $user['id'];
$_SESSION['school_id'] = (int) $user['school_id'];
$_SESSION['user_role'] = $user['role'];

$redirect = '/SMS/admin/dashboard.php';
if ($user['role'] === 'teacher') {
    $redirect = '/SMS/teacher/dashboard.php';
} elseif ($user['role'] === 'parent') {
    $redirect = '/SMS/parent/dashboard.php';
} elseif ($user['role'] === 'driver') {
    $redirect = '/SMS/driver/dashboard.php';
} elseif ($user['role'] === 'accountant') {
    $redirect = '/SMS/accountant/dashboard.php';
}

send_json([
    'success'      => true,
    'redirect_url' => $redirect,
]);


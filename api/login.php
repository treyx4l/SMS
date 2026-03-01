<?php

require_once __DIR__ . '/firebase_helpers.php';
require_once dirname(__DIR__) . '/config.php';

// Ensure PHP warnings/notices don't corrupt JSON output
if (!headers_sent()) {
    ini_set('display_errors', '0');
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Method not allowed'], 405);
}

$data         = read_json_input();
$requestedRole = $data['role'] ?? null;
$idToken      = $data['idToken'] ?? '';
$email        = trim($data['email'] ?? '');
$password     = $data['password'] ?? '';

$user = null;

// Admin: Firebase auth only
if ($requestedRole === 'admin') {
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
        "SELECT u.id, u.school_id, u.role FROM users u WHERE u.firebase_uid = ?"
    );
    $stmt->bind_param('s', $uid);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        send_json(['error' => 'Admin not registered in Axis SMS'], 404);
    }
}
// Teacher, parent, driver, accountant: MySQL auth
elseif (in_array($requestedRole, ['teacher', 'parent', 'driver', 'accountant'], true)) {
    if ($email === '' || $password === '') {
        send_json(['error' => 'Email and password required'], 422);
    }

    $tables = [
        'teacher'    => ['table' => 'teachers', 'id_col' => 'id'],
        'parent'     => ['table' => 'parents', 'id_col' => 'id'],
        'driver'     => ['table' => 'bus_drivers', 'id_col' => 'id'],
        'accountant' => ['table' => 'accountants', 'id_col' => 'id'],
    ];
    $cfg = $tables[$requestedRole] ?? null;
    if (!$cfg) {
        send_json(['error' => 'Invalid role'], 400);
    }

    $conn = get_db_connection();
    $res = $conn->query("SHOW COLUMNS FROM {$cfg['table']} LIKE 'password_hash'");
    if (!$res || $res->num_rows === 0) {
        send_json(['error' => 'Local auth not configured. Run database_migration_local_auth_passwords.sql'], 500);
    }

    $stmt = $conn->prepare(
        "SELECT {$cfg['id_col']} AS rid, school_id, full_name, password_hash FROM {$cfg['table']} WHERE email = ? LIMIT 1"
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !$row['password_hash']) {
        send_json(['error' => 'Invalid email or password'], 401);
    }
    if (!password_verify($password, $row['password_hash'])) {
        send_json(['error' => 'Invalid email or password'], 401);
    }

    $localUid = 'local:' . $requestedRole . ':' . (int) $row['rid'];
    $schoolId = (int) $row['school_id'];
    $fullName = $row['full_name'] ?? $email;

    // Get or create users row
    $stmt = $conn->prepare(
        "SELECT id, school_id, role FROM users WHERE firebase_uid = ?"
    );
    $stmt->bind_param('s', $localUid);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $stmt = $conn->prepare(
            "INSERT INTO users (school_id, firebase_uid, email, full_name, role) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issss', $schoolId, $localUid, $email, $fullName, $requestedRole);
        $stmt->execute();
        $user = [
            'id'        => (int) $stmt->insert_id,
            'school_id' => $schoolId,
            'role'      => $requestedRole,
        ];
        $stmt->close();
    }
} else {
    send_json(['error' => 'Invalid role'], 400);
}

if (!$user) {
    send_json(['error' => 'Login failed'], 401);
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

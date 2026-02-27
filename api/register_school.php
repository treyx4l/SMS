<?php

require_once __DIR__ . '/firebase_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Method not allowed'], 405);
}

$data = read_json_input();
$idToken    = $data['idToken']    ?? '';
$schoolName = trim($data['school_name'] ?? '');
$schoolCode = trim($data['school_code'] ?? '');

if ($idToken === '' || $schoolName === '' || $schoolCode === '') {
    send_json(['error' => 'Missing required fields'], 422);
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

// Ensure school code is unique
$stmt = $conn->prepare("SELECT id FROM schools WHERE code = ?");
$stmt->bind_param('s', $schoolCode);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    send_json(['error' => 'School code already exists'], 409);
}
$stmt->close();

// Create school
$stmt = $conn->prepare("INSERT INTO schools (name, code) VALUES (?, ?)");
$stmt->bind_param('ss', $schoolName, $schoolCode);
$stmt->execute();
$schoolId = (int) $stmt->insert_id;
$stmt->close();

// Create admin user linked to this school
$fullName = $data['admin_name'] ?? $email;
$stmt     = $conn->prepare(
    "INSERT INTO users (school_id, firebase_uid, email, full_name, role)
     VALUES (?, ?, ?, ?, 'admin')"
);
$stmt->bind_param('isss', $schoolId, $uid, $email, $fullName);
$stmt->execute();
$userId = (int) $stmt->insert_id;
$stmt->close();

$_SESSION['user_id']   = $userId;
$_SESSION['school_id'] = $schoolId;
$_SESSION['user_role'] = 'admin';

send_json([
    'success'      => true,
    'redirect_url' => '/SMS/admin/dashboard.php',
]);


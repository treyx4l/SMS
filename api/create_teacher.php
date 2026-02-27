<?php
/**
 * api/create_teacher.php
 * Called from admin teacher form after Firebase account is created client-side.
 * Receives the new teacher's idToken + profile data, writes to users + teachers tables.
 */
require_once __DIR__ . '/firebase_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Method not allowed'], 405);
}

// Ensure caller is an admin
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    send_json(['error' => 'Forbidden — admin only'], 403);
}

$data      = read_json_input();
$idToken   = trim($data['idToken']   ?? '');
$fullName  = trim($data['full_name'] ?? '');
$phone     = trim($data['phone']     ?? '');
$address   = trim($data['address']   ?? '');
$schoolId  = (int) ($_SESSION['school_id'] ?? 0);

if ($idToken === '' || $fullName === '' || $schoolId === 0) {
    send_json(['error' => 'Missing required fields (idToken, full_name)'], 422);
}

// Verify the Firebase token for the newly created teacher account
$verified = verify_firebase_id_token($idToken);
if (!$verified) {
    send_json(['error' => 'Invalid Firebase token'], 401);
}

$uid   = $verified['uid']   ?? null;
$email = $verified['email'] ?? null;

if (!$uid || !$email) {
    send_json(['error' => 'Token missing uid or email'], 400);
}

$conn = get_db_connection();

// Check if this Firebase UID is already registered
$stmt = $conn->prepare("SELECT id FROM users WHERE firebase_uid = ?");
$stmt->bind_param('s', $uid);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    send_json(['error' => 'An account with this email already exists in this school'], 409);
}

// --- Transaction: insert into users AND teachers ---
$conn->begin_transaction();

try {
    // 1. Insert into users table (this is what allows login)
    $stmt = $conn->prepare(
        "INSERT INTO users (school_id, firebase_uid, email, full_name, role)
         VALUES (?, ?, ?, ?, 'teacher')"
    );
    $stmt->bind_param('isss', $schoolId, $uid, $email, $fullName);
    $stmt->execute();
    $userId = (int) $stmt->insert_id;
    $stmt->close();

    // 2. Insert into teachers table (detailed profile)
    $stmt = $conn->prepare(
        "INSERT INTO teachers (school_id, full_name, email, phone, address)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('issss', $schoolId, $fullName, $email, $phone, $address);
    $stmt->execute();
    $teacherId = (int) $stmt->insert_id;
    $stmt->close();

    $conn->commit();

    send_json([
        'success'    => true,
        'user_id'    => $userId,
        'teacher_id' => $teacherId,
        'message'    => "Teacher account created for {$fullName}",
    ]);
} catch (Exception $e) {
    $conn->rollback();
    send_json(['error' => 'Database error: ' . $e->getMessage()], 500);
}

<?php
require_once __DIR__ . '/firebase_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Method not allowed'], 405);
}

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    send_json(['error' => 'Forbidden — admin only'], 403);
}

$data     = read_json_input();
$fullName = trim($data['full_name'] ?? '');
$email    = trim($data['email'] ?? '');
$phone    = trim($data['phone'] ?? '');
$address  = trim($data['address'] ?? '');
$password = $data['password'] ?? '';
$schoolId = (int) ($_SESSION['school_id'] ?? 0);

if ($fullName === '' || $email === '' || $schoolId === 0) {
    send_json(['error' => 'Full name and email are required'], 422);
}
if (strlen($password) < 6) {
    send_json(['error' => 'Password must be at least 6 characters'], 422);
}

$conn = get_db_connection();

// Cap: max accountants per school
$cap = $conn->prepare("SELECT COUNT(*) FROM accountants WHERE school_id = ?");
$cap->bind_param('i', $schoolId);
$cap->execute();
if ((int) $cap->get_result()->fetch_row()[0] >= SCHOOL_LIMIT_ACCOUNTANTS) {
    $cap->close();
    send_json(['error' => 'This school has reached the maximum of ' . SCHOOL_LIMIT_ACCOUNTANTS . ' accountants.'], 422);
}
$cap->close();

$stmt = $conn->prepare("SELECT id FROM accountants WHERE email = ? AND school_id = ?");
$stmt->bind_param('si', $email, $schoolId);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    send_json(['error' => 'An accountant with this email already exists in this school'], 409);
}
$stmt->close();

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "INSERT INTO accountants (school_id, full_name, email, phone, address, password_hash)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('isssss', $schoolId, $fullName, $email, $phone, $address, $passwordHash);
    $stmt->execute();
    $accountantId = (int) $stmt->insert_id;
    $stmt->close();

    $localUid = 'local:accountant:' . $accountantId;
    $stmt = $conn->prepare(
        "INSERT INTO users (school_id, firebase_uid, email, full_name, role)
         VALUES (?, ?, ?, ?, 'accountant')"
    );
    $stmt->bind_param('isss', $schoolId, $localUid, $email, $fullName);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    send_json(['success' => true, 'accountant_id' => $accountantId, 'message' => "Accountant account created for {$fullName}"]);
} catch (Exception $e) {
    $conn->rollback();
    send_json(['error' => 'Database error: ' . $e->getMessage()], 500);
}

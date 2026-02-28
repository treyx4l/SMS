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
$idToken  = trim($data['idToken'] ?? '');
$fullName = trim($data['full_name'] ?? '');
$email    = trim($data['email'] ?? '');
$phone    = trim($data['phone'] ?? '');
$address  = trim($data['address'] ?? '');
$schoolId = (int) ($_SESSION['school_id'] ?? 0);

if ($idToken === '' || $fullName === '' || $schoolId === 0) {
    send_json(['error' => 'Missing required fields (idToken, full_name)'], 422);
}

$verified = verify_firebase_id_token($idToken);
if (!$verified) {
    send_json(['error' => 'Invalid Firebase token'], 401);
}

$uid   = $verified['uid'] ?? null;
$email = $verified['email'] ?? $email;

if (!$uid || !$email) {
    send_json(['error' => 'Token missing uid or email'], 400);
}

$conn = get_db_connection();

$stmt = $conn->prepare("SELECT id FROM users WHERE firebase_uid = ?");
$stmt->bind_param('s', $uid);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    send_json(['error' => 'An account with this email already exists'], 409);
}
$stmt->close();

$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        "INSERT INTO users (school_id, firebase_uid, email, full_name, role)
         VALUES (?, ?, ?, ?, 'driver')"
    );
    $stmt->bind_param('isss', $schoolId, $uid, $email, $fullName);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare(
        "INSERT INTO bus_drivers (school_id, full_name, email, phone, address)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('issss', $schoolId, $fullName, $email, $phone, $address);
    $stmt->execute();
    $driverId = (int) $stmt->insert_id;
    $stmt->close();

    $conn->commit();

    send_json(['success' => true, 'driver_id' => $driverId, 'message' => "Bus driver account created for {$fullName}"]);
} catch (Exception $e) {
    $conn->rollback();
    send_json(['error' => 'Database error: ' . $e->getMessage()], 500);
}

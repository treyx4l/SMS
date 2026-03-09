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
$route_id = !empty($data['route_id']) ? (int) $data['route_id'] : null;
$password = $data['password'] ?? '';
$schoolId = (int) ($_SESSION['school_id'] ?? 0);

if ($fullName === '' || $email === '' || $schoolId === 0) {
    send_json(['error' => 'Full name and email are required'], 422);
}
if (strlen($password) < 6) {
    send_json(['error' => 'Password must be at least 6 characters'], 422);
}

$conn = get_db_connection();

// Cap: max bus drivers per school
$cap = $conn->prepare("SELECT COUNT(*) FROM bus_drivers WHERE school_id = ?");
$cap->bind_param('i', $schoolId);
$cap->execute();
if ((int) $cap->get_result()->fetch_row()[0] >= SCHOOL_LIMIT_BUS_DRIVERS) {
    $cap->close();
    send_json(['error' => 'This school has reached the maximum of ' . SCHOOL_LIMIT_BUS_DRIVERS . ' bus drivers.'], 422);
}
$cap->close();

$stmt = $conn->prepare("SELECT id FROM bus_drivers WHERE email = ? AND school_id = ?");
$stmt->bind_param('si', $email, $schoolId);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    send_json(['error' => 'A bus driver with this email already exists in this school'], 409);
}
$stmt->close();

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "INSERT INTO bus_drivers (school_id, full_name, email, phone, address, route_id, password_hash)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('issssis', $schoolId, $fullName, $email, $phone, $address, $route_id, $passwordHash);
    $stmt->execute();
    $driverId = (int) $stmt->insert_id;
    $stmt->close();

    $localUid = 'local:driver:' . $driverId;
    $stmt = $conn->prepare(
        "INSERT INTO users (school_id, firebase_uid, email, full_name, role)
         VALUES (?, ?, ?, ?, 'driver')"
    );
    $stmt->bind_param('isss', $schoolId, $localUid, $email, $fullName);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    send_json(['success' => true, 'driver_id' => $driverId, 'message' => "Bus driver account created for {$fullName}"]);
} catch (Exception $e) {
    $conn->rollback();
    send_json(['error' => 'Database error: ' . $e->getMessage()], 500);
}

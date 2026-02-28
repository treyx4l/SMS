<?php
require_once __DIR__ . '/firebase_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Method not allowed'], 405);
}

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    send_json(['error' => 'Forbidden — admin only'], 403);
}

$data       = read_json_input();
$fullName   = trim($data['full_name'] ?? '');
$email      = trim($data['email'] ?? '');
$phone      = trim($data['phone'] ?? '');
$address    = trim($data['address'] ?? '');
$password   = $data['password'] ?? '';
$wardIds    = $data['ward_ids'] ?? [];
$schoolId   = (int) ($_SESSION['school_id'] ?? 0);

if ($fullName === '' || $email === '' || $schoolId === 0) {
    send_json(['error' => 'Full name and email are required'], 422);
}
if (strlen($password) < 6) {
    send_json(['error' => 'Password must be at least 6 characters'], 422);
}

$conn = get_db_connection();

$stmt = $conn->prepare("SELECT id FROM parents WHERE email = ? AND school_id = ?");
$stmt->bind_param('si', $email, $schoolId);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    send_json(['error' => 'A parent with this email already exists in this school'], 409);
}
$stmt->close();

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "INSERT INTO parents (school_id, full_name, email, phone, address, password_hash)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('isssss', $schoolId, $fullName, $email, $phone, $address, $passwordHash);
    $stmt->execute();
    $parentId = (int) $stmt->insert_id;
    $stmt->close();

    $localUid = 'local:parent:' . $parentId;
    $stmt = $conn->prepare(
        "INSERT INTO users (school_id, firebase_uid, email, full_name, role)
         VALUES (?, ?, ?, ?, 'parent')"
    );
    $stmt->bind_param('isss', $schoolId, $localUid, $email, $fullName);
    $stmt->execute();
    $stmt->close();

    foreach ((array) $wardIds as $sid) {
        $sid = (int) $sid;
        if ($sid > 0) {
            $stmt = $conn->prepare("UPDATE students SET parent_id = ? WHERE id = ? AND school_id = ?");
            $stmt->bind_param('iii', $parentId, $sid, $schoolId);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->commit();

    send_json(['success' => true, 'parent_id' => $parentId, 'message' => "Parent account created for {$fullName}"]);
} catch (Exception $e) {
    $conn->rollback();
    send_json(['error' => 'Database error: ' . $e->getMessage()], 500);
}

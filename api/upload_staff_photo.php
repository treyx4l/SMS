<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed']);
    exit(405);
}

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Forbidden — admin only']);
    exit(403);
}

$type = trim($_POST['type'] ?? '');
$id   = (int) ($_POST['id'] ?? 0);
$schoolId = (int) ($_SESSION['school_id'] ?? 0);

$tables = ['teacher' => 'teachers', 'parent' => 'parents', 'driver' => 'bus_drivers', 'accountant' => 'accountants'];
if (!isset($tables[$type]) || $id <= 0 || $schoolId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid type or id']);
    exit(422);
}

$table = $tables[$type];

// Check photo_path column exists
$conn = get_db_connection();
$res = $conn->query("SHOW COLUMNS FROM {$table} LIKE 'photo_path'");
if (!$res || $res->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Photo not supported for this table']);
    exit(400);
}

// Verify record exists and belongs to school
$stmt = $conn->prepare("SELECT id FROM {$table} WHERE id=? AND school_id=?");
$stmt->bind_param('ii', $id, $schoolId);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Record not found']);
    exit(404);
}
$stmt->close();

$photoPath = null;
if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = dirname(__DIR__) . '/storage/staff/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $filename = $type . '_' . $id . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
            $photoPath = 'storage/staff/' . $filename;
        }
    }
}

if (!$photoPath) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No valid photo uploaded']);
    exit(400);
}

$stmt = $conn->prepare("UPDATE {$table} SET photo_path=? WHERE id=? AND school_id=?");
$stmt->bind_param('sii', $photoPath, $id, $schoolId);
$stmt->execute();
$stmt->close();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'photo_path' => $photoPath]);

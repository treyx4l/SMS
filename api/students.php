<?php
// Simple JSON API for students (admin-only, same tenant)
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$conn     = get_db_connection();
$schoolId = current_school_id();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $hasIndexNo = (bool) ($conn->query("SHOW COLUMNS FROM students LIKE 'index_no'")->num_rows ?? 0);
    $idCol = $hasIndexNo ? 'index_no' : 'admission_no';
    $stmt = $conn->prepare(
        "SELECT id, first_name, last_name, {$idCol} AS index_no, gender, phone
         FROM students WHERE school_id = ? ORDER BY created_at DESC"
    );
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    echo json_encode($rows);
    exit;
}

// For brevity, other HTTP verbs can be added later.
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);


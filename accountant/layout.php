<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';

$conn = get_db_connection();
$schoolId = current_school_id();
$schoolName = 'Axis SMS';
if ($schoolId) {
    $stmt = $conn->prepare("SELECT name FROM schools WHERE id = ?");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!empty($row['name'])) $schoolName = $row['name'];
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'accountant') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$page_title = $page_title ?? 'Accountant';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($schoolName) ?> - Accountant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-slate-900 min-h-screen">
<div class="flex min-h-screen">
    <aside class="w-56 bg-white border-r border-slate-200 p-4">
        <div class="font-bold text-slate-800"><?= htmlspecialchars($schoolName) ?></div>
        <div class="text-xs text-slate-500 mt-1">Accountant</div>
        <nav class="mt-6 space-y-1">
            <a href="dashboard.php" class="block px-3 py-2 rounded-lg text-sm font-medium text-indigo-700 bg-indigo-50">Dashboard</a>
        </nav>
        <form method="post" action="../logout.php" class="mt-6">
            <button type="submit" class="w-full px-3 py-2 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50">Logout</button>
        </form>
    </aside>
    <main class="flex-1 p-6 overflow-y-auto">
        <h1 class="text-lg font-bold text-slate-900 mb-4"><?= htmlspecialchars($page_title) ?></h1>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';

$conn = get_db_connection();
$schoolId = current_school_id();
$schoolName = 'Axis SMS';
$schoolLogoPath = null;
$schoolAccent = '#6366f1';
if ($schoolId) {
    $stmt = $conn->prepare("SELECT name, logo_path, accent_color FROM schools WHERE id = ?");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        if (!empty($row['name'])) $schoolName = $row['name'];
        if (!empty($row['logo_path'])) $schoolLogoPath = $row['logo_path'];
        if (!empty($row['accent_color'])) $schoolAccent = $row['accent_color'];
    }
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'parent') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$page_title = $page_title ?? 'Parent';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($schoolName) ?> - Parent</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        :root { --accent: <?= htmlspecialchars($schoolAccent) ?>; }
        .text-indigo-700 { color: var(--accent) !important; }
        .bg-indigo-50 { background-color: color-mix(in srgb, var(--accent) 15%, white) !important; }
        .parent-sidebar-nav::-webkit-scrollbar { width: 3px; }
        .parent-sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .parent-sidebar-nav::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 2px; }
    </style>
</head>
<body class="bg-gray-50 text-slate-900 min-h-screen">
<div class="min-h-screen flex">
    <!-- Sidebar similar to admin -->
    <aside class="w-56 shrink-0 bg-white border-r border-slate-200 flex flex-col">
        <!-- Brand -->
        <div class="px-4 py-4 border-b border-slate-100 flex items-center gap-3">
            <?php if ($schoolLogoPath && file_exists(dirname(__DIR__) . '/' . $schoolLogoPath)): ?>
            <img src="../<?= htmlspecialchars($schoolLogoPath) ?>" alt="" class="w-9 h-9 rounded-lg object-contain shrink-0">
            <?php endif; ?>
            <div>
                <div class="font-bold text-slate-900 tracking-tight"><?= htmlspecialchars($schoolName) ?></div>
                <div class="text-[10px] font-semibold text-indigo-600 uppercase tracking-widest mt-0.5">Parent Portal</div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="parent-sidebar-nav flex-1 overflow-y-auto px-3 py-3 space-y-4">
            <!-- Overview group -->
            <div>
                <p class="flex items-center justify-between cursor-default px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1">
                    <span>Overview</span>
                </p>
                <div class="space-y-0.5">
                    <a href="dashboard.php"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium w-full">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>

            <!-- Your wards group -->
            <div>
                <p class="flex items-center justify-between cursor-default px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1">
                    <span>Your wards</span>
                </p>
                <div class="space-y-0.5 text-xs font-medium">
                    <button type="button"
                            data-tab-target="wards"
                            class="flex items-center gap-2 w-full text-left px-3 py-1.5 rounded-lg border-l-2 border-transparent text-slate-500 hover:bg-slate-50">
                        <i data-lucide="users" class="w-4 h-4 shrink-0"></i>
                        <span>Wards</span>
                    </button>
                    <button type="button"
                            data-tab-target="attendance"
                            class="flex items-center gap-2 w-full text-left px-3 py-1.5 rounded-lg border-l-2 border-transparent text-slate-500 hover:bg-slate-50">
                        <i data-lucide="calendar-check" class="w-4 h-4 shrink-0"></i>
                        <span>Attendance</span>
                    </button>
                    <button type="button"
                            data-tab-target="grades"
                            class="flex items-center gap-2 w-full text-left px-3 py-1.5 rounded-lg border-l-2 border-transparent text-slate-500 hover:bg-slate-50">
                        <i data-lucide="clipboard-list" class="w-4 h-4 shrink-0"></i>
                        <span>Grades</span>
                    </button>
                    <button type="button"
                            data-tab-target="fees"
                            class="flex items-center gap-2 w-full text-left px-3 py-1.5 rounded-lg border-l-2 border-transparent text-slate-500 hover:bg-slate-50">
                        <i data-lucide="credit-card" class="w-4 h-4 shrink-0"></i>
                        <span>Fees</span>
                    </button>
                    <button type="button"
                            data-tab-target="reports"
                            class="flex items-center gap-2 w-full text-left px-3 py-1.5 rounded-lg border-l-2 border-transparent text-slate-500 hover:bg-slate-50">
                        <i data-lucide="file-text" class="w-4 h-4 shrink-0"></i>
                        <span>Reports</span>
                    </button>
                    <button type="button"
                            data-tab-target="analytics"
                            class="flex items-center gap-2 w-full text-left px-3 py-1.5 rounded-lg border-l-2 border-transparent text-slate-500 hover:bg-slate-50">
                        <i data-lucide="trending-up" class="w-4 h-4 shrink-0"></i>
                        <span>Analytics</span>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Logout -->
        <div class="px-3 pb-4 pt-2 border-t border-slate-100">
            <form method="post" action="../logout.php" class="m-0">
                <button type="submit"
                        class="flex items-center gap-3 w-full px-3 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200 transition-colors">
                    <i data-lucide="log-out" class="w-4 h-4 shrink-0"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 p-6 overflow-y-auto">
        <h1 class="text-lg font-bold text-slate-900 mb-4"><?= htmlspecialchars($page_title) ?></h1>

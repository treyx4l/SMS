<?php
// Shared layout for all admin pages under /admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

$conn     = get_db_connection();
$schoolId = current_school_id();
$schoolName = 'Axis SMS';

if ($schoolId) {
    $stmt = $conn->prepare("SELECT name FROM schools WHERE id = ?");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!empty($row['name'])) {
        $schoolName = $row['name'];
    }
}

if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'admin';
}
if (!isset($_SESSION['school_id'])) {
    $_SESSION['school_id'] = 1;
}

if ($_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

if (!isset($page_title)) {
    $page_title = 'Admin';
}

function navLink(string $check, string $current): string {
    return $check === $current
        ? 'flex items-center gap-3 px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium w-full'
        : 'flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-800 text-sm w-full transition-colors';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($schoolName) ?> - Admin - <?= htmlspecialchars($page_title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        });
    </script>
    <style>
        * { font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', Arial, sans-serif; }
        .sidebar-nav::-webkit-scrollbar { width: 3px; }
        .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 2px; }
        details > summary { list-style: none; }
        details > summary::-webkit-details-marker { display: none; }
        details[open] > summary .chevron { transform: rotate(90deg); }
        .chevron { transition: transform 0.15s ease; }
    </style>
    <?php if (isset($extra_head)) echo $extra_head; ?>
</head>
<body class="bg-gray-50 text-slate-900">
<div class="min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-56 shrink-0 bg-white border-r border-slate-200 flex flex-col">

        <!-- Brand -->
        <div class="px-4 py-4 border-b border-slate-100">
            <div class="text-base font-bold text-slate-900 tracking-tight"><?= htmlspecialchars(strtoupper($schoolName)) ?></div>
            <div class="text-[10px] font-semibold text-indigo-600 uppercase tracking-widest mt-0.5">Admin Console</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Limit: 25 users</div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav flex-1 overflow-y-auto px-3 py-3 space-y-4">

            <!-- Overview -->
            <details open>
                <summary class="flex items-center justify-between cursor-pointer px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400 hover:text-slate-600 select-none mb-1">
                    <span>Overview</span>
                    <i data-lucide="chevron-right" class="chevron w-3 h-3"></i>
                </summary>
                <div class="space-y-0.5">
                    <a href="dashboard.php" class="<?= navLink('Dashboard', $page_title) ?>">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="reports.php" class="<?= navLink('Reports', $page_title) ?>">
                        <i data-lucide="bar-chart-2" class="w-4 h-4 shrink-0"></i>
                        <span>Reports</span>
                    </a>
                    <a href="analytics.php" class="<?= navLink('Analytics', $page_title) ?>">
                        <i data-lucide="trending-up" class="w-4 h-4 shrink-0"></i>
                        <span>Analytics</span>
                    </a>
                    <a href="activity.php" class="<?= navLink('Activity', $page_title) ?>">
                        <i data-lucide="activity" class="w-4 h-4 shrink-0"></i>
                        <span>Activity log</span>
                    </a>
                </div>
            </details>

            <!-- Management -->
            <details open>
                <summary class="flex items-center justify-between cursor-pointer px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400 hover:text-slate-600 select-none mb-1">
                    <span>Management</span>
                    <i data-lucide="chevron-right" class="chevron w-3 h-3"></i>
                </summary>
                <div class="space-y-0.5">
                    <a href="students.php" class="<?= navLink('Students', $page_title) ?>">
                        <i data-lucide="graduation-cap" class="w-4 h-4 shrink-0"></i>
                        <span>Students</span>
                    </a>
                    <a href="teachers.php" class="<?= navLink('Teachers', $page_title) ?>">
                        <i data-lucide="user-check" class="w-4 h-4 shrink-0"></i>
                        <span>Teachers</span>
                    </a>
                    <a href="classes.php" class="<?= navLink('Classes', $page_title) ?>">
                        <i data-lucide="book-open" class="w-4 h-4 shrink-0"></i>
                        <span>Classes</span>
                    </a>
                    <a href="subjects.php" class="<?= navLink('Subjects', $page_title) ?>">
                        <i data-lucide="layers" class="w-4 h-4 shrink-0"></i>
                        <span>Subjects</span>
                    </a>
                    <a href="parents.php" class="<?= navLink('Parents', $page_title) ?>">
                        <i data-lucide="users" class="w-4 h-4 shrink-0"></i>
                        <span>Parents</span>
                    </a>
                    <a href="attendance.php" class="<?= navLink('Attendance', $page_title) ?>">
                        <i data-lucide="calendar-check" class="w-4 h-4 shrink-0"></i>
                        <span>Attendance</span>
                    </a>
                    <a href="grades.php" class="<?= navLink('Grades', $page_title) ?>">
                        <i data-lucide="clipboard-list" class="w-4 h-4 shrink-0"></i>
                        <span>Grades</span>
                    </a>
                    <a href="lesson_plans.php" class="<?= navLink('Lesson Plans', $page_title) ?>">
                        <i data-lucide="file-text" class="w-4 h-4 shrink-0"></i>
                        <span>Lesson plans</span>
                    </a>
                </div>
            </details>

            <!-- People -->
            <details open>
                <summary class="flex items-center justify-between cursor-pointer px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400 hover:text-slate-600 select-none mb-1">
                    <span>People</span>
                    <i data-lucide="chevron-right" class="chevron w-3 h-3"></i>
                </summary>
                <div class="space-y-0.5">
                    <a href="accountants.php" class="<?= navLink('Accountants', $page_title) ?>">
                        <i data-lucide="calculator" class="w-4 h-4 shrink-0"></i>
                        <span>Accountants</span>
                    </a>
                    <a href="drivers.php" class="<?= navLink('Bus Drivers', $page_title) ?>">
                        <i data-lucide="bus" class="w-4 h-4 shrink-0"></i>
                        <span>Bus drivers</span>
                    </a>
                    <a href="staff_directory.php" class="<?= navLink('Staff Directory', $page_title) ?>">
                        <i data-lucide="contact" class="w-4 h-4 shrink-0"></i>
                        <span>Staff directory</span>
                    </a>
                </div>
            </details>

            <!-- System -->
            <details open>
                <summary class="flex items-center justify-between cursor-pointer px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400 hover:text-slate-600 select-none mb-1">
                    <span>System</span>
                    <i data-lucide="chevron-right" class="chevron w-3 h-3"></i>
                </summary>
                <div class="space-y-0.5">
                    <a href="timetable.php" class="<?= navLink('Timetable', $page_title) ?>">
                        <i data-lucide="calendar" class="w-4 h-4 shrink-0"></i>
                        <span>Timetable</span>
                    </a>
                    <a href="settings.php" class="<?= navLink('Settings', $page_title) ?>">
                        <i data-lucide="settings" class="w-4 h-4 shrink-0"></i>
                        <span>Settings</span>
                    </a>
                    <a href="school_profile.php" class="<?= navLink('School Profile', $page_title) ?>">
                        <i data-lucide="landmark" class="w-4 h-4 shrink-0"></i>
                        <span>School profile</span>
                    </a>
                    <a href="integrations.php" class="<?= navLink('Integrations', $page_title) ?>">
                        <i data-lucide="plug" class="w-4 h-4 shrink-0"></i>
                        <span>Integrations</span>
                    </a>
                </div>
            </details>
        </nav>

        <!-- Logout button at bottom -->
        <div class="px-3 pb-4 pt-2 border-t border-slate-100">
            <form method="post" action="../logout.php" class="m-0">
                <button type="submit"
                        class="flex items-center gap-3 w-full px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">
                    <i data-lucide="log-out" class="w-4 h-4 shrink-0"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main content area -->
    <main class="flex-1 flex flex-col min-w-0 bg-gray-50">

        <!-- Top bar -->
        <header class="flex items-center justify-between px-6 py-3.5 border-b border-slate-200 bg-white">
            <h1 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($page_title) ?></h1>
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500"><?= htmlspecialchars($schoolName) ?></span>
                <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold">
                    A
                </div>
            </div>
        </header>

        <!-- Flash message -->
        <?php if (isset($flash_message)): ?>
            <div class="mx-6 mt-4 px-4 py-3 rounded-xl text-sm border
                <?= ($flash_type ?? 'alert-success') === 'alert-success'
                    ? 'bg-green-50 border-green-200 text-green-700'
                    : 'bg-red-50 border-red-200 text-red-700' ?>">
                <?= htmlspecialchars($flash_message) ?>
            </div>
        <?php endif; ?>

        <!-- Page content wrapper -->
        <div class="flex-1 p-6 space-y-4 overflow-y-auto">
<?php
// Page content continues; closes in footer.php
?>

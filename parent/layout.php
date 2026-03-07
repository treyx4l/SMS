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

// Logged-in parent details (from users table)
$parentName    = 'Parent';
$parentEmail   = '';
$parentInitial = 'P';

if (isset($_SESSION['user_id'], $_SESSION['school_id'])) {
    $userId    = (int) $_SESSION['user_id'];
    $userSchId = (int) $_SESSION['school_id'];

    $stmt = $conn->prepare("SELECT full_name, email FROM users WHERE id = ? AND school_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ii', $userId, $userSchId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if (!empty($row['full_name'])) {
                $parentName = $row['full_name'];
                $parts = preg_split('/\s+/', trim($row['full_name']));
                if (!empty($parts[0])) {
                    $parentInitial = strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'));
                }
            }
            if (!empty($row['email'])) {
                $parentEmail = $row['email'];
            }
        }
        $stmt->close();
    }
}

function parentNavLink(string $check, string $current): string
{
    return $check === $current
        ? 'flex items-center gap-3 px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium w-full'
        : 'flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-800 text-sm w-full transition-colors';
}
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
        * { font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', Arial, sans-serif; }
        :root { --accent: <?= htmlspecialchars($schoolAccent) ?>; }
        .parent-sidebar-nav::-webkit-scrollbar { width: 3px; }
        .parent-sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .parent-sidebar-nav::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 2px; }
        .bg-indigo-600, .bg-indigo-500, .hover\:bg-indigo-700:hover, .hover\:bg-indigo-600:hover { background-color: var(--accent) !important; }
        .text-indigo-600, .text-indigo-700, .text-indigo-500 { color: var(--accent) !important; }
        .border-indigo-200, .border-indigo-100 { border-color: var(--accent) !important; }
        .focus\:ring-indigo-500:focus { --tw-ring-color: var(--accent) !important; }
        .bg-indigo-50 { background-color: color-mix(in srgb, var(--accent) 15%, white) !important; }
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
                       class="<?= parentNavLink('Parents dashboard', $page_title) ?>">
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
                    <a href="wards.php"
                       class="<?= parentNavLink('Wards', $page_title) ?>">
                        <i data-lucide="users" class="w-4 h-4 shrink-0"></i>
                        <span>Wards</span>
                    </a>
                    <a href="attendance.php"
                       class="<?= parentNavLink('Attendance', $page_title) ?>">
                        <i data-lucide="calendar-check" class="w-4 h-4 shrink-0"></i>
                        <span>Attendance</span>
                    </a>
                    <a href="grades.php"
                       class="<?= parentNavLink('Grades', $page_title) ?>">
                        <i data-lucide="clipboard-list" class="w-4 h-4 shrink-0"></i>
                        <span>Grades</span>
                    </a>
                    <a href="fees.php"
                       class="<?= parentNavLink('Fees', $page_title) ?>">
                        <i data-lucide="credit-card" class="w-4 h-4 shrink-0"></i>
                        <span>Fees</span>
                    </a>
                    <a href="reports.php"
                       class="<?= parentNavLink('Reports', $page_title) ?>">
                        <i data-lucide="file-text" class="w-4 h-4 shrink-0"></i>
                        <span>Reports</span>
                    </a>
                    <a href="analytics.php"
                       class="<?= parentNavLink('Analytics', $page_title) ?>">
                        <i data-lucide="trending-up" class="w-4 h-4 shrink-0"></i>
                        <span>Analytics</span>
                    </a>
                </div>
            </div>

            <!-- Account (logout) -->
            <div>
                <p class="flex items-center justify-between cursor-default px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1">
                    <span>Account</span>
                </p>
                <div class="space-y-0.5">
                    <form method="post" action="../logout.php" class="m-0">
                        <button type="submit"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium w-full hover:bg-indigo-700 transition-colors">
                            <i data-lucide="log-out" class="w-4 h-4 shrink-0"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </nav>
    </aside>

    <!-- Main content -->
    <main class="flex-1 p-6 overflow-y-auto">
        <header class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($page_title) ?></h1>
            <div class="flex items-center gap-3">
                <!-- Messages -->
                <button type="button"
                        id="parentMessagesButton"
                        class="relative inline-flex items-center justify-center w-8 h-8 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 focus:outline-none"
                        aria-label="Messages"
                        title="Messages">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-indigo-500 text-white text-[9px] font-semibold">
                        1
                    </span>
                </button>

                <!-- Notifications -->
                <div class="relative">
                    <button type="button"
                            id="parentNotificationsButton"
                            class="relative inline-flex items-center justify-center w-8 h-8 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 focus:outline-none"
                            aria-label="Notifications"
                            title="Notifications">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                        <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-rose-500 text-white text-[9px] font-semibold">
                            2
                        </span>
                    </button>
                    <div id="parentNotificationsMenu"
                         class="hidden absolute right-0 mt-2 w-60 bg-white border border-slate-200 rounded-xl shadow-lg py-1 text-[11px] text-slate-700 z-20">
                        <div class="px-3 py-1.5 border-b border-slate-100 flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-slate-800">Notifications</span>
                            <span class="text-[10px] text-indigo-600 cursor-pointer hover:text-indigo-700">Mark all read</span>
                        </div>
                        <div class="px-3 pt-1 pb-0.5 text-[10px] uppercase tracking-wide text-slate-400">
                            Parents · Staff · Admin
                        </div>
                        <button type="button" class="w-full flex items-start gap-2 px-3 py-1.5 hover:bg-slate-50 text-left app-notif"
                                data-notif-id="parent-fees" data-notif-title="Fees reminder" data-notif-body="Your ward has an upcoming fee deadline.">
                            <span class="mt-1 w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span>
                                <span class="block text-[11px] font-medium text-slate-800">Fees reminder</span>
                                <span class="block text-[10px] text-slate-500">Upcoming school fees due soon.</span>
                            </span>
                        </button>
                        <button type="button" class="w-full flex items-start gap-2 px-3 py-1.5 hover:bg-slate-50 text-left app-notif"
                                data-notif-id="parent-attendance" data-notif-title="Attendance update" data-notif-body="Recent attendance summary is available.">
                            <span class="mt-1 w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>
                                <span class="block text-[11px] font-medium text-slate-800">Attendance update</span>
                                <span class="block text-[10px] text-slate-500">Recent attendance summary is ready.</span>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Profile dropdown -->
                <div class="relative">
                    <button type="button"
                            id="parentProfileButton"
                            class="flex items-center gap-2 rounded-full pl-2 pr-1.5 py-1.5 hover:bg-slate-50 focus:outline-none">
                        <div class="hidden sm:flex flex-col items-end mr-1">
                            <span class="text-xs font-medium text-slate-700 leading-tight">
                                <?= htmlspecialchars($parentName) ?>
                            </span>
                            <span class="text-[10px] text-slate-400 leading-tight">
                                <?= htmlspecialchars($schoolName) ?>
                            </span>
                        </div>
                        <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold"
                             title="Account">
                            <?= htmlspecialchars($parentInitial) ?>
                        </div>
                        <i data-lucide="chevron-down" class="w-3 h-3 text-slate-400"></i>
                    </button>

                    <div id="parentProfileMenu"
                         class="hidden absolute right-0 mt-2 w-40 bg-white border border-slate-200 rounded-xl shadow-lg py-1 text-[11px] text-slate-700 z-20">
                        <a href="profile.php"
                           class="flex items-center gap-2 px-3 py-1.5 hover:bg-slate-50">
                            <i data-lucide="user" class="w-3 h-3"></i>
                            <span>Profile</span>
                        </a>
                        <a href="edit_profile.php"
                           class="flex items-center gap-2 px-3 py-1.5 hover:bg-slate-50">
                            <i data-lucide="edit-3" class="w-3 h-3"></i>
                            <span>Edit profile</span>
                        </a>
                        <div class="my-1 border-t border-slate-100"></div>
                        <form method="post" action="../logout.php">
                            <button type="submit"
                                    class="w-full flex items-center gap-2 px-3 py-1.5 text-rose-600 hover:bg-rose-50">
                                <i data-lucide="log-out" class="w-3 h-3"></i>
                                <span>Sign out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';

$conn = get_db_connection();
$schoolId = current_school_id();
$schoolName = 'Axis SMS';
$driverName = 'Driver';
$driverInitial = 'D';

if (isset($_SESSION['user_id'], $_SESSION['school_id']) && $schoolId) {
    $userId = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ? AND school_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ii', $userId, $schoolId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row && !empty($row['full_name'])) {
            $driverName = $row['full_name'];
            $parts = preg_split('/\s+/', trim($row['full_name']));
            if (!empty($parts[0])) {
                $driverInitial = strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'));
            }
        }
    }
}

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

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'driver') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$page_title = $page_title ?? 'Driver';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($schoolName) ?> - Bus Driver</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>:root { --accent: <?= htmlspecialchars($schoolAccent) ?>; } .bg-indigo-600, .text-indigo-700 { background-color: var(--accent) !important; } .text-indigo-700 { color: var(--accent) !important; } .bg-indigo-50 { background-color: color-mix(in srgb, var(--accent) 15%, white) !important; }</style>
</head>
<body class="bg-gray-50 text-slate-900 min-h-screen">
<div class="flex flex-col md:flex-row min-h-screen">
    <aside class="w-full md:w-56 bg-white border-b md:border-b-0 md:border-r border-slate-200 flex flex-col">
        <div class="flex items-center gap-3 p-4 border-b border-slate-100">
            <?php if ($schoolLogoPath && file_exists(dirname(__DIR__) . '/' . $schoolLogoPath)): ?>
            <img src="../<?= htmlspecialchars($schoolLogoPath) ?>" alt="" class="w-9 h-9 rounded-lg object-contain shrink-0">
            <?php endif; ?>
            <div>
                <div class="font-bold text-slate-800"><?= htmlspecialchars($schoolName) ?></div>
                <div class="text-xs text-slate-500 mt-0.5">Bus Driver</div>
            </div>
        </div>
        <nav class="flex-1 overflow-y-auto p-3 space-y-1">
            <div class="px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Driver Portal</div>
            <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-emerald-700 bg-emerald-50' : 'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-dashboard"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                Dashboard
            </a>
            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map"><path d="M14.106 5.553a2 2 0 0 0 1.788 0l3.659-1.83A1 1 0 0 1 21 4.619v12.764a1 1 0 0 1-.553.894l-4.553 2.277a2 2 0 0 1-1.788 0l-4.212-2.106a2 2 0 0 0-1.788 0l-3.659 1.83A1 1 0 0 1 3 19.381V6.618a1 1 0 0 1 .553-.894l4.553-2.277a2 2 0 0 1 1.788 0z"/><path d="M15 5.764v15"/><path d="M9 3.236v15"/></svg>
                My Routes
            </a>
            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-list"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                Manifest
            </a>
            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                Incidents
            </a>
        </nav>
        <div class="p-4 border-t border-slate-100">
            <form method="post" action="../logout.php">
                <button type="submit" class="flex items-center justify-center gap-2 w-full px-3 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                    Logout
                </button>
            </form>
        </div>
    </aside>
    <main class="w-full flex-1 p-4 md:p-6 overflow-y-auto">
        <header class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200">
            <h1 class="text-lg md:text-xl font-bold text-slate-900"><?= htmlspecialchars($page_title) ?></h1>
            <div class="flex items-center gap-3">
                <!-- Messages -->
                <button type="button" 
                        id="driverMessagesButton"
                        class="relative inline-flex items-center justify-center w-8 h-8 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 focus:outline-none"
                        aria-label="Messages" title="Messages">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <!-- Unread badge -->
                    <span id="driverMsgBadge" class="hidden absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-emerald-500 text-white text-[9px] font-semibold"></span>
                </button>

                <!-- Notifications -->
                <div class="relative">
                    <button type="button" 
                            id="driverNotificationsButton"
                            class="relative inline-flex items-center justify-center w-8 h-8 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 focus:outline-none"
                            aria-label="Notifications" title="Notifications">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                        <!-- Unread badge -->
                        <span id="driverNotifBadge" class="hidden absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-rose-500 text-white text-[9px] font-semibold"></span>
                    </button>

                    <!-- Notification Menu -->
                    <div id="driverNotificationsMenu"
                         class="hidden absolute right-0 mt-2 w-72 bg-white border border-slate-200 rounded-xl shadow-xl py-1 text-[11px] text-slate-700 z-50">
                        <div class="px-3 py-1.5 border-b border-slate-100 flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-slate-800">Notifications</span>
                            <button id="driverMarkAllRead" class="text-[10px] text-emerald-600 hover:text-emerald-700">Mark all read</button>
                        </div>
                        <div class="px-3 pt-2 pb-0.5 text-[10px] uppercase tracking-wide text-slate-400 flex items-center gap-1">
                            <i data-lucide="users" class="w-2.5 h-2.5"></i><span>Parents &middot; Staff &middot; Admin</span>
                        </div>
                        <div id="driverNotifGroup1"></div>
                        <div class="border-t border-slate-100 mt-1 pt-1 flex items-center justify-between">
                            <button type="button" id="driverClearAll" class="px-3 py-1.5 text-[10px] font-medium text-slate-500 hover:text-slate-800 transition-colors">
                                Clear notifications
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Driver Profile Dropdown -->
                <div class="relative">
                    <button type="button" 
                            id="driverProfileButton"
                            class="flex items-center gap-2 rounded-full pl-2 pr-1.5 py-1.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1 transition-colors">
                        <div class="hidden sm:flex flex-col items-end mr-1">
                            <span class="text-xs font-medium text-slate-700 leading-tight">
                                <?= htmlspecialchars($driverName) ?>
                            </span>
                            <span class="text-[10px] text-slate-400 leading-tight">
                                <?= htmlspecialchars($schoolName) ?>
                            </span>
                        </div>
                        <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-semibold" title="Account">
                            <?= htmlspecialchars($driverInitial) ?>
                        </div>
                        <i data-lucide="chevron-down" class="w-3 h-3 text-slate-400"></i>
                    </button>
                    <!-- Dropdown menu -->
                    <div id="driverProfileMenu"
                         class="hidden absolute right-0 mt-2 w-40 bg-white border border-slate-200 rounded-xl shadow-lg py-1 text-[11px] text-slate-700 z-20">
                        <a href="profile.php" class="flex items-center gap-2 px-3 py-1.5 hover:bg-slate-50">
                            <i data-lucide="user" class="w-3 h-3"></i>
                            <span>My profile</span>
                        </a>
                        <div class="my-1 border-t border-slate-100"></div>
                        <form method="post" action="../logout.php">
                            <button type="submit" class="w-full flex items-center gap-2 px-3 py-1.5 text-rose-600 hover:bg-rose-50">
                                <i data-lucide="log-out" class="w-3 h-3"></i>
                                <span>Sign out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <?php
        $chat_role   = 'driver';
        $chat_prefix = 'driver';
        include dirname(__DIR__) . '/includes/chat_modal.php';
        ?>


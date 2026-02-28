<?php
// Shared layout for all admin pages under /admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

$conn       = get_db_connection();
$schoolId   = current_school_id();
$schoolName = 'Axis SMS';
$adminName  = 'Admin';
$adminInitial = 'A';

if (isset($_SESSION['user_id'], $_SESSION['school_id']) && $schoolId) {
    $userId = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ? AND school_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ii', $userId, $schoolId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row && !empty($row['full_name'])) {
            $adminName = $row['full_name'];
            $parts = preg_split('/\s+/', trim($row['full_name']));
            if (!empty($parts[0])) {
                $adminInitial = strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'));
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
    <style>
        * { font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', Arial, sans-serif; }
        :root { --accent: <?= htmlspecialchars($schoolAccent) ?>; }
        .sidebar-nav::-webkit-scrollbar { width: 3px; }
        .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 2px; }
        details > summary { list-style: none; }
        details > summary::-webkit-details-marker { display: none; }
        details[open] > summary .chevron { transform: rotate(90deg); }
        .chevron { transition: transform 0.15s ease; }
        .bg-indigo-600, .bg-indigo-500, .hover\:bg-indigo-700:hover, .hover\:bg-indigo-600:hover { background-color: var(--accent) !important; }
        .text-indigo-600, .text-indigo-700, .text-indigo-500 { color: var(--accent) !important; }
        .border-indigo-200, .border-indigo-100 { border-color: var(--accent) !important; }
        .focus\:ring-indigo-500:focus { --tw-ring-color: var(--accent) !important; }
        .bg-indigo-50 { background-color: color-mix(in srgb, var(--accent) 15%, white) !important; }
        .file\:bg-indigo-50:focus, .file\:text-indigo-700 { color: var(--accent) !important; }
    </style>
    <?php if (isset($extra_head)) echo $extra_head; ?>
</head>
<body class="bg-gray-50 text-slate-900">
<div class="min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-56 shrink-0 bg-white border-r border-slate-200 flex flex-col">

        <!-- Brand -->
        <div class="px-4 py-4 border-b border-slate-100 flex items-center gap-3">
            <?php if ($schoolLogoPath && file_exists(dirname(__DIR__) . '/' . $schoolLogoPath)): ?>
            <img src="../<?= htmlspecialchars($schoolLogoPath) ?>" alt="" class="w-10 h-10 rounded-lg object-contain shrink-0">
            <?php endif; ?>
            <div>
                <div class="text-base font-bold text-slate-900 tracking-tight"><?= htmlspecialchars(strtoupper($schoolName)) ?></div>
                <div class="text-[10px] font-semibold text-indigo-600 uppercase tracking-widest mt-0.5">Admin Console</div>
                <div class="text-[10px] text-slate-400 mt-0.5">Limit: 25 users</div>
            </div>
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
        <header class="flex items-center justify-between px-6 py-3.5 border-b border-slate-200 bg-white relative z-10">
            <div class="flex items-center gap-3">
                <?php if ($schoolLogoPath && file_exists(dirname(__DIR__) . '/' . $schoolLogoPath)): ?>
                <img src="../<?= htmlspecialchars($schoolLogoPath) ?>" alt="" class="h-8 w-auto max-w-[120px] object-contain">
                <?php endif; ?>
                <h1 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($page_title) ?></h1>
            </div>
            <div class="flex items-center gap-3">
                <!-- Message icon (opens modal) -->
                <button type="button"
                        id="adminMessagesButton"
                        class="relative inline-flex items-center justify-center w-8 h-8 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 focus:outline-none"
                        aria-label="Messages"
                        title="Messages">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-indigo-500 text-white text-[9px] font-semibold">
                        2
                    </span>
                </button>

                <!-- Notifications icon + dropdown -->
                <div class="relative">
                    <button type="button"
                            id="adminNotificationsButton"
                            class="relative inline-flex items-center justify-center w-8 h-8 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 focus:outline-none"
                            aria-label="Notifications"
                            title="Notifications">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                        <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-rose-500 text-white text-[9px] font-semibold">
                            3
                        </span>
                    </button>
                    <div id="adminNotificationsMenu"
                         class="hidden absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-xl shadow-lg py-1 text-[11px] text-slate-700 z-20">
                        <div class="px-3 py-1.5 border-b border-slate-100 flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-slate-800">Notifications</span>
                            <span class="text-[10px] text-indigo-600 cursor-pointer hover:text-indigo-700">Mark all read</span>
                        </div>
                        <button type="button" class="w-full flex items-start gap-2 px-3 py-1.5 hover:bg-slate-50 text-left">
                            <span class="mt-1 w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                            <span>
                                <span class="block text-[11px] font-medium text-slate-800">New teacher account</span>
                                <span class="block text-[10px] text-slate-500">Jane Smith was added to staff.</span>
                            </span>
                        </button>
                        <button type="button" class="w-full flex items-start gap-2 px-3 py-1.5 hover:bg-slate-50 text-left">
                            <span class="mt-1 w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>
                                <span class="block text-[11px] font-medium text-slate-800">Lesson plan submitted</span>
                                <span class="block text-[10px] text-slate-500">Week 3 Math plan awaits approval.</span>
                            </span>
                        </button>
                        <button type="button" class="w-full flex items-start gap-2 px-3 py-1.5 hover:bg-slate-50 text-left">
                            <span class="mt-1 w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span>
                                <span class="block text-[11px] font-medium text-slate-800">Settings update</span>
                                <span class="block text-[10px] text-slate-500">School profile was modified.</span>
                            </span>
                        </button>
                        <div class="border-t border-slate-100 mt-1 pt-1">
                            <a href="activity.php" class="flex items-center justify-between px-3 py-1.5 hover:bg-slate-50">
                                <span>View activity log</span>
                                <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Admin profile dropdown -->
                <div class="relative">
                    <button type="button"
                            id="adminProfileButton"
                            class="flex items-center gap-2 rounded-full pl-2 pr-1.5 py-1.5 hover:bg-slate-50 focus:outline-none">
                        <div class="hidden sm:flex flex-col items-end mr-1">
                            <span class="text-xs font-medium text-slate-700 leading-tight">
                                <?= htmlspecialchars($adminName) ?>
                            </span>
                            <span class="text-[10px] text-slate-400 leading-tight">
                                <?= htmlspecialchars($schoolName) ?>
                            </span>
                        </div>
                        <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold"
                             title="Account">
                            <?= htmlspecialchars($adminInitial) ?>
                        </div>
                        <i data-lucide="chevron-down" class="w-3 h-3 text-slate-400"></i>
                    </button>

                    <!-- Dropdown menu -->
                    <div id="adminProfileMenu"
                         class="hidden absolute right-0 mt-2 w-40 bg-white border border-slate-200 rounded-xl shadow-lg py-1 text-[11px] text-slate-700 z-20">
                        <a href="admin_profile.php"
                           class="flex items-center gap-2 px-3 py-1.5 hover:bg-slate-50">
                            <i data-lucide="user" class="w-3 h-3"></i>
                            <span>Admin profile</span>
                        </a>
                        <a href="edit_admin_profile.php"
                           class="flex items-center gap-2 px-3 py-1.5 hover:bg-slate-50">
                            <i data-lucide="edit-3" class="w-3 h-3"></i>
                            <span>Edit admin profile</span>
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

        <!-- Messages modal (overlay, hidden by default) -->
        <div id="adminMessagesModal"
             class="hidden fixed inset-0 z-30 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md mx-4">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">Messages (sample)</h2>
                        <p class="text-[11px] text-slate-400">Later, this will show real messages or announcements.</p>
                    </div>
                    <button type="button"
                            id="adminMessagesClose"
                            class="inline-flex items-center justify-center w-7 h-7 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-700">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                <div class="px-4 py-3 space-y-2 text-[11px] text-slate-700 max-h-72 overflow-y-auto">
                    <div class="flex items-start gap-2 rounded-lg border border-slate-100 px-3 py-2">
                        <span class="mt-0.5 w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-[10px] font-semibold">ADM</span>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-800">School Admin</span>
                                <span class="text-[10px] text-slate-400">2h ago</span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-0.5">
                                Please remember to submit your Week 3 lesson notes before Friday.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2 rounded-lg border border-slate-100 px-3 py-2">
                        <span class="mt-0.5 w-6 h-6 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-[10px] font-semibold">HOD</span>
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-slate-800">Head of Department</span>
                                <span class="text-[10px] text-slate-400">Yesterday</span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-0.5">
                                Mid-term tests will start next week. Update your grading templates where necessary.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">Messaging is not yet connected &mdash; this is just a UI preview.</span>
                    <button type="button"
                            id="adminMessagesOk"
                            class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700">
                        Close
                    </button>
                </div>
            </div>
        </div>

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

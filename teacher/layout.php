<?php
// Shared layout for all teacher pages under /teacher
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

$conn       = get_db_connection();
$schoolId   = current_school_id();
$schoolName = 'Axis SMS';

// Logged-in teacher details (from users table)
$teacherName    = 'Teacher';
$teacherEmail   = '';
$teacherInitial = 'T';

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
                $teacherName = $row['full_name'];
                // Use first letter of first word as avatar initial
                $parts = preg_split('/\s+/', trim($row['full_name']));
                if (!empty($parts[0])) {
                    $teacherInitial = strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'));
                }
            }
            if (!empty($row['email'])) {
                $teacherEmail = $row['email'];
            }
        }
        $stmt->close();
    }
}

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

// Basic role guard – allow access when there is no user_role yet,
// but block explicitly logged-in non-teacher roles.
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'teacher') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

if (!isset($page_title)) {
    $page_title = 'Teacher';
}

function teacherNavLink(string $check, string $current): string
{
    return $check === $current
        ? 'flex items-center gap-3 px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium w-full'
        : 'flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-800 text-sm w-full transition-colors';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($schoolName) ?> - Teacher - <?= htmlspecialchars($page_title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
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
            <div class="text-[10px] font-semibold text-emerald-600 uppercase tracking-widest mt-0.5">Teacher Portal</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Focused on classes & students</div>
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
                    <a href="dashboard.php" class="<?= teacherNavLink('Dashboard', $page_title) ?>">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i>
                        <span>My dashboard</span>
                    </a>
                    <a href="reports.php" class="<?= teacherNavLink('Reports', $page_title) ?>">
                        <i data-lucide="bar-chart-2" class="w-4 h-4 shrink-0"></i>
                        <span>Reports</span>
                    </a>
                    <a href="analytics.php" class="<?= teacherNavLink('Analytics', $page_title) ?>">
                        <i data-lucide="activity" class="w-4 h-4 shrink-0"></i>
                        <span>Analytics</span>
                    </a>
                </div>
            </details>

            <!-- Teaching -->
            <details open>
                <summary class="flex items-center justify-between cursor-pointer px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400 hover:text-slate-600 select-none mb-1">
                    <span>Teaching</span>
                    <i data-lucide="chevron-right" class="chevron w-3 h-3"></i>
                </summary>
                <div class="space-y-0.5">
                    <a href="classes.php" class="<?= teacherNavLink('Classes', $page_title) ?>">
                        <i data-lucide="book-open" class="w-4 h-4 shrink-0"></i>
                        <span>Classes assigned</span>
                    </a>
                    <a href="attendance.php" class="<?= teacherNavLink('Attendance', $page_title) ?>">
                        <i data-lucide="calendar-check" class="w-4 h-4 shrink-0"></i>
                        <span>Student attendance</span>
                    </a>
                    <a href="grades.php" class="<?= teacherNavLink('Grades', $page_title) ?>">
                        <i data-lucide="clipboard-list" class="w-4 h-4 shrink-0"></i>
                        <span>Grading</span>
                    </a>
                    <a href="lesson_notes.php" class="<?= teacherNavLink('Lesson Notes', $page_title) ?>">
                        <i data-lucide="file-text" class="w-4 h-4 shrink-0"></i>
                        <span>Lesson notes</span>
                    </a>
                </div>
            </details>

            <!-- Reference -->
            <details open>
                <summary class="flex items-center justify-between cursor-pointer px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400 hover:text-slate-600 select-none mb-1">
                    <span>Reference</span>
                    <i data-lucide="chevron-right" class="chevron w-3 h-3"></i>
                </summary>
                <div class="space-y-0.5">
                    <a href="timetable.php" class="<?= teacherNavLink('Timetable', $page_title) ?>">
                        <i data-lucide="calendar" class="w-4 h-4 shrink-0"></i>
                        <span>View timetable</span>
                    </a>
                    <a href="subjects.php" class="<?= teacherNavLink('Subjects', $page_title) ?>">
                        <i data-lucide="layers" class="w-4 h-4 shrink-0"></i>
                        <span>View subjects</span>
                    </a>
                </div>
            </details>

            <!-- Account -->
            <details open>
                <summary class="flex items-center justify-between cursor-pointer px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400 hover:text-slate-600 select-none mb-1">
                    <span>Account</span>
                    <i data-lucide="chevron-right" class="chevron w-3 h-3"></i>
                </summary>
                <div class="space-y-0.5">
                    <form method="post" action="../logout.php" class="m-0">
                        <button type="submit"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium w-full hover:bg-emerald-700 transition-colors">
                            <i data-lucide="log-out" class="w-4 h-4 shrink-0"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </details>
        </nav>
    </aside>

    <!-- Main content area -->
    <main class="flex-1 flex flex-col min-w-0 bg-gray-50">

        <!-- Top bar -->
        <header class="flex items-center justify-between px-6 py-3.5 border-b border-slate-200 bg-white">
            <h1 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($page_title) ?></h1>
            <div class="flex items-center gap-3">
                <!-- Message & notification icons -->
                <button type="button"
                        class="relative inline-flex items-center justify-center w-8 h-8 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 focus:outline-none"
                        aria-label="Messages">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-emerald-500 text-white text-[9px] font-semibold">
                        2
                    </span>
                </button>
                <button type="button"
                        class="relative inline-flex items-center justify-center w-8 h-8 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 focus:outline-none"
                        aria-label="Notifications">
                    <i data-lucide="bell" class="w-4 h-4"></i>
                    <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-3.5 h-3.5 rounded-full bg-rose-500 text-white text-[9px] font-semibold">
                        3
                    </span>
                </button>

                <!-- Teacher profile dropdown -->
                <div class="relative">
                    <button type="button"
                            id="teacherProfileButton"
                            class="flex items-center gap-2 rounded-full pl-2 pr-1.5 py-1.5 hover:bg-slate-50 focus:outline-none">
                        <div class="hidden sm:flex flex-col items-end mr-1">
                            <span class="text-xs font-medium text-slate-700 leading-tight">
                                <?= htmlspecialchars($teacherName) ?>
                            </span>
                            <span class="text-[10px] text-slate-400 leading-tight">
                                <?= htmlspecialchars($schoolName) ?>
                            </span>
                        </div>
                        <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-semibold"
                             title="<?= htmlspecialchars($teacherName . ($teacherEmail ? ' • ' . $teacherEmail : '')) ?>">
                            <?= htmlspecialchars($teacherInitial) ?>
                        </div>
                        <i data-lucide="chevron-down" class="w-3 h-3 text-slate-400"></i>
                    </button>

                    <!-- Dropdown menu -->
                    <div id="teacherProfileMenu"
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

        <!-- Optional flash message placeholder -->
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

<?php
// Shared layout for all admin pages under /admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

// Placeholder auth + tenant bootstrap.
// In production, verify Firebase ID token and set these.
if (!isset($_SESSION['user_role'])) {
    // Temporary dev defaults so you can see the UI
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Axis SMS - Admin - <?= htmlspecialchars($page_title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets_css_admin.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="logo">
            <div style="width:40px;height:40px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;color:#1e88e5;font-weight:700;">
                A
            </div>
            <span>Axis SMS Admin</span>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Overview</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Dashboard' ? ' active' : '') ?>" href="dashboard.php">
                        <span class="nav-icon">🏠</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Reports' ? ' active' : '') ?>" href="reports.php">
                        <span class="nav-icon">📊</span>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Analytics' ? ' active' : '') ?>" href="analytics.php">
                        <span class="nav-icon">📈</span>
                        <span>Analytics</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Management</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Students' ? ' active' : '') ?>" href="students.php">
                        <span class="nav-icon">👨‍🎓</span>
                        <span>Students</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Teachers' ? ' active' : '') ?>" href="teachers.php">
                        <span class="nav-icon">👩‍🏫</span>
                        <span>Teachers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Classes' ? ' active' : '') ?>" href="classes.php">
                        <span class="nav-icon">🏫</span>
                        <span>Classes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Subjects' ? ' active' : '') ?>" href="subjects.php">
                        <span class="nav-icon">📚</span>
                        <span>Subjects</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Parents' ? ' active' : '') ?>" href="parents.php">
                        <span class="nav-icon">👨‍👩‍👧</span>
                        <span>Parents</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">People</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Accountants' ? ' active' : '') ?>" href="accountants.php">
                        <span class="nav-icon">💼</span>
                        <span>Accountants</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Bus Drivers' ? ' active' : '') ?>" href="drivers.php">
                        <span class="nav-icon">🚌</span>
                        <span>Bus Drivers</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">System</div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Timetable' ? ' active' : '') ?>" href="timetable.php">
                        <span class="nav-icon">🗓</span>
                        <span>Timetable</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Settings' ? ' active' : '') ?>" href="settings.php">
                        <span class="nav-icon">⚙️</span>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-title"><?= htmlspecialchars($page_title) ?></div>
            <div class="topbar-actions">
                <span class="text-muted">Admin</span>
                <form method="post" action="../logout.php" style="margin:0;">
                    <button type="submit" class="btn btn-outline">Logout</button>
                </form>
            </div>
        </header>

<?php
// Page content continues in each specific page and closes with footer.php
?>


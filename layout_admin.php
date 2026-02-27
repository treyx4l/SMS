<?php
// Shared layout for admin pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

// Fake auth guard placeholder: in real usage, enforce Firebase + role check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // For now, allow bypass if ?debug=1 is set (local development helper)
    if (!isset($_GET['debug']) || $_GET['debug'] !== '1') {
        // In a real app, redirect to login page
        // header('Location: login.php');
        // exit;
    }
}

// Determine current page title from child script
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
    <link rel="stylesheet" href="assets_css_admin.css">
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
                    <a class="nav-link<?= ($page_title === 'Dashboard' ? ' active' : '') ?>" href="admin_dashboard.php">
                        <span class="nav-icon">🏠</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Reports' ? ' active' : '') ?>" href="admin_reports.php">
                        <span class="nav-icon">📊</span>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Analytics' ? ' active' : '') ?>" href="admin_analytics.php">
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
                    <a class="nav-link<?= ($page_title === 'Students' ? ' active' : '') ?>" href="admin_students.php">
                        <span class="nav-icon">👨‍🎓</span>
                        <span>Students</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Teachers' ? ' active' : '') ?>" href="admin_teachers.php">
                        <span class="nav-icon">👩‍🏫</span>
                        <span>Teachers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Classes' ? ' active' : '') ?>" href="admin_classes.php">
                        <span class="nav-icon">🏫</span>
                        <span>Classes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Subjects' ? ' active' : '') ?>" href="admin_subjects.php">
                        <span class="nav-icon">📚</span>
                        <span>Subjects</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Parents' ? ' active' : '') ?>" href="admin_parents.php">
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
                    <a class="nav-link<?= ($page_title === 'Accountants' ? ' active' : '') ?>" href="admin_accountants.php">
                        <span class="nav-icon">💼</span>
                        <span>Accountants</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Bus Drivers' ? ' active' : '') ?>" href="admin_drivers.php">
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
                    <a class="nav-link<?= ($page_title === 'Timetable' ? ' active' : '') ?>" href="admin_timetable.php">
                        <span class="nav-icon">🗓</span>
                        <span>Timetable</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= ($page_title === 'Settings' ? ' active' : '') ?>" href="admin_settings.php">
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
                <form method="post" action="logout.php" style="margin:0;">
                    <button type="submit" class="btn btn-outline">Logout</button>
                </form>
            </div>
        </header>

        <?php if (isset($flash_message)): ?>
            <div class="alert <?= $flash_type ?? 'alert-success' ?>">
                <?= htmlspecialchars($flash_message) ?>
            </div>
        <?php endif; ?>

        <?php
        // Child content will echo inside this file by including layout_admin.php at top
        ?>


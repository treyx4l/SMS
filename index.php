<?php
// Public landing page linking to Login and Register
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Axis SMS - School Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets_css_public.css">
</head>
<body>
<div class="page">
    <header class="nav">
        <a href="index.php" class="nav-brand">
            <div class="nav-logo">A</div>
            <div>Axis SMS</div>
        </a>
        <nav class="nav-links">
            <a href="login.php" class="btn btn-ghost">Login</a>
            <a href="register.php" class="btn btn-primary">Register school</a>
        </nav>
    </header>

    <main class="hero">
        <section>
            <div class="hero-badges">
                <span class="badge">Multi-tenant</span>
                <span class="badge">Firebase powered</span>
            </div>
            <h1 class="hero-heading">
                Smart school management for growing institutions.
            </h1>
            <p class="hero-subtitle">
                Axis SMS brings admins, teachers, parents, accountants and bus drivers
                onto a single, secure platform. Manage students, classes, attendance,
                grades, transport and more across multiple schools.
            </p>
            <div class="hero-actions">
                <a href="register.php" class="btn btn-primary">Register your school</a>
                <a href="login.php" class="btn btn-ghost">Already registered? Login</a>
            </div>
            <p class="hero-footnote">
                Built with PHP, MySQL and Firebase Authentication. Each school runs as its own tenant.
            </p>
        </section>

        <section class="hero-card">
            <div class="hero-card-title">Role-based dashboards</div>
            <div class="hero-card-subtitle">
                Tailored experiences for each role in your school community.
            </div>

            <div class="hero-metrics">
                <div class="hero-metric">
                    <div class="hero-metric-label">Admin</div>
                    <div class="hero-metric-value">Central control</div>
                </div>
                <div class="hero-metric">
                    <div class="hero-metric-label">Teachers</div>
                    <div class="hero-metric-value">Classes & grades</div>
                </div>
                <div class="hero-metric">
                    <div class="hero-metric-label">Parents</div>
                    <div class="hero-metric-value">Progress & fees</div>
                </div>
                <div class="hero-metric">
                    <div class="hero-metric-label">Transport</div>
                    <div class="hero-metric-value">Routes & riders</div>
                </div>
            </div>

            <div class="hero-roles">
                <span class="hero-role-pill">Admin dashboard</span>
                <span class="hero-role-pill">Accountant</span>
                <span class="hero-role-pill">Bus driver (mobile)</span>
                <span class="hero-role-pill">Teachers</span>
                <span class="hero-role-pill">Parents (mobile)</span>
            </div>
        </section>
    </main>

    <footer class="footer">
        <span>© <?= date('Y') ?> Axis SMS.</span>
        <span>Multi-tenant school management with Firebase Auth.</span>
    </footer>
</div>
</body>
</html>


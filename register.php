<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Axis SMS - Register School</title>
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
        </nav>
    </header>

    <main class="hero" style="grid-template-columns:minmax(0,1fr);max-width:560px;margin:0 auto;">
        <section class="hero-card">
            <div class="hero-card-title">Register your school</div>
            <div class="hero-card-subtitle">
                Create a school tenant and its first admin account.
            </div>

            <form id="register-form">
                <div class="hero-metrics" style="grid-template-columns:minmax(0,1fr);gap:0.8rem;">
                    <div>
                        <div class="hero-metric-label">School name</div>
                        <input type="text" id="school-name" required
                               style="width:100%;padding:0.45rem 0.55rem;border-radius:0.5rem;border:1px solid #d1d5db;font-size:0.9rem;">
                    </div>
                    <div>
                        <div class="hero-metric-label">School code</div>
                        <input type="text" id="school-code" required
                               placeholder="e.g. GREENHILL"
                               style="width:100%;padding:0.45rem 0.55rem;border-radius:0.5rem;border:1px solid #d1d5db;font-size:0.9rem;">
                    </div>
                    <div>
                        <div class="hero-metric-label">Admin email</div>
                        <input type="email" id="admin-email" required
                               style="width:100%;padding:0.45rem 0.55rem;border-radius:0.5rem;border:1px solid #d1d5db;font-size:0.9rem;">
                    </div>
                    <div>
                        <div class="hero-metric-label">Admin password</div>
                        <input type="password" id="admin-password" required
                               style="width:100%;padding:0.45rem 0.55rem;border-radius:0.5rem;border:1px solid #d1d5db;font-size:0.9rem;">
                    </div>
                </div>

                <div style="margin-top:1rem;display:flex;justify-content:space-between;align-items:center;">
                    <button type="submit" class="btn btn-primary">Register school</button>
                    <a href="login.php" style="font-size:0.8rem;color:#1e88e5;">Already registered?</a>
                </div>
            </form>

            <p class="hero-footnote" style="margin-top:1rem;">
                Registration creates a Firebase user for the admin, a school record in MySQL (`schools` table),
                and links them in the `users` table as an admin for that school.
            </p>
        </section>
    </main>

    <footer class="footer">
        <span>© <?= date('Y') ?> Axis SMS.</span>
        <span>Each school is a separate tenant in the system.</span>
    </footer>
</div>

<script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-app.js";
    import { getAuth, createUserWithEmailAndPassword, sendEmailVerification } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-auth.js";

    const firebaseConfig = {
        apiKey: "<?= htmlspecialchars(getenv('FIREBASE_API_KEY')) ?>",
        authDomain: "<?= htmlspecialchars(getenv('FIREBASE_AUTH_DOMAIN')) ?>",
        projectId: "<?= htmlspecialchars(getenv('FIREBASE_PROJECT_ID')) ?>",
    };

    const app = initializeApp(firebaseConfig);
    const auth = getAuth(app);

    const form = document.getElementById('register-form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const schoolName = document.getElementById('school-name').value;
        const schoolCode = document.getElementById('school-code').value;
        const email = document.getElementById('admin-email').value;
        const password = document.getElementById('admin-password').value;

        try {
            const cred = await createUserWithEmailAndPassword(auth, email, password);
            const idToken = await cred.user.getIdToken();

            const response = await fetch('api/register_school.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idToken,
                    school_name: schoolName,
                    school_code: schoolCode,
                    admin_name: email
                })
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                alert(data.error || 'Registration failed');
                return;
            }

            await sendEmailVerification(cred.user);
            alert('Registration successful. Please check your email inbox or spam folder to verify your account, then log in.');
            window.location.href = 'login.php';
        } catch (err) {
            console.error(err);
            alert('Firebase registration failed');
        }
    });
</script>
</body>
</html>


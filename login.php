<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Axis SMS - Login</title>
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
            <a href="register.php" class="btn btn-ghost">Register school</a>
        </nav>
    </header>

    <main class="hero" style="grid-template-columns:minmax(0,1fr);max-width:520px;margin:0 auto;">
        <section class="hero-card">
            <div class="hero-card-title">Login</div>
            <div class="hero-card-subtitle">
                Sign in with your email to access your dashboard.
            </div>

            <form id="login-form">
                <div class="hero-metrics" style="grid-template-columns:minmax(0,1fr);gap:0.8rem;">
                    <div>
                        <div class="hero-metric-label">Email</div>
                        <input type="email" id="login-email" required
                               style="width:100%;padding:0.45rem 0.55rem;border-radius:0.5rem;border:1px solid #d1d5db;font-size:0.9rem;">
                    </div>
                    <div>
                        <div class="hero-metric-label">Password</div>
                        <input type="password" id="login-password" required
                               style="width:100%;padding:0.45rem 0.55rem;border-radius:0.5rem;border:1px solid #d1d5db;font-size:0.9rem;">
                    </div>
                </div>

                <div style="margin-top:1rem;display:flex;justify-content:space-between;align-items:center;">
                    <button type="submit" class="btn btn-primary">Login</button>
                    <a href="register.php" style="font-size:0.8rem;color:#1e88e5;">Need an account?</a>
                </div>
            </form>

            <p class="hero-footnote" style="margin-top:1rem;">
                This form will be wired to Firebase Authentication. After successful login,
                you will be redirected to the correct dashboard based on your role.
            </p>
        </section>
    </main>

    <footer class="footer">
        <span>© <?= date('Y') ?> Axis SMS.</span>
        <span>Secure login powered by Firebase.</span>
    </footer>
</div>

<script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-app.js";
    import { getAuth, signInWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-auth.js";

    const firebaseConfig = {
        apiKey: "<?= htmlspecialchars(getenv('FIREBASE_API_KEY')) ?>",
        authDomain: "<?= htmlspecialchars(getenv('FIREBASE_AUTH_DOMAIN')) ?>",
        projectId: "<?= htmlspecialchars(getenv('FIREBASE_PROJECT_ID')) ?>",
    };

    const app = initializeApp(firebaseConfig);
    const auth = getAuth(app);

    const form = document.getElementById('login-form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;

        try {
            const cred = await signInWithEmailAndPassword(auth, email, password);
            const idToken = await cred.user.getIdToken();

            const response = await fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ idToken })
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                alert(data.error || 'Login failed');
                return;
            }

            window.location.href = data.redirect_url;
        } catch (err) {
            console.error(err);
            alert('Firebase login failed');
        }
    });
</script>
</body>
</html>


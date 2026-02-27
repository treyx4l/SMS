<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Axis SMS - Register School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Register your school on Axis SMS - School Management System">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col">

    <!-- Navbar -->
    <header class="flex items-center justify-between px-6 md:px-12 py-5 border-b border-slate-800">
        <a href="index.php" class="flex items-center gap-3 group">
            <div class="w-9 h-9 rounded-xl bg-blue-500 text-white flex items-center justify-center font-bold text-lg shadow-lg shadow-blue-500/25 group-hover:scale-105 transition-transform">A</div>
            <span class="font-semibold text-slate-100 text-sm tracking-wide">Axis SMS</span>
        </a>
        <nav>
            <a href="login.php" class="px-4 py-2 text-sm font-medium border border-slate-700 text-slate-300 hover:text-white hover:border-slate-500 rounded-lg transition-colors">Login</a>
        </nav>
    </header>

    <!-- Register Form -->
    <main class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-white mb-1">Register your school</h1>
                    <p class="text-sm text-slate-500">Create a school tenant and its first admin account.</p>
                </div>

                <form id="register-form" class="space-y-4">
                    <div>
                        <label for="school-name" class="block text-xs font-medium text-slate-400 mb-1.5 uppercase tracking-wide">School name</label>
                        <input type="text" id="school-name" required autocomplete="organization"
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="Greenhill Academy">
                    </div>
                    <div>
                        <label for="school-code" class="block text-xs font-medium text-slate-400 mb-1.5 uppercase tracking-wide">School code</label>
                        <input type="text" id="school-code" required
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="e.g. GREENHILL">
                    </div>
                    <div>
                        <label for="admin-email" class="block text-xs font-medium text-slate-400 mb-1.5 uppercase tracking-wide">Admin email</label>
                        <input type="email" id="admin-email" required autocomplete="email"
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="admin@school.com">
                    </div>
                    <div>
                        <label for="admin-password" class="block text-xs font-medium text-slate-400 mb-1.5 uppercase tracking-wide">Admin password</label>
                        <input type="password" id="admin-password" required autocomplete="new-password"
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="••••••••">
                    </div>

                    <div id="register-error" class="hidden px-4 py-3 bg-red-950 border border-red-800 rounded-xl text-sm text-red-400"></div>
                    <div id="register-success" class="hidden px-4 py-3 bg-green-950 border border-green-800 rounded-xl text-sm text-green-400"></div>

                    <button type="submit" id="register-btn"
                            class="w-full py-2.5 bg-blue-500 hover:bg-blue-400 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-500/25 hover:shadow-blue-400/30 hover:-translate-y-0.5 mt-2">
                        Register school
                    </button>
                </form>

                <p class="text-xs text-slate-600 mt-5 text-center">
                    Already registered?
                    <a href="login.php" class="text-blue-400 hover:text-blue-300 transition-colors ml-1">Login here</a>
                </p>

                <p class="text-xs text-slate-700 mt-4 leading-relaxed">
                    Registration creates a Firebase user for the admin, a school record in MySQL, and links them as an admin for that school.
                </p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="px-6 py-4 border-t border-slate-800 text-center text-xs text-slate-700">
        &copy; <?= date('Y') ?> Axis SMS. Each school is a separate tenant.
    </footer>

<script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-app.js";
    import { getAuth, createUserWithEmailAndPassword, sendEmailVerification } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-auth.js";

    const firebaseConfig = {
        apiKey: "<?= htmlspecialchars(getenv('FIREBASE_API_KEY')) ?>",
        authDomain: "<?= htmlspecialchars(getenv('FIREBASE_AUTH_DOMAIN')) ?>",
        projectId: "<?= htmlspecialchars(getenv('FIREBASE_PROJECT_ID')) ?>",
    };

    const app  = initializeApp(firebaseConfig);
    const auth = getAuth(app);

    const form    = document.getElementById('register-form');
    const btn     = document.getElementById('register-btn');
    const errEl   = document.getElementById('register-error');
    const succEl  = document.getElementById('register-success');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        btn.disabled = true;
        btn.textContent = 'Registering…';
        errEl.classList.add('hidden');
        succEl.classList.add('hidden');

        const schoolName = document.getElementById('school-name').value;
        const schoolCode = document.getElementById('school-code').value;
        const email      = document.getElementById('admin-email').value;
        const password   = document.getElementById('admin-password').value;

        try {
            const cred    = await createUserWithEmailAndPassword(auth, email, password);
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
                throw new Error(data.error || 'Registration failed');
            }

            await sendEmailVerification(cred.user);
            succEl.textContent = 'Registration successful! Check your email inbox or spam folder to verify your account, then log in.';
            succEl.classList.remove('hidden');
            form.reset();
            setTimeout(() => { window.location.href = 'login.php'; }, 4000);
        } catch (err) {
            errEl.textContent = err.message || 'Firebase registration failed';
            errEl.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'Register school';
        }
    });
</script>
</body>
</html>

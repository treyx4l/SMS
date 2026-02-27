<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Axis SMS - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to Axis SMS - School Management System">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        * { font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', Arial, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-slate-900 min-h-screen flex flex-col">

    <!-- Navbar -->
    <header class="flex items-center justify-between px-6 md:px-14 py-4 bg-white border-b border-slate-200">
        <a href="index.php" class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm">A</div>
            <span class="font-semibold text-slate-800 text-sm">Axis SMS</span>
        </a>
        <nav>
            <a href="register.php" class="px-4 py-1.5 text-sm font-medium border border-slate-300 text-slate-600 hover:text-slate-900 hover:border-slate-400 rounded-lg transition-colors">Register school</a>
        </nav>
    </header>

    <!-- Login Form -->
    <main class="flex-1 flex items-center justify-center px-4 py-14">
        <div class="w-full max-w-sm">
            <div class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">

                <!-- Header -->
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center">
                        <i data-lucide="log-in" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-slate-900 leading-tight">Sign in</h1>
                        <p class="text-xs text-slate-400">Access your Axis SMS dashboard</p>
                    </div>
                </div>

                <form id="login-form" class="space-y-4">

                    <!-- Role selector -->
                    <div>
                        <label for="login-role" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Sign in as</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                <i data-lucide="shield" class="w-4 h-4"></i>
                            </span>
                            <select id="login-role"
                                    class="w-full pl-9 pr-9 py-2.5 bg-gray-50 border border-slate-200 rounded-lg text-sm text-slate-700 appearance-none focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition cursor-pointer">
                                <option value="admin">Admin</option>
                                <option value="teacher">Teacher / Staff</option>
                                <option value="parent">Parent</option>
                                <option value="accountant">Accountant</option>
                                <option value="driver">Bus Driver</option>
                            </select>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </span>
                        </div>
                        <!-- Role description badges -->
                        <div id="role-badge" class="mt-2 hidden">
                            <span id="role-badge-text" class="inline-flex items-center gap-1.5 text-[11px] px-2.5 py-1 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-600"></span>
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="login-email" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Email</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <i data-lucide="mail" class="w-4 h-4"></i>
                            </span>
                            <input type="email" id="login-email" required autocomplete="email"
                                   class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-slate-200 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                   placeholder="you@school.com">
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="login-password" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Password</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <i data-lucide="lock" class="w-4 h-4"></i>
                            </span>
                            <input type="password" id="login-password" required autocomplete="current-password"
                                   class="w-full pl-9 pr-10 py-2.5 bg-gray-50 border border-slate-200 rounded-lg text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                   placeholder="••••••••">
                            <button type="button" id="togglePassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                                <i data-lucide="eye" class="w-4 h-4" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Error -->
                    <div id="login-error" class="hidden flex items-center gap-2 px-3 py-2.5 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
                        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                        <span id="login-error-text"></span>
                    </div>

                    <!-- Submit -->
                    <button type="submit" id="login-btn"
                            class="w-full flex items-center justify-center gap-2 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-sm transition-all shadow-sm hover:-translate-y-0.5 mt-1">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        Sign in
                    </button>
                </form>

                <p class="text-center text-xs text-slate-400 mt-5">
                    Don't have an account?
                    <a href="register.php" class="text-indigo-600 hover:text-indigo-700 font-medium ml-1">Register your school</a>
                </p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="px-6 py-4 bg-white border-t border-slate-200 text-center text-xs text-slate-400">
        &copy; <?= date('Y') ?> Axis SMS. Secure login powered by Firebase.
    </footer>

    <script>
        lucide.createIcons();

        // Role descriptions
        const roleInfo = {
            admin:      { label: 'Admin console access', icon: 'shield' },
            teacher:    { label: 'Teacher / Staff portal', icon: 'user-check' },
            parent:     { label: 'Parent tracking portal', icon: 'users' },
            accountant: { label: 'Finance & accounting access', icon: 'calculator' },
            driver:     { label: 'Transport management', icon: 'bus' },
        };

        const roleSelect   = document.getElementById('login-role');
        const roleBadge    = document.getElementById('role-badge');
        const roleBadgeText= document.getElementById('role-badge-text');

        function updateRoleBadge() {
            const role = roleSelect.value;
            const info = roleInfo[role];
            if (info) {
                roleBadgeText.textContent = info.label;
                roleBadge.classList.remove('hidden');
            }
        }

        roleSelect.addEventListener('change', updateRoleBadge);
        updateRoleBadge();

        // Password toggle
        const pwInput = document.getElementById('login-password');
        const eyeIcon = document.getElementById('eyeIcon');
        document.getElementById('togglePassword').addEventListener('click', () => {
            const isText = pwInput.type === 'text';
            pwInput.type = isText ? 'password' : 'text';
            eyeIcon.setAttribute('data-lucide', isText ? 'eye' : 'eye-off');
            lucide.createIcons();
        });
    </script>

<script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-app.js";
    import { getAuth, signInWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-auth.js";

    const firebaseConfig = {
        apiKey: "<?= htmlspecialchars(getenv('FIREBASE_API_KEY')) ?>",
        authDomain: "<?= htmlspecialchars(getenv('FIREBASE_AUTH_DOMAIN')) ?>",
        projectId: "<?= htmlspecialchars(getenv('FIREBASE_PROJECT_ID')) ?>",
    };

    const app  = initializeApp(firebaseConfig);
    const auth = getAuth(app);

    const form    = document.getElementById('login-form');
    const btn     = document.getElementById('login-btn');
    const errEl   = document.getElementById('login-error');
    const errText = document.getElementById('login-error-text');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Signing in…`;
        errEl.classList.add('hidden');

        const email    = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;
        const role     = document.getElementById('login-role').value;

        try {
            const cred    = await signInWithEmailAndPassword(auth, email, password);
            const idToken = await cred.user.getIdToken();

            const response = await fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ idToken, role })
            });

            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.error || 'Login failed');
            window.location.href = data.redirect_url;
        } catch (err) {
            errText.textContent = err.message || 'Firebase login failed';
            errEl.classList.remove('hidden');
            btn.disabled = false;
            btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg> Sign in`;
        }
    });
</script>
</body>
</html>

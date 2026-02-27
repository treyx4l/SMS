<?php
// Public landing page linking to Login and Register
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Axis SMS - School Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Axis SMS - Smart school management for growing institutions.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        * { font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', Arial, sans-serif; }
        #bg-canvas { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; overflow: hidden; }
        .orb { position: absolute; border-radius: 50%; opacity: 0.07; filter: blur(80px); }
    </style>
</head>
<body class="bg-gray-50 text-slate-900 min-h-screen relative overflow-x-hidden">

    <!-- GSAP Animated Background (subtle, light) -->
    <div id="bg-canvas" aria-hidden="true">
        <div class="orb bg-indigo-400 w-[500px] h-[500px]" id="orb1" style="top:-10%;left:-5%;"></div>
        <div class="orb bg-blue-300 w-[400px] h-[400px]" id="orb2" style="top:40%;right:-10%;"></div>
        <div class="orb bg-violet-300 w-[350px] h-[350px]" id="orb3" style="bottom:-5%;left:30%;"></div>
        <!-- Subtle dot grid -->
        <svg class="absolute inset-0 w-full h-full opacity-[0.04]" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="dots" width="24" height="24" patternUnits="userSpaceOnUse">
                    <circle cx="1" cy="1" r="1" fill="#6366f1"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#dots)"/>
        </svg>
    </div>

    <div class="relative z-10 min-h-screen flex flex-col">

        <!-- Navbar -->
        <header class="flex items-center justify-between px-6 md:px-14 py-4 bg-white/80 backdrop-blur-md border-b border-slate-200">
            <a href="index.php" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm">A</div>
                <span class="font-semibold text-slate-800 text-sm">Axis SMS</span>
            </a>
            <nav class="flex items-center gap-2">
                <a href="login.php" class="px-4 py-1.5 text-sm text-slate-600 hover:text-slate-900 rounded-lg hover:bg-slate-100 transition-colors">Login</a>
                <a href="register.php" class="px-4 py-1.5 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors">Register school</a>
            </nav>
        </header>

        <!-- Hero -->
        <main class="flex-1">
            <section class="max-w-6xl mx-auto px-6 md:px-14 pt-20 pb-16 grid md:grid-cols-2 gap-14 items-center">

                <!-- Left copy -->
                <div id="hero-text">
                    <div class="flex flex-wrap gap-2 mb-5">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium bg-indigo-50 text-indigo-700 rounded-full border border-indigo-200">
                            <i data-lucide="building-2" class="w-3 h-3"></i> Multi-tenant
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium bg-blue-50 text-blue-700 rounded-full border border-blue-200">
                            <i data-lucide="flame" class="w-3 h-3"></i> Firebase powered
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium bg-violet-50 text-violet-700 rounded-full border border-violet-200">
                            <i data-lucide="shield-check" class="w-3 h-3"></i> Role-based access
                        </span>
                    </div>
                    <h1 class="text-4xl md:text-5xl font-extrabold leading-tight text-slate-900 mb-5">
                        Smart school<br>
                        <span class="text-indigo-600">management</span><br>
                        for institutions.
                    </h1>
                    <p class="text-slate-500 text-base leading-relaxed mb-7 max-w-md">
                        Axis SMS brings admins, teachers, parents, accountants and bus drivers onto a single, secure platform. Manage students, classes, attendance, grades and transport.
                    </p>
                    <div class="flex flex-wrap gap-3 mb-6">
                        <a href="register.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-sm transition-all shadow-md hover:-translate-y-0.5">
                            Register your school
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                        <a href="login.php" class="inline-flex items-center gap-2 px-5 py-2.5 border border-slate-300 text-slate-700 hover:border-slate-400 hover:bg-white font-medium rounded-lg text-sm transition-all hover:-translate-y-0.5">
                            Already registered? Login
                        </a>
                    </div>
                    <p class="text-xs text-slate-400">Built with PHP, MySQL &amp; Firebase Authentication. Each school runs as its own tenant.</p>
                </div>

                <!-- Right card -->
                <div id="hero-card" class="bg-white border border-slate-200 rounded-2xl p-6 shadow-lg">
                    <div class="text-sm font-semibold text-slate-800 mb-1">Role-based dashboards</div>
                    <div class="text-xs text-slate-400 mb-5">Tailored experiences for each role in your school community.</div>

                    <div class="grid grid-cols-2 gap-3 mb-5">
                        <div class="flex items-start gap-2.5 bg-slate-50 border border-slate-100 rounded-xl p-3.5">
                            <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                                <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-400">Admin</div>
                                <div class="text-xs font-semibold text-slate-700">Central control</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 bg-slate-50 border border-slate-100 rounded-xl p-3.5">
                            <div class="w-7 h-7 rounded-lg bg-green-100 text-green-600 flex items-center justify-center shrink-0">
                                <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-400">Teachers</div>
                                <div class="text-xs font-semibold text-slate-700">Classes &amp; grades</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 bg-slate-50 border border-slate-100 rounded-xl p-3.5">
                            <div class="w-7 h-7 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center shrink-0">
                                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-400">Parents</div>
                                <div class="text-xs font-semibold text-slate-700">Progress &amp; fees</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-2.5 bg-slate-50 border border-slate-100 rounded-xl p-3.5">
                            <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                <i data-lucide="bus" class="w-3.5 h-3.5"></i>
                            </div>
                            <div>
                                <div class="text-[11px] text-slate-400">Transport</div>
                                <div class="text-xs font-semibold text-slate-700">Routes &amp; riders</div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-4 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 border border-slate-200">
                            <i data-lucide="check" class="w-2.5 h-2.5 text-indigo-500"></i> Admin dashboard
                        </span>
                        <span class="inline-flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 border border-slate-200">
                            <i data-lucide="check" class="w-2.5 h-2.5 text-indigo-500"></i> Accountant
                        </span>
                        <span class="inline-flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 border border-slate-200">
                            <i data-lucide="check" class="w-2.5 h-2.5 text-indigo-500"></i> Bus driver
                        </span>
                        <span class="inline-flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 border border-slate-200">
                            <i data-lucide="check" class="w-2.5 h-2.5 text-indigo-500"></i> Teachers
                        </span>
                        <span class="inline-flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 border border-slate-200">
                            <i data-lucide="check" class="w-2.5 h-2.5 text-indigo-500"></i> Parents
                        </span>
                    </div>
                </div>
            </section>

            <!-- Features strip -->
            <section class="border-t border-slate-200 bg-white">
                <div class="max-w-6xl mx-auto px-6 md:px-14 py-10 grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center shrink-0">
                            <i data-lucide="building-2" class="w-4 h-4 text-indigo-600"></i>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">Multi-school</div>
                            <div class="text-xs text-slate-400 mt-0.5">Separate tenants per school</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center shrink-0">
                            <i data-lucide="shield-check" class="w-4 h-4 text-blue-600"></i>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">Secure auth</div>
                            <div class="text-xs text-slate-400 mt-0.5">Firebase Authentication</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-green-50 border border-green-100 flex items-center justify-center shrink-0">
                            <i data-lucide="trending-up" class="w-4 h-4 text-green-600"></i>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">Real-time data</div>
                            <div class="text-xs text-slate-400 mt-0.5">Live dashboards</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-orange-50 border border-orange-100 flex items-center justify-center shrink-0">
                            <i data-lucide="smartphone" class="w-4 h-4 text-orange-600"></i>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">Mobile ready</div>
                            <div class="text-xs text-slate-400 mt-0.5">Parents &amp; drivers apps</div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="px-6 md:px-14 py-5 bg-white border-t border-slate-200 flex flex-wrap justify-between gap-3 text-xs text-slate-400">
            <span>&copy; <?= date('Y') ?> Axis SMS. All rights reserved.</span>
            <span>Multi-tenant school management with Firebase Auth.</span>
        </footer>
    </div>

    <script>
        lucide.createIcons();

        // GSAP background orbs — subtle, light theme
        ['#orb1','#orb2','#orb3'].forEach((id, i) => {
            const el = document.querySelector(id);
            if (!el) return;
            gsap.to(el, {
                x: () => (Math.random() - 0.5) * 160,
                y: () => (Math.random() - 0.5) * 160,
                duration: 10 + i * 4,
                repeat: -1, yoyo: true, ease: 'sine.inOut', delay: i * 1.5
            });
            gsap.to(el, {
                opacity: 0.04 + Math.random() * 0.06,
                duration: 5 + i * 2,
                repeat: -1, yoyo: true, ease: 'sine.inOut', delay: i * 0.8
            });
        });

        // Hero entrance
        gsap.from('#hero-text > *', { y: 24, opacity: 0, duration: 0.6, stagger: 0.1, ease: 'power3.out', delay: 0.15 });
        gsap.from('#hero-card',     { y: 32, opacity: 0, duration: 0.7, ease: 'power3.out', delay: 0.4 });
    </script>
</body>
</html>

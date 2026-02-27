        </div>
    </main>
</div>
<script>
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }

    // Simple dropdown toggle for teacher profile menu
    (function () {
        const btn = document.getElementById('teacherProfileButton');
        const menu = document.getElementById('teacherProfileMenu');
        if (!btn || !menu) return;

        function toggleMenu() {
            menu.classList.toggle('hidden');
        }

        function closeMenu(event) {
            if (!menu.classList.contains('hidden')) {
                if (!menu.contains(event.target) && !btn.contains(event.target)) {
                    menu.classList.add('hidden');
                }
            }
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleMenu();
        });

        document.addEventListener('click', closeMenu);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                menu.classList.add('hidden');
            }
        });
    })();
</script>
</body>
</html>


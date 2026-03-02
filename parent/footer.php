    </main>
</div>
<script>
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }

    (function () {
        const profileBtn  = document.getElementById('parentProfileButton');
        const profileMenu = document.getElementById('parentProfileMenu');

        function closeProfileMenu() {
            if (profileMenu) profileMenu.classList.add('hidden');
        }

        if (profileBtn && profileMenu) {
            profileBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                profileMenu.classList.toggle('hidden');
            });
        }

        document.addEventListener('click', function (event) {
            if (profileMenu && profileBtn &&
                !profileMenu.contains(event.target) &&
                !profileBtn.contains(event.target)) {
                closeProfileMenu();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeProfileMenu();
            }
        });
    })();
</script>
</body>
</html>

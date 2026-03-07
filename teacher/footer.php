        </div>
    </main>
</div>
<script>
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }

    // Simple dropdowns and modal for teacher header
    (function () {
        const profileBtn  = document.getElementById('teacherProfileButton');
        const profileMenu = document.getElementById('teacherProfileMenu');
        const notifBtn    = document.getElementById('teacherNotificationsButton');
        const notifMenu   = document.getElementById('teacherNotificationsMenu');
        const msgBtn      = document.getElementById('teacherMessagesButton');
        const msgModal    = document.getElementById('teacherMessagesModal');
        const msgClose    = document.getElementById('teacherMessagesClose');
        const msgOk       = document.getElementById('teacherMessagesOk');

        function toggleMenu(menu) {
            if (!menu) return;
            menu.classList.toggle('hidden');
        }

        function closeAllMenus() {
            if (profileMenu) profileMenu.classList.add('hidden');
            if (notifMenu) notifMenu.classList.add('hidden');
        }

        if (profileBtn && profileMenu) {
            profileBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                // Close other menus first
                if (notifMenu) notifMenu.classList.add('hidden');
                toggleMenu(profileMenu);
            });
        }

        if (notifBtn && notifMenu) {
            notifBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                // Close other menus first
                if (profileMenu) profileMenu.classList.add('hidden');
                toggleMenu(notifMenu);
            });
        }

        if (msgBtn && msgModal) {
            const openModal = () => msgModal.classList.remove('hidden');
            const closeModal = () => msgModal.classList.add('hidden');

            msgBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                closeAllMenus();
                openModal();
            });

            if (msgClose) msgClose.addEventListener('click', closeModal);
            if (msgOk) msgOk.addEventListener('click', closeModal);
        }

        document.addEventListener('click', function (event) {
            // Close profile + notifications when clicking outside
            if (profileMenu && profileBtn &&
                !profileMenu.contains(event.target) &&
                !profileBtn.contains(event.target)) {
                profileMenu.classList.add('hidden');
            }
            if (notifMenu && notifBtn &&
                !notifMenu.contains(event.target) &&
                !notifBtn.contains(event.target)) {
                notifMenu.classList.add('hidden');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAllMenus();
                if (msgModal) msgModal.classList.add('hidden');
            }
        });
    })();

    // Browser notifications for teacher (Admin & Staff group)
    (function () {
        if (!('Notification' in window)) return;

        function sendTeacherNotifications() {
            if (Notification.permission !== 'granted') return;
            const key = 'teacher_seen_notifications';
            let seen = [];
            try {
                seen = JSON.parse(localStorage.getItem(key) || '[]');
            } catch (_) {}
            document.querySelectorAll('.app-notif').forEach(btn => {
                const id    = btn.dataset.notifId;
                const title = btn.dataset.notifTitle || 'School notification';
                const body  = btn.dataset.notifBody  || '';
                if (!id || seen.includes(id)) return;
                try {
                    new Notification(title, { body });
                    seen.push(id);
                } catch (_) {}
            });
            try {
                localStorage.setItem(key, JSON.stringify(seen));
            } catch (_) {}
        }

        const notifBtn = document.getElementById('teacherNotificationsButton');
        if (notifBtn) {
            notifBtn.addEventListener('click', function () {
                if (Notification.permission === 'default') {
                    Notification.requestPermission().then(sendTeacherNotifications);
                } else {
                    sendTeacherNotifications();
                }
            });
        }
    })();
</script>
</body>
</html>


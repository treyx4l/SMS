        </div>
    </main>
</div>
<script>
    (function initIcons() {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        } else {
            document.addEventListener('DOMContentLoaded', initIcons);
            window.addEventListener('load', initIcons);
        }
    })();

    // Dropdowns and modal for admin header
    (function () {
        const profileBtn  = document.getElementById('adminProfileButton');
        const profileMenu = document.getElementById('adminProfileMenu');
        const notifBtn    = document.getElementById('adminNotificationsButton');
        const notifMenu   = document.getElementById('adminNotificationsMenu');
        const msgBtn      = document.getElementById('adminMessagesButton');
        const msgModal    = document.getElementById('adminMessagesModal');
        const msgClose    = document.getElementById('adminMessagesClose');
        const msgOk       = document.getElementById('adminMessagesOk');

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
                if (notifMenu) notifMenu.classList.add('hidden');
                toggleMenu(profileMenu);
            });
        }

        if (notifBtn && notifMenu) {
            notifBtn.addEventListener('click', function (e) {
                e.stopPropagation();
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

    // Browser notifications helper for all dashboards
    (function () {
        if (!('Notification' in window)) return;

        function sendBrowserNotifications(prefix) {
            if (Notification.permission !== 'granted') return;
            const seenKey = prefix + '_seen_notifications';
            let seen = [];
            try {
                seen = JSON.parse(localStorage.getItem(seenKey) || '[]');
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
                localStorage.setItem(seenKey, JSON.stringify(seen));
            } catch (_) {}
        }

        // Request permission when user first opens notifications, then fire
        const adminNotifBtn = document.getElementById('adminNotificationsButton');
        if (adminNotifBtn) {
            adminNotifBtn.addEventListener('click', function () {
                if (Notification.permission === 'default') {
                    Notification.requestPermission().then(() => sendBrowserNotifications('admin'));
                } else {
                    sendBrowserNotifications('admin');
                }
            });
        }
    })();
</script>
</body>
</html>


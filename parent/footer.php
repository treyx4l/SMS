    </main>
</div>
<script>
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }

    (function () {
        const profileBtn  = document.getElementById('parentProfileButton');
        const profileMenu = document.getElementById('parentProfileMenu');
        const notifBtn    = document.getElementById('parentNotificationsButton');
        const notifMenu   = document.getElementById('parentNotificationsMenu');
        const msgBtn      = document.getElementById('parentMessagesButton');

        function closeProfileMenu() {
            if (profileMenu) profileMenu.classList.add('hidden');
        }
        function closeNotifMenu() {
            if (notifMenu) notifMenu.classList.add('hidden');
        }

        if (profileBtn && profileMenu) {
            profileBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                closeNotifMenu();
                profileMenu.classList.toggle('hidden');
            });
        }

        if (notifBtn && notifMenu) {
            notifBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                closeProfileMenu();
                notifMenu.classList.toggle('hidden');
            });
        }

        if (msgBtn) {
            msgBtn.addEventListener('click', function () {
                if (!('Notification' in window)) return;
                const body = 'Open the parent dashboard to view your latest messages.';
                if (Notification.permission === 'default') {
                    Notification.requestPermission().then(() => {
                        if (Notification.permission === 'granted') {
                            try { new Notification('Messages', { body }); } catch (_) {}
                        }
                    });
                } else if (Notification.permission === 'granted') {
                    try { new Notification('Messages', { body }); } catch (_) {}
                }
            });
        }

        document.addEventListener('click', function (event) {
            if (profileMenu && profileBtn &&
                !profileMenu.contains(event.target) &&
                !profileBtn.contains(event.target)) {
                closeProfileMenu();
            }
            if (notifMenu && notifBtn &&
                !notifMenu.contains(event.target) &&
                !notifBtn.contains(event.target)) {
                closeNotifMenu();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeProfileMenu();
                closeNotifMenu();
            }
        });
    })();

    // Browser notifications for parent (Parents · Staff · Admin group)
    (function () {
        if (!('Notification' in window)) return;
        function sendParentNotifications() {
            if (Notification.permission !== 'granted') return;
            const key = 'parent_seen_notifications';
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
        const notifBtn = document.getElementById('parentNotificationsButton');
        if (notifBtn) {
            notifBtn.addEventListener('click', function () {
                if (Notification.permission === 'default') {
                    Notification.requestPermission().then(sendParentNotifications);
                } else {
                    sendParentNotifications();
                }
            });
        }
    })();
</script>
</body>
</html>

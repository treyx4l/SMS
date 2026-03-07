<script>
    (function initIcons() {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        } else {
            document.addEventListener('DOMContentLoaded', initIcons);
            window.addEventListener('load', initIcons);
        }
    })();

    // Shared Chat & Notifications script for all dashboards
    document.addEventListener('DOMContentLoaded', function() {
        // Find which prefix we are dealing with based on the modal
        const chatModal = document.querySelector('[id$="ChatModal"]');
        if (!chatModal) return;

        const prefix = chatModal.dataset.prefix; // 'admin', 'teacher', 'parent'
        const role = chatModal.dataset.role;

        // --- Elements ---
        const profileBtn = document.getElementById(prefix + 'ProfileButton');
        const profileMenu = document.getElementById(prefix + 'ProfileMenu');
        
        const notifBtn = document.getElementById(prefix + 'NotificationsButton');
        const notifMenu = document.getElementById(prefix + 'NotificationsMenu');
        const notifBadge = document.getElementById(prefix + 'NotifBadge');
        const notifGroup1 = document.getElementById(prefix + 'NotifGroup1');
        const notifGroup2 = document.getElementById(prefix + 'NotifGroup2');
        const markAllReadBtn = document.getElementById(prefix + 'MarkAllRead') || document.getElementById(prefix + 'ClearAll');

        const msgBtn = document.getElementById(prefix + 'MessagesButton');
        const msgBadge = document.getElementById(prefix + 'MsgBadge');
        
        const chatClose = document.getElementById(prefix + 'ChatClose');
        const chatSearch = document.getElementById(prefix + 'ChatSearch');
        const searchResults = document.getElementById(prefix + 'ChatSearchResults');
        const recentList = document.getElementById(prefix + 'RecentList');
        
        const threadHeader = document.getElementById(prefix + 'ThreadHeader');
        const threadAvatar = document.getElementById(prefix + 'ThreadAvatar');
        const threadName = document.getElementById(prefix + 'ThreadName');
        const threadSub = document.getElementById(prefix + 'ThreadSub');
        const threadMessages = document.getElementById(prefix + 'ThreadMessages');
        
        const chatInput = document.getElementById(prefix + 'ChatInput');
        const chatSend = document.getElementById(prefix + 'ChatSend');

        // State
        let activeThreadType = null; // 'user' | 'group'
        let activeThreadId = null;   // user.id or 'staff'/'parents_staff'
        let messagesPollInterval = null;
        let convPollInterval = null;

        // --- Basic Toggles ---
        function closeAllMenus() {
            if (profileMenu) profileMenu.classList.add('hidden');
            if (notifMenu) notifMenu.classList.add('hidden');
        }

        if (profileBtn) {
            profileBtn.addEventListener('click', e => {
                e.stopPropagation();
                if (notifMenu) notifMenu.classList.add('hidden');
                profileMenu.classList.toggle('hidden');
            });
        }

        document.addEventListener('click', e => {
            if (profileMenu && profileBtn && !profileMenu.contains(e.target) && !profileBtn.contains(e.target)) {
                profileMenu.classList.add('hidden');
            }
            if (notifMenu && notifBtn && !notifMenu.contains(e.target) && !notifBtn.contains(e.target)) {
                notifMenu.classList.add('hidden');
            }
            if (searchResults && !chatSearch.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('hidden');
            }
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeAllMenus();
                chatModal.classList.add('hidden');
            }
        });

        if (chatClose) {
            chatClose.addEventListener('click', () => {
                chatModal.classList.add('hidden');
                clearInterval(messagesPollInterval);
            });
        }

        if (msgBtn) {
            msgBtn.addEventListener('click', e => {
                e.stopPropagation();
                closeAllMenus();
                chatModal.classList.remove('hidden');
                loadConversations(); // Initial load
            });
        }

        // --- Notifications ---
        let notifsLoadedCount = 0;
        
        function loadNotifications() {
            fetch('../api/notifications.php')
                .then(r => r.json())
                .then(data => {
                    if (data.error) return;
                    renderNotifications(data);
                })
                .catch(console.error);
        }

        function renderNotifications(data) {
            // Group 1: Parents/Staff
            if (notifGroup1) {
                notifGroup1.innerHTML = data.parents_staff.length === 0 
                    ? '<div class="px-4 py-3 text-center text-slate-400 text-[10px]">No recent notifications</div>'
                    : data.parents_staff.map(n => {
                        const tag = n.link ? 'a' : 'button';
                        const href = n.link ? ` href="${n.link}"` : ' type="button"';
                        return `
                        <${tag}${href} class="w-full flex items-start gap-2 px-3 py-1.5 hover:bg-slate-50 text-left app-notif block" data-notif-id="${n.id}" data-notif-title="${n.title}" data-notif-body="${n.body}">
                            <span class="mt-1 w-1.5 h-1.5 rounded-full bg-${n.color}-500 shrink-0"></span>
                            <div class="min-w-0 flex-1">
                                <span class="flex items-center justify-between gap-2 text-[11px] font-medium text-slate-800">
                                    <span class="truncate">${n.title}</span>
                                    <span class="text-[9px] text-slate-400 shrink-0">${n.ago}</span>
                                </span>
                                <span class="block text-[10px] text-slate-500 truncate">${n.body}</span>
                            </div>
                        </${tag}>
                    `}).join('');
            }
            
            // Group 2: Staff only (not rendered for parents usually)
            if (notifGroup2 && data.staff) {
                notifGroup2.innerHTML = data.staff.length === 0
                    ? '<div class="px-4 py-3 text-center text-slate-400 text-[10px]">No recent notifications</div>'
                    : data.staff.map(n => {
                        const tag = n.link ? 'a' : 'button';
                        const href = n.link ? ` href="${n.link}"` : ' type="button"';
                        return `
                        <${tag}${href} class="w-full flex items-start gap-2 px-3 py-1.5 hover:bg-slate-50 text-left app-notif block" data-notif-id="${n.id}" data-notif-title="${n.title}" data-notif-body="${n.body}">
                            <span class="mt-1 w-1.5 h-1.5 rounded-full bg-${n.color}-500 shrink-0"></span>
                            <div class="min-w-0 flex-1">
                                <span class="flex items-center justify-between gap-2 text-[11px] font-medium text-slate-800">
                                    <span class="truncate">${n.title}</span>
                                    <span class="text-[9px] text-slate-400 shrink-0">${n.ago}</span>
                                </span>
                                <span class="block text-[10px] text-slate-500 truncate">${n.body}</span>
                            </div>
                        </${tag}>
                    `}).join('');
            }

            // Badge count logic (client-side unread proxy)
            const seenKey = prefix + '_seen_notifications';
            const seen = JSON.parse(localStorage.getItem(seenKey) || '[]');
            
            let unread = 0;
            const newIds = [];
            data.parents_staff.forEach(n => { newIds.push(n.id.toString()); if(!seen.includes(n.id.toString())) unread++; });
            if(data.staff) { data.staff.forEach(n => { newIds.push(n.id.toString()); if(!seen.includes(n.id.toString())) unread++; }); }

            if (unread > 0) {
                notifBadge.textContent = unread;
                notifBadge.classList.remove('hidden');
            } else {
                notifBadge.classList.add('hidden');
            }

            // Handle browser push notifications for unseen
            if ('Notification' in window && Notification.permission === 'granted') {
                document.querySelectorAll('#' + prefix + 'NotificationsMenu .app-notif').forEach(btn => {
                    const id = btn.dataset.notifId;
                    if (!seen.includes(id)) {
                        try {
                            new Notification(btn.dataset.notifTitle, { body: btn.dataset.notifBody });
                        } catch(e) {}
                    }
                });
                
                // Keep seen list updated without clicking "mark read" (so they don't fire twice)
                // Just marking them as seen for push notifications. The badge will still show them if they aren't fully read?
                // Actually, let's keep badge strictly tied to Mark All Read
            }
        }

        if (notifBtn) {
            notifBtn.addEventListener('click', e => {
                e.stopPropagation();
                if (profileMenu) profileMenu.classList.add('hidden');
                notifMenu.classList.toggle('hidden');
                
                if (!notifMenu.classList.contains('hidden')) {
                    if ('Notification' in window && Notification.permission === 'default') {
                        Notification.requestPermission();
                    }
                    loadNotifications();
                }
            });
        }

        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', e => {
                e.stopPropagation();
                // Get all current notif IDs rendered
                const seenKey = prefix + '_seen_notifications';
                const seen = JSON.parse(localStorage.getItem(seenKey) || '[]');
                document.querySelectorAll('#' + prefix + 'NotificationsMenu .app-notif').forEach(btn => {
                    if (!seen.includes(btn.dataset.notifId)) seen.push(btn.dataset.notifId);
                });
                localStorage.setItem(seenKey, JSON.stringify(seen));
                notifBadge.classList.add('hidden');
                
                // Visually clear them
                const emptyMsg = '<div class="px-4 py-3 text-center text-slate-400 text-[10px]">No recent notifications</div>';
                if (notifGroup1) notifGroup1.innerHTML = emptyMsg;
                if (notifGroup2) notifGroup2.innerHTML = emptyMsg;
                
                // Could call api/notifications.php POST action='mark_read' if implemented
            });
        }
        
        // Initial load for badges
        if (notifBtn) loadNotifications();
        
        // --- Chat System ---
        
        function loadConversations() {
            fetch('../api/messaging.php?action=conversations')
                .then(r => r.json())
                .then(data => {
                    if (data.error || !data.conversations) return;
                    
                    // Update global msg badge
                    if (msgBadge) {
                        if (data.total_unread > 0) {
                            msgBadge.textContent = data.total_unread;
                            msgBadge.classList.remove('hidden');
                        } else {
                            msgBadge.classList.add('hidden');
                        }
                    }

                    if (data.conversations.length === 0) {
                        recentList.innerHTML = '<p class="text-[10px] text-slate-400 px-2 py-2">No recent messages</p>';
                        return;
                    }
                    recentList.innerHTML = data.conversations.map(c => `
                        <button type="button" class="chat-sidebar-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 text-left transition-colors relative" data-type="user" data-uid="${c.partner_id}" data-name="${c.name}" data-sub="${c.role}" data-initials="${c.initials}">
                            <span class="w-7 h-7 rounded-full bg-${c.color}-100 text-${c.color}-700 flex items-center justify-center shrink-0 text-[10px] font-semibold">
                                ${c.initials}
                            </span>
                            <div class="min-w-0 flex-1">
                                <span class="flex items-center justify-between text-[11px] font-medium text-slate-800">
                                    <span class="truncate">${c.name}</span>
                                    <span class="text-[9px] text-slate-400 shrink-0 ml-1">${c.ago}</span>
                                </span>
                                <span class="flex items-center justify-between mt-0.5">
                                    <span class="block text-[10px] text-slate-500 truncate mr-2" style="${c.unread > 0 ? 'font-weight: 600; color: #1e293b;' : ''}">${c.last_msg || 'No messages'}</span>
                                    ${c.unread > 0 ? `<span class="inline-flex items-center justify-center min-w-[14px] h-[14px] px-1 rounded-full bg-indigo-500 text-white text-[8px] font-bold msg-unread-badge">${c.unread}</span>` : ''}
                                </span>
                            </div>
                        </button>
                    `).join('');
                    
                    // Add click listeners to recents
                    document.querySelectorAll('#' + prefix + 'RecentList .chat-sidebar-item').forEach(btn => {
                        btn.addEventListener('click', () => {
                            openThread(btn.dataset.type, btn.dataset.uid, btn.dataset.name, btn.dataset.sub, btn.dataset.initials);
                        });
                    });
                });
        }

        // Group clicks
        const groupBtns = document.querySelectorAll(chatModal ? '#' + chatModal.id + ' [data-type="group"]' : null);
        groupBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const groupName = btn.dataset.group;
                const title = groupName === 'staff' ? 'Staff & Admin' : 'Parents & Staff';
                const sub = groupName === 'staff' ? 'Group Chat' : 'School Group Chat';
                openThread('group', groupName, title, sub, '<i data-lucide="users" class="w-3.5 h-3.5"></i>');
            });
        });

        // Search users
        let searchTimeout;
        if (chatSearch) {
            chatSearch.addEventListener('input', e => {
                const q = e.target.value.trim();
                clearTimeout(searchTimeout);
                if (q.length < 2) {
                    searchResults.classList.add('hidden');
                    return;
                }
                searchTimeout = setTimeout(() => {
                    fetch('../api/user_search.php?q=' + encodeURIComponent(q))
                        .then(r => r.json())
                        .then(data => {
                            if (data.error || !data.users || data.users.length === 0) {
                                searchResults.innerHTML = '<div class="p-2 text-xs text-center text-slate-400">No users found</div>';
                            } else {
                                searchResults.innerHTML = data.users.map(u => `
                                    <button type="button" class="w-full flex items-center gap-2 px-3 py-2 hover:bg-slate-50 text-left border-b border-slate-50 last:border-0" data-uid="${u.id}" data-name="${u.full_name}" data-sub="${u.role}" data-initials="${u.initials}">
                                        <div class="min-w-0">
                                            <span class="block text-xs font-medium text-slate-800 truncate">${u.full_name}</span>
                                            <span class="block text-[10px] text-slate-400 capitalize">${u.role}</span>
                                        </div>
                                    </button>
                                `).join('');
                                
                                searchResults.querySelectorAll('button').forEach(btn => {
                                    btn.addEventListener('click', () => {
                                        searchResults.classList.add('hidden');
                                        chatSearch.value = '';
                                        openThread('user', btn.dataset.uid, btn.dataset.name, btn.dataset.sub, btn.dataset.initials);
                                    });
                                });
                            }
                            searchResults.classList.remove('hidden');
                        });
                }, 300);
            });
        }

        function openThread(type, idOrGroup, name, subText, avatarContent) {
            activeThreadType = type;
            activeThreadId = idOrGroup;
            
            threadName.textContent = name;
            threadSub.textContent = subText;
            threadAvatar.innerHTML = avatarContent;
            
            // Highlight active sidebar item
            document.querySelectorAll(chatModal ? '#' + chatModal.id + ' .chat-sidebar-item' : null).forEach(el => {
                if (el.dataset.type === type && (el.dataset.uid === idOrGroup || el.dataset.group === idOrGroup)) {
                    el.classList.add('bg-white', 'shadow-sm', 'border', 'border-slate-100');
                    // Remove unread badge visually if we just clicked it
                    if (type === 'user') {
                        const badge = el.querySelector('.msg-unread-badge');
                        if (badge) badge.remove();
                        const msgText = el.querySelector('.text-slate-500.truncate');
                        if (msgText) {
                            msgText.style.fontWeight = 'normal';
                            msgText.style.color = '#64748b';
                        }
                    }
                } else {
                    el.classList.remove('bg-white', 'shadow-sm', 'border', 'border-slate-100');
                }
            });

            loadThreadMessages();
            if (messagesPollInterval) clearInterval(messagesPollInterval);
            messagesPollInterval = setInterval(loadThreadMessages, 5000);
        }

        function loadThreadMessages() {
            if (!activeThreadType || !activeThreadId) return;
            
            let url = '';
            if (activeThreadType === 'user') {
                url = '../api/messaging.php?action=messages&with=' + activeThreadId;
            } else {
                url = '../api/messaging.php?action=group_messages&group=' + activeThreadId;
            }

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (data.error) return;
                    const msgs = data.messages || [];
                    
                    if (msgs.length === 0) {
                        threadMessages.innerHTML = `
                            <div class="flex items-center justify-center h-full">
                                <div class="text-center text-slate-400">
                                    <p class="text-[11px] mb-1">No messages yet</p>
                                    <p class="text-[9px]">Send a message to start the conversation.</p>
                                </div>
                            </div>
                        `;
                        return;
                    }

                    // Render bubbles
                    threadMessages.innerHTML = msgs.map(m => {
                        const isMine = m.is_mine;
                        if (isMine) {
                            return `
                                <div class="flex flex-col items-end gap-1 mb-3">
                                    <div class="flex items-center gap-1.5 px-1 mb-0.5">
                                        <span class="text-[8px] text-slate-400">${m.ago}</span>
                                        <span class="text-[10px] font-medium text-slate-700">You <span class="font-normal text-slate-500 capitalize">(${m.sender_role})</span></span>
                                    </div>
                                    <div class="max-w-[85%] bg-indigo-600 text-white rounded-2xl rounded-tr-sm px-3 py-2 text-[11px] shadow-sm whitespace-pre-wrap">${m.body}</div>
                                </div>
                            `;
                        } else {
                            return `
                                <div class="flex items-end gap-1.5 mb-3">
                                    <div class="w-6 h-6 rounded-full bg-${m.color}-100 text-${m.color}-700 flex items-center justify-center text-[8px] font-semibold shrink-0 mb-4">${m.initials}</div>
                                    <div class="flex flex-col items-start gap-1 max-w-[85%]">
                                        <div class="flex items-baseline gap-1.5 px-1 mb-0.5">
                                            <span class="text-[10px] font-medium text-slate-700">${m.sender_name} <span class="font-normal text-slate-500 capitalize">(${m.sender_role})</span></span>
                                            <span class="text-[8px] text-slate-400">${m.ago}</span>
                                        </div>
                                        <div class="bg-white border border-slate-200 text-slate-800 rounded-2xl rounded-tl-sm px-3 py-2 text-[11px] shadow-sm whitespace-pre-wrap">${m.body}</div>
                                    </div>
                                </div>
                            `;
                        }
                    }).join('');
                    
                    // Auto scroll to bottom
                    threadMessages.scrollTop = threadMessages.scrollHeight;
                });
        }

        if (chatSend) {
            chatSend.addEventListener('click', sendMessage);
        }
        
        if (chatInput) {
            chatInput.addEventListener('keydown', e => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }

        function sendMessage() {
            if (!activeThreadType || !activeThreadId) return;
            const body = chatInput.value.trim();
            if (!body) return;

            chatSend.disabled = true;
            chatInput.disabled = true;

            const payload = { type: activeThreadType, body: body };
            if (activeThreadType === 'user') {
                payload.to_user_id = activeThreadId;
            } else {
                payload.group = activeThreadId;
            }

            fetch('../api/messaging.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    chatInput.value = '';
                    loadThreadMessages();
                    loadConversations(); // update recents
                } else {
                    alert('Could not send: ' + (data.error || 'Unknown error'));
                }
            })
            .finally(() => {
                chatSend.disabled = false;
                chatInput.disabled = false;
                chatInput.focus();
            });
        }
        
        // Start conversation polling for global badge
        if (msgBadge) {
            loadConversations(); // Initial load to set badge before modal is ever opened
            if (convPollInterval) clearInterval(convPollInterval);
            convPollInterval = setInterval(loadConversations, 10000);
        }

        // Ensure Lucide icons run after loading dynamic content occasionally
        let lastObserverTime = 0;
        const observer = new MutationObserver(() => {
            const now = Date.now();
            if (now - lastObserverTime > 100) {
                lastObserverTime = now;
                if (window.lucide) window.lucide.createIcons();
            }
        });
        if (chatModal) {
            observer.observe(chatModal, { childList: true, subtree: true });
        }
        if (notifMenu) {
            observer.observe(notifMenu, { childList: true, subtree: true });
        }
    });
</script>

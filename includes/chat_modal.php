<?php
/**
 * includes/chat_modal.php
 *
 * Role-aware chat modal: sidebar (search + groups + recents) + thread view.
 * Included by admin/layout.php, teacher/layout.php, parent/layout.php.
 *
 * Expects PHP vars set before include:
 *   $chat_role  – 'admin' | 'teacher' | 'parent'
 *   $chat_prefix – 'admin' | 'teacher' | 'parent'  (used for element IDs)
 *
 * For parents: 'staff' group is hidden; only 'parents_staff' group shown.
 */

$_chat_is_parent  = ($chat_role ?? 'admin') === 'parent';
$_chat_prefix     = $chat_prefix ?? 'admin';
$_api_base        = isset($is_admin_dashboard) ? '../api' : '../api';
?>
<!-- ═══════════════════════════════════════════════════════════ -->
<!-- Chat Modal                                                   -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div id="<?= $_chat_prefix ?>ChatModal"
     data-role="<?= htmlspecialchars($chat_role ?? 'admin') ?>"
     data-prefix="<?= htmlspecialchars($_chat_prefix) ?>"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">

    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-3xl mx-4 flex overflow-hidden"
         style="height: min(88vh, 640px);">

        <!-- ── Left Sidebar ──────────────────────────────────────── -->
        <div class="w-64 shrink-0 border-r border-slate-100 flex flex-col bg-slate-50">

            <!-- Header -->
            <div class="flex items-center justify-between px-3 py-3 border-b border-slate-100 bg-white">
                <div class="flex items-center gap-1.5">
                    <i data-lucide="message-circle" class="w-4 h-4 text-indigo-600"></i>
                    <span class="text-sm font-semibold text-slate-800">Messages</span>
                </div>
                <button type="button" id="<?= $_chat_prefix ?>ChatClose"
                        class="w-6 h-6 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>

            <!-- Search box -->
            <div class="px-2 pt-2 pb-1">
                <div class="relative">
                    <i data-lucide="search" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3 h-3 text-slate-400"></i>
                    <input type="text"
                           id="<?= $_chat_prefix ?>ChatSearch"
                           placeholder="Search people…"
                           autocomplete="off"
                           class="w-full pl-7 pr-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-300">
                </div>
                <!-- Search results dropdown -->
                <div id="<?= $_chat_prefix ?>ChatSearchResults"
                     class="hidden mt-1 bg-white rounded-lg border border-slate-200 shadow-lg overflow-hidden max-h-48 overflow-y-auto z-10">
                </div>
            </div>

            <!-- Group chats -->
            <div class="px-2 pt-1 pb-0.5">
                <p class="text-[9px] uppercase tracking-widest text-slate-400 px-1 mb-1">Group Chats</p>

                <?php if (!$_chat_is_parent): ?>
                <!-- Staff & Admin (hidden from parents) -->
                <button type="button"
                        id="<?= $_chat_prefix ?>GroupStaff"
                        class="chat-sidebar-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-white text-left"
                        data-type="group" data-group="staff">
                    <span class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                        <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                    </span>
                    <div class="min-w-0">
                        <span class="block text-[11px] font-medium text-slate-800 truncate">Staff &amp; Admin</span>
                        <span class="block text-[10px] text-slate-400">All teachers &amp; admin</span>
                    </div>
                </button>
                <?php endif; ?>

                <!-- Parents, Staff & Admin (everyone) -->
                <button type="button"
                        id="<?= $_chat_prefix ?>GroupParentsStaff"
                        class="chat-sidebar-item w-full flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-white text-left"
                        data-type="group" data-group="parents_staff">
                    <span class="w-7 h-7 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                    </span>
                    <div class="min-w-0">
                        <span class="block text-[11px] font-medium text-slate-800 truncate">Parents &amp; Staff</span>
                        <span class="block text-[10px] text-slate-400">All parents, staff &amp; admin</span>
                    </div>
                </button>
            </div>

            <!-- Recent DMs -->
            <div class="flex-1 overflow-y-auto px-2 pb-2">
                <p class="text-[9px] uppercase tracking-widest text-slate-400 px-1 mt-2 mb-1">Recent</p>
                <div id="<?= $_chat_prefix ?>RecentList" class="space-y-0.5">
                    <p class="text-[10px] text-slate-400 px-2 py-2">Loading…</p>
                </div>
            </div>
        </div>

        <!-- ── Right: Thread View ─────────────────────────────────── -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- Thread header -->
            <div id="<?= $_chat_prefix ?>ThreadHeader"
                 class="px-4 py-3 border-b border-slate-100 flex items-center gap-2 bg-white">
                <div id="<?= $_chat_prefix ?>ThreadAvatar"
                     class="w-7 h-7 rounded-full bg-slate-200 flex items-center justify-center text-[10px] font-semibold text-slate-500 shrink-0">
                    ?
                </div>
                <div>
                    <p id="<?= $_chat_prefix ?>ThreadName" class="text-sm font-semibold text-slate-800">Select a conversation</p>
                    <p id="<?= $_chat_prefix ?>ThreadSub"  class="text-[10px] text-slate-400"></p>
                </div>
            </div>

            <!-- Messages area -->
            <div id="<?= $_chat_prefix ?>ThreadMessages"
                 class="flex-1 overflow-y-auto px-4 py-3 space-y-2 bg-slate-50/50">
                <div class="flex items-center justify-center h-full">
                    <div class="text-center text-slate-400">
                        <i data-lucide="message-square-dashed" class="w-10 h-10 mx-auto mb-2 opacity-30"></i>
                        <p class="text-xs">Select or search for someone to start chatting</p>
                    </div>
                </div>
            </div>

            <!-- Input bar -->
            <div class="px-3 py-2.5 border-t border-slate-100 bg-white flex items-end gap-2">
                <textarea id="<?= $_chat_prefix ?>ChatInput"
                          rows="1"
                          placeholder="Type a message…"
                          class="flex-1 resize-none text-xs rounded-xl border border-slate-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300 max-h-28 overflow-y-auto"
                          style="min-height:36px"></textarea>
                <button type="button"
                        id="<?= $_chat_prefix ?>ChatSend"
                        class="shrink-0 w-8 h-8 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center transition-colors disabled:opacity-40">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>
    </div>
</div>

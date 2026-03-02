<?php
$page_title = 'Profile';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-800">My profile</h2>
        <a href="edit_profile.php"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700">
            <i data-lucide="edit-3" class="w-3 h-3"></i>
            <span>Edit profile</span>
        </a>
    </div>
    <p class="text-[11px] text-slate-500">
        This is a placeholder profile view. Later, it will show your full parent details pulled from the database.
    </p>
</div>

<?php require __DIR__ . '/footer.php'; ?>


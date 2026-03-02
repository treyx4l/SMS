<?php
$page_title = 'Edit Profile';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-800">Edit profile</h2>
        <span class="text-[11px] text-slate-400">This form is a placeholder UI only</span>
    </div>
    <p class="text-[11px] text-slate-500">
        Here you will later be able to update your personal details such as name, email and contact information.
    </p>

    <form class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-3 text-[11px]">
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Full name</label>
            <input type="text" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Your full name" />
        </div>
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Email</label>
            <input type="email" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500" placeholder="you@example.com" />
        </div>
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Phone</label>
            <input type="text" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500" placeholder="+233..." />
        </div>
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Address</label>
            <input type="text" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Home address" />
        </div>
        <div class="md:col-span-2 flex items-center justify-end gap-2 pt-2">
            <a href="profile.php"
               class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-slate-300 hover:bg-slate-50">
                Cancel
            </a>
            <button type="button"
                    class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700">
                Save changes
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/footer.php'; ?>


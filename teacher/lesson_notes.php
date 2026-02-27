<?php
$page_title = 'Lesson Notes';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-800">Lesson notes</h2>
        <span class="text-[11px] text-slate-400">Plan your teaching</span>
    </div>
    <p class="text-[11px] text-slate-500">
        This page will store your lesson notes and schemes of work, grouped by term, week, class and subject.
    </p>
    <ul class="text-[11px] text-slate-500 list-disc list-inside space-y-1">
        <li>Structured editor for objectives, materials, introduction, activities and evaluation.</li>
        <li>Status tracking (draft, submitted for approval, approved).</li>
        <li>Optional sharing with heads of department or school admins.</li>
    </ul>
</div>

<?php require __DIR__ . '/footer.php'; ?>


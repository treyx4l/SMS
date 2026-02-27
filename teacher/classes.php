<?php
$page_title = 'Classes';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-xl border border-slate-200 p-5">
    <div class="flex items-center justify-between mb-2">
        <h2 class="text-sm font-semibold text-slate-800">Classes assigned to you</h2>
        <span class="text-[11px] text-slate-400">Teacher view</span>
    </div>
    <p class="text-[11px] text-slate-500 mb-3">
        This page will list all classes and streams you are responsible for (e.g. JSS1A, JSS2B), including:
    </p>
    <ul class="text-[11px] text-slate-500 list-disc list-inside space-y-1">
        <li>Basic details: class name, stream, form teacher (if applicable).</li>
        <li>Quick links into attendance, grading and lesson notes for each class.</li>
        <li>Student counts per class and summary of recent activity.</li>
    </ul>
</div>

<?php require __DIR__ . '/footer.php'; ?>


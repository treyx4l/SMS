<?php
$page_title = 'Grades';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-800">Grading</h2>
        <span class="text-[11px] text-slate-400">Continuous assessment &amp; exams</span>
    </div>
    <p class="text-[11px] text-slate-500">
        This page will handle score entry for assignments, tests and exams, per subject and class that you teach.
    </p>
    <ul class="text-[11px] text-slate-500 list-disc list-inside space-y-1">
        <li>Grade books per class &amp; subject with configurable assessment components.</li>
        <li>Automatic computation of totals, averages and positions (per class).</li>
        <li>Export‑ready data for report cards and analytics.</li>
    </ul>
</div>

<?php require __DIR__ . '/footer.php'; ?>


<?php
$page_title = 'Reports';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-800">Reports</h2>
        <span class="text-[11px] text-slate-400">Printable and exportable summaries</span>
    </div>
    <p class="text-[11px] text-slate-500">
        This page will list structured reports you can download or print for your classes and subjects.
    </p>
    <ul class="text-[11px] text-slate-500 list-disc list-inside space-y-1">
        <li>Attendance reports per class, subject, or date range.</li>
        <li>Grade sheets and result summaries for each assessment.</li>
        <li>End-of-term summaries you can share with administrators or parents.</li>
    </ul>
</div>

<?php require __DIR__ . '/footer.php'; ?>


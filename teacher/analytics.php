<?php
$page_title = 'Analytics';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-800">Analytics</h2>
        <span class="text-[11px] text-slate-400">Insights for your classes</span>
    </div>
    <p class="text-[11px] text-slate-500">
        This page will visualize trends for the classes and subjects under your care, combining attendance and grades.
    </p>
    <ul class="text-[11px] text-slate-500 list-disc list-inside space-y-1">
        <li>Attendance trends over time (per class, per subject, per student).</li>
        <li>Performance distributions and averages across assessments.</li>
        <li>Flags for at‑risk students based on low attendance or grades.</li>
    </ul>
</div>

<?php require __DIR__ . '/footer.php'; ?>


<?php
$page_title = 'Subjects';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-800">Subjects you teach</h2>
        <span class="text-[11px] text-slate-400">Per class &amp; stream</span>
    </div>
    <p class="text-[11px] text-slate-500">
        This page will list all subjects assigned to you across different classes and streams.
    </p>
    <ul class="text-[11px] text-slate-500 list-disc list-inside space-y-1">
        <li>Class‑subject combinations (e.g. JSS1A &mdash; Mathematics).</li>
        <li>Quick navigation into lesson notes, grading and attendance for each pairing.</li>
        <li>Indicators for exam classes or core subjects.</li>
    </ul>
</div>

<?php require __DIR__ . '/footer.php'; ?>


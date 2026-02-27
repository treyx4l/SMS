<?php
$page_title = 'Attendance';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-800">Student attendance</h2>
        <span class="text-[11px] text-slate-400">Mark &amp; review</span>
    </div>
    <p class="text-[11px] text-slate-500">
        This page will let you pick a class, date and subject, then quickly mark each student as present, late or absent.
    </p>
    <ul class="text-[11px] text-slate-500 list-disc list-inside space-y-1">
        <li>Daily attendance grid per class with keyboard-friendly input.</li>
        <li>Summary stats (e.g. number present, absent, late).</li>
        <li>Shortcuts to view attendance history for a single student or class.</li>
    </ul>
</div>

<?php require __DIR__ . '/footer.php'; ?>


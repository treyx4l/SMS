<?php
$page_title = 'Timetable';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-800">View timetable</h2>
        <span class="text-[11px] text-slate-400">Periods for your classes</span>
    </div>
    <p class="text-[11px] text-slate-500">
        This page will show a teacher‑centric timetable filtered automatically to only the classes and subjects you handle.
    </p>
    <ul class="text-[11px] text-slate-500 list-disc list-inside space-y-1">
        <li>Day‑by‑day grid with periods, rooms and subject codes.</li>
        <li>Highlight current and upcoming periods.</li>
        <li>Printable and mobile‑friendly views.</li>
    </ul>
</div>

<?php require __DIR__ . '/footer.php'; ?>


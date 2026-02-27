<?php
$page_title = 'Attendance';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="flex items-center justify-between mb-2">
        <h2 class="text-sm font-semibold">Attendance overview</h2>
        <span class="text-xs text-slate-500">Summary</span>
    </div>
    <p class="text-xs text-slate-600">
        This page will show daily and term-wise attendance per class and student. You can extend it to filter by date,
        class, and status, and to export attendance reports.
    </p>
</div>

<?php require __DIR__ . '/footer.php'; ?>


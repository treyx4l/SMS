<?php
$page_title = 'Lesson Plans';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="flex items-center justify-between mb-2">
        <h2 class="text-sm font-semibold">Teachers' lesson plans</h2>
        <span class="text-xs text-slate-500">Detailed</span>
    </div>
    <p class="text-xs text-slate-600">
        This page will hold teacher-submitted lesson plans grouped by class, subject, and week. You can extend it to
        allow uploads, approvals and comments by the admin or head teacher.
    </p>
</div>

<?php require __DIR__ . '/footer.php'; ?>


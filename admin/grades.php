<?php
$page_title = 'Grades';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="flex items-center justify-between mb-2">
        <h2 class="text-sm font-semibold">Grades overview</h2>
        <span class="text-xs text-slate-500">Summary</span>
    </div>
    <p class="text-xs text-slate-600">
        This page will display exam and continuous assessment results per subject and class. You can later connect it
        to your grades table to generate terminal exam reports and analytics.
    </p>
</div>

<?php require __DIR__ . '/footer.php'; ?>


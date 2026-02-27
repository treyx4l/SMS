<?php
$page_title = 'School Profile';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="flex items-center justify-between mb-2">
        <h2 class="text-sm font-semibold">School profile</h2>
        <span class="text-xs text-slate-500">Summary</span>
    </div>
    <p class="text-xs text-slate-600">
        Use this page later to display read-only information about the school (name, address, contact details, logo)
        as configured under Settings.
    </p>
</div>

<?php require __DIR__ . '/footer.php'; ?>


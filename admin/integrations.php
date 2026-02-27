<?php
$page_title = 'Integrations';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="flex items-center justify-between mb-2">
        <h2 class="text-sm font-semibold">Integrations</h2>
        <span class="text-xs text-slate-500">Detailed</span>
    </div>
    <p class="text-xs text-slate-600">
        This page will list third-party integrations for Axis SMS, such as SMS gateways, payment providers or external
        reporting tools, configured per school.
    </p>
</div>

<?php require __DIR__ . '/footer.php'; ?>


<?php
$page_title = 'Fees';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/common.php';
?>

<?php if (!$parent): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <h2 class="text-sm font-semibold text-amber-800 mb-1">Parent profile not linked</h2>
    <p class="text-sm text-amber-700">
        Your login is not yet linked to a parent record in Axis SMS.
        Please contact the school administrator to complete your profile.
    </p>
</div>
<?php else: ?>

<div class="space-y-4">
    <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Fees &amp; payments</h2>
                <p class="text-[11px] text-slate-500">
                    Fees, invoices, and payment status for your wards.
                </p>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 text-xs text-blue-800">
            The detailed fees and payments module is not yet connected in this version of Axis SMS.
            Once your school enables it, you will see term invoices, amounts paid, and any outstanding balances here
            for each ward.
        </div>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>
if (window.lucide) {
    lucide.createIcons();
}
</script>


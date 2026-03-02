<?php
$page_title = 'Wards';
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
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-800">Your wards</h2>
            <p class="text-[11px] text-slate-500">
                Detailed information for each ward linked to your account.
            </p>
        </div>

        <?php if (!$wards): ?>
            <p class="text-sm text-slate-500">
                No wards are currently linked to your account. Please contact the school to update your ward assignments.
            </p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($wards as $w): ?>
                    <div class="border border-slate-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">
                                    <?= htmlspecialchars(trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? ''))) ?>
                                </p>
                                <p class="text-[11px] text-slate-400">
                                    ID: <?= htmlspecialchars($w['index_no'] ?? (string) $w['id']) ?>
                                </p>
                            </div>
                        </div>
                        <dl class="text-[11px] text-slate-600 space-y-1.5">
                            <div class="flex justify-between">
                                <dt class="text-slate-400">Class</dt>
                                <dd class="font-medium text-slate-700">
                                    <?= htmlspecialchars(trim(($w['class_name'] ?? 'Unassigned') . (isset($w['class_section']) && $w['class_section'] ? ' ' . $w['class_section'] : ''))) ?>
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-400">Gender</dt>
                                <dd class="font-medium text-slate-700">
                                    <?= htmlspecialchars(ucfirst($w['gender'] ?? '—')) ?>
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-400">Date of birth</dt>
                                <dd class="font-medium text-slate-700">
                                    <?= !empty($w['date_of_birth']) ? date('d M Y', strtotime($w['date_of_birth'])) : '—' ?>
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-400">Phone</dt>
                                <dd class="font-medium text-slate-700">
                                    <?= htmlspecialchars($w['phone'] ?? '—') ?>
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-400">Address</dt>
                                <dd class="font-medium text-slate-700 text-right max-w-[70%] break-words">
                                    <?= htmlspecialchars($w['address'] ?? '—') ?>
                                </dd>
                            </div>
                        </dl>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>
if (window.lucide) {
    lucide.createIcons();
}
</script>


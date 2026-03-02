<?php
$page_title = 'Reports';
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
    <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Reports</h2>
                <p class="text-[11px] text-slate-500">
                    High-level reports summarising your wards' attendance and grades.
                </p>
            </div>
        </div>

        <?php if (!$wards): ?>
            <p class="text-sm text-slate-500">
                No wards are linked to your account, so reports cannot be generated.
            </p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="border border-slate-200 rounded-lg p-4">
                    <p class="text-[11px] text-slate-500 mb-1">Total wards</p>
                    <p class="text-3xl font-bold text-slate-800"><?= $totalWards ?></p>
                </div>
                <div class="border border-slate-200 rounded-lg p-4">
                    <p class="text-[11px] text-slate-500 mb-1">Attendance records (30 days)</p>
                    <p class="text-3xl font-bold text-slate-800"><?= $overallAttendance['total'] ?></p>
                </div>
                <div class="border border-slate-200 rounded-lg p-4">
                    <p class="text-[11px] text-slate-500 mb-1">Wards with grade data</p>
                    <p class="text-3xl font-bold text-slate-800"><?= count($averageGradeByStudent) ?></p>
                </div>
            </div>

            <div class="border border-slate-200 rounded-lg p-4">
                <p class="text-[11px] text-slate-500 mb-2">
                    Simple ward report card overview.
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs text-left text-slate-700">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Ward</th>
                                <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">Class</th>
                                <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">Attendance records</th>
                                <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">Avg grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wards as $w):
                                $sid = (int) $w['id'];
                                $att = $attendanceSummary[$sid] ?? ['total' => 0];
                                $avg = $averageGradeByStudent[$sid] ?? null;
                            ?>
                            <tr class="border-b border-slate-100">
                                <td class="px-4 py-2">
                                    <?= htmlspecialchars(trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? ''))) ?>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <?= htmlspecialchars(trim(($w['class_name'] ?? 'Unassigned') . (isset($w['class_section']) && $w['class_section'] ? ' ' . $w['class_section'] : ''))) ?>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <?= (int) $att['total'] ?>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <?php if ($avg === null): ?>
                                        <span class="text-slate-400">—</span>
                                    <?php else: ?>
                                        <span class="font-semibold <?= $avg >= 70 ? 'text-emerald-600' : ($avg >= 50 ? 'text-amber-600' : 'text-rose-600') ?>">
                                            <?= number_format($avg, 1) ?>%
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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


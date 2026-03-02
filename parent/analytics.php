<?php
$page_title = 'Analytics';
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
                <h2 class="text-sm font-semibold text-slate-800">Analytics</h2>
                <p class="text-[11px] text-slate-500">
                    Simple analytics to help you keep track of how your wards are doing.
                </p>
            </div>
        </div>

        <?php if (!$wards): ?>
            <p class="text-sm text-slate-500">
                No wards are linked to your account yet.
            </p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border border-slate-200 rounded-lg p-4">
                    <p class="text-[11px] text-slate-500 mb-2">Attendance health</p>
                    <?php if ($overallAttendance['total'] === 0): ?>
                        <p class="text-[11px] text-slate-400">
                            Attendance records are not available yet.
                        </p>
                    <?php else: ?>
                        <?php
                        $presentRate = $overallAttendance['present'] / max(1, $overallAttendance['total']) * 100.0;
                        ?>
                        <p class="text-sm font-semibold text-slate-800 mb-1">
                            <?= number_format($presentRate, 1) ?>% days present
                        </p>
                        <p class="text-[11px] text-slate-500 mb-2">
                            This is across all wards over the last 30 days.
                        </p>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full <?= $presentRate >= 90 ? 'bg-emerald-500' : ($presentRate >= 75 ? 'bg-amber-400' : 'bg-rose-500') ?>"
                                 style="width: <?= max(1, (int) round($presentRate)) ?>%;"></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="border border-slate-200 rounded-lg p-4">
                    <p class="text-[11px] text-slate-500 mb-2">Academic snapshot</p>
                    <?php if (!$averageGradeByStudent): ?>
                        <p class="text-[11px] text-slate-400">
                            Once teachers start entering grades, you will see performance bands for each ward here.
                        </p>
                    <?php else: ?>
                        <ul class="space-y-1.5 text-[11px]">
                            <?php foreach ($wards as $w):
                                $sid = (int) $w['id'];
                                if (!isset($averageGradeByStudent[$sid])) continue;
                                $avg = $averageGradeByStudent[$sid];
                            ?>
                            <li class="flex items-center justify-between">
                                <span class="truncate pr-2">
                                    <?= htmlspecialchars(trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? ''))) ?>
                                </span>
                                <span class="inline-flex items-center gap-2">
                                    <span class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <span class="block h-full <?= $avg >= 70 ? 'bg-emerald-500' : ($avg >= 50 ? 'bg-amber-400' : 'bg-rose-500') ?>"
                                              style="width: <?= max(1, (int) round($avg)) ?>%;"></span>
                                    </span>
                                    <span class="font-semibold <?= $avg >= 70 ? 'text-emerald-600' : ($avg >= 50 ? 'text-amber-600' : 'text-rose-600') ?>">
                                        <?= number_format($avg, 1) ?>%
                                    </span>
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <p class="text-[11px] text-slate-400">
                These analytics are simple summaries to give you a quick overview.
                For detailed term reports, please request official report cards from the school.
            </p>
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


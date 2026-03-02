<?php
$page_title = 'Attendance';
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
                <h2 class="text-sm font-semibold text-slate-800">Attendance (last 30 days)</h2>
                <p class="text-[11px] text-slate-500">
                    Attendance over the last 30 days for each ward.
                </p>
            </div>
        </div>

        <?php if (!$hasAttendanceTable): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-xs text-amber-800">
                Attendance tracking is not yet enabled for this school in Axis SMS.
            </div>
        <?php elseif (!$wards): ?>
            <p class="text-sm text-slate-500">
                No wards are linked to your account, so attendance cannot be shown.
            </p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($wards as $w):
                    $sid = (int) $w['id'];
                    $att = $attendanceSummary[$sid] ?? ['present' => 0, 'late' => 0, 'absent' => 0, 'total' => 0];
                ?>
                <div class="border border-slate-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">
                                <?= htmlspecialchars(trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? ''))) ?>
                            </p>
                            <p class="text-[11px] text-slate-400">
                                <?= htmlspecialchars(trim(($w['class_name'] ?? 'Unassigned') . (isset($w['class_section']) && $w['class_section'] ? ' ' . $w['class_section'] : ''))) ?>
                            </p>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            <?= $att['total'] ?> day<?= $att['total'] === 1 ? '' : 's' ?>
                        </p>
                    </div>
                    <?php if ($att['total'] === 0): ?>
                        <p class="text-[11px] text-slate-400">
                            No attendance records yet for this ward.
                        </p>
                    <?php else: ?>
                        <dl class="space-y-1.5 text-[11px]">
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-500">Present</dt>
                                <dd class="flex items-center gap-2">
                                    <div class="w-24 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full bg-emerald-500"
                                             style="width: <?= max(1, (int) round($att['present'] / max(1, $att['total']) * 100)) ?>%;"></div>
                                    </div>
                                    <span class="font-semibold text-emerald-600">
                                        <?= $att['present'] ?>
                                    </span>
                                </dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-500">Late</dt>
                                <dd class="flex items-center gap-2">
                                    <div class="w-24 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full bg-amber-400"
                                             style="width: <?= max(1, (int) round($att['late'] / max(1, $att['total']) * 100)) ?>%;"></div>
                                    </div>
                                    <span class="font-semibold text-amber-600">
                                        <?= $att['late'] ?>
                                    </span>
                                </dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-500">Absent</dt>
                                <dd class="flex items-center gap-2">
                                    <div class="w-24 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full bg-rose-500"
                                             style="width: <?= max(1, (int) round($att['absent'] / max(1, $att['total']) * 100)) ?>%;"></div>
                                    </div>
                                    <span class="font-semibold text-rose-600">
                                        <?= $att['absent'] ?>
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    <?php endif; ?>
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


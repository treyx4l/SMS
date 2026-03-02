<?php
$page_title = 'Grades';
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
                <h2 class="text-sm font-semibold text-slate-800">Recent grades</h2>
                <p class="text-[11px] text-slate-500">
                    Recent grades recorded by teachers for your wards.
                </p>
            </div>
        </div>

        <?php if (!$hasGradesTables): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-xs text-amber-800">
                The grades module has not been enabled for this school yet.
            </div>
        <?php elseif (!$wards): ?>
            <p class="text-sm text-slate-500">
                No wards are linked to your account, so grades cannot be shown.
            </p>
        <?php else: ?>
            <?php
            $hasAnyGrades = false;
            foreach ($gradesByStudent as $rows) {
                if (!empty($rows)) { $hasAnyGrades = true; break; }
            }
            ?>
            <?php if (!$hasAnyGrades): ?>
                <p class="text-sm text-slate-500">
                    No grades have been recorded yet for your wards.
                </p>
            <?php else: ?>
                <div class="overflow-x-auto border border-slate-200 rounded-lg">
                    <table class="min-w-full text-xs text-left text-slate-700">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Ward</th>
                                <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Subject</th>
                                <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Exam</th>
                                <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">Score</th>
                                <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Class</th>
                                <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wards as $w):
                                $sid = (int) $w['id'];
                                $rows = $gradesByStudent[$sid] ?? [];
                                foreach ($rows as $row):
                                    $percent = ($row['max_score'] ?? 0) > 0
                                        ? (float) $row['score'] / (float) $row['max_score'] * 100.0
                                        : null;
                            ?>
                            <tr class="border-b border-slate-100">
                                <td class="px-4 py-2">
                                    <?= htmlspecialchars(trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? ''))) ?>
                                </td>
                                <td class="px-4 py-2">
                                    <?= htmlspecialchars($row['subject_name'] ?? '—') ?>
                                </td>
                                <td class="px-4 py-2">
                                    <?= htmlspecialchars($row['exam_name'] ?? '—') ?>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <?php if ($percent === null): ?>
                                        <span class="text-slate-400">—</span>
                                    <?php else: ?>
                                        <span class="font-semibold <?= $percent >= 70 ? 'text-emerald-600' : ($percent >= 50 ? 'text-amber-600' : 'text-rose-600') ?>">
                                            <?= number_format($percent, 1) ?>%
                                        </span>
                                        <span class="text-[10px] text-slate-400">
                                            (<?= htmlspecialchars((string) $row['score']) ?>/<?= htmlspecialchars((string) $row['max_score']) ?>)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-2">
                                    <?= htmlspecialchars(trim(($row['class_name'] ?? 'Unassigned') . (isset($row['class_section']) && $row['class_section'] ? ' ' . $row['class_section'] : ''))) ?>
                                </td>
                                <td class="px-4 py-2 text-slate-500">
                                    <?= !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '—' ?>
                                </td>
                            </tr>
                            <?php endforeach; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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


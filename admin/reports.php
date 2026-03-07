<?php
$page_title = 'Reports';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

// Detect graduation column
$hasGraduatedCol = false;
$res = $conn->query("SHOW COLUMNS FROM students LIKE 'is_graduated'");
if ($res && $res->num_rows > 0) {
    $hasGraduatedCol = true;
}

// Counts per report category
$counts = [];
foreach (['students','teachers','parents','classes'] as $tbl) {
    if ($tbl === 'students' && $hasGraduatedCol) {
        $s = $conn->prepare("SELECT COUNT(*) AS c FROM students WHERE school_id = ? AND (is_graduated IS NULL OR is_graduated = 0)");
    } else {
        $s = $conn->prepare("SELECT COUNT(*) AS c FROM {$tbl} WHERE school_id = ?");
    }
    $s->bind_param('i', $schoolId);
    $s->execute();
    $counts[$tbl] = (int)($s->get_result()->fetch_assoc()['c'] ?? 0);
    $s->close();
}

// Students per class (active only if graduation enabled)
$classBreakdown = [];
$sql = "
    SELECT c.name AS class_name, COUNT(st.id) AS student_count
    FROM classes c
    LEFT JOIN students st ON st.class_id = c.id AND st.school_id = c.school_id";
if ($hasGraduatedCol) {
    $sql .= " AND (st.is_graduated IS NULL OR st.is_graduated = 0)";
}
$sql .= "
    WHERE c.school_id = ?
    GROUP BY c.id, c.name
    ORDER BY student_count DESC
    LIMIT 10
";
$s = $conn->prepare($sql);
$s->bind_param('i', $schoolId);
$s->execute();
$res = $s->get_result();
while ($row = $res->fetch_assoc()) $classBreakdown[] = $row;
$s->close();

// Teachers list summary
$teachers = [];
$s = $conn->prepare("SELECT full_name, email, phone, created_at FROM teachers WHERE school_id = ? ORDER BY full_name ASC LIMIT 20");
$s->bind_param('i', $schoolId);
$s->execute();
$res = $s->get_result();
while ($row = $res->fetch_assoc()) $teachers[] = $row;
$s->close();
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Summary KPIs -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ([
        ['Students', $counts['students'], 'graduation-cap', 'text-indigo-600', 'bg-indigo-50', 'border-indigo-100'],
        ['Teachers', $counts['teachers'], 'user-check',     'text-green-600',  'bg-green-50',  'border-green-100'],
        ['Parents',  $counts['parents'],  'users',          'text-orange-500', 'bg-orange-50', 'border-orange-100'],
        ['Classes',  $counts['classes'],  'book-open',      'text-purple-600', 'bg-purple-50', 'border-purple-100'],
    ] as [$lbl,$val,$ico,$col,$bg,$brd]): ?>
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-slate-500"><?= $lbl ?></span>
            <div class="w-8 h-8 rounded-lg <?= $bg ?> border <?= $brd ?> flex items-center justify-center">
                <i data-lucide="<?= $ico ?>" class="w-4 h-4 <?= $col ?>"></i>
            </div>
        </div>
        <div class="text-3xl font-bold <?= $col ?>"><?= $val ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Report sections -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    <!-- Students per class bar chart -->
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Students per Class</h2>
                <p class="text-xs text-slate-400 mt-0.5">Top 10 classes by enrolment</p>
            </div>
            <i data-lucide="bar-chart-2" class="w-4 h-4 text-indigo-500"></i>
        </div>
        <?php if ($classBreakdown): ?>
        <div class="relative w-full overflow-hidden" style="height:220px; min-height:220px; max-height:220px;">
            <canvas id="classChart" class="block w-full" style="height:220px !important; max-height:220px;"></canvas>
        </div>
        <?php else: ?>
        <div class="flex flex-col items-center justify-center h-40 text-slate-400">
            <i data-lucide="inbox" class="w-8 h-8 mb-2"></i>
            <p class="text-sm">No class data yet</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Staff summary table -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <i data-lucide="user-check" class="w-4 h-4 text-green-500"></i>
                <span class="text-sm font-semibold text-slate-800">Teacher Summary</span>
            </div>
            <a href="teachers.php" class="text-xs font-medium text-indigo-600 hover:underline">Manage</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50">
                        <th class="text-left px-5 py-2.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Name</th>
                        <th class="text-left px-4 py-2.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Email</th>
                        <th class="text-left px-4 py-2.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Joined</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$teachers): ?>
                    <tr><td colspan="3" class="px-5 py-4 text-xs text-slate-400">No teachers added yet.</td></tr>
                    <?php else: foreach ($teachers as $t): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-6 h-6 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-xs font-semibold">
                                    <?= strtoupper(substr($t['full_name'],0,1)) ?>
                                </div>
                                <span class="text-slate-700 font-medium"><?= htmlspecialchars($t['full_name']) ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs"><?= htmlspecialchars($t['email'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-slate-400 text-xs"><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Export notice -->
<div class="bg-white border border-slate-200 rounded-xl p-5">
    <div class="flex items-start gap-3">
        <div class="w-9 h-9 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center shrink-0">
            <i data-lucide="download" class="w-4 h-4 text-indigo-600"></i>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-slate-800 mb-1">Export Reports</h3>
            <p class="text-xs text-slate-400 mb-3">Download detailed reports for attendance, fees, grade distributions, and more.</p>
            <div class="flex flex-wrap gap-2">
                <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-600 hover:border-indigo-300 hover:text-indigo-600 hover:bg-indigo-50 transition-colors cursor-not-allowed opacity-60">
                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Attendance Report
                </button>
                <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-600 hover:border-indigo-300 hover:text-indigo-600 hover:bg-indigo-50 transition-colors cursor-not-allowed opacity-60">
                    <i data-lucide="clipboard-list" class="w-3.5 h-3.5"></i> Grade Report
                </button>
                <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-600 hover:border-indigo-300 hover:text-indigo-600 hover:bg-indigo-50 transition-colors cursor-not-allowed opacity-60">
                    <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i> Fee Status
                </button>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-yellow-50 border border-yellow-200 text-[11px] text-yellow-600">
                    <i data-lucide="clock" class="w-3 h-3"></i> Coming soon
                </span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>
<?php if ($classBreakdown): ?>
<script>
const ctx = document.getElementById('classChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($classBreakdown, 'class_name')) ?>,
        datasets: [{
            label: 'Students',
            data: <?= json_encode(array_map('intval', array_column($classBreakdown, 'student_count'))) ?>,
            backgroundColor: 'rgba(99,102,241,0.15)',
            borderColor: '#6366f1',
            borderWidth: 1.5,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 0, right: 0, bottom: 0, left: 0 } },
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0, font:{size:11}, color:'#94a3b8' }, grid:{color:'#f1f5f9'} },
            x: { ticks: { font:{size:11}, color:'#94a3b8', maxRotation: 45 }, grid:{display:false} }
        }
    }
});
</script>
<?php endif; ?>

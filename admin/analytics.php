<?php
$page_title = 'Analytics';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

// Monthly data for last 12 months
$months = [];
$studentsByMonth = [];
$teachersByMonth = [];
for ($i = 11; $i >= 0; $i--) {
    $label = date('M', strtotime("-{$i} months"));
    $y     = date('Y', strtotime("-{$i} months"));
    $m     = date('m', strtotime("-{$i} months"));
    $months[] = $label;

    $s = $conn->prepare("SELECT COUNT(*) AS c FROM students WHERE school_id=? AND YEAR(created_at)=? AND MONTH(created_at)=?");
    $s->bind_param('iii',$schoolId,$y,$m); $s->execute();
    $studentsByMonth[] = (int)($s->get_result()->fetch_assoc()['c'] ?? 0); $s->close();

    $s = $conn->prepare("SELECT COUNT(*) AS c FROM teachers WHERE school_id=? AND YEAR(created_at)=? AND MONTH(created_at)=?");
    $s->bind_param('iii',$schoolId,$y,$m); $s->execute();
    $teachersByMonth[] = (int)($s->get_result()->fetch_assoc()['c'] ?? 0); $s->close();
}

// Class sizes
$classData = [];
$s = $conn->prepare("SELECT c.name, COUNT(st.id) AS cnt FROM classes c LEFT JOIN students st ON st.class_id=c.id AND st.school_id=c.school_id WHERE c.school_id=? GROUP BY c.id,c.name ORDER BY cnt DESC LIMIT 8");
$s->bind_param('i',$schoolId); $s->execute();
$res = $s->get_result();
while ($row = $res->fetch_assoc()) $classData[] = $row;
$s->close();

// Total counts
$totals = [];
foreach (['students','teachers','parents','classes'] as $tbl) {
    $s = $conn->prepare("SELECT COUNT(*) AS c FROM {$tbl} WHERE school_id=?");
    $s->bind_param('i',$schoolId); $s->execute();
    $totals[$tbl] = (int)($s->get_result()->fetch_assoc()['c'] ?? 0); $s->close();
}

// Students this month vs last month
$thisMonth = (int)end($studentsByMonth);
$lastMonth = (int)($studentsByMonth[count($studentsByMonth)-2] ?? 0);
$growth = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : ($thisMonth > 0 ? 100 : 0);
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- KPI metric strip -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <p class="text-xs font-medium text-slate-500 mb-2">Total Students</p>
        <p class="text-3xl font-bold text-indigo-600"><?= $totals['students'] ?></p>
        <span class="inline-flex items-center gap-1 mt-2 text-[11px] px-2 py-0.5 rounded-full <?= $growth >= 0 ? 'bg-green-50 text-green-600 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200' ?>">
            <i data-lucide="<?= $growth >= 0 ? 'trending-up' : 'trending-down' ?>" class="w-3 h-3"></i>
            <?= $growth >= 0 ? '+' : '' ?><?= $growth ?>% this month
        </span>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <p class="text-xs font-medium text-slate-500 mb-2">Total Teachers</p>
        <p class="text-3xl font-bold text-green-600"><?= $totals['teachers'] ?></p>
        <p class="text-[11px] text-slate-400 mt-2">Active staff members</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <p class="text-xs font-medium text-slate-500 mb-2">Student-Teacher Ratio</p>
        <p class="text-3xl font-bold text-orange-500">
            <?= $totals['teachers'] > 0 ? round($totals['students'] / $totals['teachers'], 1) : '—' ?>:1
        </p>
        <p class="text-[11px] text-slate-400 mt-2">Students per teacher</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <p class="text-xs font-medium text-slate-500 mb-2">Classes</p>
        <p class="text-3xl font-bold text-purple-600"><?= $totals['classes'] ?></p>
        <p class="text-[11px] text-slate-400 mt-2">
            Avg <?= $totals['classes'] > 0 ? round($totals['students'] / $totals['classes'], 1) : '0' ?> students/class
        </p>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    <!-- 12-month trend -->
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Enrolment Trend (12 Months)</h2>
                <p class="text-xs text-slate-400 mt-0.5">Students &amp; teachers added per month</p>
            </div>
            <i data-lucide="activity" class="w-4 h-4 text-indigo-500"></i>
        </div>
        <canvas id="trendChart" style="height:220px;"></canvas>
    </div>

    <!-- Class size distribution -->
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Class Size Distribution</h2>
                <p class="text-xs text-slate-400 mt-0.5">Students per class (top 8)</p>
            </div>
            <i data-lucide="bar-chart" class="w-4 h-4 text-indigo-500"></i>
        </div>
        <?php if ($classData): ?>
        <canvas id="classSizeChart" style="height:220px;"></canvas>
        <?php else: ?>
        <div class="flex flex-col items-center justify-center h-40 text-slate-300">
            <i data-lucide="inbox" class="w-10 h-10 mb-2"></i>
            <p class="text-sm text-slate-400">No class data yet</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Insight cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center">
                <i data-lucide="users" class="w-4 h-4 text-indigo-600"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Community Size</h3>
        </div>
        <p class="text-xs text-slate-500 leading-relaxed">
            Your school community has <strong class="text-slate-700"><?= $totals['students'] + $totals['teachers'] + $totals['parents'] ?></strong> total members across students, teachers, and parents.
        </p>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 rounded-lg bg-green-50 border border-green-100 flex items-center justify-center">
                <i data-lucide="trending-up" class="w-4 h-4 text-green-600"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Growth</h3>
        </div>
        <p class="text-xs text-slate-500 leading-relaxed">
            <?= $thisMonth ?> new student<?= $thisMonth !== 1 ? 's' : '' ?> registered this month.
            <?= $growth > 0 ? "That's a <strong class='text-green-600'>{$growth}%</strong> increase." : ($growth < 0 ? "That's a <strong class='text-red-500'>".abs($growth)."%</strong> decrease." : "Steady from last month.") ?>
        </p>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 rounded-lg bg-orange-50 border border-orange-100 flex items-center justify-center">
                <i data-lucide="book-open" class="w-4 h-4 text-orange-600"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Class Load</h3>
        </div>
        <p class="text-xs text-slate-500 leading-relaxed">
            <?= $totals['classes'] ?> active class<?= $totals['classes'] !== 1 ? 'es' : '' ?> with an average of
            <strong class="text-slate-700"><?= $totals['classes'] > 0 ? round($totals['students']/$totals['classes'],1) : 0 ?></strong> students each.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>
<script>
// 12-month trend line chart
new Chart(document.getElementById('trendChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
            {
                label: 'Students',
                data: <?= json_encode($studentsByMonth) ?>,
                borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.07)',
                borderWidth: 2, pointRadius: 3, fill: true, tension: 0.4
            },
            {
                label: 'Teachers',
                data: <?= json_encode($teachersByMonth) ?>,
                borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.07)',
                borderWidth: 2, pointRadius: 3, fill: true, tension: 0.4
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { labels: { font:{size:11}, color:'#64748b', boxWidth:10 } } },
        scales: {
            y: { beginAtZero:true, ticks:{precision:0,font:{size:11},color:'#94a3b8'}, grid:{color:'#f1f5f9'} },
            x: { ticks:{font:{size:11},color:'#94a3b8'}, grid:{display:false} }
        }
    }
});

<?php if ($classData): ?>
// Class size horizontal bar
new Chart(document.getElementById('classSizeChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($classData,'name')) ?>,
        datasets: [{
            label: 'Students',
            data: <?= json_encode(array_column($classData,'cnt')) ?>,
            backgroundColor: 'rgba(99,102,241,0.15)',
            borderColor: '#6366f1', borderWidth: 1.5, borderRadius: 6
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: { legend:{display:false} },
        scales: {
            x: { beginAtZero:true, ticks:{precision:0,font:{size:11},color:'#94a3b8'}, grid:{color:'#f1f5f9'} },
            y: { ticks:{font:{size:11},color:'#64748b'}, grid:{display:false} }
        }
    }
});
<?php endif; ?>
</script>

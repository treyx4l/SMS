<?php
$page_title = 'Analytics';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

// Detect graduation column
$hasGraduatedCol = false;
$res = $conn->query("SHOW COLUMNS FROM students LIKE 'is_graduated'");
if ($res && $res->num_rows > 0) {
    $hasGraduatedCol = true;
}

// Monthly data for last 12 months
$months = [];
$studentsByMonth = [];
$teachersByMonth = [];
for ($i = 11; $i >= 0; $i--) {
    $label = date('M', strtotime("-{$i} months"));
    $y     = date('Y', strtotime("-{$i} months"));
    $m     = date('m', strtotime("-{$i} months"));
    $months[] = $label;

    $sql = "SELECT COUNT(*) AS c FROM students WHERE school_id=? AND YEAR(created_at)=? AND MONTH(created_at)=?";
    if ($hasGraduatedCol) {
        $sql .= " AND (is_graduated IS NULL OR is_graduated = 0)";
    }
    $s = $conn->prepare($sql);
    $s->bind_param('iii',$schoolId,$y,$m); $s->execute();
    $studentsByMonth[] = (int)($s->get_result()->fetch_assoc()['c'] ?? 0); $s->close();

    $s = $conn->prepare("SELECT COUNT(*) AS c FROM teachers WHERE school_id=? AND YEAR(created_at)=? AND MONTH(created_at)=?");
    $s->bind_param('iii',$schoolId,$y,$m); $s->execute();
    $teachersByMonth[] = (int)($s->get_result()->fetch_assoc()['c'] ?? 0); $s->close();
}

// Class sizes
$classData = [];
$sql = "SELECT c.name, COUNT(st.id) AS cnt FROM classes c LEFT JOIN students st ON st.class_id=c.id AND st.school_id=c.school_id";
if ($hasGraduatedCol) {
    $sql .= " AND (st.is_graduated IS NULL OR st.is_graduated = 0)";
}
$sql .= " WHERE c.school_id=? GROUP BY c.id,c.name ORDER BY cnt DESC LIMIT 8";
$s = $conn->prepare($sql);
$s->bind_param('i',$schoolId); $s->execute();
$res = $s->get_result();
while ($row = $res->fetch_assoc()) $classData[] = $row;
$s->close();

// Total counts
$totals = [];
foreach (['students','teachers','parents','classes'] as $tbl) {
    if ($tbl === 'students' && $hasGraduatedCol) {
        $s = $conn->prepare("SELECT COUNT(*) AS c FROM students WHERE school_id=? AND (is_graduated IS NULL OR is_graduated = 0)");
    } else {
        $s = $conn->prepare("SELECT COUNT(*) AS c FROM {$tbl} WHERE school_id=?");
    }
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
        <div class="chart-container" style="position:relative;height:220px;width:100%;min-height:220px;max-height:220px;">
            <canvas id="trendChart"></canvas>
        </div>
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
        <div class="chart-container" style="position:relative;height:220px;width:100%;min-height:220px;max-height:220px;">
            <canvas id="classSizeChart"></canvas>
        </div>
        <?php else: ?>
        <div class="flex flex-col items-center justify-center h-40 text-slate-300">
            <i data-lucide="inbox" class="w-10 h-10 mb-2"></i>
            <p class="text-sm text-slate-400">No class data yet</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Additional analytics -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
    <!-- Gender distribution -->
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Gender distribution</h3>
                <p class="text-xs text-slate-400 mt-0.5">Active students by gender</p>
            </div>
            <i data-lucide="pie-chart" class="w-4 h-4 text-indigo-500"></i>
        </div>
        <div class="chart-container" style="position:relative;height:160px;width:100%;min-height:160px;max-height:160px;">
            <canvas id="genderChart"></canvas>
        </div>
    </div>

    <!-- Graduation summary -->
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 rounded-lg bg-slate-900/90 text-white flex items-center justify-center">
                <i data-lucide="award" class="w-4 h-4"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Graduation summary</h3>
        </div>
        <?php
        $graduatedCount = 0;
        if ($hasGraduatedCol) {
            $gs = $conn->prepare("SELECT COUNT(*) AS c FROM students WHERE school_id=? AND is_graduated=1");
            $gs->bind_param('i', $schoolId);
            $gs->execute();
            $graduatedCount = (int) ($gs->get_result()->fetch_assoc()['c'] ?? 0);
            $gs->close();
        }
        ?>
        <p class="text-xs text-slate-500 leading-relaxed">
            <?= $graduatedCount ?> student<?= $graduatedCount === 1 ? '' : 's' ?> have graduated across all years.
            Active students shown elsewhere exclude graduates so your ratios stay accurate.
        </p>
    </div>

    <!-- Engagement summary -->
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center">
                <i data-lucide="users" class="w-4 h-4 text-indigo-600"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Community size</h3>
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

// Gender distribution doughnut
(function () {
    const ctx = document.getElementById('genderChart').getContext('2d');
    const data = {
        labels: ['Male','Female','Other/Unspecified'],
        datasets: [{
            data: <?php
                $gm = $gf = $go = 0;
                $sqlGender = "SELECT gender, COUNT(*) AS c FROM students WHERE school_id=?";
                if ($hasGraduatedCol) {
                    $sqlGender .= " AND (is_graduated IS NULL OR is_graduated = 0)";
                }
                $sqlGender .= " GROUP BY gender";
                $gs2 = $conn->prepare($sqlGender);
                $gs2->bind_param('i', $schoolId);
                $gs2->execute();
                $gr = $gs2->get_result();
                while ($row = $gr->fetch_assoc()) {
                    $g = $row['gender'] ?? '';
                    $c = (int) ($row['c'] ?? 0);
                    if ($g === 'male') $gm += $c;
                    elseif ($g === 'female') $gf += $c;
                    else $go += $c;
                }
                $gs2->close();
                echo json_encode([$gm, $gf, $go]);
            ?>,
            backgroundColor: ['#6366f1','#ec4899','#e5e7eb'],
            borderWidth: 0
        }]
    };
    new Chart(ctx, {
        type: 'doughnut',
        data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false }
            }
        }
    });
})();
</script>

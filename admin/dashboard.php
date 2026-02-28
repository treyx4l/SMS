<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

// Entity counts
$counts = [];
foreach (['students','teachers','parents','classes'] as $tbl) {
    $s = $conn->prepare("SELECT COUNT(*) AS c FROM {$tbl} WHERE school_id = ?");
    $s->bind_param('i', $schoolId);
    $s->execute();
    $counts[$tbl] = (int)($s->get_result()->fetch_assoc()['c'] ?? 0);
    $s->close();
}

// Monthly student enrolment (last 6 months from created_at)
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $label = date('M Y', strtotime("-{$i} months"));
    $y     = date('Y', strtotime("-{$i} months"));
    $m     = date('m', strtotime("-{$i} months"));
    $s = $conn->prepare("SELECT COUNT(*) AS c FROM students WHERE school_id = ? AND YEAR(created_at)=? AND MONTH(created_at)=?");
    $s->bind_param('iii', $schoolId, $y, $m);
    $s->execute();
    $monthlyData[] = ['label' => $label, 'count' => (int)($s->get_result()->fetch_assoc()['c'] ?? 0)];
    $s->close();
}

// Recent teachers (last 5 added)
$recentTeachers = [];
$s = $conn->prepare("SELECT full_name, email, created_at FROM teachers WHERE school_id = ? ORDER BY created_at DESC LIMIT 5");
$s->bind_param('i', $schoolId);
$s->execute();
$res = $s->get_result();
while ($row = $res->fetch_assoc()) $recentTeachers[] = $row;
$s->close();

// Recent students (last 5 added)
$recentStudents = [];
$s = $conn->prepare("SELECT CONCAT(first_name,' ',last_name) AS full_name, created_at FROM students WHERE school_id = ? ORDER BY created_at DESC LIMIT 5");
$s->bind_param('i', $schoolId);
$s->execute();
$res = $s->get_result();
while ($row = $res->fetch_assoc()) $recentStudents[] = $row;
$s->close();

$chartLabels = json_encode(array_column($monthlyData, 'label'));
$chartData   = json_encode(array_column($monthlyData, 'count'));
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- KPI Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <?php
    $kpis = [
        ['label'=>'Total Students',  'value'=>$counts['students'],  'icon'=>'graduation-cap', 'color'=>'text-indigo-600', 'bg'=>'bg-indigo-50',  'border'=>'border-indigo-100', 'sub'=>'Enrolled'],
        ['label'=>'Total Teachers',  'value'=>$counts['teachers'],  'icon'=>'user-check',     'color'=>'text-green-600',  'bg'=>'bg-green-50',   'border'=>'border-green-100',  'sub'=>'Active staff'],
        ['label'=>'Parents',         'value'=>$counts['parents'],   'icon'=>'users',          'color'=>'text-orange-600', 'bg'=>'bg-orange-50',  'border'=>'border-orange-100', 'sub'=>'Registered'],
        ['label'=>'Classes',         'value'=>$counts['classes'],   'icon'=>'book-open',      'color'=>'text-purple-600', 'bg'=>'bg-purple-50',  'border'=>'border-purple-100', 'sub'=>'Active'],
    ];
    foreach ($kpis as $k): ?>
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-slate-500"><?= $k['label'] ?></span>
            <div class="w-8 h-8 rounded-lg <?= $k['bg'] ?> border <?= $k['border'] ?> flex items-center justify-center">
                <i data-lucide="<?= $k['icon'] ?>" class="w-4 h-4 <?= $k['color'] ?>"></i>
            </div>
        </div>
        <div class="text-3xl font-bold <?= $k['color'] ?> mb-1"><?= $k['value'] ?></div>
        <div class="text-[11px] text-slate-400"><?= $k['sub'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    <!-- Enrolment trend (line chart) -->
    <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Student Enrolment (Last 6 Months)</h2>
                <p class="text-xs text-slate-400 mt-0.5">New students registered per month</p>
            </div>
            <i data-lucide="trending-up" class="w-4 h-4 text-indigo-500"></i>
        </div>
        <div class="chart-container" style="position:relative;height:220px;width:100%;min-height:220px;max-height:220px;">
            <canvas id="enrolmentChart"></canvas>
        </div>
    </div>

    <!-- Composition doughnut -->
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">School Composition</h2>
                <p class="text-xs text-slate-400 mt-0.5">People breakdown</p>
            </div>
            <i data-lucide="pie-chart" class="w-4 h-4 text-indigo-500"></i>
        </div>
        <div class="chart-container" style="position:relative;height:180px;width:100%;min-height:180px;max-height:180px;">
            <canvas id="compositionChart"></canvas>
        </div>
        <div class="mt-3 space-y-1.5">
            <?php foreach([
                ['Students','#6366f1',$counts['students']],
                ['Teachers','#22c55e',$counts['teachers']],
                ['Parents', '#f97316',$counts['parents']],
            ] as [$lbl,$col,$val]): ?>
            <div class="flex items-center justify-between text-xs">
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full inline-block" style="background:<?= $col ?>"></span>
                    <span class="text-slate-600"><?= $lbl ?></span>
                </span>
                <span class="font-semibold text-slate-800"><?= $val ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Bottom row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    <!-- Recent Students -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <i data-lucide="graduation-cap" class="w-4 h-4 text-indigo-500"></i>
                <span class="text-sm font-semibold text-slate-800">Recent Students</span>
            </div>
            <a href="students.php" class="text-xs text-indigo-600 hover:underline">View all</a>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if (!$recentStudents): ?>
            <div class="px-5 py-4 text-xs text-slate-400">No students yet.</div>
            <?php else: foreach ($recentStudents as $st): ?>
            <div class="flex items-center justify-between px-5 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold">
                        <?= strtoupper(substr($st['full_name'],0,1)) ?>
                    </div>
                    <span class="text-sm text-slate-700"><?= htmlspecialchars($st['full_name']) ?></span>
                </div>
                <span class="text-[11px] text-slate-400"><?= date('d M', strtotime($st['created_at'])) ?></span>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Recent Teachers -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <i data-lucide="user-check" class="w-4 h-4 text-green-500"></i>
                <span class="text-sm font-semibold text-slate-800">Recent Teachers</span>
            </div>
            <a href="teachers.php" class="text-xs text-indigo-600 hover:underline">View all</a>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if (!$recentTeachers): ?>
            <div class="px-5 py-4 text-xs text-slate-400">No teachers yet.</div>
            <?php else: foreach ($recentTeachers as $t): ?>
            <div class="flex items-center justify-between px-5 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-7 h-7 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-xs font-semibold">
                        <?= strtoupper(substr($t['full_name'],0,1)) ?>
                    </div>
                    <div>
                        <div class="text-sm text-slate-700"><?= htmlspecialchars($t['full_name']) ?></div>
                        <div class="text-[11px] text-slate-400"><?= htmlspecialchars($t['email'] ?? '—') ?></div>
                    </div>
                </div>
                <span class="text-[11px] text-slate-400"><?= date('d M', strtotime($t['created_at'])) ?></span>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>
<script>
// Enrolment line chart
const ctx1 = document.getElementById('enrolmentChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [{
            label: 'New Students',
            data: <?= $chartData ?>,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.08)',
            borderWidth: 2,
            pointBackgroundColor: '#6366f1',
            pointRadius: 4,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0, font: { size: 11 }, color: '#94a3b8' },
                grid: { color: '#f1f5f9' }
            },
            x: {
                ticks: { font: { size: 11 }, color: '#94a3b8' },
                grid: { display: false }
            }
        }
    }
});

// Composition doughnut
const ctx2 = document.getElementById('compositionChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Students','Teachers','Parents'],
        datasets: [{
            data: [<?= $counts['students'] ?>, <?= $counts['teachers'] ?>, <?= $counts['parents'] ?>],
            backgroundColor: ['#6366f1','#22c55e','#f97316'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } } }
    }
});
</script>

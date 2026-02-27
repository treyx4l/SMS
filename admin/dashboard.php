<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$entities = ['students', 'teachers', 'parents', 'classes'];
$counts   = [];
foreach ($entities as $entity) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM {$entity} WHERE school_id = ?");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $row             = $stmt->get_result()->fetch_assoc();
    $counts[$entity] = (int) ($row['c'] ?? 0);
    $stmt->close();
}

$stats = [
    ['label' => 'Students',  'value' => $counts['students'],  'icon' => 'graduation-cap', 'color' => 'text-indigo-600'],
    ['label' => 'Teachers',  'value' => $counts['teachers'],  'icon' => 'user-check',     'color' => 'text-green-600'],
    ['label' => 'Parents',   'value' => $counts['parents'],   'icon' => 'users',          'color' => 'text-orange-500'],
    ['label' => 'Classes',   'value' => $counts['classes'],   'icon' => 'book-open',      'color' => 'text-purple-600'],
];
?>

<!-- KPI Cards -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="flex items-center gap-2.5 px-5 py-3.5 border-b border-slate-100">
        <i data-lucide="bar-chart-2" class="w-4 h-4 text-indigo-600"></i>
        <span class="text-sm font-semibold text-slate-800">Key Performance Indicators</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-slate-100">
        <?php foreach ($stats as $stat): ?>
        <div class="px-5 py-5">
            <div class="text-xs text-slate-500 mb-2"><?= $stat['label'] ?></div>
            <div class="text-3xl font-bold <?= $stat['color'] ?> mb-1"><?= $stat['value'] ?></div>
            <div class="text-[11px] text-slate-400">All enrolled</div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Quick Links + Info -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- Quick links -->
    <div class="md:col-span-2 bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-800">Quick Access</h2>
            <span class="text-[11px] text-slate-400">School ID <?= (int) $schoolId ?></span>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <a href="students.php"
               class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                <i data-lucide="graduation-cap" class="w-4 h-4 shrink-0"></i>
                <span>Students</span>
            </a>
            <a href="teachers.php"
               class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                <i data-lucide="user-check" class="w-4 h-4 shrink-0"></i>
                <span>Teachers</span>
            </a>
            <a href="classes.php"
               class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                <i data-lucide="book-open" class="w-4 h-4 shrink-0"></i>
                <span>Classes</span>
            </a>
            <a href="subjects.php"
               class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                <i data-lucide="layers" class="w-4 h-4 shrink-0"></i>
                <span>Subjects</span>
            </a>
        </div>
        <p class="text-[11px] text-slate-400 mt-4">Extend this dashboard with attendance, grades, lesson plans, bus routes, and more.</p>
    </div>

    <!-- Info panel -->
    <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-3">
        <h2 class="text-sm font-semibold text-slate-800 mb-3">Quick Stats</h2>
        <div class="rounded-lg bg-blue-50 border border-blue-100 px-4 py-3">
            <div class="text-[11px] text-slate-500 mb-1">Total Students</div>
            <div class="text-xl font-bold text-indigo-600"><?= $counts['students'] ?></div>
        </div>
        <div class="rounded-lg bg-red-50 border border-red-100 px-4 py-3">
            <div class="text-[11px] text-slate-500 mb-1">Total Teachers</div>
            <div class="text-xl font-bold text-red-500"><?= $counts['teachers'] ?></div>
        </div>
        <div class="rounded-lg bg-green-50 border border-green-100 px-4 py-3">
            <div class="text-[11px] text-slate-500 mb-1">Total Classes</div>
            <div class="text-xl font-bold text-green-600"><?= $counts['classes'] ?></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>

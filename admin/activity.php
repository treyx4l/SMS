<?php
$page_title = 'Activity';
require_once __DIR__ . '/layout.php';

// We'll synthesise activity from recent DB changes across tables.
// Each recent record in key tables = a "log entry".
$conn     = get_db_connection();
$schoolId = current_school_id();

$activities = [];

$queries = [
    ['table'=>'students',  'icon'=>'graduation-cap', 'color'=>'indigo',  'action'=>'Student enrolled',   'name_col'=>"CONCAT(first_name,' ',last_name)"],
    ['table'=>'teachers',  'icon'=>'user-check',     'color'=>'green',   'action'=>'Teacher added',      'name_col'=>'full_name'],
    ['table'=>'parents',   'icon'=>'users',          'color'=>'orange',  'action'=>'Parent registered',  'name_col'=>'full_name'],
    ['table'=>'classes',   'icon'=>'book-open',      'color'=>'purple',  'action'=>'Class created',      'name_col'=>'name'],
];

foreach ($queries as $q) {
    // Pull a reasonable recent window per table; overall paging is done in PHP
    $sql = "SELECT {$q['name_col']} AS label, created_at FROM {$q['table']} WHERE school_id=? ORDER BY created_at DESC LIMIT 100";
    $s = $conn->prepare($sql);
    if (!$s) continue;
    $s->bind_param('i', $schoolId);
    $s->execute();
    $res = $s->get_result();
    while ($row = $res->fetch_assoc()) {
        $activities[] = [
            'label'  => $row['label'],
            'time'   => $row['created_at'],
            'action' => $q['action'],
            'icon'   => $q['icon'],
            'color'  => $q['color'],
        ];
    }
    $s->close();
}

// Sort all by time desc
usort($activities, fn($a,$b) => strtotime($b['time']) - strtotime($a['time']));

// Basic server-side pagination (within the recent window above)
$perPage    = 20;
$page       = max(1, (int) ($_GET['page'] ?? 1));
$totalRows  = count($activities);
$totalPages = $totalRows ? (int) ceil($totalRows / $perPage) : 1;
$page       = min($page, max(1, $totalPages));
$offset     = ($page - 1) * $perPage;
$pagedActivities = array_slice($activities, $offset, $perPage);

// Color map
$colorMap = [
    'indigo' => ['bg'=>'bg-indigo-50','border'=>'border-indigo-100','icon'=>'text-indigo-600'],
    'green'  => ['bg'=>'bg-green-50', 'border'=>'border-green-100', 'icon'=>'text-green-600'],
    'orange' => ['bg'=>'bg-orange-50','border'=>'border-orange-100','icon'=>'text-orange-500'],
    'purple' => ['bg'=>'bg-purple-50','border'=>'border-purple-100','icon'=>'text-purple-600'],
];
?>

<!-- Header stats -->
<div class="grid grid-cols-3 gap-4">
    <?php
    $headerStats = [
        ['Today\'s Events', count(array_filter($activities, fn($a) => date('Y-m-d', strtotime($a['time'])) === date('Y-m-d'))), 'calendar-check', 'indigo'],
        ['This Week',       count(array_filter($activities, fn($a) => strtotime($a['time']) >= strtotime('-7 days'))), 'clock', 'green'],
        ['Total Logged',    count($activities), 'list', 'purple'],
    ];
    foreach ($headerStats as [$lbl,$val,$ico,$col]): ?>
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-slate-500"><?= $lbl ?></span>
            <div class="w-7 h-7 rounded-lg <?= $colorMap[$col]['bg'] ?> border <?= $colorMap[$col]['border'] ?> flex items-center justify-center">
                <i data-lucide="<?= $ico ?>" class="w-3.5 h-3.5 <?= $colorMap[$col]['icon'] ?>"></i>
            </div>
        </div>
        <div class="text-2xl font-bold <?= $colorMap[$col]['icon'] ?>"><?= $val ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Activity feed -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
        <div class="flex items-center gap-2">
            <i data-lucide="activity" class="w-4 h-4 text-indigo-500"></i>
            <span class="text-sm font-semibold text-slate-800">Activity Log</span>
        </div>
        <span class="text-xs text-slate-400">
            System-generated from recent records
            <?php if ($totalRows): ?>
                · Page <?= $page ?> of <?= $totalPages ?>
            <?php endif; ?>
        </span>
    </div>

    <?php if (!$activities): ?>
    <div class="flex flex-col items-center justify-center py-16 text-slate-300">
        <i data-lucide="inbox" class="w-12 h-12 mb-3"></i>
        <p class="text-sm text-slate-400 font-medium">No activity yet</p>
        <p class="text-xs text-slate-400 mt-1">Add students, teachers, or classes to see activity here.</p>
    </div>
    <?php else: ?>
    <div class="divide-y divide-slate-100">
        <?php foreach ($pagedActivities as $i => $act):
            $c = $colorMap[$act['color']];
            $ts = strtotime($act['time']);
            $diff = time() - $ts;
            if ($diff < 60) $rel = 'Just now';
            elseif ($diff < 3600) $rel = floor($diff/60).'m ago';
            elseif ($diff < 86400) $rel = floor($diff/3600).'h ago';
            else $rel = date('d M Y', $ts);
        ?>
        <div class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50 transition-colors">
            <!-- Icon -->
            <div class="w-8 h-8 rounded-lg <?= $c['bg'] ?> border <?= $c['border'] ?> flex items-center justify-center shrink-0">
                <i data-lucide="<?= $act['icon'] ?>" class="w-4 h-4 <?= $c['icon'] ?>"></i>
            </div>
            <!-- Text -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-slate-800"><?= htmlspecialchars($act['action']) ?></span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium <?= $c['bg'] ?> <?= $c['icon'] ?> border <?= $c['border'] ?>">
                        <?= htmlspecialchars($act['label']) ?>
                    </span>
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5"><?= date('l, d M Y \a\t H:i', $ts) ?></div>
            </div>
            <!-- Relative time -->
            <span class="text-[11px] text-slate-400 shrink-0"><?= $rel ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($totalPages > 1): ?>
    <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
        <p>
            Showing <?= $totalRows ? $offset + 1 : 0 ?>–<?= min($offset + $perPage, $totalRows) ?> of <?= $totalRows ?>
        </p>
        <div class="flex items-center gap-1">
            <?php
            $baseUrl = 'activity.php?';
            $query   = $_GET;
            unset($query['page']);
            $baseQuery = $query ? http_build_query($query) . '&' : '';
            if ($page > 1): ?>
                <a href="<?= $baseUrl . $baseQuery ?>page=<?= $page - 1 ?>" class="inline-flex items-center gap-1 px-2.5 py-1.5 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-600">Prev</a>
            <?php endif;
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++): ?>
                <a href="<?= $baseUrl . $baseQuery ?>page=<?= $i ?>" class="inline-flex w-8 h-8 items-center justify-center rounded-lg text-xs font-medium <?= $i === $page ? 'bg-indigo-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' ?>"><?= $i ?></a>
            <?php endfor;
            if ($page < $totalPages): ?>
                <a href="<?= $baseUrl . $baseQuery ?>page=<?= $page + 1 ?>" class="inline-flex items-center gap-1 px-2.5 py-1.5 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-600">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Info note -->
<div class="flex items-start gap-3 px-5 py-4 bg-blue-50 border border-blue-100 rounded-xl">
    <i data-lucide="info" class="w-4 h-4 text-blue-500 shrink-0 mt-0.5"></i>
    <p class="text-xs text-blue-700">Activity is derived from creation timestamps on database records. A dedicated audit log with user-level tracking can be added when user authentication is fully wired up.</p>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>

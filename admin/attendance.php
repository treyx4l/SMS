<?php
$page_title = 'Attendance';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$tablesExist = false;
$res = $conn->query("SHOW TABLES LIKE 'attendance'");
$tablesExist = $res && $res->num_rows > 0;

$classes  = [];
$stmt = $conn->prepare("SELECT id, name, section FROM classes WHERE school_id=? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $classes[] = $row;
$stmt->close();

$filterClass = (int) ($_GET['class_id'] ?? 0);
$filterDate  = trim($_GET['date'] ?? date('Y-m-d'));

$perPage = 10;
$page    = max(1, (int) ($_GET['page'] ?? 1));

// Aggregates for present/late/absent (all pages)
$present = $late = $absent = 0;
$totalRows = 0;
if ($tablesExist) {
    $aggSql = "SELECT status, COUNT(*) as c FROM attendance a WHERE a.school_id=?";
    $aggParams = [$schoolId];
    $aggTypes = 'i';
    if ($filterClass) { $aggSql .= " AND a.class_id=?"; $aggParams[] = $filterClass; $aggTypes .= 'i'; }
    if ($filterDate)  { $aggSql .= " AND a.date=?"; $aggParams[] = $filterDate; $aggTypes .= 's'; }
    $aggSql .= " GROUP BY status";
    $astmt = $conn->prepare($aggSql);
    $astmt->bind_param($aggTypes, ...$aggParams);
    $astmt->execute();
    $ares = $astmt->get_result();
    while ($arow = $ares->fetch_assoc()) {
        if ($arow['status'] === 'present') $present = $arow['c'];
        elseif ($arow['status'] === 'late') $late = $arow['c'];
        elseif ($arow['status'] === 'absent') $absent = $arow['c'];
    }
    $astmt->close();
    $totalRows = $present + $late + $absent;
}

$totalPages = $totalRows ? (int) ceil($totalRows / $perPage) : 1;
$page       = min($page, max(1, $totalPages));
$offset     = ($page - 1) * $perPage;

$records = [];
if ($tablesExist) {
    $sql = "SELECT a.id, a.student_id, a.date, a.status, a.remarks,
                   CONCAT(s.first_name,' ',s.last_name) AS student_name,
                   c.name AS class_name, c.section
            FROM attendance a
            JOIN students s ON s.id=a.student_id AND s.school_id=a.school_id
            LEFT JOIN classes c ON c.id=a.class_id AND c.school_id=a.school_id
            WHERE a.school_id=?";
    $params = [$schoolId];
    $types  = 'i';
    if ($filterClass) { $sql .= " AND a.class_id=?"; $params[] = $filterClass; $types .= 'i'; }
    if ($filterDate)  { $sql .= " AND a.date=?"; $params[] = $filterDate; $types .= 's'; }
    $sql .= " ORDER BY c.name, s.first_name, s.last_name LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $records[] = $row;
    $stmt->close();
}
?>

<?php if (!$tablesExist): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <div class="flex items-start gap-3">
        <i data-lucide="alert-triangle" class="w-6 h-6 text-amber-600 shrink-0"></i>
        <div>
            <h3 class="text-sm font-semibold text-amber-800">Run migration first</h3>
            <p class="text-sm text-amber-700 mt-1">Execute <code class="bg-amber-100 px-1 rounded">database_migration_photos_attendance.sql</code> to create the attendance table.</p>
        </div>
    </div>
</div>
<?php else: ?>

<div class="flex items-center justify-between flex-wrap gap-4 mb-4">
    <div>
        <h2 class="text-base font-semibold text-slate-800">Attendance overview</h2>
        <p class="text-xs text-slate-500 mt-0.5">View only — teachers mark attendance</p>
    </div>
    <form method="get" class="flex flex-wrap gap-2 items-center">
        <select name="class_id" onchange="this.form.submit()" class="px-3 py-2 border border-slate-200 rounded-lg text-sm">
            <option value="">All classes</option>
            <?php foreach ($classes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $filterClass === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name'] . ($c['section'] ? ' ' . $c['section'] : '')) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>" onchange="this.form.submit()" class="px-3 py-2 border border-slate-200 rounded-lg text-sm">
    </form>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="text-xs font-medium text-slate-500 mb-1">Present</div>
        <div class="text-2xl font-bold text-green-600"><?= $present ?></div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="text-xs font-medium text-slate-500 mb-1">Late</div>
        <div class="text-2xl font-bold text-amber-500"><?= $late ?></div>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <div class="text-xs font-medium text-slate-500 mb-1">Absent</div>
        <div class="text-2xl font-bold text-rose-500"><?= $absent ?></div>
    </div>
</div>

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <span class="text-sm font-semibold text-slate-800">Attendance records — <?= htmlspecialchars($filterDate) ?></span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50">
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Student</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Class</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Status</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Remarks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($records)): ?>
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-slate-400">No attendance records for this date. Teachers mark attendance from the Teacher portal.</td>
                </tr>
                <?php else: foreach ($records as $r): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($r['student_name']) ?></td>
                    <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars($r['class_name'] . ($r['section'] ? ' ' . $r['section'] : '')) ?></td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium
                            <?= $r['status'] === 'present' ? 'bg-green-50 text-green-700 border border-green-200' : ($r['status'] === 'late' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') ?>">
                            <?= ucfirst($r['status']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs"><?= htmlspecialchars($r['remarks'] ?? '—') ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
        <p>Showing <?= $totalRows ? $offset + 1 : 0 ?>–<?= min($offset + $perPage, $totalRows) ?> of <?= $totalRows ?> records</p>
        <div class="flex items-center gap-1">
            <?php
            $baseUrl = 'attendance.php?';
            $query = $_GET;
            unset($query['page']);
            $baseQuery = $query ? http_build_query($query) . '&' : '';
            if ($page > 1): ?>
            <a href="<?= $baseUrl . $baseQuery ?>page=<?= $page - 1 ?>" class="px-2.5 py-1.5 border border-slate-200 rounded-lg hover:bg-slate-50">Prev</a>
            <?php endif;
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++): ?>
            <a href="<?= $baseUrl . $baseQuery ?>page=<?= $i ?>" class="w-8 h-8 flex items-center justify-center rounded-lg <?= $i === $page ? 'bg-indigo-600 text-white' : 'border border-slate-200 hover:bg-slate-50' ?>"><?= $i ?></a>
            <?php endfor;
            if ($page < $totalPages): ?>
            <a href="<?= $baseUrl . $baseQuery ?>page=<?= $page + 1 ?>" class="px-2.5 py-1.5 border border-slate-200 rounded-lg hover:bg-slate-50">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>

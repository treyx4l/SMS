<?php
$page_title = 'Graduated Students';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$errors  = [];
$success = null;

// Check if graduation columns exist
$hasGraduatedCol = false;
$res = $conn->query("SHOW COLUMNS FROM students LIKE 'is_graduated'");
if ($res && $res->num_rows > 0) {
    $hasGraduatedCol = true;
}

if (!$hasGraduatedCol): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
    <div class="flex items-start gap-3">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 mt-0.5"></i>
        <div>
            <h2 class="text-sm font-semibold text-amber-800">Graduation not configured</h2>
            <p class="text-xs text-amber-700 mt-1">
                The students table does not yet have graduation fields. Please run the <code>database_migration_students_graduation.sql</code> migration,
                then reload this page to see graduated students.
            </p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>
<?php return; endif;

// Pagination & search
$searchQ = trim($_GET['q'] ?? '');
$searchParam = $searchQ !== '' ? '%' . $searchQ . '%' : null;

$perPage = 10;
$page    = max(1, (int) ($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Total count
if ($searchParam !== null) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS c
        FROM students
        WHERE school_id = ? AND is_graduated = 1
          AND (first_name LIKE ? OR last_name LIKE ? OR index_no LIKE ?)
    ");
    $stmt->bind_param('isss', $schoolId, $searchParam, $searchParam, $searchParam);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM students WHERE school_id = ? AND is_graduated = 1");
    $stmt->bind_param('i', $schoolId);
}
$stmt->execute();
$totalRows = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

$totalPages = $totalRows ? (int) ceil($totalRows / $perPage) : 1;
$page       = min($page, max(1, $totalPages));
$offset     = ($page - 1) * $perPage;

// Fetch graduated students
$students = [];
$where = "s.school_id = ? AND s.is_graduated = 1";
$params = [$schoolId];
$types  = 'i';
if ($searchParam !== null) {
    $where .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.index_no LIKE ?)";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types   .= 'sss';
}
$params[] = $perPage;
$params[] = $offset;
$types   .= 'ii';

$sql = "
    SELECT s.id,
           s.first_name,
           s.last_name,
           s.index_no,
           s.gender,
           s.photo_path,
           s.graduated_at,
           c.name   AS class_name,
           c.section AS class_section
    FROM students s
    LEFT JOIN classes c ON c.id = s.class_id AND c.school_id = s.school_id
    WHERE {$where}
    ORDER BY s.graduated_at DESC, s.last_name, s.first_name
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();
?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-slate-800">Graduated Students</h2>
        <p class="text-xs text-slate-500 mt-0.5">
            <?= $totalRows ?> student<?= $totalRows === 1 ? '' : 's' ?> have been marked as graduated and removed from active counts.
        </p>
    </div>
    <form method="get" action="graduated_students.php" class="relative">
        <input type="hidden" name="page" value="1">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
            <i data-lucide="search" class="w-3.5 h-3.5"></i>
        </span>
        <input type="text" name="q" value="<?= htmlspecialchars($searchQ) ?>" placeholder="Search by name or index…"
               class="pl-8 pr-3 py-1.5 border border-slate-200 rounded-lg text-xs bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
    </form>
</div>

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">Photo</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">Name</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">Index No</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">Last Class</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">Graduated</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$students): ?>
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-xs text-slate-400">
                        No graduated students yet.
                    </td>
                </tr>
                <?php else: foreach ($students as $s): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3">
                        <?php if (!empty($s['photo_path'])): ?>
                        <img src="../<?= htmlspecialchars($s['photo_path']) ?>" alt="" class="w-8 h-8 rounded-full object-cover bg-slate-100">
                        <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold">
                            <?= strtoupper(substr($s['first_name'], 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-slate-800 font-medium">
                        <?= htmlspecialchars(trim($s['first_name'] . ' ' . $s['last_name'])) ?>
                    </td>
                    <td class="px-4 py-3 text-slate-600 text-xs">
                        <?= htmlspecialchars($s['index_no'] ?? '—') ?>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">
                        <?= $s['class_name']
                            ? htmlspecialchars($s['class_name'] . ($s['class_section'] ? ' ' . $s['class_section'] : ''))
                            : '—' ?>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">
                        <?= $s['graduated_at'] ? date('d M Y', strtotime($s['graduated_at'])) : '—' ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
        <p>
            Showing <?= $totalRows ? $offset + 1 : 0 ?>–<?= min($offset + $perPage, $totalRows) ?> of <?= $totalRows ?>
        </p>
        <div class="flex items-center gap-1">
            <?php
            $baseUrl = 'graduated_students.php?';
            $query   = $_GET;
            unset($query['page']);
            $baseQuery = $query ? http_build_query($query) . '&' : '';
            if ($page > 1): ?>
            <a href="<?= $baseUrl . $baseQuery ?>page=<?= $page - 1 ?>" class="inline-flex items-center gap-1 px-2.5 py-1.5 border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50">Prev</a>
            <?php endif;
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++): ?>
            <a href="<?= $baseUrl . $baseQuery ?>page=<?= $i ?>" class="inline-flex w-8 h-8 items-center justify-center rounded-lg text-xs font-medium <?= $i === $page ? 'bg-indigo-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' ?>"><?= $i ?></a>
            <?php endfor;
            if ($page < $totalPages): ?>
            <a href="<?= $baseUrl . $baseQuery ?>page=<?= $page + 1 ?>" class="inline-flex items-center gap-1 px-2.5 py-1.5 border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>


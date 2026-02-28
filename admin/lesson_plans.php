<?php
$page_title = 'Lesson Plans';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$tablesExist = false;
$res = $conn->query("SHOW TABLES LIKE 'lesson_plans'");
$tablesExist = $res && $res->num_rows > 0;

// Admin: view only. Teachers add/edit lesson plans.
$classes  = [];
$subjects = [];
$teachers = [];
$stmt = $conn->prepare("SELECT id, name, section FROM classes WHERE school_id=? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $classes[] = $row;
$stmt->close();

$stmt = $conn->prepare("SELECT id, name FROM subjects WHERE school_id=? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $subjects[] = $row;
$stmt->close();

$stmt = $conn->prepare("SELECT id, full_name FROM teachers WHERE school_id=? ORDER BY full_name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $teachers[] = $row;
$stmt->close();

$filterClass = (int) ($_GET['class_id'] ?? 0);
$filterSubj  = (int) ($_GET['subject_id'] ?? 0);
$filterWeek  = trim($_GET['week_start'] ?? '');

$plans = [];
if ($tablesExist) {
    $sql = "SELECT p.id, p.teacher_id, p.class_id, p.subject_id, p.week_start, p.topic, p.objectives, p.content, p.resources, p.status, p.created_at,
                   t.full_name AS teacher_name, c.name AS class_name, c.section AS class_section, s.name AS subject_name
            FROM lesson_plans p
            LEFT JOIN teachers t ON t.id=p.teacher_id AND t.school_id=p.school_id
            LEFT JOIN classes c ON c.id=p.class_id AND c.school_id=p.school_id
            LEFT JOIN subjects s ON s.id=p.subject_id AND s.school_id=p.school_id
            WHERE p.school_id=?";
    $params = [$schoolId];
    $types  = 'i';
    if ($filterClass) { $sql .= " AND p.class_id=?"; $params[] = $filterClass; $types .= 'i'; }
    if ($filterSubj)  { $sql .= " AND p.subject_id=?"; $params[] = $filterSubj; $types .= 'i'; }
    if ($filterWeek)  { $sql .= " AND p.week_start=?"; $params[] = $filterWeek; $types .= 's'; }
    $sql .= " ORDER BY p.week_start DESC, c.name, s.name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $plans[] = $row;
    $stmt->close();
}

?>

<?php if (!$tablesExist): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <div class="flex items-start gap-3">
        <i data-lucide="alert-triangle" class="w-6 h-6 text-amber-600 shrink-0"></i>
        <div>
            <h3 class="text-sm font-semibold text-amber-800">Run migration first</h3>
            <p class="text-sm text-amber-700 mt-1">Execute <code class="bg-amber-100 px-1 rounded">database_migration_grades_lesson_plans.sql</code> to create the lesson_plans table.</p>
        </div>
    </div>
</div>
<?php else: ?>

<div class="flex items-center justify-between flex-wrap gap-4 mb-4">
    <div>
        <h2 class="text-base font-semibold text-slate-800">Lesson plans</h2>
        <p class="text-xs text-slate-500 mt-0.5">View only — teachers add and edit lesson plans</p>
    </div>
    <form method="get" class="flex flex-wrap gap-2 items-center">
        <select name="class_id" onchange="this.form.submit()" class="px-3 py-2 border border-slate-200 rounded-lg text-sm">
            <option value="">All classes</option>
            <?php foreach ($classes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $filterClass === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name'] . ($c['section'] ? ' ' . $c['section'] : '')) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="subject_id" onchange="this.form.submit()" class="px-3 py-2 border border-slate-200 rounded-lg text-sm">
            <option value="">All subjects</option>
            <?php foreach ($subjects as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= $filterSubj === (int)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="week_start" value="<?= htmlspecialchars($filterWeek) ?>" onchange="this.form.submit()" class="px-3 py-2 border border-slate-200 rounded-lg text-sm" title="Week start (Monday)">
        <button type="submit" class="px-3 py-2 border border-slate-200 rounded-lg text-sm hover:bg-slate-50">Filter</button>
    </form>
</div>

<!-- List -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <span class="text-sm font-semibold text-slate-800">Lesson plans</span>
    </div>
    <div class="divide-y divide-slate-100">
        <?php if (empty($plans)): ?>
        <div class="px-5 py-12 text-center text-slate-400">
            <i data-lucide="file-text" class="w-10 h-10 mx-auto mb-3 text-slate-300"></i>
            <p class="text-sm font-medium">No lesson plans yet</p>
            <p class="text-xs mt-1">Teachers add lesson plans from the Teacher portal. Filter above to narrow results.</p>
        </div>
        <?php else: foreach ($plans as $p): ?>
        <div class="px-5 py-4 hover:bg-slate-50">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="font-semibold text-slate-800"><?= htmlspecialchars($p['topic']) ?></div>
                    <div class="text-xs text-slate-500 mt-1">
                        <?= htmlspecialchars($p['class_name'] . ($p['class_section'] ? ' ' . $p['class_section'] : '')) ?>
                        · <?= htmlspecialchars($p['subject_name']) ?>
                        · <?= htmlspecialchars($p['teacher_name']) ?>
                        · Week of <?= date('d M Y', strtotime($p['week_start'])) ?>
                    </div>
                    <?php if (!empty($p['objectives'])): ?>
                    <p class="text-xs text-slate-600 mt-2 line-clamp-2"><?= htmlspecialchars($p['objectives']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium
                        <?= $p['status'] === 'approved' ? 'bg-green-50 text-green-700 border border-green-200' : ($p['status'] === 'submitted' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-500') ?>">
                        <?= ucfirst($p['status']) ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>

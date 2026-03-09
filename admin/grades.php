<?php
$page_title = 'Grades';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$tablesExist = false;
$res = $conn->query("SHOW TABLES LIKE 'grades'");
if ($res && $res->num_rows > 0) {
    $r2 = $conn->query("SHOW TABLES LIKE 'exam_types'");
    $tablesExist = $r2 && $r2->num_rows > 0;
}

// Admin: view only. Teachers add/edit grades.
$examTypes = [];
$classes   = [];
$subjects  = [];
$students  = [];

if ($tablesExist) {
    $stmt = $conn->prepare("SELECT id, name, weight FROM exam_types WHERE school_id=? ORDER BY name");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $examTypes[] = $row;
    $stmt->close();

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
}

$filterClass = (int) ($_GET['class_id'] ?? 0);
$filterExam  = (int) ($_GET['exam_type_id'] ?? 0);
$filterSubj  = (int) ($_GET['subject_id'] ?? 0);

if ($filterClass) {
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM students WHERE school_id=? AND class_id=? ORDER BY first_name, last_name");
    $stmt->bind_param('ii', $schoolId, $filterClass);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $students[] = $row;
    $stmt->close();
}

$grades = [];
if ($tablesExist && ($filterClass || $filterExam || $filterSubj)) {
    $sql = "SELECT g.id, g.student_id, g.subject_id, g.exam_type_id, g.score, g.max_score,
                   CONCAT(s.first_name,' ',s.last_name) AS student_name,
                   sub.name AS subject_name, e.name AS exam_name
            FROM grades g
            JOIN students s ON s.id=g.student_id AND s.school_id=g.school_id
            JOIN subjects sub ON sub.id=g.subject_id
            JOIN exam_types e ON e.id=g.exam_type_id
            WHERE g.school_id=?";
    $params = [$schoolId];
    $types  = 'i';
    if ($filterClass) { $sql .= " AND g.class_id=?"; $params[] = $filterClass; $types .= 'i'; }
    if ($filterExam)  { $sql .= " AND g.exam_type_id=?"; $params[] = $filterExam; $types .= 'i'; }
    if ($filterSubj)  { $sql .= " AND g.subject_id=?"; $params[] = $filterSubj; $types .= 'i'; }
    $sql .= " ORDER BY s.first_name, sub.name, e.name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $grades[] = $row;
    $stmt->close();
}
?>

<?php if (!$tablesExist): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <div class="flex items-start gap-3">
        <i data-lucide="alert-triangle" class="w-6 h-6 text-amber-600 shrink-0"></i>
        <div>
            <h3 class="text-sm font-semibold text-amber-800">Run migration first</h3>
            <p class="text-sm text-amber-700 mt-1">Execute <code class="bg-amber-100 px-1 rounded">database_migration_grades_lesson_plans.sql</code> to create grades and exam types tables.</p>
        </div>
    </div>
</div>
<?php else: ?>

<div class="flex items-center justify-between flex-wrap gap-4 mb-4">
    <div>
        <h2 class="text-base font-semibold text-slate-800">Grades overview</h2>
        <p class="text-xs text-slate-500 mt-0.5">View only — teachers add and edit grades</p>
    </div>
    <form method="get" class="flex flex-wrap gap-2 items-center">
        <select name="class_id" class="px-3 py-2 border border-slate-200 rounded-lg text-sm">
            <option value="">All classes</option>
            <?php foreach ($classes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $filterClass === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name'] . ($c['section'] ? ' ' . $c['section'] : '')) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="subject_id" class="px-3 py-2 border border-slate-200 rounded-lg text-sm">
            <option value="">All subjects</option>
            <?php foreach ($subjects as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= $filterSubj === (int)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="exam_type_id" class="px-3 py-2 border border-slate-200 rounded-lg text-sm">
            <option value="">All exams</option>
            <?php foreach ($examTypes as $e): ?>
            <option value="<?= (int)$e['id'] ?>" <?= $filterExam === (int)$e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">Filter</button>
    </form>
</div>

<!-- Grades table -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <span class="text-sm font-semibold text-slate-800">Grades</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50">
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Student</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Subject</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Exam</th>
                    <th class="text-right px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Score</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($grades)): ?>
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-slate-400">No grades. Teachers add grades from the Teacher portal.</td>
                </tr>
                <?php else: foreach ($grades as $g): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($g['student_name']) ?></td>
                    <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($g['subject_name']) ?></td>
                    <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars($g['exam_name']) ?></td>
                    <td class="px-4 py-3 text-right font-semibold text-indigo-600"><?= $g['score'] ?> / <?= $g['max_score'] ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>

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

$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tablesExist) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_exam_type') {
        $name   = trim($_POST['name'] ?? '');
        $weight = (float) ($_POST['weight'] ?? 100);
        if ($name === '') {
            $errors[] = 'Exam type name required.';
        } elseif ($name) {
            $stmt = $conn->prepare("INSERT INTO exam_types (school_id, name, weight) VALUES (?, ?, ?)");
            $stmt->bind_param('isd', $schoolId, $name, $weight);
            $stmt->execute();
            $stmt->close();
            $success = 'Exam type added.';
        }
    } elseif ($action === 'save_grade') {
        $student_id   = (int) ($_POST['student_id'] ?? 0);
        $subject_id   = (int) ($_POST['subject_id'] ?? 0);
        $class_id     = (int) ($_POST['class_id'] ?? 0);
        $exam_type_id = (int) ($_POST['exam_type_id'] ?? 0);
        $score        = (float) ($_POST['score'] ?? 0);
        $max_score    = (float) ($_POST['max_score'] ?? 100);

        if ($student_id && $subject_id && $class_id && $exam_type_id && $max_score > 0) {
            $stmt = $conn->prepare("INSERT INTO grades (school_id, student_id, subject_id, class_id, exam_type_id, score, max_score) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE score=VALUES(score), max_score=VALUES(max_score)");
            $stmt->bind_param('iiiiidd', $schoolId, $student_id, $subject_id, $class_id, $exam_type_id, $score, $max_score);
            $stmt->execute();
            $stmt->close();
            $success = 'Grade saved.';
        } else {
            $errors[] = 'Invalid grade data.';
        }
    } elseif ($action === 'delete_grade') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM grades WHERE id=? AND school_id=?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();
            $success = 'Grade removed.';
        }
    }
}

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

<?php if ($errors): ?>
<div class="flex items-center gap-2.5 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600 mb-4">
    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
    <?= htmlspecialchars(implode(' ', $errors)) ?>
</div>
<?php elseif ($success): ?>
<div class="flex items-center gap-2.5 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700 mb-4">
    <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<div class="flex items-center justify-between flex-wrap gap-4 mb-4">
    <h2 class="text-base font-semibold text-slate-800">Grades overview</h2>
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
        <select name="exam_type_id" onchange="this.form.submit()" class="px-3 py-2 border border-slate-200 rounded-lg text-sm">
            <option value="">All exams</option>
            <?php foreach ($examTypes as $e): ?>
            <option value="<?= (int)$e['id'] ?>" <?= $filterExam === (int)$e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Add exam type -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden mb-6">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <span class="text-sm font-semibold text-slate-800">Exam types</span>
    </div>
    <form method="post" class="p-5 flex flex-wrap gap-4 items-end">
        <input type="hidden" name="action" value="save_exam_type">
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Name</label>
            <input type="text" name="name" placeholder="e.g. Mid-term" required class="px-3 py-2 border border-slate-200 rounded-lg text-sm w-40">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Weight %</label>
            <input type="number" name="weight" value="100" min="0" max="100" step="0.01" class="px-3 py-2 border border-slate-200 rounded-lg text-sm w-20">
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">Add exam type</button>
    </form>
    <?php if ($examTypes): ?>
    <div class="px-5 pb-4 flex flex-wrap gap-2">
        <?php foreach ($examTypes as $e): ?>
        <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200"><?= htmlspecialchars($e['name']) ?> (<?= $e['weight'] ?>%)</span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add grade (requires class filter so students match) -->
<?php if ($examTypes && $classes && $subjects && $students && $filterClass): ?>
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden mb-6">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <span class="text-sm font-semibold text-slate-800">Enter grade</span>
        <span class="text-xs text-slate-500 ml-2">Filter by class above first</span>
    </div>
    <form method="post" class="p-5">
        <input type="hidden" name="action" value="save_grade">
        <input type="hidden" name="class_id" value="<?= (int)$filterClass ?>">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Student *</label>
                <select name="student_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm">
                    <option value="">—</option>
                    <?php foreach ($students as $s): ?>
                    <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Subject</label>
                <select name="subject_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm">
                    <?php foreach ($subjects as $s): ?>
                    <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Exam</label>
                <select name="exam_type_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm">
                    <?php foreach ($examTypes as $e): ?>
                    <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Score</label>
                <div class="flex gap-1 items-center">
                    <input type="number" name="score" step="0.01" min="0" required placeholder="0" class="px-3 py-2 border border-slate-200 rounded-lg text-sm w-20">
                    <span class="text-slate-400">/</span>
                    <input type="number" name="max_score" value="100" step="0.01" min="0.01" class="px-3 py-2 border border-slate-200 rounded-lg text-sm w-20">
                </div>
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">Save</button>
            </div>
        </div>
        <?php
        $currentClass = null;
        foreach ($classes as $c) { if ((int)$c['id'] === $filterClass) { $currentClass = $c['name'] . ($c['section'] ? ' ' . $c['section'] : ''); break; } }
        ?>
        <p class="text-xs text-slate-500 mt-2">Adding grade for class: <strong><?= htmlspecialchars($currentClass ?? '—') ?></strong></p>
    </form>
</div>
<?php endif; ?>

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
                    <th class="text-right px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($grades)): ?>
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-slate-400">No grades. Filter by class/subject/exam or add grades above.</td>
                </tr>
                <?php else: foreach ($grades as $g): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($g['student_name']) ?></td>
                    <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($g['subject_name']) ?></td>
                    <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars($g['exam_name']) ?></td>
                    <td class="px-4 py-3 text-right font-semibold text-indigo-600"><?= $g['score'] ?> / <?= $g['max_score'] ?></td>
                    <td class="px-4 py-3 text-right">
                        <form method="post" class="inline" onsubmit="return confirm('Remove this grade?');">
                            <input type="hidden" name="action" value="delete_grade">
                            <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>

<?php
$page_title = 'Grades';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

// Resolve teacher_id from logged-in user (users.email = teachers.email)
$teacherId = null;
$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId && $schoolId) {
    $stmt = $conn->prepare("SELECT t.id FROM teachers t JOIN users u ON u.email=t.email AND u.school_id=t.school_id WHERE u.id=? AND u.role='teacher' AND u.school_id=? LIMIT 1");
    $stmt->bind_param('ii', $userId, $schoolId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) $teacherId = (int) $row['id'];
}

$tablesExist = false;
$res = $conn->query("SHOW TABLES LIKE 'grades'");
if ($res && $res->num_rows > 0) {
    $r2 = $conn->query("SHOW TABLES LIKE 'exam_types'");
    $tablesExist = $r2 && $r2->num_rows > 0;
}

$examTypes = [];
$classes   = [];
$subjects  = [];
$students  = [];
$grades    = [];
$teacherClassesSubjects = [];

if ($teacherId) {
    $stmt = $conn->prepare("SELECT DISTINCT class_id, subject_id FROM timetable_entries WHERE school_id=? AND teacher_id=?");
    $stmt->bind_param('ii', $schoolId, $teacherId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $teacherClassesSubjects[] = $row;
    $stmt->close();
}

if (empty($teacherClassesSubjects)) {
    // Fallback: all classes/subjects for school (if no timetable)
    $stmt = $conn->prepare("SELECT id AS class_id FROM classes WHERE school_id=? ORDER BY name");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $res = $stmt->get_result();
    $subs = [];
    $stmt2 = $conn->prepare("SELECT id AS subject_id FROM subjects WHERE school_id=? ORDER BY name");
    $stmt2->bind_param('i', $schoolId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($r = $res2->fetch_assoc()) $subs[] = $r['subject_id'];
    $stmt2->close();
    while ($r = $res->fetch_assoc()) {
        foreach ($subs as $sid) $teacherClassesSubjects[] = ['class_id' => $r['class_id'], 'subject_id' => $sid];
    }
    $stmt->close();
}

$classIds = array_unique(array_column($teacherClassesSubjects, 'class_id'));
$subjectIds = array_unique(array_column($teacherClassesSubjects, 'subject_id'));

if ($tablesExist) {
    $stmt = $conn->prepare("SELECT id, name, weight FROM exam_types WHERE school_id=? ORDER BY name");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $examTypes[] = $row;
    $stmt->close();

    foreach ($classIds as $cid) {
        $stmt = $conn->prepare("SELECT id, name, section FROM classes WHERE id=? AND school_id=?");
        $stmt->bind_param('ii', $cid, $schoolId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) $classes[] = $row;
    }
    usort($classes, fn($a,$b) => strcmp($a['name'].$a['section'], $b['name'].$b['section']));

    foreach ($subjectIds as $sid) {
        $stmt = $conn->prepare("SELECT id, name FROM subjects WHERE id=? AND school_id=?");
        $stmt->bind_param('ii', $sid, $schoolId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) $subjects[] = $row;
    }
    usort($subjects, fn($a,$b) => strcmp($a['name'], $b['name']));
}

$filterClass = (int) ($_GET['class_id'] ?? 0);
$filterExam  = (int) ($_GET['exam_type_id'] ?? 0);
$filterSubj  = (int) ($_GET['subject_id'] ?? 0);

// Ensure filters are within teacher's scope
if ($filterClass && !in_array($filterClass, $classIds)) $filterClass = 0;
if ($filterSubj && !in_array($filterSubj, $subjectIds)) $filterSubj = 0;

if ($filterClass) {
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM students WHERE school_id=? AND class_id=? ORDER BY first_name, last_name");
    $stmt->bind_param('ii', $schoolId, $filterClass);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $students[] = $row;
    $stmt->close();
}

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $teacherId && $tablesExist) {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_grades') {
        $classId = (int) ($_POST['class_id'] ?? 0);
        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        $examTypeId = (int) ($_POST['exam_type_id'] ?? 0);
        if (!$classId || !$subjectId || !$examTypeId || !in_array($classId, $classIds) || !in_array($subjectId, $subjectIds)) {
            $errors[] = 'Invalid class, subject or exam type.';
        } else {
            $scores = $_POST['scores'] ?? [];
            $maxScore = (float) ($_POST['max_score'] ?? 100);
            $saved = 0;
            foreach ($scores as $studentId => $score) {
                $studentId = (int) $studentId;
                $score = trim($score);
                if ($studentId <= 0) continue;
                $numScore = $score === '' ? null : (float) $score;
                if ($numScore === null) continue;
                $stmt = $conn->prepare("INSERT INTO grades (school_id, student_id, subject_id, class_id, exam_type_id, score, max_score) VALUES (?,?,?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE score=VALUES(score), max_score=VALUES(max_score)");
                $stmt->bind_param('iiiiidd', $schoolId, $studentId, $subjectId, $classId, $examTypeId, $numScore, $maxScore);
                $stmt->execute();
                $saved += $stmt->affected_rows;
                $stmt->close();
            }
            $success = "Grades saved. ($saved record(s) updated.)";
        }
    }
}

// Load grades for current filter
if ($tablesExist && $filterClass && $filterSubj) {
    $sql = "SELECT g.id, g.student_id, g.subject_id, g.exam_type_id, g.score, g.max_score, g.class_id,
                   CONCAT(s.first_name,' ',s.last_name) AS student_name
            FROM grades g
            JOIN students s ON s.id=g.student_id AND s.school_id=g.school_id
            WHERE g.school_id=? AND g.class_id=? AND g.subject_id=?";
    $params = [$schoolId, $filterClass, $filterSubj];
    $types = 'iii';
    if ($filterExam) { $sql .= " AND g.exam_type_id=?"; $params[] = $filterExam; $types .= 'i'; }
    $sql .= " ORDER BY s.first_name, s.last_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $gradesByStudent = [];
    while ($row = $res->fetch_assoc()) $gradesByStudent[$row['student_id']][$row['exam_type_id']] = $row;
    $stmt->close();
}
?>

<?php if (!$teacherId): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-800">Your account is not linked to a teacher record. Please contact the admin.</p>
</div>
<?php elseif (!$tablesExist): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-700">Run <code class="bg-amber-100 px-1 rounded">database_migration_grades_lesson_plans.sql</code> first.</p>
</div>
<?php else: ?>

<?php if ($errors): ?>
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700"><?= htmlspecialchars(implode(' ', $errors)) ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h2 class="text-sm font-semibold text-slate-800 mb-4">Grading workspace</h2>
    <form method="get" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Class</label>
            <select name="class_id" class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700">
                <option value="">Select class</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $filterClass === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name'] . ($c['section'] ? ' ' . $c['section'] : '')) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Subject</label>
            <select name="subject_id" class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700">
                <option value="">Select subject</option>
                <?php foreach ($subjects as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= $filterSubj === (int)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Exam type</label>
            <select name="exam_type_id" class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700">
                <option value="">All exams</option>
                <?php foreach ($examTypes as $e): ?>
                <option value="<?= (int)$e['id'] ?>" <?= $filterExam === (int)$e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="border border-emerald-600 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-3 py-1.5 text-xs font-medium">Filter</button>
    </form>
</div>

<?php
$examIdForInput = $filterExam;
$canSave = $filterClass && $filterSubj && $examIdForInput && count($examTypes) > 0;
?>
<?php if ($filterClass && $filterSubj): ?>
<form method="post">
    <input type="hidden" name="action" value="save_grades">
    <input type="hidden" name="class_id" value="<?= $filterClass ?>">
    <input type="hidden" name="subject_id" value="<?= $filterSubj ?>">
    <input type="hidden" name="exam_type_id" value="<?= $examIdForInput ?>">
    <input type="hidden" name="max_score" value="100">

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-800">Grade book</span>
            <?php if ($canSave): ?>
            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">
                <i data-lucide="save" class="w-3.5 h-3.5"></i>
                Save grades
            </button>
            <?php else: ?>
            <span class="text-[11px] text-slate-500">Select an exam type above to add or edit grades.</span>
            <?php endif; ?>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left text-slate-700">
                <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">#</th>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Student</th>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">Score</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $idx = 0;
                foreach ($students as $st):
                    $idx++;
                    $sid = (int)$st['id'];
                    $existing = $gradesByStudent[$sid] ?? [];
                    $g = $examIdForInput ? ($existing[$examIdForInput] ?? null) : null;
                    $val = $g ? $g['score'] : '';
                ?>
                <tr class="border-b border-slate-100">
                    <td class="px-4 py-2 text-slate-400"><?= $idx ?></td>
                    <td class="px-4 py-2 font-medium"><?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?></td>
                    <td class="px-2 py-2">
                        <?php if ($canSave): ?>
                        <input type="number" name="scores[<?= $sid ?>]" min="0" max="100" step="0.01" placeholder="—"
                               value="<?= $val !== '' ? htmlspecialchars($val) : '' ?>"
                               class="w-20 border border-slate-200 rounded px-2 py-1 text-[11px] text-center">
                        <?php else: ?>
                        <span class="text-slate-600"><?= $val !== '' ? $val : '—' ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>
<?php else: ?>
<div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500 text-sm">
    Select class and subject to load the grade book.
</div>
<?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>

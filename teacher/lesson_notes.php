<?php
$page_title = 'Lesson Notes';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

// Resolve teacher_id from logged-in user
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
$res = $conn->query("SHOW TABLES LIKE 'lesson_plans'");
$tablesExist = $res && $res->num_rows > 0;

if ($tablesExist) {
    $resCol = $conn->query("SHOW COLUMNS FROM lesson_plans LIKE 'file_path'");
    if ($resCol && $resCol->num_rows === 0) {
        $conn->query("ALTER TABLE lesson_plans ADD COLUMN file_path VARCHAR(255) NULL AFTER resources");
    }
}

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
    $stmt = $conn->prepare("SELECT c.id AS class_id, s.id AS subject_id FROM classes c, subjects s WHERE c.school_id=? AND s.school_id=?");
    $stmt->bind_param('ii', $schoolId, $schoolId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $teacherClassesSubjects[] = $r;
    $stmt->close();
}

$classIds = array_unique(array_column($teacherClassesSubjects, 'class_id'));
$subjectIds = array_unique(array_filter(array_column($teacherClassesSubjects, 'subject_id')));
$classes = [];
$subjects = [];
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
    if (!$sid) continue;
    $stmt = $conn->prepare("SELECT id, name FROM subjects WHERE id=? AND school_id=?");
    $stmt->bind_param('ii', $sid, $schoolId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) $subjects[] = $row;
}
if (empty($subjects)) {
    $stmt = $conn->prepare("SELECT id, name FROM subjects WHERE school_id=? ORDER BY name");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $subjects[] = $row;
    $stmt->close();
}
usort($subjects, fn($a,$b) => strcmp($a['name'], $b['name']));

// Week start = Monday; generate options
$weeks = [];
for ($i = -4; $i <= 4; $i++) {
    $monday = date('Y-m-d', strtotime('monday this week + ' . ($i * 7) . ' days'));
    $weeks[] = $monday;
}

$filterClass = (int) ($_GET['class_id'] ?? 0);
$filterSubj  = (int) ($_GET['subject_id'] ?? 0);
$filterWeek  = trim($_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week')));

if ($filterClass && !in_array($filterClass, $classIds)) $filterClass = 0;
if ($filterSubj && !in_array($filterSubj, array_column($subjects, 'id'))) $filterSubj = 0;

$editPlan = null;
if ($tablesExist && $teacherId && $filterClass && $filterSubj && $filterWeek) {
    $stmt = $conn->prepare("SELECT * FROM lesson_plans WHERE school_id=? AND teacher_id=? AND class_id=? AND subject_id=? AND week_start=? LIMIT 1");
    $stmt->bind_param('iiiss', $schoolId, $teacherId, $filterClass, $filterSubj, $filterWeek);
    $stmt->execute();
    $editPlan = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// List of plans for sidebar
$plansList = [];
if ($tablesExist && $teacherId && $filterClass && $filterSubj) {
    $stmt = $conn->prepare("SELECT id, week_start, topic, status FROM lesson_plans WHERE school_id=? AND teacher_id=? AND class_id=? AND subject_id=? ORDER BY week_start DESC LIMIT 12");
    $stmt->bind_param('iiii', $schoolId, $teacherId, $filterClass, $filterSubj);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $plansList[] = $row;
    $stmt->close();
}

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $teacherId && $tablesExist) {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_plan') {
        $classId = (int) ($_POST['class_id'] ?? 0);
        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        $weekStart = trim($_POST['week_start'] ?? '');
        $topic = trim($_POST['topic'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['draft','submitted','approved']) ? $_POST['status'] : 'draft';

        $ok = $classId && $subjectId && $weekStart && $topic;
        $ok = $ok && in_array($classId, $classIds);
        $subjIds = array_column($subjects, 'id');
        $ok = $ok && in_array($subjectId, $subjIds);

        if (!$ok) {
            $errors[] = 'Invalid class, subject, week or topic.';
        } else {
            $objectives = trim($_POST['objectives'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $resources = trim($_POST['resources'] ?? '');

            // Get old file path to replace if needed
            $oldFilePath = null;
            $stmtOld = $conn->prepare("SELECT file_path FROM lesson_plans WHERE school_id=? AND teacher_id=? AND class_id=? AND subject_id=? AND week_start=?");
            $stmtOld->bind_param('iiiis', $schoolId, $teacherId, $classId, $subjectId, $weekStart);
            $stmtOld->execute();
            $rowOld = $stmtOld->get_result()->fetch_assoc();
            $stmtOld->close();
            if ($rowOld) {
                $oldFilePath = $rowOld['file_path'];
            }
            $filePath = $oldFilePath;

            if (!empty($_FILES['lesson_note']['name']) && $_FILES['lesson_note']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['lesson_note']['size'] > 3 * 1024 * 1024) {
                    $errors[] = 'Lesson note file cannot exceed 3MB.';
                } else {
                    $uploadDir = dirname(__DIR__) . '/storage/lesson_notes/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $ext = strtolower(pathinfo($_FILES['lesson_note']['name'], PATHINFO_EXTENSION));
                    $allowedExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'xls', 'xlsx'];
                    if (in_array($ext, $allowedExts)) {
                        $filename = 'note_' . $schoolId . '_' . $teacherId . '_' . $classId . '_' . $subjectId . '_' . uniqid() . '.' . $ext;
                        if (move_uploaded_file($_FILES['lesson_note']['tmp_name'], $uploadDir . $filename)) {
                            $filePath = 'storage/lesson_notes/' . $filename;
                            
                            // Delete old file if exists and different
                            if ($oldFilePath && file_exists(dirname(__DIR__) . '/' . $oldFilePath)) {
                                unlink(dirname(__DIR__) . '/' . $oldFilePath);
                            }
                        } else {
                            $errors[] = 'Failed to save uploaded lesson note.';
                        }
                    } else {
                        $errors[] = 'Invalid file format. Only PDF, Word, Excel, PowerPoint, and TXT are allowed.';
                    }
                }
            }

            if (!$errors) {
                $stmt = $conn->prepare("INSERT INTO lesson_plans (school_id, teacher_id, class_id, subject_id, week_start, topic, objectives, content, resources, status, file_path)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE topic=VALUES(topic), objectives=VALUES(objectives), content=VALUES(content), resources=VALUES(resources), status=VALUES(status), file_path=VALUES(file_path)");
                $stmt->bind_param('iiiisssssss', $schoolId, $teacherId, $classId, $subjectId, $weekStart, $topic, $objectives, $content, $resources, $status, $filePath);
                $stmt->execute();
                $stmt->close();
                $success = 'Lesson plan saved.';
                $editPlan = [
                    'topic' => $topic,
                    'objectives' => $objectives,
                    'content' => $content,
                    'resources' => $resources,
                    'status' => $status,
                    'file_path' => $filePath,
                ] + ($editPlan ?? []);
            }
        }
    }
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
    <h2 class="text-sm font-semibold text-slate-800 mb-4">Lesson notes workspace</h2>
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
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Week (Monday)</label>
            <select name="week_start" class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700">
                <?php foreach ($weeks as $w): ?>
                <option value="<?= htmlspecialchars($w) ?>" <?= $filterWeek === $w ? 'selected' : '' ?>><?= date('d M Y', strtotime($w)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="border border-emerald-600 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-3 py-1.5 text-xs font-medium">Filter</button>
    </form>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 flex flex-col">
        <?php if ($filterClass && $filterSubj): ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_plan">
            <input type="hidden" name="class_id" value="<?= $filterClass ?>">
            <input type="hidden" name="subject_id" value="<?= $filterSubj ?>">
            <input type="hidden" name="week_start" value="<?= htmlspecialchars($filterWeek) ?>">

            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-800">Lesson note editor — Week of <?= date('d M Y', strtotime($filterWeek)) ?></span>
                <div class="flex items-center gap-2">
                    <select name="status" class="border border-slate-200 rounded-lg px-2 py-1 text-[10px] text-slate-700">
                        <option value="draft" <?= ($editPlan['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="submitted" <?= ($editPlan['status'] ?? '') === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                        <option value="approved" <?= ($editPlan['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                    </select>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">
                        <i data-lucide="save" class="w-3.5 h-3.5"></i>
                        Save
                    </button>
                </div>
            </div>
            <div class="p-4 space-y-3 overflow-y-auto">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Topic *</label>
                    <input type="text" name="topic" required value="<?= htmlspecialchars($editPlan['topic'] ?? '') ?>" placeholder="e.g. Integers and Number Lines"
                           class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-700">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Lesson objectives</label>
                    <textarea name="objectives" rows="3" placeholder="By the end of the lesson, students should be able to..."
                              class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-700"><?= htmlspecialchars($editPlan['objectives'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Content / activities</label>
                    <textarea name="content" rows="5" placeholder="Step-by-step teacher and student activities..."
                              class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-700"><?= htmlspecialchars($editPlan['content'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Resources / materials</label>
                    <textarea name="resources" rows="2" placeholder="e.g. Number line chart, marker, projector..."
                              class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-700"><?= htmlspecialchars($editPlan['resources'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Upload Lesson Note Document (Max: 3MB)</label>
                    <?php if (!empty($editPlan['file_path'])): ?>
                    <div class="mb-2 text-xs flex items-center gap-2">
                        <i data-lucide="file-check" class="w-4 h-4 text-emerald-600"></i>
                        <a href="../<?= htmlspecialchars($editPlan['file_path']) ?>" target="_blank" class="text-emerald-600 hover:underline">View Current Document</a>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="lesson_note" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.xls,.xlsx"
                           class="block w-full text-[11px] text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-[11px] file:font-medium hover:file:bg-indigo-100">
                </div>
            </div>
        </form>
        <?php else: ?>
        <div class="px-4 py-3 border-b border-slate-100">
            <span class="text-xs font-semibold text-slate-800">Lesson note editor</span>
        </div>
        <div class="p-8 text-center text-slate-500 text-sm">Select class and subject to create or edit a lesson plan.</div>
        <?php endif; ?>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">Plans for this class & subject</h3>
            <?php if (empty($plansList)): ?>
            <p class="text-[11px] text-slate-500">No lesson plans yet. Create one using the editor.</p>
            <?php else: ?>
            <div class="space-y-1 text-[11px]">
                <?php foreach ($plansList as $p): ?>
                <a href="?class_id=<?= $filterClass ?>&subject_id=<?= $filterSubj ?>&week_start=<?= urlencode($p['week_start']) ?>" class="flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-200 text-left text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 block">
                    <span>
                        <?= date('d M', strtotime($p['week_start'])) ?> — <?= htmlspecialchars($p['topic'] ?: '(Untitled)') ?>
                        <span class="block text-[10px] text-slate-400"><?= ucfirst($p['status']) ?></span>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>

<?php
$page_title = 'Subjects';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

// Resolve teacher_id from logged-in user
$teacherId = null;
$userId    = (int) ($_SESSION['user_id'] ?? 0);
if ($userId && $schoolId) {
    $stmt = $conn->prepare("
        SELECT t.id
        FROM teachers t
        JOIN users u
          ON u.email = t.email
         AND u.school_id = t.school_id
        WHERE u.id = ?
          AND u.role = 'teacher'
          AND u.school_id = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('ii', $userId, $schoolId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $teacherId = (int) $row['id'];
        }
    }
}

$classSubjectsForTeacher = [];
$allClassSubjects        = [];

if ($teacherId && $schoolId) {
    // Teacher-specific subject assignments (from teacher_class_subjects if available)
    $hasTcs = (bool) ($conn->query("SHOW TABLES LIKE 'teacher_class_subjects'")->num_rows ?? 0);
    if ($hasTcs) {
        $stmt = $conn->prepare("
            SELECT class_id, subject_id
            FROM teacher_class_subjects
            WHERE school_id = ? AND teacher_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param('ii', $schoolId, $teacherId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $cid = (int) $row['class_id'];
                $sid = (int) $row['subject_id'];
                if (!isset($classSubjectsForTeacher[$cid])) {
                    $classSubjectsForTeacher[$cid] = [];
                }
                if ($sid > 0 && !in_array($sid, $classSubjectsForTeacher[$cid], true)) {
                    $classSubjectsForTeacher[$cid][] = $sid;
                }
            }
            $stmt->close();
        }
    }

    // Timetable-driven assignments (complements TCS)
    $res = $conn->query("SHOW TABLES LIKE 'timetable_entries'");
    if ($res && $res->num_rows > 0) {
        $stmt = $conn->prepare("
            SELECT DISTINCT class_id, subject_id
            FROM timetable_entries
            WHERE school_id = ? AND teacher_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param('ii', $schoolId, $teacherId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $cid = (int) $row['class_id'];
                $sid = (int) $row['subject_id'];
                if (!isset($classSubjectsForTeacher[$cid])) {
                    $classSubjectsForTeacher[$cid] = [];
                }
                if ($sid > 0 && !in_array($sid, $classSubjectsForTeacher[$cid], true)) {
                    $classSubjectsForTeacher[$cid][] = $sid;
                }
            }
            $stmt->close();
        }
    }

    // For each class where the teacher teaches, fetch all subjects that class takes from timetable
    $classIds = array_keys($classSubjectsForTeacher);
    if ($classIds) {
        foreach ($classIds as $cid) {
            $allClassSubjects[$cid] = [
                'subjects_all'     => [],
                'subjects_teacher' => $classSubjectsForTeacher[$cid],
            ];

            // All subjects this class takes (any teacher) based on timetable_entries
            $res = $conn->query("SHOW TABLES LIKE 'timetable_entries'");
            if ($res && $res->num_rows > 0) {
                $stmt = $conn->prepare("
                    SELECT DISTINCT subject_id
                    FROM timetable_entries
                    WHERE school_id = ? AND class_id = ?
                ");
                if ($stmt) {
                    $stmt->bind_param('ii', $schoolId, $cid);
                    $stmt->execute();
                    $r = $stmt->get_result();
                    while ($row = $r->fetch_assoc()) {
                        $sid = (int) $row['subject_id'];
                        if ($sid > 0 && !in_array($sid, $allClassSubjects[$cid]['subjects_all'], true)) {
                            $allClassSubjects[$cid]['subjects_all'][] = $sid;
                        }
                    }
                    $stmt->close();
                }
            }
            // Fallback: if timetable not yet populated, assume only teacher's subjects for now
            if (empty($allClassSubjects[$cid]['subjects_all'])) {
                $allClassSubjects[$cid]['subjects_all'] = $classSubjectsForTeacher[$cid];
            }
        }
    }
}

// Load class and subject names
$classesById  = [];
$subjectsById = [];
if ($allClassSubjects) {
    $classIds = array_keys($allClassSubjects);
    foreach ($classIds as $cid) {
        $stmt = $conn->prepare("
            SELECT id, name, section
            FROM classes
            WHERE id = ? AND school_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param('ii', $cid, $schoolId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                $classesById[$cid] = $row;
            }
        }
    }

    $allSubjectIds = [];
    foreach ($allClassSubjects as $info) {
        $allSubjectIds = array_merge($allSubjectIds, $info['subjects_all']);
    }
    $allSubjectIds = array_unique(array_filter($allSubjectIds));
    foreach ($allSubjectIds as $sid) {
        $stmt = $conn->prepare("
            SELECT id, name, code
            FROM subjects
            WHERE id = ? AND school_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param('ii', $sid, $schoolId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                $subjectsById[$sid] = $row;
            }
        }
    }
}

// Build a simpler structure: per subject, list of classes where teacher teaches it
$subjectsForTeacher = [];
foreach ($allClassSubjects as $cid => $info) {
    $teacherSubIds = $info['subjects_teacher'] ?? [];
    foreach ($teacherSubIds as $sid) {
        if (!isset($subjectsById[$sid], $classesById[$cid])) continue;
        if (!isset($subjectsForTeacher[$sid])) {
            $subjectsForTeacher[$sid] = [
                'subject' => $subjectsById[$sid],
                'classes' => [],
            ];
        }
        $subjectsForTeacher[$sid]['classes'][] = $classesById[$cid];
    }
}

// Sort subjects alphabetically
uasort($subjectsForTeacher, function ($a, $b) {
    return strcmp($a['subject']['name'], $b['subject']['name']);
});
?>

<?php if (!$teacherId): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-800">Your account is not linked to a teacher record. Please contact the admin.</p>
</div>
<?php elseif (empty($subjectsForTeacher)): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-800">
        No subjects have been assigned to you yet. Once the admin assigns you to subjects on the timetable or in
        the teacher-class-subjects mapping, they will appear here.
    </p>
</div>
<?php else: ?>

<!-- Header -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">Subjects you teach</h2>
            <p class="text-[11px] text-slate-500">
                For each subject below, you can see the classes that take it and where you are the teacher.
            </p>
        </div>
        <div class="text-[11px] text-slate-500">
            <?= count($subjectsForTeacher) ?> subject<?= count($subjectsForTeacher) === 1 ? '' : 's' ?> linked to you.
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-500">
        <span class="inline-flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>Your subject</span>
        </span>
        <span class="inline-flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
            <span>Other class subjects (from timetable)</span>
        </span>
    </div>
</div>

<!-- Subjects grid -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <?php foreach ($subjectsForTeacher as $sid => $info): ?>
    <?php
        $sub = $info['subject'];
        $classes = $info['classes'];
    ?>
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col gap-3">
        <div class="flex items-start justify-between gap-2">
            <div>
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-[10px] font-medium text-emerald-700">
                        <?= htmlspecialchars($sub['name']) ?>
                        <?php if (!empty($sub['code'])): ?>
                            (<?= htmlspecialchars($sub['code']) ?>)
                        <?php endif; ?>
                    </span>
                </div>
                <div class="mt-1 text-[11px] text-slate-500">
                    You teach this subject in <?= count($classes) ?> class<?= count($classes) === 1 ? '' : 'es' ?>.
                </div>
            </div>
        </div>

        <div class="space-y-1 text-[11px] text-slate-600">
            <?php foreach ($classes as $c): ?>
            <?php $label = $c['name'] . ($c['section'] ? ' ' . $c['section'] : ''); ?>
            <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-1.5">
                <div>
                    <div class="font-medium text-slate-800"><?= htmlspecialchars($label) ?></div>
                    <div class="text-[10px] text-slate-400">Class ID: <?= (int) $c['id'] ?></div>
                </div>
                <div class="flex items-center gap-1">
                    <a href="attendance.php?class_id=<?= (int) $c['id'] ?>"
                       class="inline-flex items-center justify-center w-6 h-6 rounded-full border border-slate-200 text-slate-500 hover:border-emerald-300 hover:text-emerald-700"
                       title="Attendance">
                        <i data-lucide="calendar-check" class="w-3 h-3"></i>
                    </a>
                    <a href="grades.php?class_id=<?= (int) $c['id'] ?>&subject_id=<?= (int) $sid ?>"
                       class="inline-flex items-center justify-center w-6 h-6 rounded-full border border-slate-200 text-slate-500 hover:border-violet-300 hover:text-violet-700"
                       title="Grades">
                        <i data-lucide="clipboard-list" class="w-3 h-3"></i>
                    </a>
                    <a href="lesson_notes.php?class_id=<?= (int) $c['id'] ?>&subject_id=<?= (int) $sid ?>"
                       class="inline-flex items-center justify-center w-6 h-6 rounded-full border border-slate-200 text-slate-500 hover:border-amber-300 hover:text-amber-700"
                       title="Lesson notes">
                        <i data-lucide="file-text" class="w-3 h-3"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>


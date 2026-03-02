<?php
$page_title = 'Classes';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

// Resolve teacher_id from logged-in user (users.email = teachers.email)
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

$classes = [];

if ($teacherId && $schoolId) {
    // Prefer explicit teacher_class_subjects mapping when available
    $hasTcs = (bool) ($conn->query("SHOW TABLES LIKE 'teacher_class_subjects'")->num_rows ?? 0);
    $assignments = [];

    if ($hasTcs) {
        $stmt = $conn->prepare("
            SELECT tcs.class_id, tcs.subject_id
            FROM teacher_class_subjects tcs
            WHERE tcs.school_id = ? AND tcs.teacher_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param('ii', $schoolId, $teacherId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $cid = (int) $row['class_id'];
                $sid = (int) $row['subject_id'];
                if (!isset($assignments[$cid])) {
                    $assignments[$cid] = ['subjects' => []];
                }
                if ($sid > 0) {
                    $assignments[$cid]['subjects'][] = $sid;
                }
            }
            $stmt->close();
        }
    }

    // Also consider timetable_entries as a source of class/subject load
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
                if (!isset($assignments[$cid])) {
                    $assignments[$cid] = ['subjects' => []];
                }
                if ($sid > 0 && !in_array($sid, $assignments[$cid]['subjects'], true)) {
                    $assignments[$cid]['subjects'][] = $sid;
                }
            }
            $stmt->close();
        }
    }

    if ($assignments) {
        $classIds   = array_keys($assignments);
        $subjectIds = [];
        foreach ($assignments as $a) {
            $subjectIds = array_merge($subjectIds, $a['subjects']);
        }
        $subjectIds = array_unique(array_filter($subjectIds));

        // Load class details
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
                    $classes[$cid] = [
                        'id'        => (int) $row['id'],
                        'name'      => $row['name'],
                        'section'   => $row['section'],
                        'subjects'  => [],
                        'students'  => 0,
                    ];
                }
            }
        }

        // Load subjects
        $subjectsById = [];
        if ($subjectIds) {
            foreach ($subjectIds as $sid) {
                $stmt = $conn->prepare("
                    SELECT id, name
                    FROM subjects
                    WHERE id = ? AND school_id = ?
                ");
                if ($stmt) {
                    $stmt->bind_param('ii', $sid, $schoolId);
                    $stmt->execute();
                    $row = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    if ($row) {
                        $subjectsById[(int) $row['id']] = $row['name'];
                    }
                }
            }
        }

        // Attach subjects to classes
        foreach ($assignments as $cid => $a) {
            if (!isset($classes[$cid])) continue;
            $names = [];
            foreach ($a['subjects'] as $sid) {
                if (isset($subjectsById[$sid])) {
                    $names[] = $subjectsById[$sid];
                }
            }
            sort($names, SORT_NATURAL | SORT_FLAG_CASE);
            $classes[$cid]['subjects'] = $names;
        }

        // Student counts per class
        foreach (array_keys($classes) as $cid) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) AS c
                FROM students
                WHERE school_id = ? AND class_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param('ii', $schoolId, $cid);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $classes[$cid]['students'] = (int) ($row['c'] ?? 0);
            }
        }

        // Sort classes by name/section
        usort($classes, function ($a, $b) {
            return strcmp($a['name'] . ($a['section'] ?? ''), $b['name'] . ($b['section'] ?? ''));
        });
    }
}
?>

<?php if (!$teacherId): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-800">Your account is not linked to a teacher record. Please contact the admin.</p>
</div>
<?php elseif (empty($classes)): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-800">
        No classes have been assigned to you yet. Once the admin assigns you to classes and subjects, they will appear here.
    </p>
</div>
<?php else: ?>

<!-- Header -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">Classes assigned to you</h2>
            <p class="text-[11px] text-slate-500">
                These are classes where you are scheduled on the timetable or explicitly assigned by the admin.
            </p>
        </div>
        <div class="text-[11px] text-slate-500">
            <?= count($classes) ?> class<?= count($classes) === 1 ? '' : 'es' ?> linked to your account.
        </div>
    </div>
    <div class="text-[11px] text-slate-500">
        <span class="font-medium text-slate-600">Tip:</span>
        Use the shortcuts on each card to jump straight into attendance, grading or lesson notes for that class.
    </div>
</div>

<!-- Classes list -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <?php foreach ($classes as $c): ?>
    <?php
        $label = $c['name'] . ($c['section'] ? ' ' . $c['section'] : '');
        $subjectsLabel = $c['subjects']
            ? implode(', ', $c['subjects'])
            : 'Subjects assigned by admin';
    ?>
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col gap-3">
        <div class="flex items-start justify-between gap-2">
            <div>
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-[10px] font-medium text-emerald-700"><?= htmlspecialchars($label) ?></span>
                </div>
                <div class="mt-1 text-[11px] text-slate-500">
                    Class ID: <span class="font-medium text-slate-700"><?= (int) $c['id'] ?></span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-[10px] text-slate-400">Students</div>
                <div class="text-base font-bold text-slate-800"><?= (int) $c['students'] ?></div>
            </div>
        </div>

        <div class="flex items-center justify-between text-[11px] text-slate-500">
            <span>Subjects you teach:
                <span class="font-medium text-slate-700">
                    <?= htmlspecialchars($subjectsLabel) ?>
                </span>
            </span>
        </div>

        <div class="flex flex-wrap gap-2 mt-1">
            <a href="attendance.php?class_id=<?= (int) $c['id'] ?>"
               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
                <i data-lucide="calendar-check" class="w-3 h-3"></i>
                <span>Attendance</span>
            </a>
            <a href="grades.php?class_id=<?= (int) $c['id'] ?>"
               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700">
                <i data-lucide="clipboard-list" class="w-3 h-3"></i>
                <span>Grades</span>
            </a>
            <a href="lesson_notes.php?class_id=<?= (int) $c['id'] ?>"
               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700">
                <i data-lucide="file-text" class="w-3 h-3"></i>
                <span>Lesson notes</span>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>


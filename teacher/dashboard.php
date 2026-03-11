<?php
$page_title = 'Dashboard';
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

$classesAssigned    = 0;
$lessonsToday       = 0;
$attendanceMarked   = 0;
$gradingPending     = 0;
$subjectsSummary    = [];
$todaySchedule      = [];

if ($teacherId && $schoolId) {
    // Determine classes & subjects from timetable_entries + teacher_class_subjects
    $classIds   = [];
    $subjectIds = [];

    // From timetable_entries (primary source)
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
                if ($cid > 0 && !in_array($cid, $classIds, true)) $classIds[] = $cid;
                if ($sid > 0 && !in_array($sid, $subjectIds, true)) $subjectIds[] = $sid;
            }
            $stmt->close();
        }
    }

    // Complement from teacher_class_subjects when available
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
                if ($cid > 0 && !in_array($cid, $classIds, true)) $classIds[] = $cid;
                if ($sid > 0 && !in_array($sid, $subjectIds, true)) $subjectIds[] = $sid;
            }
            $stmt->close();
        }
    }

    $classesAssigned = count($classIds);

    // Lessons today from timetable_entries
    $res = $conn->query("SHOW TABLES LIKE 'timetable_entries'");
    if ($res && $res->num_rows > 0) {
        $todayDow = (int) date('N'); // 1..7
        if ($todayDow >= 1 && $todayDow <= 5) {
            $stmt = $conn->prepare("
                SELECT e.class_id, e.subject_id, e.day_of_week, e.period_order,
                       c.name AS class_name, c.section AS class_section,
                       s.name AS subject_name
                FROM timetable_entries e
                LEFT JOIN classes c
                  ON c.id = e.class_id
                 AND c.school_id = e.school_id
                LEFT JOIN subjects s
                  ON s.id = e.subject_id
                 AND s.school_id = e.school_id
                WHERE e.school_id = ?
                  AND e.teacher_id = ?
                  AND e.day_of_week = ?
                ORDER BY e.period_order
            ");
            if ($stmt) {
                $stmt->bind_param('iii', $schoolId, $teacherId, $todayDow);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $todaySchedule[] = $row;
                }
                $stmt->close();
            }
        }
        $lessonsToday = count($todaySchedule);
    }

    // Attendance marked today (per class) from attendance table
    $res = $conn->query("SHOW TABLES LIKE 'attendance'");
    if ($res && $res->num_rows > 0 && $classIds) {
        $today = date('Y-m-d');
        $placeholders = implode(',', array_fill(0, count($classIds), '?'));
        // school_id (int), date (string), then one int per class_id
        $types = 'is' . str_repeat('i', count($classIds));
        $sql = "
            SELECT COUNT(DISTINCT class_id) AS c
            FROM attendance
            WHERE school_id = ?
              AND date = ?
              AND class_id IN ($placeholders)
        ";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $params = array_merge([$schoolId, $today], $classIds);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $attendanceMarked = (int) ($row['c'] ?? 0);
        }
    }

    // Grading pending: approximate as students in your classes minus students who have at least one grade
    $totalStudentsTaught = 0;
    if ($classIds) {
        $placeholders = implode(',', array_fill(0, count($classIds), '?'));
        $types = 'i' . str_repeat('i', count($classIds));
        $sql = "
            SELECT COUNT(*) AS c
            FROM students
            WHERE school_id = ?
              AND class_id IN ($placeholders)
        ";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $params = array_merge([$schoolId], $classIds);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $totalStudentsTaught = (int) ($row['c'] ?? 0);
        }
    }

    $studentsWithGrades = 0;
    $res = $conn->query("SHOW TABLES LIKE 'grades'");
    if ($res && $res->num_rows > 0 && $classIds && $subjectIds) {
        $classPh   = implode(',', array_fill(0, count($classIds), '?'));
        $subjectPh = implode(',', array_fill(0, count($subjectIds), '?'));
        $types = 'i' . str_repeat('i', count($classIds)) . str_repeat('i', count($subjectIds));
        $sql = "
            SELECT COUNT(DISTINCT student_id) AS c
            FROM grades
            WHERE school_id = ?
              AND class_id IN ($classPh)
              AND subject_id IN ($subjectPh)
        ";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $params = array_merge([$schoolId], $classIds, $subjectIds);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $studentsWithGrades = (int) ($row['c'] ?? 0);
        }
    }
    $gradingPending = max(0, $totalStudentsTaught - $studentsWithGrades);

    // Subjects quick summary: subject name + classes
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
                    $subjectsSummary[$sid] = [
                        'id'      => (int) $row['id'],
                        'name'    => $row['name'],
                        'classes' => [],
                    ];
                }
            }
        }

        // Which classes for each subject
        $res = $conn->query("SHOW TABLES LIKE 'timetable_entries'");
        if ($res && $res->num_rows > 0) {
            $subjectPh = implode(',', array_fill(0, count($subjectIds), '?'));
            $types = 'ii' . str_repeat('i', count($subjectIds));
            $params = array_merge([$schoolId, $teacherId], $subjectIds);
            $sql = "
                SELECT DISTINCT subject_id, class_id
                FROM timetable_entries
                WHERE school_id = ?
                  AND teacher_id = ?
                  AND subject_id IN ($subjectPh)
            ";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $sid = (int) $row['subject_id'];
                    $cid = (int) $row['class_id'];
                    if (!isset($subjectsSummary[$sid])) continue;
                    if (!in_array($cid, $subjectsSummary[$sid]['classes'], true)) {
                        $subjectsSummary[$sid]['classes'][] = $cid;
                    }
                }
                $stmt->close();
            }
        }

        // Load class names for summary
        if ($subjectsSummary) {
            $allClassIds = [];
            foreach ($subjectsSummary as $s) {
                $allClassIds = array_merge($allClassIds, $s['classes']);
            }
            $allClassIds = array_unique($allClassIds);
            $classesById = [];
            foreach ($allClassIds as $cid) {
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
            // Replace class ids with labels
            foreach ($subjectsSummary as $sid => &$s) {
                $labels = [];
                foreach ($s['classes'] as $cid) {
                    if (!isset($classesById[$cid])) continue;
                    $row = $classesById[$cid];
                    $labels[] = $row['name'] . ($row['section'] ? ' ' . $row['section'] : '');
                }
                sort($labels, SORT_NATURAL | SORT_FLAG_CASE);
                $s['classes'] = $labels;
            }
            unset($s);
        }
    }
}
?>

<!-- Top: Today at a glance -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden mb-4">
    <div class="flex items-center justify-between gap-2.5 px-5 py-3.5 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                <i data-lucide="bar-chart-2" class="w-4 h-4 text-emerald-600"></i>
            </div>
            <span class="text-base font-bold text-slate-800">Today at a glance</span>
        </div>
        <span class="text-sm text-slate-400">
            Data shown is scoped to your assigned classes and subjects.
        </span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-slate-100">
        <div class="px-5 py-4">
            <div class="text-sm font-medium text-slate-500 mb-1.5">Classes assigned</div>
            <div class="text-2xl md:text-3xl font-bold text-emerald-600 mb-0.5"><?= $classesAssigned ?></div>
            <div class="text-sm text-slate-400">Total classes</div>
        </div>
        <div class="px-5 py-4">
            <div class="text-sm font-medium text-slate-500 mb-1.5">Lessons today</div>
            <div class="text-2xl md:text-3xl font-bold text-indigo-600 mb-0.5"><?= $lessonsToday ?></div>
            <div class="text-sm text-slate-400">Periods today</div>
        </div>
        <div class="px-5 py-4">
            <div class="text-sm font-medium text-slate-500 mb-1.5">Attendance marked</div>
            <div class="text-2xl md:text-3xl font-bold text-amber-500 mb-0.5">
                <?= $lessonsToday > 0 ? $attendanceMarked . '/' . $lessonsToday : $attendanceMarked ?>
            </div>
            <div class="text-sm text-slate-400">Classes completed</div>
        </div>
        <div class="px-5 py-4">
            <div class="text-sm font-medium text-slate-500 mb-1.5">Pending grades</div>
            <div class="text-2xl md:text-3xl font-bold text-rose-500 mb-0.5"><?= $gradingPending ?></div>
            <div class="text-sm text-slate-400">Students without grades</div>
        </div>
    </div>
</div>

<!-- Main content grid -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <!-- Teaching workspace & open items -->
    <div class="xl:col-span-2 space-y-4">
        <!-- Teaching workspace quick actions -->
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-bold text-slate-800">Your teaching workspace</h2>
                <span class="text-sm text-slate-400">Jump into the most common tasks</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                <a href="classes.php"
                   class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">
                    <i data-lucide="book-open" class="w-4 h-4 shrink-0"></i>
                    <div class="flex flex-col">
                        <span class="font-medium">Classes assigned to me</span>
                        <span class="text-xs text-slate-400 mt-0.5">See all classes &amp; shortcuts</span>
                    </div>
                </a>
                <a href="timetable.php"
                   class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700 transition-colors">
                    <i data-lucide="calendar" class="w-4 h-4 shrink-0"></i>
                    <div class="flex flex-col">
                        <span class="font-medium">View my timetable</span>
                        <span class="text-xs text-slate-400 mt-0.5">See periods for the week</span>
                    </div>
                </a>
                <a href="attendance.php"
                   class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                    <i data-lucide="calendar-check" class="w-4 h-4 shrink-0"></i>
                    <div class="flex flex-col">
                        <span class="font-medium">Record student attendance</span>
                        <span class="text-xs text-slate-400 mt-0.5">Mark present / late / absent</span>
                    </div>
                </a>
                <a href="subjects.php"
                   class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800 transition-colors">
                    <i data-lucide="layers" class="w-4 h-4 shrink-0"></i>
                    <div class="flex flex-col">
                        <span class="font-medium">Subjects I teach</span>
                        <span class="text-xs text-slate-400 mt-0.5">Per level &amp; stream</span>
                    </div>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                <a href="grades.php"
                   class="flex items-center justify-between px-3 py-2 rounded-lg border border-slate-200 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-800">
                    <div class="flex flex-col">
                        <span class="font-semibold text-slate-700">Enter / review grades</span>
                        <span class="text-xs text-slate-400 mt-0.5">Assignments, tests &amp; exams</span>
                    </div>
                    <i data-lucide="clipboard-list" class="w-4 h-4 shrink-0 text-violet-500"></i>
                </a>
                <a href="lesson_notes.php"
                   class="flex items-center justify-between px-3 py-2 rounded-lg border border-slate-200 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-800">
                    <div class="flex flex-col">
                        <span class="font-semibold text-slate-700">Prepare lesson notes</span>
                        <span class="text-xs text-slate-400 mt-0.5">Draft, submit &amp; track status</span>
                    </div>
                    <i data-lucide="file-text" class="w-4 h-4 shrink-0 text-amber-500"></i>
                </a>
                <a href="analytics.php"
                   class="flex items-center justify-between px-3 py-2 rounded-lg border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800">
                    <div class="flex flex-col">
                        <span class="font-semibold text-slate-700">View analytics</span>
                        <span class="text-xs text-slate-400 mt-0.5">Performance &amp; attendance trends</span>
                    </div>
                    <i data-lucide="activity" class="w-4 h-4 shrink-0 text-emerald-500"></i>
                </a>
            </div>
        </div>

        <!-- Open items (overview text, data-backed hint) -->
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold text-slate-800">Open items (overview)</h2>
                <span class="text-sm text-slate-400">Based on your classes and timetable</span>
            </div>
            <p class="text-sm text-slate-500 leading-relaxed">
                You currently have <strong><?= $gradingPending ?></strong> student<?= $gradingPending === 1 ? '' : 's' ?> across your classes
                without any recorded grade yet. Use the grading workspace to capture scores for recent tests and exams.
            </p>
        </div>
    </div>

    <!-- Right column: timetable, subjects, reports -->
    <div class="space-y-4">
        <!-- Today’s timetable -->
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-bold text-slate-800">Today&rsquo;s timetable</h2>
                <a href="timetable.php" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">View full</a>
            </div>
            <?php if (empty($todaySchedule)): ?>
            <p class="text-sm text-slate-500">
                You have no scheduled periods on the timetable for today.
            </p>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($todaySchedule as $slot): ?>
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-4 py-3 bg-slate-50/50">
                    <div>
                        <div class="font-bold text-slate-800 text-sm mb-0.5">
                            Period <?= (int) $slot['period_order'] ?>
                        </div>
                        <div class="text-xs text-slate-500">
                            <span class="font-medium text-slate-600"><?= htmlspecialchars($slot['subject_name'] ?? 'Subject') ?></span>
                            &middot;
                            <?= htmlspecialchars(($slot['class_name'] ?? 'Class') . (!empty($slot['class_section']) ? ' ' . $slot['class_section'] : '')) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Subjects quick summary -->
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-bold text-slate-800">Subjects you teach</h2>
                <a href="subjects.php" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">View all</a>
            </div>
            <?php if (empty($subjectsSummary)): ?>
            <p class="text-sm text-slate-500">
                No subjects have been linked to your account yet.
            </p>
            <?php else: ?>
            <div class="space-y-2.5">
                <?php foreach ($subjectsSummary as $s): ?>
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-4 py-2.5 bg-slate-50/50">
                    <div>
                        <div class="font-semibold text-slate-800 text-sm mb-0.5"><?= htmlspecialchars($s['name']) ?></div>
                        <div class="text-xs text-slate-500">
                            <?= $s['classes'] ? htmlspecialchars(implode(', ', $s['classes'])) : 'Class assignments via timetable' ?>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Subject
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Reports & analytics overview -->
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold text-slate-800">Reports &amp; analytics</h2>
                <div class="flex items-center gap-3">
                    <a href="reports.php" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">Open reports</a>
                    <a href="analytics.php" class="text-sm text-slate-500 hover:text-slate-700 font-medium">View analytics</a>
                </div>
            </div>
            <p class="text-sm text-slate-500 mb-1 leading-relaxed">
                Use reports and analytics to review attendance and performance trends for your assigned classes only.
            </p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>


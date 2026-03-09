<?php
$page_title = 'Reports';
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
          ON u.firebase_uid = CONCAT('local:teacher:', t.id)
         AND u.school_id = t.school_id
         AND u.role = 'teacher'
        WHERE u.id = ?
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

$hasAttendance = (bool) ($conn->query("SHOW TABLES LIKE 'attendance'")->num_rows ?? 0);
$hasGrades     = false;
$res = $conn->query("SHOW TABLES LIKE 'grades'");
if ($res && $res->num_rows > 0) {
    $res2 = $conn->query("SHOW TABLES LIKE 'exam_types'");
    $hasGrades = $res2 && $res2->num_rows > 0;
}

// Determine classes & subjects for this teacher (from timetable + teacher_class_subjects)
$classIdsByTeacher   = [];
$subjectIdsByClass   = []; // [class_id] => [subject_ids teacher teaches]

if ($teacherId && $schoolId) {
    // timetable_entries
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
                if (!in_array($cid, $classIdsByTeacher, true)) {
                    $classIdsByTeacher[] = $cid;
                }
                if ($sid > 0) {
                    if (!isset($subjectIdsByClass[$cid])) $subjectIdsByClass[$cid] = [];
                    if (!in_array($sid, $subjectIdsByClass[$cid], true)) {
                        $subjectIdsByClass[$cid][] = $sid;
                    }
                }
            }
            $stmt->close();
        }
    }

    // teacher_class_subjects as complement
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
                if (!in_array($cid, $classIdsByTeacher, true)) {
                    $classIdsByTeacher[] = $cid;
                }
                if ($sid > 0) {
                    if (!isset($subjectIdsByClass[$cid])) $subjectIdsByClass[$cid] = [];
                    if (!in_array($sid, $subjectIdsByClass[$cid], true)) {
                        $subjectIdsByClass[$cid][] = $sid;
                    }
                }
            }
            $stmt->close();
        }
    }
}

// Load classes and students for filters
$classes = [];
if ($classIdsByTeacher) {
    foreach ($classIdsByTeacher as $cid) {
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
                $classes[] = $row;
            }
        }
    }
    usort($classes, fn($a,$b) => strcmp($a['name'] . ($a['section'] ?? ''), $b['name'] . ($b['section'] ?? '')));
}

// Filters
$reportType = $_GET['report_type'] ?? 'grades'; // 'grades' or 'attendance'
$filterClassId = (int) ($_GET['class_id'] ?? 0);
if ($filterClassId && !in_array($filterClassId, $classIdsByTeacher, true)) {
    $filterClassId = 0;
}

$students = [];
if ($filterClassId) {
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name
        FROM students
        WHERE school_id = ? AND class_id = ?
        ORDER BY first_name, last_name
    ");
    if ($stmt) {
        $stmt->bind_param('ii', $schoolId, $filterClassId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
    }
}

$filterStudentId = (int) ($_GET['student_id'] ?? 0);
if ($filterStudentId && $students) {
    $ids = array_column($students, 'id');
    if (!in_array($filterStudentId, $ids, true)) {
        $filterStudentId = 0;
    }
}

// Academic years from grades (based on created_at year)
$years = [];
if ($hasGrades && $classIdsByTeacher) {
    $placeholders = implode(',', array_fill(0, count($classIdsByTeacher), '?'));
    $types = 'i' . str_repeat('i', count($classIdsByTeacher));
    $sql = "
        SELECT DISTINCT YEAR(created_at) AS y
        FROM grades
        WHERE school_id = ?
          AND class_id IN ($placeholders)
        ORDER BY y DESC
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $params = array_merge([$schoolId], $classIdsByTeacher);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['y'])) $years[] = (int) $row['y'];
        }
        $stmt->close();
    }
}

$currentYear = (int) date('Y');
$filterYear  = (int) ($_GET['year'] ?? ($years[0] ?? $currentYear));
if ($filterYear && $years && !in_array($filterYear, $years, true)) {
    $filterYear = $years[0];
}

// Exam types for this teacher's classes/subjects in selected year
$examTypes = [];
if ($hasGrades && $filterClassId && isset($subjectIdsByClass[$filterClassId])) {
    $subjectIds = $subjectIdsByClass[$filterClassId];
    if ($subjectIds) {
        $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
        $types = 'iii' . str_repeat('i', count($subjectIds));
        $sql = "
            SELECT DISTINCT et.id, et.name
            FROM grades g
            JOIN exam_types et
              ON et.id = g.exam_type_id
             AND et.school_id = g.school_id
            WHERE g.school_id = ?
              AND g.class_id = ?
              AND YEAR(g.created_at) = ?
              AND g.subject_id IN ($placeholders)
            ORDER BY et.name
        ";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $params = array_merge([$schoolId, $filterClassId, $filterYear], $subjectIds);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $examTypes[] = $row;
            }
            $stmt->close();
        }
    }
}

$filterExamTypeId = (int) ($_GET['exam_type_id'] ?? 0);
if ($filterExamTypeId && $examTypes) {
    $ids = array_column($examTypes, 'id');
    if (!in_array($filterExamTypeId, $ids, true)) {
        $filterExamTypeId = 0;
    }
}

// DATA QUERIES
$gradeRows      = [];
$attendanceRows = [];

if ($reportType === 'grades' && $hasGrades && $filterClassId && $filterStudentId && isset($subjectIdsByClass[$filterClassId])) {
    $subjectIds = $subjectIdsByClass[$filterClassId];
    if ($subjectIds) {
        $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
        $types = 'iii' . str_repeat('i', count($subjectIds));
        $sql = "
            SELECT g.score, g.max_score, g.created_at,
                   subj.name AS subject_name,
                   et.name   AS exam_name
            FROM grades g
            JOIN subjects subj
              ON subj.id = g.subject_id
             AND subj.school_id = g.school_id
            JOIN exam_types et
              ON et.id = g.exam_type_id
             AND et.school_id = g.school_id
            WHERE g.school_id = ?
              AND g.class_id = ?
              AND g.student_id = ?
              AND YEAR(g.created_at) = ?
              AND g.subject_id IN ($placeholders)
        ";
        $params = [$schoolId, $filterClassId, $filterStudentId, $filterYear];
        if ($filterExamTypeId) {
            $sql .= " AND g.exam_type_id = ?";
            $params[] = $filterExamTypeId;
            $types   .= 'i';
        }
        $sql .= " ORDER BY subj.name, et.name, g.created_at";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $params = array_merge($params, $subjectIds);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $gradeRows[] = $row;
            }
            $stmt->close();
        }
    }
}

if ($reportType === 'attendance' && $hasAttendance && $filterClassId && $filterStudentId) {
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT date, status, remarks
        FROM attendance
        WHERE school_id = ?
          AND class_id = ?
          AND student_id = ?
          AND date BETWEEN ? AND ?
        ORDER BY date
    ");
    if ($stmt) {
        $stmt->bind_param('iiiss', $schoolId, $filterClassId, $filterStudentId, $from, $to);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $attendanceRows[] = $row;
        }
        $stmt->close();
    }
}
?>

<?php if (!$teacherId): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-800">
        Your account is not linked to a teacher record. Please contact the admin if you believe this is an error.
    </p>
</div>
<?php else: ?>

<!-- Header & filters -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">Reports</h2>
            <p class="text-[11px] text-slate-500">
                View attendance or grade summaries for students in your classes. Use the filters to narrow by class, student,
                academic year and exam/term.
            </p>
        </div>

        <div class="flex flex-wrap gap-2 text-[11px]">
            <a href="?report_type=grades"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium <?= $reportType === 'grades' ? 'border-emerald-500 text-emerald-700 bg-emerald-50' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50' ?>">
                <i data-lucide="clipboard-list" class="w-3 h-3"></i>
                Grades
            </a>
            <a href="?report_type=attendance"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium <?= $reportType === 'attendance' ? 'border-emerald-500 text-emerald-700 bg-emerald-50' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50' ?>">
                <i data-lucide="calendar-check" class="w-3 h-3"></i>
                Attendance
            </a>
        </div>
    </div>

    <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-3 text-[11px]">
        <input type="hidden" name="report_type" value="<?= htmlspecialchars($reportType) ?>">
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Class</label>
            <select name="class_id" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700">
                <option value="">Select class</option>
                <?php foreach ($classes as $c): ?>
                    <?php $label = $c['name'] . ($c['section'] ? ' ' . $c['section'] : ''); ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $filterClassId === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Student</label>
            <select name="student_id" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700">
                <option value="">Select student</option>
                <?php foreach ($students as $st): ?>
                    <option value="<?= (int) $st['id'] ?>" <?= $filterStudentId === (int) $st['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($reportType === 'grades'): ?>
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Academic year</label>
            <select name="year" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700">
                <?php if (!$years): ?>
                    <option value="<?= (int) $filterYear ?>"><?= (int) $filterYear ?></option>
                <?php else: foreach ($years as $y): ?>
                    <option value="<?= (int) $y ?>" <?= $filterYear === (int) $y ? 'selected' : '' ?>>
                        <?= (int) $y ?>
                    </option>
                <?php endforeach; endif; ?>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Exam / term</label>
            <select name="exam_type_id" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700">
                <option value="">All exams / terms</option>
                <?php foreach ($examTypes as $e): ?>
                    <option value="<?= (int) $e['id'] ?>" <?= $filterExamTypeId === (int) $e['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">From</label>
            <input type="date" name="from" value="<?= htmlspecialchars($_GET['from'] ?? date('Y-m-01')) ?>"
                   class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700">
        </div>
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">To</label>
            <input type="date" name="to" value="<?= htmlspecialchars($_GET['to'] ?? date('Y-m-d')) ?>"
                   class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700">
        </div>
        <?php endif; ?>
        <div class="md:col-span-4 flex justify-end">
            <button type="submit" class="border border-emerald-600 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-1.5 text-xs font-medium">Filter</button>
        </div>
    </form>

    <div class="mt-4 flex flex-wrap items-center gap-3 text-[11px] text-slate-500">
        <span class="font-medium text-slate-600">Note:</span>
        <span>
            Reports are limited to classes and subjects you teach. For final-year (3-term) views, configure exam types per term
            (e.g. "1st Term Exam", "2nd Term Exam", "3rd Term Exam") and filter by academic year + exam/term.
        </span>
    </div>
</div>

<!-- Main layout: preview -->
<div class="bg-white border border-slate-200 rounded-xl flex flex-col">
    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold text-slate-800">
                <?= $reportType === 'grades' ? 'Grade summary' : 'Attendance summary' ?>
            </span>
        </div>
    </div>

    <div class="p-4 text-[11px] text-slate-700">
        <?php if (!$filterClassId || !$filterStudentId): ?>
            <p class="text-slate-500">
                Select a class and a student above to view a detailed <?= $reportType === 'grades' ? 'grade' : 'attendance' ?> report.
            </p>
        <?php elseif ($reportType === 'grades'): ?>
            <?php if (!$hasGrades): ?>
                <p class="text-slate-500">The grades tables are not available yet. Run the grades migration first.</p>
            <?php elseif (empty($gradeRows)): ?>
                <p class="text-slate-500">
                    No grades found for the selected student, year and exam/term within your subjects.
                </p>
            <?php else: ?>
                <table class="w-full text-left text-[11px]">
                    <thead class="border-b border-slate-100 text-slate-500">
                    <tr>
                        <th class="py-1.5 pr-2 font-semibold text-[10px]">Subject</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px]">Exam / Term</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Score</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Max</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">% Score</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $bySubject = [];
                    foreach ($gradeRows as $g) {
                        $sub = $g['subject_name'];
                        if (!isset($bySubject[$sub])) $bySubject[$sub] = ['total' => 0, 'count' => 0];
                        if ($g['max_score'] > 0) {
                            $pct = ((float)$g['score'] / (float)$g['max_score']) * 100.0;
                            $bySubject[$sub]['total'] += $pct;
                            $bySubject[$sub]['count']++;
                        }
                    }
                    foreach ($gradeRows as $g):
                        $pct = $g['max_score'] > 0 ? round(((float)$g['score'] / (float)$g['max_score']) * 100.0, 1) : null;
                    ?>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2"><?= htmlspecialchars($g['subject_name']) ?></td>
                        <td class="py-1.5 pr-2"><?= htmlspecialchars($g['exam_name']) ?></td>
                        <td class="py-1.5 pr-2 text-center"><?= htmlspecialchars($g['score']) ?></td>
                        <td class="py-1.5 pr-2 text-center"><?= htmlspecialchars($g['max_score']) ?></td>
                        <td class="py-1.5 pr-2 text-center"><?= $pct !== null ? $pct . '%' : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="mt-4 border-t border-slate-100 pt-3">
                    <h3 class="text-xs font-semibold text-slate-800 mb-2">Per-subject averages</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        <?php foreach ($bySubject as $sub => $agg):
                            $avg = $agg['count'] > 0 ? round($agg['total'] / $agg['count'], 1) : null;
                        ?>
                        <div class="border border-slate-100 rounded-lg px-2 py-2 text-center">
                            <div class="text-[10px] text-slate-500 mb-1"><?= htmlspecialchars($sub) ?></div>
                            <div class="text-lg font-bold text-slate-800"><?= $avg !== null ? $avg . '%' : '—' ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <?php if (!$hasAttendance): ?>
                <p class="text-slate-500">The attendance table is not available yet. Run the attendance migration first.</p>
            <?php elseif (empty($attendanceRows)): ?>
                <p class="text-slate-500">
                    No attendance records found for this student in the selected date range.
                </p>
            <?php else: ?>
                <?php
                $present = $late = $absent = 0;
                foreach ($attendanceRows as $a) {
                    if ($a['status'] === 'present') $present++;
                    elseif ($a['status'] === 'late') $late++;
                    else $absent++;
                }
                $total = $present + $late + $absent;
                $pct   = $total > 0 ? round($present / $total * 100.0, 1) : null;
                ?>
                <div class="mb-3 grid grid-cols-3 gap-2 text-center">
                    <div class="border border-slate-100 rounded-lg px-2 py-2">
                        <div class="text-[10px] text-slate-500 mb-1">Present</div>
                        <div class="text-lg font-bold text-emerald-600"><?= $present ?></div>
                    </div>
                    <div class="border border-slate-100 rounded-lg px-2 py-2">
                        <div class="text-[10px] text-slate-500 mb-1">Late</div>
                        <div class="text-lg font-bold text-amber-500"><?= $late ?></div>
                    </div>
                    <div class="border border-slate-100 rounded-lg px-2 py-2">
                        <div class="text-[10px] text-slate-500 mb-1">Absent</div>
                        <div class="text-lg font-bold text-rose-500"><?= $absent ?></div>
                    </div>
                </div>
                <p class="text-[11px] text-slate-500 mb-3">
                    Overall attendance in this range:
                    <strong class="text-slate-800"><?= $pct !== null ? $pct . '%' : '—' ?></strong>
                    (<?= $total ?> session<?= $total === 1 ? '' : 's' ?> recorded).
                </p>
                <table class="w-full text-left text-[11px]">
                    <thead class="border-b border-slate-100 text-slate-500">
                    <tr>
                        <th class="py-1.5 pr-2 font-semibold text-[10px]">Date</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px]">Status</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px]">Remarks</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($attendanceRows as $a): ?>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2"><?= htmlspecialchars($a['date']) ?></td>
                        <td class="py-1.5 pr-2">
                            <?php if ($a['status'] === 'present'): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px]">Present</span>
                            <?php elseif ($a['status'] === 'late'): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[10px]">Late</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[10px]">Absent</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-1.5 pr-2"><?= htmlspecialchars($a['remarks'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>


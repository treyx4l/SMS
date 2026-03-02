<?php
$page_title = 'Analytics';
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

// Determine teacher's classes and subjects
$classIds   = [];
$subjectIds = [];

if ($teacherId && $schoolId) {
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
}

// Load students in these classes
$students = []; // id => row
if ($classIds) {
    foreach ($classIds as $cid) {
        $stmt = $conn->prepare("
            SELECT id, first_name, last_name, class_id
            FROM students
            WHERE school_id = ? AND class_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param('ii', $schoolId, $cid);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $students[(int) $row['id']] = $row;
            }
            $stmt->close();
        }
    }
}

// Check tables for attendance and grades
$hasAttendance = (bool) ($conn->query("SHOW TABLES LIKE 'attendance'")->num_rows ?? 0);
$hasGrades     = false;
$res = $conn->query("SHOW TABLES LIKE 'grades'");
if ($res && $res->num_rows > 0) {
    $res2 = $conn->query("SHOW TABLES LIKE 'exam_types'");
    $hasGrades = $res2 && $res2->num_rows > 0;
}

// Time window for analytics
$fromDate = date('Y-m-d', strtotime('-30 days'));
$toDate   = date('Y-m-d');

// Attendance analytics per student
$attendanceByStudent = []; // id => ['present'=>, 'late'=>, 'absent'=>, 'total'=>]
if ($hasAttendance && $students) {
    $studentIds = array_keys($students);
    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $types = 'iss' . str_repeat('i', count($studentIds));
    $sql = "
        SELECT student_id, status, COUNT(*) AS c
        FROM attendance
        WHERE school_id = ?
          AND date BETWEEN ? AND ?
          AND student_id IN ($placeholders)
        GROUP BY student_id, status
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $params = array_merge([$schoolId, $fromDate, $toDate], $studentIds);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $sid = (int) $row['student_id'];
            $status = $row['status'] ?? 'present';
            $count  = (int) ($row['c'] ?? 0);
            if (!isset($attendanceByStudent[$sid])) {
                $attendanceByStudent[$sid] = ['present' => 0, 'late' => 0, 'absent' => 0, 'total' => 0];
            }
            if (isset($attendanceByStudent[$sid][$status])) {
                $attendanceByStudent[$sid][$status] += $count;
            }
            $attendanceByStudent[$sid]['total'] += $count;
        }
        $stmt->close();
    }
}

// Grade analytics per student
$averageGradeByStudent = []; // id => percent
if ($hasGrades && $students && $classIds && $subjectIds) {
    $studentIds = array_keys($students);
    $studentPh = implode(',', array_fill(0, count($studentIds), '?'));
    $classPh   = implode(',', array_fill(0, count($classIds), '?'));
    $subjectPh = implode(',', array_fill(0, count($subjectIds), '?'));
    $types = 'i' . str_repeat('i', count($studentIds) + count($classIds) + count($subjectIds));
    $sql = "
        SELECT student_id, score, max_score
        FROM grades
        WHERE school_id = ?
          AND student_id IN ($studentPh)
          AND class_id IN ($classPh)
          AND subject_id IN ($subjectPh)
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $params = array_merge([$schoolId], $studentIds, $classIds, $subjectIds);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $sid   = (int) $row['student_id'];
            $score = (float) $row['score'];
            $max   = (float) $row['max_score'];
            if ($max <= 0) continue;
            $pct = ($score / $max) * 100.0;
            if (!isset($averageGradeByStudent[$sid])) {
                $averageGradeByStudent[$sid] = ['sum' => 0.0, 'count' => 0];
            }
            $averageGradeByStudent[$sid]['sum']   += $pct;
            $averageGradeByStudent[$sid]['count'] += 1;
        }
        $stmt->close();
    }
}

// Compute overall metrics and at-risk list
$overallPresent = $overallLate = $overallAbsent = $overallTotal = 0;
$highPerforming = 0;
$atRiskCount    = 0;
$atRiskStudents = [];

foreach ($students as $sid => $s) {
    $att = $attendanceByStudent[$sid] ?? ['present' => 0, 'late' => 0, 'absent' => 0, 'total' => 0];
    $overallPresent += $att['present'];
    $overallLate    += $att['late'];
    $overallAbsent  += $att['absent'];
    $overallTotal   += $att['total'];

    $avgGrade = null;
    if (isset($averageGradeByStudent[$sid]) && $averageGradeByStudent[$sid]['count'] > 0) {
        $avgGrade = $averageGradeByStudent[$sid]['sum'] / $averageGradeByStudent[$sid]['count'];
    }

    $attPct = $att['total'] > 0 ? ($att['present'] / $att['total']) * 100.0 : null;

    if ($avgGrade !== null && $avgGrade >= 80) {
        $highPerforming++;
    }

    $isAtRisk = false;
    if ($attPct !== null && $attPct < 75) {
        $isAtRisk = true;
    }
    if ($avgGrade !== null && $avgGrade < 50) {
        $isAtRisk = true;
    }
    if ($isAtRisk) {
        $atRiskCount++;
        $atRiskStudents[] = [
            'id'        => $sid,
            'first_name'=> $s['first_name'],
            'last_name' => $s['last_name'],
            'class_id'  => $s['class_id'],
            'attendance_percent' => $attPct,
            'grade_percent'      => $avgGrade,
        ];
    }
}

$avgAttendanceAll = $overallTotal > 0 ? round($overallPresent / $overallTotal * 100.0, 1) : null;

// Load class names for at-risk listing
$classesById = [];
if ($classIds) {
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
            if ($row) $classesById[$cid] = $row;
        }
    }
}

// Sort at-risk students by combination of low attendance and grade
usort($atRiskStudents, function ($a, $b) {
    $aScore = ($a['attendance_percent'] ?? 100) + ($a['grade_percent'] ?? 100);
    $bScore = ($b['attendance_percent'] ?? 100) + ($b['grade_percent'] ?? 100);
    return $aScore <=> $bScore;
});
?>

<?php if (!$teacherId): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-800">
        Your account is not linked to a teacher record. Please contact the admin if you believe this is an error.
    </p>
</div>
<?php elseif (!$classIds): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-800">
        No classes have been assigned to you yet. Once the admin links you to classes and subjects, you will see analytics here.
    </p>
</div>
<?php else: ?>

<!-- Header -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">Analytics</h2>
            <p class="text-[11px] text-slate-500">
                Insights for your classes based on attendance (last 30 days) and grades recorded so far.
            </p>
        </div>
        <div class="text-[11px] text-slate-500">
            Time window: <?= htmlspecialchars($fromDate) ?> &rarr; <?= htmlspecialchars($toDate) ?>
        </div>
    </div>
</div>

<!-- Overview cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <p class="text-xs font-medium text-slate-500 mb-2">Average attendance</p>
        <p class="text-3xl font-bold text-emerald-600">
            <?= $avgAttendanceAll !== null ? $avgAttendanceAll . '%' : '—' ?>
        </p>
        <p class="text-[11px] text-slate-400 mt-2">Across students in your classes (last 30 days).</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <p class="text-xs font-medium text-slate-500 mb-2">Students with grades</p>
        <?php
        $withGrade = 0;
        foreach ($averageGradeByStudent as $sid => $agg) {
            if ($agg['count'] > 0) $withGrade++;
        }
        ?>
        <p class="text-3xl font-bold text-sky-600"><?= $withGrade ?></p>
        <p class="text-[11px] text-slate-400 mt-2">Students in your classes with at least one recorded grade.</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <p class="text-xs font-medium text-slate-500 mb-2">High-performing students</p>
        <p class="text-3xl font-bold text-emerald-600"><?= $highPerforming ?></p>
        <p class="text-[11px] text-slate-400 mt-2">Average grade &ge; 80% across your subjects.</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <p class="text-xs font-medium text-slate-500 mb-2">At-risk students</p>
        <p class="text-3xl font-bold text-rose-500"><?= $atRiskCount ?></p>
        <p class="text-[11px] text-slate-400 mt-2">Low attendance (&lt;75%) and/or low average grade (&lt;50%).</p>
    </div>
</div>

<!-- Two-column layout: at-risk list + explanation -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-semibold text-slate-800">At-risk students in your classes</h3>
            <?php if ($atRiskStudents): ?>
            <span class="text-[10px] text-slate-400">
                Ordered from most at-risk (lowest combined attendance/grade) to least.
            </span>
            <?php endif; ?>
        </div>
        <?php if (!$atRiskStudents): ?>
            <p class="text-[11px] text-slate-500">
                No students currently meet the at-risk criteria based on attendance and grades recorded so far.
            </p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-[11px]">
                <thead class="border-b border-slate-100 text-slate-500">
                <tr>
                    <th class="py-1.5 pr-2 font-semibold text-[10px]">Student</th>
                    <th class="py-1.5 pr-2 font-semibold text-[10px]">Class</th>
                    <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Attendance %</th>
                    <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Avg grade %</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($atRiskStudents as $st): ?>
                <?php
                    $c = $classesById[$st['class_id']] ?? null;
                    $classLabel = $c ? $c['name'] . ($c['section'] ? ' ' . $c['section'] : '') : '—';
                    $attPct = $st['attendance_percent'];
                    $gradePct = $st['grade_percent'];
                ?>
                <tr class="border-b border-slate-50">
                    <td class="py-1.5 pr-2">
                        <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                    </td>
                    <td class="py-1.5 pr-2"><?= htmlspecialchars($classLabel) ?></td>
                    <td class="py-1.5 pr-2 text-center">
                        <?= $attPct !== null ? round($attPct, 1) . '%' : '—' ?>
                    </td>
                    <td class="py-1.5 pr-2 text-center">
                        <?= $gradePct !== null ? round($gradePct, 1) . '%' : '—' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-5">
        <h3 class="text-xs font-semibold text-slate-800 mb-2">How to use these insights</h3>
        <ul class="text-[11px] text-slate-500 space-y-1">
            <li><span class="font-semibold text-slate-700">Attendance focus</span>: For students with low attendance, follow up via class teacher and parents.</li>
            <li><span class="font-semibold text-slate-700">Grade support</span>: For low average grades, plan revision or small group support in your lesson notes.</li>
            <li><span class="font-semibold text-slate-700">Combine with reports</span>: Use the Reports page to print or export detailed records per student.</li>
        </ul>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>

<?php
$page_title = 'Analytics';
require_once __DIR__ . '/layout.php';
?>

<!-- Header & filters -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">Analytics</h2>
            <p class="text-[11px] text-slate-500">
                High-level insights for your classes and subjects, combining attendance and performance.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <select class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>Metric focus</option>
                <option>Attendance</option>
                <option>Performance</option>
                <option>Combined</option>
            </select>
            <select class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>Time range</option>
                <option>This week</option>
                <option>This month</option>
                <option>This term</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Class</label>
            <select class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>All classes</option>
                <option>JSS1 A</option>
                <option>JSS2 B</option>
                <option>SS1 C</option>
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Subject</label>
            <select class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>All subjects</option>
                <option>Mathematics</option>
                <option>English</option>
                <option>Basic Science</option>
                <option>ICT</option>
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Term</label>
            <select class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>All terms</option>
                <option>1st Term</option>
                <option>2nd Term</option>
                <option>3rd Term</option>
            </select>
        </div>

        <div class="flex items-end justify-end gap-2">
            <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-slate-300 hover:bg-slate-50">
                Reset
            </button>
            <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">
                Refresh insights
            </button>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3 text-[11px] text-slate-500">
        <span class="font-medium text-slate-600">Note:</span>
        <span>This is a UI-only analytics skeleton – later, selections here will query real attendance and grades data.</span>
    </div>
</div>

<!-- Main layout: overview + details -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Overview & charts (mocked) -->
    <div class="lg:col-span-2 space-y-4">
        <!-- High-level overview -->
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-800">Class overview (sample)</h2>
                <span class="text-[11px] text-slate-400">Attendance &amp; performance at a glance</span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center text-[11px]">
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">Avg. attendance</div>
                    <div class="text-lg font-bold text-emerald-600">92%</div>
                    <div class="text-[10px] text-slate-400">All classes</div>
                </div>
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">Avg. score</div>
                    <div class="text-lg font-bold text-sky-600">74%</div>
                    <div class="text-[10px] text-slate-400">Selected subject(s)</div>
                </div>
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">High-performing</div>
                    <div class="text-lg font-bold text-emerald-600">8</div>
                    <div class="text-[10px] text-slate-400">Students &gt;= 80%</div>
                </div>
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">At-risk (combined)</div>
                    <div class="text-lg font-bold text-rose-500">5</div>
                    <div class="text-[10px] text-slate-400">&lt; 70% attendance or score</div>
                </div>
            </div>
        </div>

        <!-- Attendance & performance "charts" (table-based mock) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white border border-slate-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-semibold text-slate-800">Attendance trends (sample)</h3>
                    <span class="text-[10px] text-slate-400">Per week</span>
                </div>
                <p class="text-[11px] text-slate-500 mb-2">
                    This block will later be replaced by a line / bar chart showing attendance percentages across the selected period.
                </p>
                <table class="w-full text-[11px] text-left">
                    <thead class="border-b border-slate-100 text-slate-500">
                    <tr>
                        <th class="py-1.5 pr-2 font-semibold text-[10px]">Week</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">JSS1 A</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">JSS2 B</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">SS1 C</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2 text-[10px] text-slate-500">Week 1</td>
                        <td class="py-1.5 pr-2 text-center text-emerald-600 font-semibold">94%</td>
                        <td class="py-1.5 pr-2 text-center text-emerald-600 font-semibold">91%</td>
                        <td class="py-1.5 pr-2 text-center text-emerald-600 font-semibold">89%</td>
                    </tr>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2 text-[10px] text-slate-500">Week 2</td>
                        <td class="py-1.5 pr-2 text-center text-emerald-600 font-semibold">96%</td>
                        <td class="py-1.5 pr-2 text-center text-emerald-600 font-semibold">92%</td>
                        <td class="py-1.5 pr-2 text-center text-amber-600 font-semibold">84%</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 pr-2 text-[10px] text-slate-500">Week 3</td>
                        <td class="py-1.5 pr-2 text-center text-emerald-600 font-semibold">93%</td>
                        <td class="py-1.5 pr-2 text-center text-amber-600 font-semibold">82%</td>
                        <td class="py-1.5 pr-2 text-center text-rose-500 font-semibold">72%</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-semibold text-slate-800">Performance insights (sample)</h3>
                    <span class="text-[10px] text-slate-400">Recent assessment</span>
                </div>
                <p class="text-[11px] text-slate-500 mb-2">
                    This block will later display charts for score distribution, averages and grade breakdowns.
                </p>
                <table class="w-full text-[11px] text-left">
                    <thead class="border-b border-slate-100 text-slate-500">
                    <tr>
                        <th class="py-1.5 pr-2 font-semibold text-[10px]">Range</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Count</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Share</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2">80 &ndash; 100 (A)</td>
                        <td class="py-1.5 pr-2 text-center">9</td>
                        <td class="py-1.5 pr-2 text-center text-emerald-600 font-semibold">28%</td>
                    </tr>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2">60 &ndash; 79 (B)</td>
                        <td class="py-1.5 pr-2 text-center">14</td>
                        <td class="py-1.5 pr-2 text-center text-sky-600 font-semibold">44%</td>
                    </tr>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2">50 &ndash; 59 (C)</td>
                        <td class="py-1.5 pr-2 text-center">5</td>
                        <td class="py-1.5 pr-2 text-center text-amber-600 font-semibold">16%</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 pr-2">&lt; 50 (D/E/F)</td>
                        <td class="py-1.5 pr-2 text-center">4</td>
                        <td class="py-1.5 pr-2 text-center text-rose-500 font-semibold">12%</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar: at-risk & shortcuts -->
    <div class="space-y-4">
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">At-risk students (sample)</h3>
            <p class="text-[11px] text-slate-500 mb-2">
                Later, this list will be generated based on low attendance and/or low performance, scoped to your classes.
            </p>
            <div class="space-y-1.5 text-[11px]">
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-1.5">
                    <div>
                        <div class="font-medium text-slate-800">Adeyemi T.</div>
                        <div class="text-[10px] text-slate-500">JSS1 A &middot; Attendance: 68% &middot; Avg: 55%</div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-200 text-[10px]">
                        High risk
                    </span>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-1.5">
                    <div>
                        <div class="font-medium text-slate-800">Bisi O.</div>
                        <div class="text-[10px] text-slate-500">JSS2 B &middot; Attendance: 82% &middot; Avg: 48%</div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-50 text-amber-600 border border-amber-200 text-[10px]">
                        Academic risk
                    </span>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-1.5">
                    <div>
                        <div class="font-medium text-slate-800">Chidi K.</div>
                        <div class="text-[10px] text-slate-500">SS1 C &middot; Attendance: 72% &middot; Avg: 62%</div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px]">
                        Monitor
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">Shortcuts</h3>
            <p class="text-[11px] text-slate-500 mb-2">
                Use these links to jump into the underlying data for more detail.
            </p>
            <div class="space-y-1.5 text-[11px]">
                <a href="attendance.php" class="flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800">
                    <span>Open attendance workspace</span>
                    <i data-lucide="calendar-check" class="w-3 h-3"></i>
                </a>
                <a href="grades.php" class="flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-200 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-800">
                    <span>Open grading workspace</span>
                    <i data-lucide="clipboard-list" class="w-3 h-3"></i>
                </a>
                <a href="reports.php" class="flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800">
                    <span>Generate detailed report</span>
                    <i data-lucide="bar-chart-2" class="w-3 h-3"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>


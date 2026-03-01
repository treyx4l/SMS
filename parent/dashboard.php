<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/layout.php';

// At this point $conn and $schoolId are already set in layout.php.
// Resolve the logged-in parent (via users.email = parents.email).
$parent      = null;
$wards       = [];
$attendanceSummary = [];
$gradesByStudent   = [];
$overallAttendance = ['present' => 0, 'late' => 0, 'absent' => 0, 'total' => 0];
$averageGradeByStudent = [];

$userId   = (int) ($_SESSION['user_id'] ?? 0);
$schoolId = $schoolId ?? current_school_id();

if ($userId && $schoolId && isset($conn)) {
    $stmt = $conn->prepare("
        SELECT p.*
        FROM parents p
        JOIN users u
          ON u.email = p.email
         AND u.school_id = p.school_id
        WHERE u.id = ?
          AND u.role = 'parent'
          AND u.school_id = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('ii', $userId, $schoolId);
        $stmt->execute();
        $res = $stmt->get_result();
        $parent = $res->fetch_assoc() ?: null;
        $stmt->close();
    }

    if ($parent) {
        $parentId = (int) $parent['id'];
        $stmt = $conn->prepare("
            SELECT s.*, c.name AS class_name, c.section AS class_section
            FROM students s
            LEFT JOIN classes c
              ON c.id = s.class_id
             AND c.school_id = s.school_id
            WHERE s.school_id = ?
              AND s.parent_id = ?
            ORDER BY s.first_name, s.last_name
        ");
        if ($stmt) {
            $stmt->bind_param('ii', $schoolId, $parentId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $wards[] = $row;
            }
            $stmt->close();
        }
    }
}

// Check for optional tables
$hasAttendanceTable = false;
$hasGradesTables    = false;
if (isset($conn)) {
    $res = $conn->query("SHOW TABLES LIKE 'attendance'");
    $hasAttendanceTable = $res && $res->num_rows > 0;

    $res = $conn->query("SHOW TABLES LIKE 'grades'");
    if ($res && $res->num_rows > 0) {
        $res2 = $conn->query("SHOW TABLES LIKE 'exam_types'");
        $hasGradesTables = $res2 && $res2->num_rows > 0;
    }
}

// Attendance summary for each ward (last 30 days)
if ($hasAttendanceTable && $wards) {
    $stmt = $conn->prepare("
        SELECT status, COUNT(*) AS c
        FROM attendance
        WHERE school_id = ?
          AND student_id = ?
          AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY status
    ");
    if ($stmt) {
        foreach ($wards as $w) {
            $sid = (int) $w['id'];
            $summary = ['present' => 0, 'late' => 0, 'absent' => 0, 'total' => 0];

            $stmt->bind_param('ii', $schoolId, $sid);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $status = $row['status'] ?? 'present';
                $count  = (int) ($row['c'] ?? 0);
                if (isset($summary[$status])) {
                    $summary[$status] += $count;
                }
                $summary['total'] += $count;
            }
            $attendanceSummary[$sid] = $summary;

            $overallAttendance['present'] += $summary['present'];
            $overallAttendance['late']    += $summary['late'];
            $overallAttendance['absent']  += $summary['absent'];
            $overallAttendance['total']   += $summary['total'];
        }
        $stmt->close();
    }
}

// Recent grades for each ward
if ($hasGradesTables && $wards) {
    $stmt = $conn->prepare("
        SELECT g.student_id,
               g.score,
               g.max_score,
               g.created_at,
               subj.name AS subject_name,
               et.name   AS exam_name,
               c.name    AS class_name,
               c.section AS class_section
        FROM grades g
        LEFT JOIN subjects subj
          ON subj.id = g.subject_id
         AND subj.school_id = g.school_id
        LEFT JOIN exam_types et
          ON et.id = g.exam_type_id
         AND et.school_id = g.school_id
        LEFT JOIN classes c
          ON c.id = g.class_id
         AND c.school_id = g.school_id
        WHERE g.school_id = ?
          AND g.student_id = ?
        ORDER BY g.created_at DESC
        LIMIT 10
    ");
    if ($stmt) {
        foreach ($wards as $w) {
            $sid = (int) $w['id'];
            $gradesByStudent[$sid] = [];

            $stmt->bind_param('ii', $schoolId, $sid);
            $stmt->execute();
            $res = $stmt->get_result();
            $sumPercent = 0.0;
            $count      = 0;
            while ($row = $res->fetch_assoc()) {
                $gradesByStudent[$sid][] = $row;
                if ($row['max_score'] > 0) {
                    $percent = ((float) $row['score'] / (float) $row['max_score']) * 100.0;
                    $sumPercent += $percent;
                    $count++;
                }
            }
            if ($count > 0) {
                $averageGradeByStudent[$sid] = $sumPercent / $count;
            }
        }
        $stmt->close();
    }
}

$totalWards = count($wards);
?>

<?php if (!$parent): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <h2 class="text-sm font-semibold text-amber-800 mb-1">Parent profile not linked</h2>
    <p class="text-sm text-amber-700">
        Your login is not yet linked to a parent record in Axis SMS.
        Please contact the school administrator to complete your profile.
    </p>
</div>
<?php else: ?>

<div class="space-y-4">
    <!-- Welcome + quick stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 mb-1">Welcome</p>
            <p class="text-base font-semibold text-slate-800">
                <?= htmlspecialchars($parent['full_name'] ?? 'Parent') ?>
            </p>
            <p class="text-[11px] text-slate-400 mt-1">
                You are currently linked to
                <span class="font-semibold text-slate-700"><?= $totalWards ?></span>
                ward<?= $totalWards === 1 ? '' : 's' ?>.
            </p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 mb-1">Attendance (last 30 days)</p>
            <?php if ($overallAttendance['total'] === 0): ?>
                <p class="text-[11px] text-slate-400 mt-1">
                    No attendance records yet for your wards.
                </p>
            <?php else: ?>
                <div class="flex items-end gap-4">
                    <div class="flex-1">
                        <div class="flex justify-between text-[11px] text-slate-500 mb-1">
                            <span>Present</span>
                            <span><?= $overallAttendance['present'] ?></span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full bg-emerald-500"
                                 style="width: <?= max(1, (int) round($overallAttendance['present'] / max(1, $overallAttendance['total']) * 100)) ?>%;"></div>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between text-[11px] text-slate-500 mb-1">
                            <span>Late</span>
                            <span><?= $overallAttendance['late'] ?></span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full bg-amber-400"
                                 style="width: <?= max(1, (int) round($overallAttendance['late'] / max(1, $overallAttendance['total']) * 100)) ?>%;"></div>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between text-[11px] text-slate-500 mb-1">
                            <span>Absent</span>
                            <span><?= $overallAttendance['absent'] ?></span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full bg-rose-500"
                                 style="width: <?= max(1, (int) round($overallAttendance['absent'] / max(1, $overallAttendance['total']) * 100)) ?>%;"></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 mb-1">Overall performance snapshot</p>
            <?php if (!$averageGradeByStudent): ?>
                <p class="text-[11px] text-slate-400 mt-1">
                    Grades will appear here once teachers start recording them.
                </p>
            <?php else: ?>
                <ul class="space-y-1.5 text-[11px] text-slate-600">
                    <?php foreach ($wards as $w):
                        $sid = (int) $w['id'];
                        if (!isset($averageGradeByStudent[$sid])) continue;
                        $avg = $averageGradeByStudent[$sid];
                    ?>
                    <li class="flex items-center justify-between">
                        <span class="truncate pr-2">
                            <?= htmlspecialchars(trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? ''))) ?>
                        </span>
                        <span class="font-semibold <?= $avg >= 70 ? 'text-emerald-600' : ($avg >= 50 ? 'text-amber-600' : 'text-rose-600') ?>">
                            <?= number_format($avg, 1) ?>%
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabbed sections -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="flex border-b border-slate-100 text-xs font-medium">
            <button type="button"
                    data-tab-target="wards"
                    class="px-4 py-2 -mb-px border-b-2 border-indigo-600 text-indigo-700">
                Wards
            </button>
            <button type="button"
                    data-tab-target="attendance"
                    class="px-4 py-2 -mb-px border-b-2 border-transparent text-slate-500 hover:text-slate-700">
                Attendance
            </button>
            <button type="button"
                    data-tab-target="grades"
                    class="px-4 py-2 -mb-px border-b-2 border-transparent text-slate-500 hover:text-slate-700">
                Grades
            </button>
            <button type="button"
                    data-tab-target="fees"
                    class="px-4 py-2 -mb-px border-b-2 border-transparent text-slate-500 hover:text-slate-700">
                Fees
            </button>
            <button type="button"
                    data-tab-target="reports"
                    class="px-4 py-2 -mb-px border-b-2 border-transparent text-slate-500 hover:text-slate-700">
                Reports
            </button>
            <button type="button"
                    data-tab-target="analytics"
                    class="px-4 py-2 -mb-px border-b-2 border-transparent text-slate-500 hover:text-slate-700">
                Analytics
            </button>
        </div>

        <!-- Wards details -->
        <div data-tab-panel="wards" class="p-5 space-y-3">
            <?php if (!$wards): ?>
                <p class="text-sm text-slate-500">
                    No wards are currently linked to your account. Please contact the school to update your ward assignments.
                </p>
            <?php else: ?>
                <p class="text-xs text-slate-500 mb-2">
                    Detailed information for each ward linked to your account.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($wards as $w): ?>
                        <div class="border border-slate-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">
                                        <?= htmlspecialchars(trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? ''))) ?>
                                    </p>
                                    <p class="text-[11px] text-slate-400">
                                        ID: <?= htmlspecialchars($w['index_no'] ?? (string) $w['id']) ?>
                                    </p>
                                </div>
                            </div>
                            <dl class="text-[11px] text-slate-600 space-y-1.5">
                                <div class="flex justify-between">
                                    <dt class="text-slate-400">Class</dt>
                                    <dd class="font-medium text-slate-700">
                                        <?= htmlspecialchars(trim(($w['class_name'] ?? 'Unassigned') . (isset($w['class_section']) && $w['class_section'] ? ' ' . $w['class_section'] : ''))) ?>
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-400">Gender</dt>
                                    <dd class="font-medium text-slate-700">
                                        <?= htmlspecialchars(ucfirst($w['gender'] ?? '—')) ?>
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-400">Date of birth</dt>
                                    <dd class="font-medium text-slate-700">
                                        <?= !empty($w['date_of_birth']) ? date('d M Y', strtotime($w['date_of_birth'])) : '—' ?>
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-400">Phone</dt>
                                    <dd class="font-medium text-slate-700">
                                        <?= htmlspecialchars($w['phone'] ?? '—') ?>
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-slate-400">Address</dt>
                                    <dd class="font-medium text-slate-700 text-right max-w-[60%]">
                                        <?= htmlspecialchars($w['address'] ?? '—') ?>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Attendance section -->
        <div data-tab-panel="attendance" class="hidden p-5 space-y-3">
            <p class="text-xs text-slate-500 mb-1">
                Attendance over the last 30 days for each ward.
            </p>
            <?php if (!$hasAttendanceTable): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-xs text-amber-800">
                    Attendance tracking is not yet enabled for this school in Axis SMS.
                </div>
            <?php elseif (!$wards): ?>
                <p class="text-sm text-slate-500">
                    No wards are linked to your account, so attendance cannot be shown.
                </p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($wards as $w):
                        $sid = (int) $w['id'];
                        $att = $attendanceSummary[$sid] ?? ['present' => 0, 'late' => 0, 'absent' => 0, 'total' => 0];
                    ?>
                    <div class="border border-slate-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">
                                    <?= htmlspecialchars(trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? ''))) ?>
                                </p>
                                <p class="text-[11px] text-slate-400">
                                    <?= htmlspecialchars(trim(($w['class_name'] ?? 'Unassigned') . (isset($w['class_section']) && $w['class_section'] ? ' ' . $w['class_section'] : ''))) ?>
                                </p>
                            </div>
                            <p class="text-[11px] text-slate-400">
                                <?= $att['total'] ?> day<?= $att['total'] === 1 ? '' : 's' ?>
                            </p>
                        </div>
                        <?php if ($att['total'] === 0): ?>
                            <p class="text-[11px] text-slate-400">
                                No attendance records yet for this ward.
                            </p>
                        <?php else: ?>
                            <dl class="space-y-1.5 text-[11px]">
                                <div class="flex items-center justify-between">
                                    <dt class="text-slate-500">Present</dt>
                                    <dd class="flex items-center gap-2">
                                        <div class="w-24 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full bg-emerald-500"
                                                 style="width: <?= max(1, (int) round($att['present'] / max(1, $att['total']) * 100)) ?>%;"></div>
                                        </div>
                                        <span class="font-semibold text-emerald-600">
                                            <?= $att['present'] ?>
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-slate-500">Late</dt>
                                    <dd class="flex items-center gap-2">
                                        <div class="w-24 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full bg-amber-400"
                                                 style="width: <?= max(1, (int) round($att['late'] / max(1, $att['total']) * 100)) ?>%;"></div>
                                        </div>
                                        <span class="font-semibold text-amber-600">
                                            <?= $att['late'] ?>
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-slate-500">Absent</dt>
                                    <dd class="flex items-center gap-2">
                                        <div class="w-24 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full bg-rose-500"
                                                 style="width: <?= max(1, (int) round($att['absent'] / max(1, $att['total']) * 100)) ?>%;"></div>
                                        </div>
                                        <span class="font-semibold text-rose-600">
                                            <?= $att['absent'] ?>
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Grades section -->
        <div data-tab-panel="grades" class="hidden p-5 space-y-3">
            <p class="text-xs text-slate-500 mb-1">
                Recent grades recorded by teachers for your wards.
            </p>
            <?php if (!$hasGradesTables): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-xs text-amber-800">
                    The grades module has not been enabled for this school yet.
                </div>
            <?php elseif (!$wards): ?>
                <p class="text-sm text-slate-500">
                    No wards are linked to your account, so grades cannot be shown.
                </p>
            <?php else: ?>
                <?php
                $hasAnyGrades = false;
                foreach ($gradesByStudent as $rows) {
                    if (!empty($rows)) { $hasAnyGrades = true; break; }
                }
                ?>
                <?php if (!$hasAnyGrades): ?>
                    <p class="text-sm text-slate-500">
                        No grades have been recorded yet for your wards.
                    </p>
                <?php else: ?>
                    <div class="overflow-x-auto border border-slate-200 rounded-lg">
                        <table class="min-w-full text-xs text-left text-slate-700">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Ward</th>
                                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Subject</th>
                                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Exam</th>
                                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">Score</th>
                                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Class</th>
                                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($wards as $w):
                                    $sid = (int) $w['id'];
                                    $rows = $gradesByStudent[$sid] ?? [];
                                    foreach ($rows as $row):
                                        $percent = ($row['max_score'] ?? 0) > 0
                                            ? (float) $row['score'] / (float) $row['max_score'] * 100.0
                                            : null;
                                ?>
                                <tr class="border-b border-slate-100">
                                    <td class="px-4 py-2">
                                        <?= htmlspecialchars(trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? ''))) ?>
                                    </td>
                                    <td class="px-4 py-2">
                                        <?= htmlspecialchars($row['subject_name'] ?? '—') ?>
                                    </td>
                                    <td class="px-4 py-2">
                                        <?= htmlspecialchars($row['exam_name'] ?? '—') ?>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <?php if ($percent === null): ?>
                                            <span class="text-slate-400">—</span>
                                        <?php else: ?>
                                            <span class="font-semibold <?= $percent >= 70 ? 'text-emerald-600' : ($percent >= 50 ? 'text-amber-600' : 'text-rose-600') ?>">
                                                <?= number_format($percent, 1) ?>%
                                            </span>
                                            <span class="text-[10px] text-slate-400">
                                                (<?= htmlspecialchars((string) $row['score']) ?>/<?= htmlspecialchars((string) $row['max_score']) ?>)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-2">
                                        <?= htmlspecialchars(trim(($row['class_name'] ?? 'Unassigned') . (isset($row['class_section']) && $row['class_section'] ? ' ' . $row['class_section'] : ''))) ?>
                                    </td>
                                    <td class="px-4 py-2 text-slate-500">
                                        <?= !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '—' ?>
                                    </td>
                                </tr>
                                <?php endforeach; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Fees section -->
        <div data-tab-panel="fees" class="hidden p-5 space-y-3">
            <p class="text-xs text-slate-500 mb-1">
                Fees, invoices, and payment status for your wards.
            </p>
            <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 text-xs text-blue-800">
                The detailed fees and payments module is not yet connected in this version of Axis SMS.
                Once your school enables it, you will see term invoices, amounts paid, and any outstanding balances here
                for each ward.
            </div>
        </div>

        <!-- Reports section -->
        <div data-tab-panel="reports" class="hidden p-5 space-y-3">
            <p class="text-xs text-slate-500 mb-1">
                High-level reports summarising your wards' attendance and grades.
            </p>

            <?php if (!$wards): ?>
                <p class="text-sm text-slate-500">
                    No wards are linked to your account, so reports cannot be generated.
                </p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="border border-slate-200 rounded-lg p-4">
                        <p class="text-[11px] text-slate-500 mb-1">Total wards</p>
                        <p class="text-3xl font-bold text-slate-800"><?= $totalWards ?></p>
                    </div>
                    <div class="border border-slate-200 rounded-lg p-4">
                        <p class="text-[11px] text-slate-500 mb-1">Attendance records (30 days)</p>
                        <p class="text-3xl font-bold text-slate-800"><?= $overallAttendance['total'] ?></p>
                    </div>
                    <div class="border border-slate-200 rounded-lg p-4">
                        <p class="text-[11px] text-slate-500 mb-1">Wards with grade data</p>
                        <p class="text-3xl font-bold text-slate-800"><?= count($averageGradeByStudent) ?></p>
                    </div>
                </div>

                <div class="border border-slate-200 rounded-lg p-4">
                    <p class="text-[11px] text-slate-500 mb-2">
                        Simple ward report card overview.
                    </p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs text-left text-slate-700">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Ward</th>
                                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">Classes</th>
                                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">Attendance records</th>
                                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">Avg grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($wards as $w):
                                    $sid = (int) $w['id'];
                                    $att = $attendanceSummary[$sid] ?? ['total' => 0];
                                    $avg = $averageGradeByStudent[$sid] ?? null;
                                ?>
                                <tr class="border-b border-slate-100">
                                    <td class="px-4 py-2">
                                        <?= htmlspecialchars(trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? ''))) ?>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <?= htmlspecialchars(trim(($w['class_name'] ?? 'Unassigned') . (isset($w['class_section']) && $w['class_section'] ? ' ' . $w['class_section'] : ''))) ?>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <?= (int) $att['total'] ?>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <?php if ($avg === null): ?>
                                            <span class="text-slate-400">—</span>
                                        <?php else: ?>
                                            <span class="font-semibold <?= $avg >= 70 ? 'text-emerald-600' : ($avg >= 50 ? 'text-amber-600' : 'text-rose-600') ?>">
                                                <?= number_format($avg, 1) ?>%
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Analytics section -->
        <div data-tab-panel="analytics" class="hidden p-5 space-y-3">
            <p class="text-xs text-slate-500 mb-1">
                Simple analytics to help you keep track of how your wards are doing.
            </p>

            <?php if (!$wards): ?>
                <p class="text-sm text-slate-500">
                    No wards are linked to your account yet.
                </p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="border border-slate-200 rounded-lg p-4">
                        <p class="text-[11px] text-slate-500 mb-2">Attendance health</p>
                        <?php if ($overallAttendance['total'] === 0): ?>
                            <p class="text-[11px] text-slate-400">
                                Attendance records are not available yet.
                            </p>
                        <?php else: ?>
                            <?php
                            $presentRate = $overallAttendance['present'] / max(1, $overallAttendance['total']) * 100.0;
                            ?>
                            <p class="text-sm font-semibold text-slate-800 mb-1">
                                <?= number_format($presentRate, 1) ?>% days present
                            </p>
                            <p class="text-[11px] text-slate-500 mb-2">
                                This is across all wards over the last 30 days.
                            </p>
                            <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full <?= $presentRate >= 90 ? 'bg-emerald-500' : ($presentRate >= 75 ? 'bg-amber-400' : 'bg-rose-500') ?>"
                                     style="width: <?= max(1, (int) round($presentRate)) ?>%;"></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="border border-slate-200 rounded-lg p-4">
                        <p class="text-[11px] text-slate-500 mb-2">Academic snapshot</p>
                        <?php if (!$averageGradeByStudent): ?>
                            <p class="text-[11px] text-slate-400">
                                Once teachers start entering grades, you will see performance bands for each ward here.
                            </p>
                        <?php else: ?>
                            <ul class="space-y-1.5 text-[11px]">
                                <?php foreach ($wards as $w):
                                    $sid = (int) $w['id'];
                                    if (!isset($averageGradeByStudent[$sid])) continue;
                                    $avg = $averageGradeByStudent[$sid];
                                ?>
                                <li class="flex items-center justify-between">
                                    <span class="truncate pr-2">
                                        <?= htmlspecialchars(trim(($w['first_name'] ?? '') . ' ' . ($w['last_name'] ?? ''))) ?>
                                    </span>
                                    <span class="inline-flex items-center gap-2">
                                        <span class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                            <span class="block h-full <?= $avg >= 70 ? 'bg-emerald-500' : ($avg >= 50 ? 'bg-amber-400' : 'bg-rose-500') ?>"
                                                  style="width: <?= max(1, (int) round($avg)) ?>%;"></span>
                                        </span>
                                        <span class="font-semibold <?= $avg >= 70 ? 'text-emerald-600' : ($avg >= 50 ? 'text-amber-600' : 'text-rose-600') ?>">
                                            <?= number_format($avg, 1) ?>%
                                        </span>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="text-[11px] text-slate-400">
                    These analytics are simple summaries to give you a quick overview.
                    For detailed term reports, please request official report cards from the school.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>
// Simple tab switcher for the parent dashboard
document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('[data-tab-target]');
    const tabPanels  = document.querySelectorAll('[data-tab-panel]');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-tab-target');
            if (!target) return;

            tabButtons.forEach(b => {
                b.classList.remove('border-indigo-600', 'text-indigo-700');
                b.classList.add('border-transparent', 'text-slate-500');
            });
            btn.classList.remove('border-transparent', 'text-slate-500');
            btn.classList.add('border-indigo-600', 'text-indigo-700');

            tabPanels.forEach(panel => {
                if (panel.getAttribute('data-tab-panel') === target) {
                    panel.classList.remove('hidden');
                } else {
                    panel.classList.add('hidden');
                }
            });
        });
    });
});
</script>

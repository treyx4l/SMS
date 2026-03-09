<?php
$page_title = 'Attendance';
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
$res = $conn->query("SHOW TABLES LIKE 'attendance'");
$tablesExist = $res && $res->num_rows > 0;

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
    $stmt = $conn->prepare("SELECT id AS class_id FROM classes WHERE school_id=? ORDER BY name");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $teacherClassesSubjects[] = ['class_id' => $r['class_id'], 'subject_id' => null];
    $stmt->close();
}

$classIds = array_unique(array_column($teacherClassesSubjects, 'class_id'));
$classes = [];
foreach ($classIds as $cid) {
    $stmt = $conn->prepare("SELECT id, name, section FROM classes WHERE id=? AND school_id=?");
    $stmt->bind_param('ii', $cid, $schoolId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) $classes[] = $row;
}
usort($classes, fn($a,$b) => strcmp($a['name'].$a['section'], $b['name'].$b['section']));

$filterClass = (int) ($_GET['class_id'] ?? 0);
$filterDate  = trim($_GET['date'] ?? date('Y-m-d'));

if ($filterClass && !in_array($filterClass, $classIds)) $filterClass = 0;

$students = [];
if ($filterClass) {
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM students WHERE school_id=? AND class_id=? ORDER BY first_name, last_name");
    $stmt->bind_param('ii', $schoolId, $filterClass);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $students[] = $row;
    $stmt->close();
}

$attendanceByStudent = [];
if ($tablesExist && $filterClass && $filterDate) {
    $stmt = $conn->prepare("SELECT student_id, status, remarks FROM attendance WHERE school_id=? AND class_id=? AND date=?");
    $stmt->bind_param('iis', $schoolId, $filterClass, $filterDate);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $attendanceByStudent[$row['student_id']] = $row;
    $stmt->close();
}

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $teacherId && $tablesExist) {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_attendance') {
        $classId = (int) ($_POST['class_id'] ?? 0);
        $date = trim($_POST['date'] ?? '');
        if (!$classId || !$date || !in_array($classId, $classIds)) {
            $errors[] = 'Invalid class or date.';
        } else {
            $statuses = $_POST['status'] ?? [];
            $remarks = $_POST['remarks'] ?? [];
            $saved = 0;
            foreach ($statuses as $studentId => $status) {
                $studentId = (int) $studentId;
                $status = in_array($status, ['present','late','absent']) ? $status : 'present';
                $remark = trim($remarks[$studentId] ?? '');
                $stmt = $conn->prepare("INSERT INTO attendance (school_id, student_id, class_id, date, status, remarks, recorded_by) VALUES (?,?,?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE status=VALUES(status), remarks=VALUES(remarks), recorded_by=VALUES(recorded_by)");
                $stmt->bind_param('iiisssi', $schoolId, $studentId, $classId, $date, $status, $remark, $userId);
                $stmt->execute();
                $saved += $stmt->affected_rows;
                $stmt->close();
            }
            $success = "Attendance saved for " . date('d M Y', strtotime($date)) . ". ($saved record(s) updated.)";
        }
    }
}

$present = 0;
$late = 0;
$absent = 0;
foreach ($attendanceByStudent as $a) {
    if ($a['status'] === 'present') $present++;
    elseif ($a['status'] === 'late') $late++;
    else $absent++;
}
?>

<?php if (!$teacherId): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-800">Your account is not linked to a teacher record. Please contact the admin.</p>
</div>
<?php elseif (!$tablesExist): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-700">Run <code class="bg-amber-100 px-1 rounded">database_migration_photos_attendance.sql</code> first.</p>
</div>
<?php else: ?>

<?php if ($errors): ?>
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700"><?= htmlspecialchars(implode(' ', $errors)) ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <h2 class="text-sm font-semibold text-slate-800 mb-4">Student attendance</h2>
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
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Date</label>
            <input type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>" class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700">
        </div>
        <button type="submit" class="border border-emerald-600 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-3 py-1.5 text-xs font-medium">Filter</button>
    </form>
    <div class="mt-4 flex flex-wrap items-center gap-3 text-[11px] text-slate-500">
        <span class="font-medium text-slate-600">Legend:</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-emerald-500"></span> Present</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-amber-400"></span> Late</span>
        <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-rose-500"></span> Absent</span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden">
        <?php if ($filterClass): ?>
        <form method="post">
            <input type="hidden" name="action" value="save_attendance">
            <input type="hidden" name="class_id" value="<?= $filterClass ?>">
            <input type="hidden" name="date" value="<?= htmlspecialchars($filterDate) ?>">

            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-800">Daily attendance — <?= htmlspecialchars($filterDate) ?></span>
                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">
                    <i data-lucide="save" class="w-3.5 h-3.5"></i>
                    Save attendance
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs text-left text-slate-700">
                    <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">#</th>
                        <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Student</th>
                        <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Status</th>
                        <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Remarks</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $idx = 0;
                    foreach ($students as $st):
                        $idx++;
                        $sid = (int)$st['id'];
                        $a = $attendanceByStudent[$sid] ?? null;
                        $currentStatus = $a ? $a['status'] : 'present';
                        $currentRemarks = $a ? ($a['remarks'] ?? '') : '';
                    ?>
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-2 text-slate-400"><?= $idx ?></td>
                        <td class="px-4 py-2 font-medium"><?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?></td>
                        <td class="px-4 py-2">
                            <div class="inline-flex gap-1">
                                <label class="cursor-pointer">
                                    <input type="radio" name="status[<?= $sid ?>]" value="present" <?= $currentStatus === 'present' ? 'checked' : '' ?> class="sr-only peer">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] border peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:border-emerald-600 <?= $currentStatus === 'present' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-emerald-50' ?>">P</span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="status[<?= $sid ?>]" value="late" <?= $currentStatus === 'late' ? 'checked' : '' ?> class="sr-only peer">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] border peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-600 <?= $currentStatus === 'late' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-amber-50' ?>">L</span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="status[<?= $sid ?>]" value="absent" <?= $currentStatus === 'absent' ? 'checked' : '' ?> class="sr-only peer">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] border peer-checked:bg-rose-500 peer-checked:text-white peer-checked:border-rose-600 <?= $currentStatus === 'absent' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-rose-50' ?>">A</span>
                                </label>
                            </div>
                        </td>
                        <td class="px-4 py-2">
                            <input type="text" name="remarks[<?= $sid ?>]" value="<?= htmlspecialchars($currentRemarks) ?>" placeholder="Optional" class="w-full border border-slate-200 rounded px-2 py-1 text-[11px]">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
        <?php else: ?>
        <div class="px-4 py-3 border-b border-slate-100">
            <span class="text-xs font-semibold text-slate-800">Daily attendance grid</span>
        </div>
        <div class="p-8 text-center text-slate-500 text-sm">Select a class to mark attendance.</div>
        <?php endif; ?>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-3">Summary</h3>
            <div class="grid grid-cols-3 gap-2 text-center">
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
        </div>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>

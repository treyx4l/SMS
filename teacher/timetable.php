<?php
$page_title = 'Timetable';
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

$hasTimetable = false;
$res = $conn->query("SHOW TABLES LIKE 'timetable_entries'");
if ($res && $res->num_rows > 0) {
    $hasTimetable = true;
}

$periods     = [];
$timetable   = []; // [period_order][day_of_week] => entry
$todaySlots  = [];
$classesById = [];
$subjectsById = [];

if ($teacherId && $schoolId && $hasTimetable) {
    // Load periods for this school
    $res = $conn->query("SHOW TABLES LIKE 'timetable_periods'");
    if ($res && $res->num_rows > 0) {
        $stmt = $conn->prepare("
            SELECT period_order, start_time, end_time, label
            FROM timetable_periods
            WHERE school_id = ?
            ORDER BY period_order
        ");
        if ($stmt) {
            $stmt->bind_param('i', $schoolId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $order = (int) $row['period_order'];
                $periods[$order] = $row;
            }
            $stmt->close();
        }
    }

    // Fallback if periods table not set up: create some generic periods
    if (!$periods) {
        for ($i = 1; $i <= 7; $i++) {
            $periods[$i] = [
                'period_order' => $i,
                'start_time'   => null,
                'end_time'     => null,
                'label'        => 'Period ' . $i,
            ];
        }
    }

    // Load timetable entries for this teacher
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
        ORDER BY e.day_of_week, e.period_order
    ");
    if ($stmt) {
        $stmt->bind_param('ii', $schoolId, $teacherId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $day   = (int) $row['day_of_week'];   // 1 = Mon ... 5 = Fri
            $order = (int) $row['period_order'];
            if (!isset($timetable[$order])) {
                $timetable[$order] = [];
            }
            $timetable[$order][$day] = $row;
        }
        $stmt->close();
    }

    // Today's schedule (based on current weekday)
    $todayDow = (int) date('N'); // 1..7
    if ($todayDow >= 1 && $todayDow <= 5) {
        foreach ($timetable as $order => $rowByDay) {
            if (isset($rowByDay[$todayDow])) {
                $slot = $rowByDay[$todayDow];
                $slot['period_order'] = $order;
                $todaySlots[] = $slot;
            }
        }
    }
}
?>

<?php if (!$teacherId): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-800">Your account is not linked to a teacher record. Please contact the admin.</p>
</div>
<?php elseif (!$hasTimetable): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-800">
        The central timetable has not been set up yet. Once the admin creates timetable entries, you will see your weekly schedule here.
    </p>
</div>
<?php elseif (empty($timetable)): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-800">
        There are currently no timetable entries linked to you. Please ask the admin to assign you to periods on the timetable.
    </p>
</div>
<?php else: ?>

<!-- Filters / selection -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">View timetable</h2>
            <p class="text-[11px] text-slate-500">
                Read-only view of your teaching periods across the week, as prepared by the admin.
            </p>
        </div>
        <div class="flex flex-wrap gap-2 text-[11px] text-slate-500">
            <span class="inline-flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Your teaching slots</span>
            </span>
        </div>
    </div>
</div>

<!-- Main layout: timetable + sidebar -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Timetable grid -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-800">Weekly timetable</span>
            <span class="text-[11px] text-slate-400">Generated from central timetable_entries; read-only.</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left text-slate-700">
                <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-3 py-2 font-semibold text-[11px] text-slate-500">Time / Period</th>
                    <th class="px-3 py-2 font-semibold text-[11px] text-slate-500 text-center">Monday</th>
                    <th class="px-3 py-2 font-semibold text-[11px] text-slate-500 text-center">Tuesday</th>
                    <th class="px-3 py-2 font-semibold text-[11px] text-slate-500 text-center">Wednesday</th>
                    <th class="px-3 py-2 font-semibold text-[11px] text-slate-500 text-center">Thursday</th>
                    <th class="px-3 py-2 font-semibold text-[11px] text-slate-500 text-center">Friday</th>
                </tr>
                </thead>
                <tbody>
                <?php
                ksort($periods);
                foreach ($periods as $order => $p):
                    $label = $p['label'] ?: ('Period ' . $order);
                    if (!empty($p['start_time']) && !empty($p['end_time'])) {
                        $timeLabel = date('H:i', strtotime($p['start_time'])) . ' – ' . date('H:i', strtotime($p['end_time']));
                    } else {
                        $timeLabel = $label;
                    }
                ?>
                <tr class="border-b border-slate-100">
                    <td class="px-3 py-2 text-[11px] text-slate-500">
                        <div class="font-semibold text-slate-700"><?= htmlspecialchars($label) ?></div>
                        <div class="text-[10px] text-slate-400"><?= htmlspecialchars($timeLabel) ?></div>
                    </td>
                    <?php for ($day = 1; $day <= 5; $day++): ?>
                    <?php $entry = $timetable[$order][$day] ?? null; ?>
                    <td class="px-3 py-2 align-top">
                        <?php if ($entry): ?>
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[10px]">
                            <div class="font-semibold text-emerald-800"><?= htmlspecialchars($entry['subject_name'] ?? 'Subject') ?></div>
                            <div class="text-[10px] text-slate-500">
                                <?= htmlspecialchars(($entry['class_name'] ?? 'Class') . (!empty($entry['class_section']) ? ' ' . $entry['class_section'] : '')) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php endfor; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sidebar: today + legend -->
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">Today’s schedule</h3>
            <?php if (!$todaySlots): ?>
            <p class="text-[11px] text-slate-500">
                You have no periods on the timetable for today.
            </p>
            <?php else: ?>
            <div class="space-y-2 text-[11px]">
                <?php foreach ($todaySlots as $slot): ?>
                <?php
                    $order = (int) $slot['period_order'];
                    $p     = $periods[$order] ?? null;
                    $timeLabel = $p && !empty($p['start_time']) && !empty($p['end_time'])
                        ? date('H:i', strtotime($p['start_time'])) . ' – ' . date('H:i', strtotime($p['end_time']))
                        : ('Period ' . $order);
                ?>
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2">
                    <div>
                        <div class="font-semibold text-slate-800"><?= htmlspecialchars($timeLabel) ?></div>
                        <div class="text-[10px] text-slate-500">
                            <?= htmlspecialchars($slot['subject_name'] ?? 'Subject') ?>
                            &middot;
                            <?= htmlspecialchars(($slot['class_name'] ?? 'Class') . (!empty($slot['class_section']) ? ' ' . $slot['class_section'] : '')) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">Legend &amp; notes</h3>
            <p class="text-[11px] text-slate-500 mb-2">
                This timetable is read-only for teachers. The admin controls the class, subject and teacher assigned to each period.
            </p>
            <ul class="text-[11px] text-slate-500 space-y-1 mb-2">
                <li><span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-1"></span>Your teaching slots.</li>
                <li>Use this view alongside your attendance and grading pages to stay on top of each period.</li>
            </ul>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>


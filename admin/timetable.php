<?php
$page_title = 'Timetable';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

// Check if tables exist
$tablesExist = false;
$res = $conn->query("SHOW TABLES LIKE 'timetable_periods'");
if ($res && $res->num_rows > 0) {
    $res2 = $conn->query("SHOW TABLES LIKE 'timetable_entries'");
    $tablesExist = $res2 && $res2->num_rows > 0;
}

$errors  = [];
$success = null;
$days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tablesExist) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_periods') {
        $periods = [];
        if (!empty($_POST['periods']) && is_array($_POST['periods'])) {
            foreach ($_POST['periods'] as $p) {
                $order = (int) ($p['order'] ?? 0);
                $start = trim($p['start'] ?? '');
                $end   = trim($p['end'] ?? '');
                if ($order > 0 && $start && $end) {
                    $periods[] = ['order' => $order, 'start' => $start, 'end' => $end];
                }
            }
        }
        usort($periods, fn($a, $b) => $a['order'] <=> $b['order']);

        $stmt = $conn->prepare("DELETE FROM timetable_periods WHERE school_id = ?");
        $stmt->bind_param('i', $schoolId);
        $stmt->execute();
        $stmt->close();

        foreach ($periods as $p) {
            $stmt = $conn->prepare("INSERT INTO timetable_periods (school_id, period_order, start_time, end_time, label) VALUES (?, ?, ?, ?, ?)");
            $label = "Period {$p['order']}";
            $stmt->bind_param('iisss', $schoolId, $p['order'], $p['start'], $p['end'], $label);
            $stmt->execute();
            $stmt->close();
        }
        $success = 'Periods saved.';
    } elseif ($action === 'save_entry') {
        $class_id   = (int) ($_POST['class_id'] ?? 0);
        $subject_id = (int) ($_POST['subject_id'] ?? 0);
        $teacher_id = (int) ($_POST['teacher_id'] ?? 0);
        $day_of_week = (int) ($_POST['day_of_week'] ?? 0);
        $period_order = (int) ($_POST['period_order'] ?? 0);

        if ($class_id && $subject_id && $teacher_id && $day_of_week >= 1 && $day_of_week <= 5 && $period_order > 0) {
            $stmt = $conn->prepare("INSERT INTO timetable_entries (school_id, class_id, subject_id, teacher_id, day_of_week, period_order) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE subject_id=VALUES(subject_id), teacher_id=VALUES(teacher_id)");
            $stmt->bind_param('iiiiii', $schoolId, $class_id, $subject_id, $teacher_id, $day_of_week, $period_order);
            $stmt->execute();
            $stmt->close();
            $success = 'Timetable entry saved.';
        } else {
            $errors[] = 'Invalid entry data.';
        }
    } elseif ($action === 'delete_entry') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM timetable_entries WHERE id = ? AND school_id = ?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();
            $success = 'Entry removed.';
        }
    }
}

// Load periods
$periods = [];
if ($tablesExist) {
    $stmt = $conn->prepare("SELECT * FROM timetable_periods WHERE school_id = ? ORDER BY period_order");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $periods[] = $row;
    $stmt->close();
}

// Default periods if none
if (empty($periods) && $tablesExist) {
    $defaults = [
        ['order' => 1, 'start' => '08:00', 'end' => '08:45'],
        ['order' => 2, 'start' => '08:50', 'end' => '09:35'],
        ['order' => 3, 'start' => '09:50', 'end' => '10:35'],
        ['order' => 4, 'start' => '10:40', 'end' => '11:25'],
        ['order' => 5, 'start' => '11:30', 'end' => '12:15'],
    ];
    foreach ($defaults as $d) {
        $periods[] = ['period_order' => $d['order'], 'start_time' => $d['start'], 'end_time' => $d['end']];
    }
}

// Load classes, subjects, teachers
$classes  = [];
$subjects = [];
$teachers = [];
$stmt = $conn->prepare("SELECT id, name, section FROM classes WHERE school_id = ? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $classes[] = $row;
$stmt->close();

$stmt = $conn->prepare("SELECT id, name, code FROM subjects WHERE school_id = ? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $subjects[] = $row;
$stmt->close();

$stmt = $conn->prepare("SELECT id, full_name FROM teachers WHERE school_id = ? ORDER BY full_name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $teachers[] = $row;
$stmt->close();

// Load entries
$entries = [];
if ($tablesExist) {
    $stmt = $conn->prepare("
        SELECT e.id, e.class_id, e.subject_id, e.teacher_id, e.day_of_week, e.period_order,
               c.name AS class_name, c.section AS class_section,
               s.name AS subject_name, t.full_name AS teacher_name
        FROM timetable_entries e
        LEFT JOIN classes c ON c.id = e.class_id
        LEFT JOIN subjects s ON s.id = e.subject_id
        LEFT JOIN teachers t ON t.id = e.teacher_id
        WHERE e.school_id = ?
        ORDER BY e.class_id, e.day_of_week, e.period_order
    ");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $entries[] = $row;
    $stmt->close();
}

$entriesBySlot = [];
foreach ($entries as $e) {
    $key = "{$e['class_id']}_{$e['day_of_week']}_{$e['period_order']}";
    $entriesBySlot[$key] = $e;
}
?>

<?php if (!$tablesExist): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <div class="flex items-start gap-3">
        <i data-lucide="alert-triangle" class="w-6 h-6 text-amber-600 shrink-0"></i>
        <div>
            <h3 class="text-sm font-semibold text-amber-800">Run migration first</h3>
            <p class="text-sm text-amber-700 mt-1">Execute <code class="bg-amber-100 px-1 rounded">database_migration_schools_profile_timetable_integrations.sql</code> to create the timetable tables.</p>
        </div>
    </div>
</div>
<?php else: ?>

<?php if ($errors): ?>
<div class="flex items-center gap-2.5 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600">
    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
    <?= htmlspecialchars(implode(' ', $errors)) ?>
</div>
<?php elseif ($success): ?>
<div class="flex items-center gap-2.5 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
    <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<!-- Periods config -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden mb-6">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <span class="text-sm font-semibold text-slate-800">Period times</span>
        <span class="text-xs text-slate-500 ml-2">Define start and end for each period</span>
    </div>
    <form method="post" class="p-5">
        <input type="hidden" name="action" value="save_periods">
        <div class="flex flex-wrap gap-4" id="periodsContainer">
            <?php foreach ($periods as $i => $p): ?>
            <div class="flex items-center gap-2">
                <span class="text-xs font-medium text-slate-500 w-16">Period <?= (int)$p['period_order'] ?></span>
                <input type="hidden" name="periods[<?= $i ?>][order]" value="<?= (int)$p['period_order'] ?>">
                <input type="time" name="periods[<?= $i ?>][start]" value="<?= htmlspecialchars(substr($p['start_time'], 0, 5)) ?>" class="px-2 py-1.5 border border-slate-200 rounded text-sm">
                <span class="text-slate-400">–</span>
                <input type="time" name="periods[<?= $i ?>][end]" value="<?= htmlspecialchars(substr($p['end_time'], 0, 5)) ?>" class="px-2 py-1.5 border border-slate-200 rounded text-sm">
            </div>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="mt-4 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">Save periods</button>
    </form>
</div>

<!-- Add entry -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden mb-6">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <span class="text-sm font-semibold text-slate-800">Add timetable entry</span>
    </div>
    <form method="post" class="p-5">
        <input type="hidden" name="action" value="save_entry">
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Class</label>
                <select name="class_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">—</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name'] . ($c['section'] ? ' ' . $c['section'] : '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Subject</label>
                <select name="subject_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">—</option>
                    <?php foreach ($subjects as $s): ?>
                    <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Teacher</label>
                <select name="teacher_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">—</option>
                    <?php foreach ($teachers as $t): ?>
                    <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Day</label>
                <select name="day_of_week" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <?php foreach ($days as $d => $label): ?>
                    <option value="<?= $d ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Period</label>
                <select name="period_order" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <?php for ($i = 1; $i <= max(8, count($periods)); $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">Add</button>
            </div>
        </div>
    </form>
</div>

<!-- Entries table -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <span class="text-sm font-semibold text-slate-800">Timetable entries</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50">
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Class</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Subject</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Teacher</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Day</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Period</th>
                    <th class="text-right px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($entries)): ?>
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-slate-400">No entries. Add one above.</td>
                </tr>
                <?php else: foreach ($entries as $e): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($e['class_name'] . ($e['class_section'] ? ' ' . $e['class_section'] : '')) ?></td>
                    <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($e['subject_name']) ?></td>
                    <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($e['teacher_name']) ?></td>
                    <td class="px-4 py-3 text-slate-500"><?= $days[$e['day_of_week']] ?? $e['day_of_week'] ?></td>
                    <td class="px-4 py-3 text-slate-500"><?= (int)$e['period_order'] ?></td>
                    <td class="px-4 py-3 text-right">
                        <form method="post" class="inline" onsubmit="return confirm('Remove this entry?');">
                            <input type="hidden" name="action" value="delete_entry">
                            <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>

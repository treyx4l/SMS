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

// Selected class for grid view
$selectedClassId = (int) ($_GET['class_id'] ?? ($classes[0]['id'] ?? 0));
$selectedClass = null;
foreach ($classes as $c) {
    if ($c['id'] == $selectedClassId) { $selectedClass = $c; break; }
}

// Load entries for the selected class
$entries = [];
if ($tablesExist && $selectedClassId) {
    $stmt = $conn->prepare("
        SELECT e.id, e.class_id, e.subject_id, e.teacher_id, e.day_of_week, e.period_order,
               c.name AS class_name, c.section AS class_section,
               s.name AS subject_name, t.full_name AS teacher_name
        FROM timetable_entries e
        LEFT JOIN classes c ON c.id = e.class_id
        LEFT JOIN subjects s ON s.id = e.subject_id
        LEFT JOIN teachers t ON t.id = e.teacher_id
        WHERE e.school_id = ? AND e.class_id = ?
        ORDER BY e.day_of_week, e.period_order
    ");
    $stmt->bind_param('ii', $schoolId, $selectedClassId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $entries[] = $row;
    $stmt->close();
}

$entriesBySlot = [];
foreach ($entries as $e) {
    $key = "{$e['day_of_week']}_{$e['period_order']}";
    $entriesBySlot[$key] = $e;
}

// Subject colors palette
$subjectColors = [
    '#4f46e5','#0891b2','#059669','#d97706','#dc2626','#7c3aed','#db2777','#0284c7','#16a34a','#ca8a04'
];
$subjectColorMap = [];
$colorIdx = 0;
foreach ($subjects as $s) {
    $subjectColorMap[$s['id']] = $subjectColors[$colorIdx % count($subjectColors)];
    $colorIdx++;
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
<div class="flex items-center gap-2.5 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600 mb-4">
    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
    <?= htmlspecialchars(implode(' ', $errors)) ?>
</div>
<?php elseif ($success): ?>
<div class="flex items-center gap-2.5 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700 mb-4">
    <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<!-- Top bar: class selector + actions -->
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div class="flex items-center gap-3">
        <span class="text-sm font-semibold text-slate-700">Class:</span>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($classes as $c): ?>
            <?php $label = $c['name'] . ($c['section'] ? ' ' . $c['section'] : ''); ?>
            <a href="?class_id=<?= (int)$c['id'] ?>"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors
                      <?= $c['id'] == $selectedClassId
                          ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                          : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-400 hover:text-indigo-600' ?>">
                <?= htmlspecialchars($label) ?>
            </a>
            <?php endforeach; ?>
            <?php if (empty($classes)): ?>
            <span class="text-sm text-slate-400 italic">No classes found</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="document.getElementById('addEntryModal').classList.remove('hidden')"
                class="flex items-center gap-1.5 px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
            <i data-lucide="plus" class="w-4 h-4"></i> Add entry
        </button>
        <button onclick="document.getElementById('periodsModal').classList.remove('hidden')"
                class="flex items-center gap-1.5 px-3 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50">
            <i data-lucide="clock" class="w-4 h-4"></i> Periods
        </button>
    </div>
</div>

<?php if (empty($classes)): ?>
<div class="bg-white border border-slate-200 rounded-xl p-12 text-center">
    <i data-lucide="layout-grid" class="w-12 h-12 text-slate-300 mx-auto mb-3"></i>
    <p class="text-sm text-slate-500 font-medium">No classes found</p>
    <p class="text-xs text-slate-400 mt-1">Create classes first, then build the timetable.</p>
</div>
<?php else: ?>

<!-- Timetable Grid -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
    <!-- Header: school timetable title bar -->
    <div class="bg-indigo-700 text-white px-5 py-3 flex items-center justify-between">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wider text-indigo-200">Weekly Timetable</div>
            <div class="text-base font-bold mt-0.5">
                <?php if ($selectedClass): ?>
                    Class: <?= htmlspecialchars($selectedClass['name'] . ($selectedClass['section'] ? ' — ' . $selectedClass['section'] : '')) ?>
                <?php else: ?>
                    Select a class above
                <?php endif; ?>
            </div>
        </div>
        <i data-lucide="calendar" class="w-6 h-6 text-indigo-300"></i>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse" style="min-width:720px">
            <thead>
                <tr>
                    <!-- Period column header -->
                    <th class="border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-semibold text-slate-500 uppercase w-28 text-center">
                        Period / Time
                    </th>
                    <?php foreach ($days as $dayNum => $dayName): ?>
                    <th class="border border-slate-200 bg-indigo-50 px-3 py-2.5 text-xs font-bold text-indigo-700 uppercase text-center">
                        <?= $dayName ?>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($periods as $p): ?>
                <?php $periodOrder = (int)$p['period_order']; ?>
                <tr>
                    <!-- Period label cell -->
                    <td class="border border-slate-200 bg-slate-50 px-3 py-2 text-center align-middle">
                        <div class="text-xs font-bold text-slate-700">Period <?= $periodOrder ?></div>
                        <div class="text-[10px] text-slate-400 mt-0.5">
                            <?= htmlspecialchars(substr($p['start_time'], 0, 5)) ?> – <?= htmlspecialchars(substr($p['end_time'], 0, 5)) ?>
                        </div>
                    </td>
                    <?php foreach ($days as $dayNum => $dayName): ?>
                    <?php
                        $key = "{$dayNum}_{$periodOrder}";
                        $entry = $entriesBySlot[$key] ?? null;
                        $bgColor = $entry ? ($subjectColorMap[$entry['subject_id']] ?? '#6366f1') : null;
                        $hexToRgb = function($hex) {
                            $hex = ltrim($hex, '#');
                            return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
                        };
                    ?>
                    <td class="border border-slate-200 p-1 align-middle" style="height:80px; min-width:120px;">
                        <?php if ($entry): ?>
                        <?php
                            $rgb = $hexToRgb($bgColor);
                            $bgLight = "rgba({$rgb[0]},{$rgb[1]},{$rgb[2]},0.1)";
                            $bgMedium = "rgba({$rgb[0]},{$rgb[1]},{$rgb[2]},0.2)";
                        ?>
                        <div class="rounded-lg h-full flex flex-col justify-between p-2 group relative"
                             style="background-color: <?= $bgLight ?>; border-left: 3px solid <?= $bgColor ?>;">
                            <div>
                                <div class="text-xs font-bold leading-tight" style="color: <?= $bgColor ?>">
                                    <?= htmlspecialchars($entry['subject_name']) ?>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-0.5 leading-snug">
                                    <?= htmlspecialchars($entry['teacher_name']) ?>
                                </div>
                            </div>
                            <!-- Delete button -->
                            <form method="post" class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity"
                                  onsubmit="return confirm('Remove this entry?');">
                                <input type="hidden" name="action" value="delete_entry">
                                <input type="hidden" name="id" value="<?= (int)$entry['id'] ?>">
                                <input type="hidden" name="class_id_redirect" value="<?= $selectedClassId ?>">
                                <button type="submit" class="w-5 h-5 flex items-center justify-center rounded bg-red-100 hover:bg-red-200 text-red-600"
                                        title="Remove">
                                    <i data-lucide="x" class="w-3 h-3"></i>
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <!-- Empty slot — click to add -->
                        <button onclick="openAddEntry(<?= $dayNum ?>, <?= $periodOrder ?>, <?= $selectedClassId ?>)"
                                class="w-full h-full rounded-lg flex items-center justify-center text-slate-300 hover:bg-indigo-50 hover:text-indigo-400 transition-colors group"
                                title="Add entry for <?= $dayName ?> P<?= $periodOrder ?>">
                            <i data-lucide="plus-circle" class="w-5 h-5 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($periods)): ?>
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-slate-400 text-sm">
                        No periods configured. Click <strong>Periods</strong> to set up period times.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Legend -->
    <?php if (!empty($entries)): ?>
    <div class="border-t border-slate-100 px-4 py-3 flex flex-wrap gap-3">
        <span class="text-xs font-semibold text-slate-400 uppercase mr-1">Subjects:</span>
        <?php
        $usedSubjects = [];
        foreach ($entries as $e) {
            $usedSubjects[$e['subject_id']] = $e['subject_name'];
        }
        foreach ($usedSubjects as $sid => $sname):
            $color = $subjectColorMap[$sid] ?? '#6366f1';
            $rgb = $hexToRgb($color);
        ?>
        <span class="inline-flex items-center gap-1.5 text-xs text-slate-600">
            <span class="w-3 h-3 rounded-sm inline-block" style="background-color: <?= $color ?>"></span>
            <?= htmlspecialchars($sname) ?>
        </span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<!-- ===== Add Entry Modal ===== -->
<div id="addEntryModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('addEntryModal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 z-10">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-semibold text-slate-800">Add timetable entry</h3>
            <button onclick="document.getElementById('addEntryModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="save_entry">
            <input type="hidden" name="class_id_redirect" value="<?= $selectedClassId ?>">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Class</label>
                <select name="class_id" id="modalClassId" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">— select —</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= $c['id'] == $selectedClassId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name'] . ($c['section'] ? ' ' . $c['section'] : '')) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Day</label>
                    <select name="day_of_week" id="modalDay" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                        <?php foreach ($days as $d => $label): ?>
                        <option value="<?= $d ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Period</label>
                    <select name="period_order" id="modalPeriod" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                        <?php for ($i = 1; $i <= max(8, count($periods)); $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Subject</label>
                <select name="subject_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">— select —</option>
                    <?php foreach ($subjects as $s): ?>
                    <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Teacher</label>
                <select name="teacher_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">— select —</option>
                    <?php foreach ($teachers as $t): ?>
                    <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2 pt-1">
                <button type="button" onclick="document.getElementById('addEntryModal').classList.add('hidden')"
                        class="flex-1 px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                    Save entry
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== Periods Modal ===== -->
<div id="periodsModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('periodsModal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 z-10">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-semibold text-slate-800">Configure periods</h3>
            <button onclick="document.getElementById('periodsModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="save_periods">
            <input type="hidden" name="class_id_redirect" value="<?= $selectedClassId ?>">
            <div class="space-y-3" id="periodsContainer">
                <?php foreach ($periods as $i => $p): ?>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold text-slate-500 w-16 shrink-0">Period <?= (int)$p['period_order'] ?></span>
                    <input type="hidden" name="periods[<?= $i ?>][order]" value="<?= (int)$p['period_order'] ?>">
                    <input type="time" name="periods[<?= $i ?>][start]" value="<?= htmlspecialchars(substr($p['start_time'], 0, 5)) ?>"
                           class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <span class="text-slate-400">–</span>
                    <input type="time" name="periods[<?= $i ?>][end]" value="<?= htmlspecialchars(substr($p['end_time'], 0, 5)) ?>"
                           class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <?php endforeach; ?>
            </div>
            <div class="flex gap-2 mt-5">
                <button type="button" onclick="document.getElementById('periodsModal').classList.add('hidden')"
                        class="flex-1 px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                    Save periods
                </button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>
<script>
function openAddEntry(day, period, classId) {
    const modal = document.getElementById('addEntryModal');
    modal.classList.remove('hidden');
    const daySelect = document.getElementById('modalDay');
    const periodSelect = document.getElementById('modalPeriod');
    const classSelect = document.getElementById('modalClassId');
    if (daySelect) daySelect.value = day;
    if (periodSelect) periodSelect.value = period;
    if (classSelect && classId) classSelect.value = classId;
}

// After form submit, preserve class filter via redirect
(function() {
    document.querySelectorAll('form[method=post]').forEach(form => {
        form.addEventListener('submit', function() {
            const classRedirect = form.querySelector('[name=class_id_redirect]');
            if (classRedirect && classRedirect.value) {
                const action = form.action || window.location.pathname;
                form.action = '?class_id=' + classRedirect.value;
            }
        });
    });
})();
</script>

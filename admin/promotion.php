<?php
$page_title = 'Promotion';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$errors  = [];
$success = null;

// Check if graduation columns exist
$hasGraduatedCol = false;
$res = $conn->query("SHOW COLUMNS FROM students LIKE 'is_graduated'");
if ($res && $res->num_rows > 0) {
    $hasGraduatedCol = true;
}

// Load classes for selectors
$classes = [];
$stmt = $conn->prepare("SELECT id, name, section FROM classes WHERE school_id = ? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $classes[] = $row;
}
$stmt->close();

// Helper: label
function class_label(array $c): string {
    return $c['name'] . (!empty($c['section']) ? ' ' . $c['section'] : '');
}

// Handle promotion / graduation POST
$fromClassId = (int) ($_POST['from_class_id'] ?? ($_GET['from_class_id'] ?? 0));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $toClassId  = (int) ($_POST['to_class_id'] ?? 0);
    $studentIds = isset($_POST['student_ids']) && is_array($_POST['student_ids'])
        ? array_map('intval', array_filter($_POST['student_ids'])) : [];
    $fromClassId  = (int) ($_POST['from_class_id'] ?? 0);

    if ($action === 'promote') {
        if (!$fromClassId || !$toClassId) {
            $errors[] = 'Please select both source and destination classes.';
        }
        if (!$studentIds) {
            $errors[] = 'Select at least one student to promote.';
        }

        if (!$errors) {
            $stmt = $conn->prepare("UPDATE students SET class_id = ? WHERE id = ? AND school_id = ? AND class_id = ?");
            foreach ($studentIds as $sid) {
                $stmt->bind_param('iiii', $toClassId, $sid, $schoolId, $fromClassId);
                $stmt->execute();
            }
            $stmt->close();
            $success = 'Selected student(s) promoted successfully. Unselected students remain in their current class (repeat).';
        }
    } elseif ($action === 'graduate') {
        if (!$hasGraduatedCol) {
            $errors[] = 'Graduation is not configured. Please run the students graduation migration first.';
        } elseif (!$studentIds) {
            $errors[] = 'Select at least one student to graduate.';
        } else {
            $stmt = $conn->prepare("
                UPDATE students
                SET is_graduated = 1, graduated_at = IFNULL(graduated_at, NOW())
                WHERE id = ? AND school_id = ?
            ");
            foreach ($studentIds as $sid) {
                $stmt->bind_param('ii', $sid, $schoolId);
                $stmt->execute();
            }
            $stmt->close();
            $success = 'Selected student(s) marked as graduated and removed from active counts.';
        }
    }
}

// Fetch students in selected "from" class
$promoteFromClass = null;
foreach ($classes as $c) {
    if ((int)$c['id'] === $fromClassId) {
        $promoteFromClass = $c;
        break;
    }
}

$students = [];
if ($promoteFromClass) {
    $whereExtra = '';
    if ($hasGraduatedCol) {
        $whereExtra = ' AND (is_graduated IS NULL OR is_graduated = 0)';
    }
    $sql = "SELECT id, first_name, last_name, gender, index_no, admission_no FROM students WHERE school_id = ? AND class_id = ?{$whereExtra} ORDER BY first_name, last_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $schoolId, $fromClassId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}
?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-slate-800">Student Promotion</h2>
        <p class="text-xs text-slate-500 mt-0.5">
            Promote students from one class to the next at the end of the academic year.
            Promote the highest classes first (e.g. SS3 before SS2) so there are no overlaps.
        </p>
    </div>
</div>

<?php if ($errors): ?>
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600">
    <?= htmlspecialchars(implode(' ', $errors)) ?>
</div>
<?php elseif ($success): ?>
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<div class="bg-white border border-slate-200 rounded-xl p-5 mb-4">
    <form method="get" action="promotion.php" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Class to promote from</label>
            <select name="from_class_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="0">Select class…</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $fromClassId ? 'selected' : '' ?>>
                    <?= htmlspecialchars(class_label($c)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="md:col-span-2 text-xs text-slate-500">
            Choose the class whose students you want to promote. Students you do not select will automatically repeat the class.
        </div>
        <div class="md:col-span-3 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                <i data-lucide="refresh-ccw" class="w-4 h-4"></i>
                Load students
            </button>
        </div>
    </form>
</div>

<?php if ($promoteFromClass): ?>
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
        <div class="flex items-center gap-2">
            <i data-lucide="chevrons-up" class="w-4 h-4 text-indigo-500"></i>
            <span class="text-sm font-semibold text-slate-800">
                Promote or graduate from <?= htmlspecialchars(class_label($promoteFromClass)) ?>
            </span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50">
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        <input type="checkbox" id="selectAllStudents" class="rounded border-slate-300 text-indigo-600">
                    </th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">Name</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">Index / Admission</th>
                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">Gender</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$students): ?>
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-xs text-slate-400">
                        No students found in this class.
                    </td>
                </tr>
                <?php else: foreach ($students as $s): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3">
                        <input type="checkbox" name="student_ids[]" form="promotionForm" value="<?= (int)$s['id'] ?>" class="student-checkbox rounded border-slate-300 text-indigo-600">
                    </td>
                    <td class="px-4 py-3 text-slate-800 font-medium">
                        <?= htmlspecialchars(trim($s['first_name'] . ' ' . $s['last_name'])) ?>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">
                        <?= htmlspecialchars($s['index_no'] ?: ($s['admission_no'] ?? '—')) ?>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">
                        <?= htmlspecialchars($s['gender'] ?? '—') ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Hidden form that actually submits selected IDs (checkboxes use form attribute) -->
    <form id="promotionForm" method="post" class="px-5 py-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
        <input type="hidden" name="from_class_id" value="<?= (int)$fromClassId ?>">
        <div>
            Select students to move up or graduate. Those not selected will remain in <?= htmlspecialchars(class_label($promoteFromClass)) ?> (repeat).
        </div>
        <div class="flex items-center gap-2">
            <label class="text-xs text-slate-500">
                Promote into:
                <select name="to_class_id" class="ml-1 px-2 py-1 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                    <option value="0">Select next class…</option>
                    <?php foreach ($classes as $c): ?>
                    <?php if ((int)$c['id'] !== $fromClassId): ?>
                    <option value="<?= (int)$c['id'] ?>">
                        <?= htmlspecialchars(class_label($c)) ?>
                    </option>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" name="action" value="promote" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-medium hover:bg-emerald-700">
                <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                Promote selected
            </button>
            <?php if ($hasGraduatedCol): ?>
            <button type="submit" name="action" value="graduate" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-medium hover:bg-slate-800">
                <i data-lucide="graduation-cap" class="w-3 h-3"></i>
                Graduate selected
            </button>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php else: ?>
<div class="bg-white border border-slate-200 rounded-xl p-6 text-xs text-slate-500">
    Select a class above to see students eligible for promotion.
</div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>
<script>
document.getElementById('selectAllStudents')?.addEventListener('change', function () {
    const checked = this.checked;
    document.querySelectorAll('.student-checkbox').forEach(cb => { cb.checked = checked; });
});
</script>


<?php
$page_title = 'Classes';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$tablesExist = (bool) ($conn->query("SHOW TABLES LIKE 'class_subjects'")->num_rows ?? 0);

$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_subjects' && $tablesExist) {
        $class_id = (int) ($_POST['class_id'] ?? 0);
        $subject_ids = isset($_POST['subject_ids']) && is_array($_POST['subject_ids'])
            ? array_map('intval', array_filter($_POST['subject_ids'])) : [];
        if ($class_id) {
            $stmt = $conn->prepare("DELETE FROM class_subjects WHERE class_id = ? AND school_id = ?");
            $stmt->bind_param('ii', $class_id, $schoolId);
            $stmt->execute();
            $stmt->close();
            foreach ($subject_ids as $sid) {
                if ($sid > 0) {
                    $stmt = $conn->prepare("INSERT INTO class_subjects (school_id, class_id, subject_id) VALUES (?, ?, ?)");
                    $stmt->bind_param('iii', $schoolId, $class_id, $sid);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            $success = 'Subjects assigned to class.';
        }
    } elseif ($action === 'save') {
        $id         = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $name       = trim($_POST['name'] ?? '');
        $section    = trim($_POST['section'] ?? '');
        $subjectIds = isset($_POST['subject_ids']) && is_array($_POST['subject_ids'])
            ? array_map('intval', array_filter($_POST['subject_ids'])) : [];

        if ($name === '') {
            $errors[] = 'Class name is required.';
        }

        if (!$errors) {
            if ($id) {
                $stmt = $conn->prepare(
                    "UPDATE classes SET name = ?, section = ? WHERE id = ? AND school_id = ?"
                );
                $stmt->bind_param('ssii', $name, $section, $id, $schoolId);
                $stmt->execute();
                $stmt->close();
                if ($tablesExist) {
                    $stmt = $conn->prepare("DELETE FROM class_subjects WHERE class_id = ? AND school_id = ?");
                    $stmt->bind_param('ii', $id, $schoolId);
                    $stmt->execute();
                    $stmt->close();
                    foreach ($subjectIds as $sid) {
                        if ($sid > 0) {
                            $stmt = $conn->prepare("INSERT INTO class_subjects (school_id, class_id, subject_id) VALUES (?, ?, ?)");
                            $stmt->bind_param('iii', $schoolId, $id, $sid);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
                $success = 'Class updated.';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO classes (school_id, name, section) VALUES (?, ?, ?)"
                );
                $stmt->bind_param('iss', $schoolId, $name, $section);
                $stmt->execute();
                $newId = (int) $stmt->insert_id;
                $stmt->close();
                if ($tablesExist && $newId) {
                    foreach ($subjectIds as $sid) {
                        if ($sid > 0) {
                            $stmt = $conn->prepare("INSERT INTO class_subjects (school_id, class_id, subject_id) VALUES (?, ?, ?)");
                            $stmt->bind_param('iii', $schoolId, $newId, $sid);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
                $success = 'Class added.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM classes WHERE id = ? AND school_id = ?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();
            $success = 'Class deleted.';
        }
    }
}

// Fetch classes
$classes = [];
$stmt    = $conn->prepare("SELECT * FROM classes WHERE school_id = ? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $classes[] = $row;
}
$stmt->close();

// Fetch subjects
$subjects = [];
$stmt = $conn->prepare("SELECT id, name, code FROM subjects WHERE school_id = ? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $subjects[] = $row;
$stmt->close();

// Class-subject assignments
$classSubjectIds = [];
if ($tablesExist) {
    $stmt = $conn->prepare("SELECT class_id, subject_id FROM class_subjects WHERE school_id = ?");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $classSubjectIds[$row['class_id']][] = (int) $row['subject_id'];
    }
    $stmt->close();
}
?>

<?php if ($errors): ?>
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600">
    <?= htmlspecialchars(implode(' ', $errors)) ?>
</div>
<?php endif; ?>
<?php if ($success): ?>
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden mb-4">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Add Class</div>
        <div class="text-xs text-slate-500 mt-0.5">Classes are specific to your school only</div>
    </div>

    <form method="post" class="p-5">
        <input type="hidden" name="action" value="save">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Class Name *</label>
                <input type="text" name="name" required placeholder="e.g. JSS1"
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Section</label>
                <input type="text" name="section" placeholder="e.g. A"
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <?php if ($tablesExist && $subjects): ?>
        <div class="mt-4">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Subjects</label>
            <div class="flex flex-wrap gap-3">
                <?php foreach ($subjects as $s): ?>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="subject_ids[]" value="<?= (int)$s['id'] ?>" class="rounded border-slate-300 text-indigo-600">
                    <span class="text-sm text-slate-700"><?= htmlspecialchars($s['name']) ?><?= $s['code'] ? ' (' . htmlspecialchars($s['code']) . ')' : '' ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-4">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                Add Class
            </button>
        </div>
    </form>
</div>

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Classes</div>
        <div class="text-xs text-slate-500 mt-0.5">Only classes for your school</div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50">
                <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">#</th>
                <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Name</th>
                <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Section</th>
                <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Subjects</th>
                <th class="text-right px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php if (!$classes): ?>
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-slate-400">No classes.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($classes as $index => $class): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3"><?= $index + 1 ?></td>
                        <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($class['name']) ?></td>
                        <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars($class['section'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <?php
                            $assigned = $classSubjectIds[$class['id']] ?? [];
                            $subjNames = [];
                            foreach ($subjects as $s) {
                                if (in_array((int)$s['id'], $assigned)) $subjNames[] = $s['name'];
                            }
                            ?>
                            <span class="text-xs text-slate-600"><?= !empty($subjNames) ? implode(', ', $subjNames) : '—' ?></span>
                            <?php if ($tablesExist): ?>
                            <button type="button" onclick='openAssignSubjectsModal(<?= json_encode([
                                "id" => (int)$class["id"],
                                "name" => $class["name"],
                                "section" => $class["section"] ?? "",
                                "subject_ids" => $assigned
                            ]) ?>)' class="ml-1 text-indigo-600 hover:text-indigo-700 text-[10px] font-medium">Assign</button>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" onclick='openEditClassModal(<?= json_encode([
                                'id' => (int) $class['id'],
                                'name' => $class['name'],
                                'section' => $class['section'] ?? '',
                                'subject_ids' => $classSubjectIds[$class['id']] ?? []
                            ]) ?>)'
                                    class="inline-flex px-2.5 py-1.5 text-xs font-medium border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50">Edit</button>
                            <form method="post" class="inline ml-2" onsubmit="return confirm('Delete this class?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $class['id'] ?>">
                                <button type="submit" class="inline-flex px-2.5 py-1.5 text-xs font-medium border border-red-200 text-red-600 rounded-lg hover:bg-red-50">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Class Modal -->
<div id="editClassModal" class="hidden fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md mx-4">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
            <span class="text-sm font-semibold text-slate-800">Edit Class</span>
            <button type="button" onclick="closeEditClassModal()" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form method="post" id="editClassForm" class="p-5">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="editClassId">

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Class Name *</label>
                    <input type="text" name="name" id="editClassName" required
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Section</label>
                    <input type="text" name="section" id="editClassSection"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <?php if ($tablesExist && $subjects): ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">Subjects</label>
                    <div class="max-h-32 overflow-y-auto border border-slate-200 rounded-lg p-3 space-y-2">
                        <?php foreach ($subjects as $s): ?>
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 rounded px-2 py-1 -mx-2">
                            <input type="checkbox" name="subject_ids[]" value="<?= (int)$s['id'] ?>" class="edit-class-subject-cb rounded border-slate-300 text-indigo-600">
                            <span class="text-sm text-slate-700"><?= htmlspecialchars($s['name']) ?><?= $s['code'] ? ' (' . htmlspecialchars($s['code']) . ')' : '' ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="flex gap-2 mt-4">
                <button type="button" onclick="closeEditClassModal()" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Update Class</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditClassModal(data) {
    document.getElementById('editClassId').value = data.id;
    document.getElementById('editClassName').value = data.name || '';
    document.getElementById('editClassSection').value = data.section || '';
    const ids = data.subject_ids || [];
    document.querySelectorAll('.edit-class-subject-cb').forEach(cb => {
        cb.checked = ids.indexOf(parseInt(cb.value, 10)) >= 0;
    });
    document.getElementById('editClassModal').classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
}

function closeEditClassModal() {
    document.getElementById('editClassModal').classList.add('hidden');
}

function openAssignSubjectsModal(data) {
    document.getElementById('assignClassId').value = data.id;
    document.getElementById('assignClassLabel').textContent = (data.name || '') + (data.section ? ' ' + data.section : '');
    const ids = data.subject_ids || [];
    document.querySelectorAll('#assignSubjectsForm input[name="subject_ids[]"]').forEach(cb => {
        cb.checked = ids.indexOf(parseInt(cb.value, 10)) >= 0;
    });
    document.getElementById('assignSubjectsModal').classList.remove('hidden');
}
function closeAssignSubjectsModal() {
    document.getElementById('assignSubjectsModal').classList.add('hidden');
}
</script>

<!-- Assign Subjects Modal -->
<?php if ($tablesExist): ?>
<div id="assignSubjectsModal" class="hidden fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md mx-4">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
            <span class="text-sm font-semibold text-slate-800">Assign subjects to <span id="assignClassLabel"></span></span>
            <button type="button" onclick="closeAssignSubjectsModal()" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form method="post" id="assignSubjectsForm" class="p-5">
            <input type="hidden" name="action" value="save_subjects">
            <input type="hidden" name="class_id" id="assignClassId">
            <p class="text-xs text-slate-500 mb-3">Select subjects offered in this class. Teachers are assigned via Timetable.</p>
            <div class="max-h-48 overflow-y-auto border border-slate-200 rounded-lg p-3 space-y-2">
                <?php foreach ($subjects as $s): ?>
                <label class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 rounded px-2 py-1 -mx-2">
                    <input type="checkbox" name="subject_ids[]" value="<?= (int)$s['id'] ?>" class="rounded border-slate-300 text-indigo-600">
                    <span class="text-sm text-slate-700"><?= htmlspecialchars($s['name']) ?><?= $s['code'] ? ' (' . htmlspecialchars($s['code']) . ')' : '' ?></span>
                </label>
                <?php endforeach; ?>
                <?php if (!$subjects): ?>
                <p class="text-xs text-slate-500">No subjects. Add subjects first.</p>
                <?php endif; ?>
            </div>
            <div class="flex gap-2 mt-4">
                <button type="button" onclick="closeAssignSubjectsModal()" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>

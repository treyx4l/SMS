<?php
$page_title = 'Subjects';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id   = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');

        if ($name === '') {
            $errors[] = 'Subject name is required.';
        }

        // Subject code must be unique per school when provided
        if (!$errors && $code !== '') {
            $excludeId = $id ?: 0;
            $stmt = $conn->prepare("SELECT id FROM subjects WHERE school_id = ? AND code = ? AND id != ? LIMIT 1");
            $stmt->bind_param('isi', $schoolId, $code, $excludeId);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                $errors[] = 'Another subject already uses this code. Each subject code must be unique.';
            }
            $stmt->close();
        }

        if (!$errors) {
            if ($id) {
                $codeVal = $code !== '' ? $code : null;
                $stmt = $conn->prepare(
                    "UPDATE subjects SET name = ?, code = ? WHERE id = ? AND school_id = ?"
                );
                $stmt->bind_param('ssii', $name, $codeVal, $id, $schoolId);
                $stmt->execute();
                $stmt->close();
                $success = 'Subject updated.';
            } else {
                // Cap: max subjects per school
                $capStmt = $conn->prepare("SELECT COUNT(*) FROM subjects WHERE school_id = ?");
                $capStmt->bind_param('i', $schoolId);
                $capStmt->execute();
                if ((int) $capStmt->get_result()->fetch_row()[0] >= SCHOOL_LIMIT_SUBJECTS) {
                    $capStmt->close();
                    $errors[] = 'This school has reached the maximum of ' . SCHOOL_LIMIT_SUBJECTS . ' subjects.';
                } else {
                    $capStmt->close();
                $stmt = $conn->prepare(
                    "INSERT INTO subjects (school_id, name, code) VALUES (?, ?, ?)"
                );
                $codeVal = $code !== '' ? $code : null;
                $stmt->bind_param('iss', $schoolId, $name, $codeVal);
                $stmt->execute();
                $stmt->close();
                $success = 'Subject added.';
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ? AND school_id = ?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();
            $success = 'Subject deleted.';
        }
    }
}

// Fetch subjects
$subjects = [];
$stmt     = $conn->prepare("SELECT * FROM subjects WHERE school_id = ? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();
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
        <div class="text-sm font-semibold text-slate-800">Add Subject</div>
        <div class="text-xs text-slate-500 mt-0.5">Subjects are specific to your school. Each code must be unique.</div>
    </div>

    <form method="post" class="p-5">
        <input type="hidden" name="action" value="save">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Subject Name *</label>
                <input type="text" name="name" required placeholder="e.g. Mathematics"
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Code (unique)</label>
                <input type="text" name="code" placeholder="e.g. MATH"
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                Add Subject
            </button>
        </div>
    </form>
</div>

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <div class="text-sm font-semibold text-slate-800">Subjects</div>
        <div class="text-xs text-slate-500 mt-0.5">Only subjects for your school</div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
            <tr class="border-b border-slate-100 bg-slate-50">
                <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">#</th>
                <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Name</th>
                <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Code</th>
                <th class="text-right px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php if (!$subjects): ?>
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-slate-400">No subjects.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($subjects as $index => $subject): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3"><?= $index + 1 ?></td>
                        <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($subject['name']) ?></td>
                        <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars($subject['code'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" onclick='openEditSubjectModal(<?= json_encode([
                                'id' => (int) $subject['id'],
                                'name' => $subject['name'],
                                'code' => $subject['code'] ?? ''
                            ]) ?>)'
                                    class="inline-flex px-2.5 py-1.5 text-xs font-medium border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50">Edit</button>
                            <form method="post" class="inline ml-2" onsubmit="return confirm('Delete this subject?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $subject['id'] ?>">
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

<!-- Edit Subject Modal -->
<div id="editSubjectModal" class="hidden fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md mx-4">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
            <span class="text-sm font-semibold text-slate-800">Edit Subject</span>
            <button type="button" onclick="closeEditSubjectModal()" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form method="post" id="editSubjectForm" class="p-5">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="editSubjectId">

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Subject Name *</label>
                    <input type="text" name="name" id="editSubjectName" required
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Code (unique)</label>
                    <input type="text" name="code" id="editSubjectCode"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div class="flex gap-2 mt-4">
                <button type="button" onclick="closeEditSubjectModal()" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Update Subject</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditSubjectModal(data) {
    document.getElementById('editSubjectId').value = data.id;
    document.getElementById('editSubjectName').value = data.name || '';
    document.getElementById('editSubjectCode').value = data.code || '';
    document.getElementById('editSubjectModal').classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
}

function closeEditSubjectModal() {
    document.getElementById('editSubjectModal').classList.add('hidden');
}
</script>
<?php require __DIR__ . '/footer.php'; ?>

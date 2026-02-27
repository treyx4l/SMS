<?php
$page_title = 'Students';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

// Use index_no if column exists, else admission_no for backward compatibility
$hasIndexNo = false;
$res = $conn->query("SHOW COLUMNS FROM students LIKE 'index_no'");
if ($res && $res->num_rows > 0) {
    $hasIndexNo = true;
}
$idCol = $hasIndexNo ? 'index_no' : 'admission_no';

$errors  = [];
$success = null;

// Handle POST actions (save, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id           = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $first_name   = trim($_POST['first_name'] ?? '');
        $last_name    = trim($_POST['last_name'] ?? '');
        $index_no     = trim($_POST['index_no'] ?? '');
        $admission_no = $index_no; // fallback for old schema
        $gender       = !empty($_POST['gender']) ? $_POST['gender'] : null;
        $class_id     = !empty($_POST['class_id']) ? (int) $_POST['class_id'] : null;
        $phone        = trim($_POST['phone'] ?? '');
        $fingerprint  = trim($_POST['fingerprint_data'] ?? '');

        if ($first_name === '' || $last_name === '' || $index_no === '') {
            $errors[] = 'First name, last name and index number are required.';
        }

        if (!$errors) {
            // Handle photo upload (only when schema has photo_path)
            $photoPath = null;
            if ($hasIndexNo && !empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(__DIR__) . '/storage/students/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    $filename = uniqid('st_') . '.' . $ext;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
                        $photoPath = 'storage/students/' . $filename;
                    }
                }
            }

            if ($id) {
                // Update
                $set = $hasIndexNo
                    ? "first_name=?, last_name=?, gender=?, index_no=?, class_id=?, phone=?, fingerprint_data=?"
                    : "first_name=?, last_name=?, gender=?, admission_no=?, class_id=?, phone=?";
                if ($hasIndexNo && $photoPath) $set .= ", photo_path=?";
                $set .= " WHERE id=? AND school_id=?";

                $stmt = $conn->prepare("UPDATE students SET {$set}");
                $params = [$first_name, $last_name, $gender, $index_no, $class_id, $phone];
                if ($hasIndexNo) $params[] = $fingerprint ?: null;
                if ($hasIndexNo && $photoPath) $params[] = $photoPath;
                $params[] = $id;
                $params[] = $schoolId;
                $types = 'ssssss' . ($hasIndexNo ? 's' : '') . ($hasIndexNo && $photoPath ? 's' : '') . 'ii';
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
                $success = 'Student updated successfully.';
            } else {
                // Insert
                $cols = $hasIndexNo
                    ? "school_id, first_name, last_name, gender, index_no, class_id, phone, fingerprint_data"
                    : "school_id, first_name, last_name, gender, admission_no, class_id, phone";
                if ($hasIndexNo && $photoPath) $cols .= ", photo_path";

                $phs = $hasIndexNo ? "?, ?, ?, ?, ?, ?, ?, ?" : "?, ?, ?, ?, ?, ?, ?";
                if ($hasIndexNo && $photoPath) $phs .= ", ?";

                $stmt = $conn->prepare("INSERT INTO students ({$cols}) VALUES ({$phs})");
                $params = [$schoolId, $first_name, $last_name, $gender, $index_no, $class_id, $phone];
                if ($hasIndexNo) $params[] = $fingerprint ?: null;
                if ($hasIndexNo && $photoPath) $params[] = $photoPath;
                $types = 'issssss' . ($hasIndexNo ? 's' : '') . ($hasIndexNo && $photoPath ? 's' : '');
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
                $success = 'Student added successfully.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ? AND school_id = ?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();
            $success = 'Student deleted.';
        }
    }
}

// Fetch classes
$classes = [];
$stmt    = $conn->prepare("SELECT id, name, section FROM classes WHERE school_id = ? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $classes[] = $row;
$stmt->close();

// Fetch students (support both index_no and admission_no)
$students = [];
$idColSel = $hasIndexNo ? "s.index_no" : "s.admission_no AS index_no";
$extraCols = $hasIndexNo ? ", s.photo_path, s.fingerprint_data, s.class_id" : ", s.class_id";
$sql  = "SELECT s.id, s.first_name, s.last_name, s.gender, {$idColSel}, s.phone{$extraCols},
         c.name AS class_name, c.section
         FROM students s
         LEFT JOIN classes c ON c.id = s.class_id
         WHERE s.school_id = ?
         ORDER BY s.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $students[] = $row;
$stmt->close();

?>

<!-- Page header with action buttons -->
<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-slate-800">Students</h2>
        <p class="text-xs text-slate-400 mt-0.5"><?= count($students) ?> student(s)</p>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" onclick="openModal('addModal')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            Add Student
        </button>
    </div>
</div>

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

<!-- Search bar -->
<div class="mb-4">
    <div class="relative max-w-sm">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
            <i data-lucide="search" class="w-4 h-4"></i>
        </span>
        <input type="text" id="studentSearch" placeholder="Search by name or index number&hellip;"
               class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
    </div>
</div>

<!-- Students table -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50">
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Photo</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Name</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Index No</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Gender</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Class</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Phone</th>
                    <th class="text-right px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$students): ?>
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                        No students yet. Click "Add Student" to add one.
                    </td>
                </tr>
                <?php else: foreach ($students as $s): ?>
                <tr class="hover:bg-slate-50 transition-colors student-row" data-name="<?= htmlspecialchars(strtolower($s['first_name'] . ' ' . $s['last_name'])) ?>" data-index="<?= htmlspecialchars(strtolower($s['index_no'])) ?>">
                    <td class="px-4 py-3">
                        <?php if (!empty($s['photo_path'])): ?>
                        <img src="../<?= htmlspecialchars($s['photo_path']) ?>" alt="" class="w-10 h-10 rounded-lg object-cover bg-slate-100">
                        <?php else: ?>
                        <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-semibold">
                            <?= strtoupper(substr($s['first_name'], 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                    <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($s['index_no']) ?></td>
                    <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars($s['gender'] ?? '-') ?></td>
                    <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars($s['class_name'] ? $s['class_name'] . ($s['section'] ? ' ' . $s['section'] : '') : 'Unassigned') ?></td>
                    <td class="px-4 py-3 text-slate-500"><?= htmlspecialchars($s['phone'] ?? '-') ?></td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" onclick='viewStudent(<?= json_encode($s) ?>)'
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition-colors">
                                <i data-lucide="eye" class="w-3 h-3"></i> View
                            </button>
                            <button type="button" onclick='editStudent(<?= json_encode($s) ?>)'
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium border border-indigo-200 text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">
                                <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                            </button>
                            <button type="button" onclick='deleteStudent(<?= json_encode(['id' => $s['id'], 'name' => $s['first_name'] . ' ' . $s['last_name']]) ?>)'
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                <i data-lucide="trash-2" class="w-3 h-3"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$classesOptions = '';
foreach ($classes as $c) {
    $val = htmlspecialchars($c['name'] . ($c['section'] ? ' - ' . $c['section'] : ''));
    $classesOptions .= "<option value=\"{$c['id']}\">{$val}</option>";
}
?>

<!-- Add Student Modal -->
<div id="addModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeModal('addModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <h3 class="text-base font-semibold text-slate-800">Add Student</h3>
                <button type="button" onclick="closeModal('addModal')" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="post" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" value="save">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">First Name *</label>
                        <input type="text" name="first_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Last Name *</label>
                        <input type="text" name="last_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Index Number *</label>
                    <input type="text" name="index_no" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Photo</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Fingerprint (optional)</label>
                    <input type="text" name="fingerprint_data" placeholder="Fingerprint scan data or ID"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Gender</label>
                        <select name="gender" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Select —</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Class</label>
                        <select name="class_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Unassigned —</option>
                            <?= $classesOptions ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Phone</label>
                    <input type="text" name="phone" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Student Modal -->
<div id="viewModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeModal('viewModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <h3 class="text-base font-semibold text-slate-800">View Student</h3>
                <button type="button" onclick="closeModal('viewModal')" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div id="viewModalContent" class="p-6 space-y-3"></div>
            <div class="px-6 pb-6">
                <button type="button" onclick="closeModal('viewModal')" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div id="editModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeModal('editModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg my-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <h3 class="text-base font-semibold text-slate-800">Edit Student</h3>
                <button type="button" onclick="closeModal('editModal')" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="post" enctype="multipart/form-data" id="editForm" class="p-6 space-y-4">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="editId">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">First Name *</label>
                        <input type="text" name="first_name" id="editFirstName" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Last Name *</label>
                        <input type="text" name="last_name" id="editLastName" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Index Number *</label>
                    <input type="text" name="index_no" id="editIndexNo" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Photo</label>
                    <div class="flex items-center gap-3">
                        <img id="editPhotoPreview" src="" alt="" class="w-14 h-14 rounded-lg object-cover bg-slate-100 hidden">
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Fingerprint (optional)</label>
                    <input type="text" name="fingerprint_data" id="editFingerprint" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Gender</label>
                        <select name="gender" id="editGender" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Select —</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Class</label>
                        <select name="class_id" id="editClassId" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Unassigned —</option>
                            <?= $classesOptions ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Phone</label>
                    <input type="text" name="phone" id="editPhone" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Update Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Student Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeModal('deleteModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <h3 class="text-base font-semibold text-slate-800">Delete Student</h3>
                <button type="button" onclick="closeModal('deleteModal')" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="post" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                <div class="p-6">
                    <p class="text-sm text-slate-600">Are you sure you want to delete <strong id="deleteName"></strong>? This action cannot be undone.</p>
                </div>
                <div class="flex justify-end gap-2 px-6 pb-6">
                    <button type="button" onclick="closeModal('deleteModal')" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.remove('hidden'); lucide.createIcons(); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

document.getElementById('studentSearch')?.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.student-row').forEach(function(row) {
        const name = row.getAttribute('data-name') || '';
        const index = row.getAttribute('data-index') || '';
        const show = !q || name.includes(q) || index.includes(q);
        row.style.display = show ? '' : 'none';
    });
});

function viewStudent(s) {
    const html = `
        <div class="flex items-center gap-4 mb-4">
            ${s.photo_path ? `<img src="../${s.photo_path}" alt="" class="w-20 h-20 rounded-xl object-cover bg-slate-100">` : `<div class="w-20 h-20 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-2xl font-bold">${(s.first_name||'')[0]}</div>`}
            <div>
                <div class="text-lg font-semibold text-slate-800">${s.first_name} ${s.last_name}</div>
                <div class="text-sm text-slate-500">Index No: ${s.index_no}</div>
            </div>
        </div>
        <div class="space-y-2 text-sm">
            <div><span class="text-slate-500">Gender:</span> <span class="text-slate-800">${s.gender || '—'}</span></div>
            <div><span class="text-slate-500">Class:</span> <span class="text-slate-800">${s.class_name ? s.class_name + (s.section ? ' ' + s.section : '') : 'Unassigned'}</span></div>
            <div><span class="text-slate-500">Phone:</span> <span class="text-slate-800">${s.phone || '—'}</span></div>
            ${s.fingerprint_data ? `<div><span class="text-slate-500">Fingerprint:</span> <span class="text-slate-800">Registered</span></div>` : ''}
        </div>
    `;
    document.getElementById('viewModalContent').innerHTML = html;
    openModal('viewModal');
}

function editStudent(s) {
    document.getElementById('editId').value = s.id;
    document.getElementById('editFirstName').value = s.first_name || '';
    document.getElementById('editLastName').value = s.last_name || '';
    document.getElementById('editIndexNo').value = s.index_no || '';
    document.getElementById('editFingerprint').value = s.fingerprint_data || '';
    document.getElementById('editGender').value = s.gender || '';
    document.getElementById('editClassId').value = s.class_id || '';
    document.getElementById('editPhone').value = s.phone || '';

    const preview = document.getElementById('editPhotoPreview');
    if (s.photo_path) {
        preview.src = '../' + s.photo_path;
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
    openModal('editModal');
}

function deleteStudent(s) {
    document.getElementById('deleteId').value = s.id;
    document.getElementById('deleteName').textContent = s.name || 'this student';
    openModal('deleteModal');
}
</script>

<?php require __DIR__ . '/footer.php'; ?>

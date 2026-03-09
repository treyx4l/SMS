<?php
$page_title = 'Students';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

// Use index_no if column exists, else admission_no for backward compatibility
$hasIndexNo      = false;
$hasAdmissionNo  = false;
$hasNationality  = false;
$res = $conn->query("SHOW COLUMNS FROM students LIKE 'index_no'");
if ($res && $res->num_rows > 0) $hasIndexNo = true;
$res = $conn->query("SHOW COLUMNS FROM students LIKE 'admission_no'");
if ($res && $res->num_rows > 0) $hasAdmissionNo = true;
$res = $conn->query("SHOW COLUMNS FROM students LIKE 'nationality'");
if ($res && $res->num_rows > 0) $hasNationality = true;
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
        $address      = trim($_POST['address'] ?? '');
        $nationality  = trim($_POST['nationality'] ?? '');
        $route_id     = !empty($_POST['route_id']) ? (int) $_POST['route_id'] : null;
        $fingerprint  = trim($_POST['fingerprint_data'] ?? '');

        if ($first_name === '' || $last_name === '' || $index_no === '') {
            $errors[] = 'First name, last name and index number are required.';
        }

        if (!$errors) {
            // Fetch old photo path for replacement if we are updating
            $oldPhotoPath = null;
            if ($id && $hasIndexNo) {
                $stmtOld = $conn->prepare("SELECT photo_path FROM students WHERE id=? AND school_id=?");
                $stmtOld->bind_param('ii', $id, $schoolId);
                $stmtOld->execute();
                $rowOld = $stmtOld->get_result()->fetch_assoc();
                $stmtOld->close();
                $oldPhotoPath = $rowOld['photo_path'] ?? null;
            }

            // Handle photo upload (only when schema has photo_path)
            $photoPath = null;
            if ($hasIndexNo && !empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                    $errors[] = 'Photo size cannot exceed 2MB.';
                } else {
                    $uploadDir = dirname(__DIR__) . '/storage/students/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                        $filename = uniqid('st_') . '.' . $ext;
                        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
                            $photoPath = 'storage/students/' . $filename;
                        } else {
                            $errors[] = 'Failed to save uploaded photo.';
                        }
                    } else {
                        $errors[] = 'Invalid photo format. Only JPG, PNG, GIF, and WebP are allowed.';
                    }
                }
            }

            if (!$errors && $id) {
                // Update
                $setParts = [
                    "first_name=?",
                    "last_name=?",
                    "gender=?",
                ];
                if ($hasIndexNo) {
                    $setParts[] = "index_no=?";
                } else {
                    $setParts[] = "admission_no=?";
                }
                $setParts[] = "class_id=?";
                $setParts[] = "phone=?";
                $setParts[] = "address=?";
                if ($hasNationality) {
                    $setParts[] = "nationality=?";
                }
                if ($hasIndexNo) {
                    $setParts[] = "fingerprint_data=?";
                }
                if ($hasAdmissionNo && $hasIndexNo) {
                    $setParts[] = "admission_no=?";
                }
                if ($hasIndexNo && $photoPath) {
                    $setParts[] = "photo_path=?";
                }
                $setParts[] = "route_id=?";
                $set = implode(', ', $setParts) . " WHERE id=? AND school_id=?";

                $stmt = $conn->prepare("UPDATE students SET {$set}");

                // Build params
                $params = [
                    $first_name,
                    $last_name,
                    $gender,
                ];
                if ($hasIndexNo) {
                    $params[] = $index_no;
                } else {
                    $params[] = $admission_no;
                }
                $params[] = $class_id;
                $params[] = $phone;
                $params[] = $address;
                if ($hasNationality) {
                    $params[] = $nationality;
                }
                if ($hasIndexNo) {
                    $params[] = $fingerprint ?: null;
                }
                if ($hasAdmissionNo && $hasIndexNo) {
                    $params[] = $index_no;
                }
                if ($hasIndexNo && $photoPath) {
                    $params[] = $photoPath;
                }
                $params[] = $route_id;
                $params[] = $id;
                $params[] = $schoolId;

                // Build types
                $types = 'sss';           // first_name, last_name, gender
                $types .= 's';            // index_no or admission_no
                $types .= 'i';            // class_id
                $types .= 's';            // phone
                $types .= 's';            // address
                if ($hasNationality) {
                    $types .= 's';
                }
                if ($hasIndexNo) {
                    $types .= 's';        // fingerprint_data
                }
                if ($hasAdmissionNo && $hasIndexNo) {
                    $types .= 's';        // admission_no copy
                }
                if ($hasIndexNo && $photoPath) {
                    $types .= 's';        // photo_path
                }
                $types .= 'i';            // route_id
                $types .= 'ii';           // id, school_id

                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
                if ($photoPath && $oldPhotoPath && file_exists(dirname(__DIR__) . '/' . $oldPhotoPath)) {
                    unlink(dirname(__DIR__) . '/' . $oldPhotoPath);
                }
                $success = 'Student updated successfully.';
            } elseif (!$errors) {
                // Cap: max students per school
                $capStmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE school_id = ?");
                $capStmt->bind_param('i', $schoolId);
                $capStmt->execute();
                $currentCount = (int) $capStmt->get_result()->fetch_row()[0];
                $capStmt->close();
                if ($currentCount >= SCHOOL_LIMIT_STUDENTS) {
                    $errors[] = 'This school has reached the maximum of ' . SCHOOL_LIMIT_STUDENTS . ' students.';
                }
                if ($errors) { /* skip insert */ } else {
                // Insert
                $cols = $hasIndexNo
                    ? "school_id, first_name, last_name, gender, index_no, class_id, phone, address, route_id"
                    : "school_id, first_name, last_name, gender, admission_no, class_id, phone, address, route_id";
                if ($hasNationality) $cols .= ", nationality";
                if ($hasIndexNo) $cols .= ", fingerprint_data";
                if ($hasAdmissionNo && $hasIndexNo) $cols .= ", admission_no";
                if ($hasIndexNo && $photoPath) $cols .= ", photo_path";

                $phs = $hasIndexNo ? "?, ?, ?, ?, ?, ?, ?, ?, ?" : "?, ?, ?, ?, ?, ?, ?, ?, ?";
                if ($hasNationality) $phs .= ", ?";
                if ($hasIndexNo) $phs .= ", ?";
                if ($hasAdmissionNo && $hasIndexNo) $phs .= ", ?";
                if ($hasIndexNo && $photoPath) $phs .= ", ?";

                $stmt = $conn->prepare("INSERT INTO students ({$cols}) VALUES ({$phs})");

                $params = [$schoolId, $first_name, $last_name, $gender, $hasIndexNo ? $index_no : $admission_no, $class_id, $phone, $address, $route_id];
                if ($hasNationality) $params[] = $nationality;
                if ($hasIndexNo) $params[] = $fingerprint ?: null;
                if ($hasAdmissionNo && $hasIndexNo) $params[] = $index_no;
                if ($hasIndexNo && $photoPath) $params[] = $photoPath;

                $types = 'isssss' . 'i' . 's' . 'i'; // school_id, first,last,gender,id/admission, class_id, phone, address, route_id
                if ($hasNationality) $types .= 's';
                if ($hasIndexNo) $types .= 's'; // fingerprint
                if ($hasAdmissionNo && $hasIndexNo) $types .= 's'; // admission copy
                if ($hasIndexNo && $photoPath) $types .= 's'; // photo_path

                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
                $success = 'Student added successfully.';
                }
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

// Fetch available routes for the dropdowns
$routes = [];
$stmt = $conn->prepare("SELECT id, route_name FROM bus_routes WHERE school_id = ? ORDER BY route_name ASC");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $routes[] = $row;
$stmt->close();

// Search (server-side, overrides pagination content)
$searchQ = trim($_GET['q'] ?? '');
$searchParam = $searchQ !== '' ? '%' . $searchQ . '%' : null;

// Pagination
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$idColForSearch = $hasIndexNo ? 's.index_no' : 's.admission_no';
if ($searchParam !== null) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM students s WHERE s.school_id = ? AND (s.first_name LIKE ? OR s.last_name LIKE ? OR {$idColForSearch} LIKE ?)");
    $stmt->bind_param('isss', $schoolId, $searchParam, $searchParam, $searchParam);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE school_id = ?");
    $stmt->bind_param('i', $schoolId);
}
$stmt->execute();
$totalRows = (int) $stmt->get_result()->fetch_row()[0];
$stmt->close();
$totalPages = $totalRows ? (int) ceil($totalRows / $perPage) : 1;
$page = min($page, max(1, $totalPages));

// Fetch students (with optional search)
$students = [];
$idColSel = $hasIndexNo ? "s.index_no" : "s.admission_no AS index_no";
$extraCols = $hasIndexNo ? ", s.photo_path, s.fingerprint_data, s.class_id" : ", s.class_id";
// Always select address and nationality when present
$extraCols .= ", s.address";
if ($hasNationality) $extraCols .= ", s.nationality";
$where = "s.school_id = ?";
$params = [$schoolId];
$types = 'i';
if ($searchParam !== null) {
    $where .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR {$idColForSearch} LIKE ?)";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';
$sql = "SELECT s.id, s.first_name, s.last_name, s.gender, {$idColSel}, s.phone{$extraCols}, s.route_id, r.route_name,
         c.name AS class_name, c.section
         FROM students s
         LEFT JOIN classes c ON c.id = s.class_id
         LEFT JOIN bus_routes r ON s.route_id = r.id AND r.school_id = s.school_id
         WHERE {$where}
         ORDER BY s.created_at DESC
         LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $students[] = $row;
$stmt->close();

?>

<!-- Page header with action buttons -->
<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-slate-800">Students</h2>
        <p class="text-xs text-slate-400 mt-0.5"><?= $totalRows ?> student(s)<?= $totalPages > 1 ? ' · Page ' . $page . ' of ' . $totalPages : '' ?></p>
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

<!-- Search bar (server-side: searches all students) -->
<div class="mb-4">
    <form method="get" action="students.php" class="relative max-w-sm">
        <input type="hidden" name="page" value="1">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
            <i data-lucide="search" class="w-4 h-4"></i>
        </span>
        <input type="text" name="q" id="studentSearch" placeholder="Search by name or index number&hellip;"
               value="<?= htmlspecialchars($searchQ) ?>"
               class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        <button type="submit" class="sr-only">Search</button>
    </form>
    <?php if ($searchQ !== ''): ?>
    <p class="text-xs text-slate-500 mt-1">Showing results for &ldquo;<?= htmlspecialchars($searchQ) ?>&rdquo; (<?= $totalRows ?> match<?= $totalRows !== 1 ? 'es' : '' ?>)</p>
    <?php endif; ?>
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
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Bus Route</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Address</th>
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
                    <td class="px-4 py-3 text-slate-500 text-xs">
                        <?= $s['route_name'] ? '<i data-lucide="map" class="inline w-3 h-3 mr-1 text-indigo-600"></i>' . htmlspecialchars($s['route_name']) : '<span class="text-slate-400">—</span>' ?>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs"><?= htmlspecialchars($s['address'] ?? '-') ?></td>
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
    <?php if ($totalPages > 1): ?>
    <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
        <p class="text-xs text-slate-500">
            Showing <?= $totalRows ? $offset + 1 : 0 ?>–<?= min($offset + $perPage, $totalRows) ?> of <?= $totalRows ?>
        </p>
        <div class="flex items-center gap-1">
            <?php
            $baseUrl = 'students.php?';
            $query = array_filter($_GET);
            unset($query['page']);
            $baseQuery = $query ? http_build_query($query) . '&' : '';
            if ($page > 1): ?>
            <a href="<?= $baseUrl . $baseQuery ?>page=<?= $page - 1 ?>" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50">Prev</a>
            <?php endif;
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++): ?>
            <a href="<?= $baseUrl . $baseQuery ?>page=<?= $i ?>" class="inline-flex w-8 h-8 items-center justify-center text-xs font-medium rounded-lg <?= $i === $page ? 'bg-indigo-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' ?>"><?= $i ?></a>
            <?php endfor;
            if ($page < $totalPages): ?>
            <a href="<?= $baseUrl . $baseQuery ?>page=<?= $page + 1 ?>" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$classesOptions = '';
foreach ($classes as $c) {
    $val = htmlspecialchars($c['name'] . ($c['section'] ? ' - ' . $c['section'] : ''));
    $classesOptions .= "<option value=\"{$c['id']}\">{$val}</option>";
}
$routesOptions = '';
foreach ($routes as $r) {
    $val = htmlspecialchars($r['route_name']);
    $routesOptions .= "<option value=\"{$r['id']}\">{$val}</option>";
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
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Home Address</label>
                    <input type="text" name="address" placeholder="Student home address"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Photo</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Nationality</label>
                    <input type="text" name="nationality" placeholder="e.g. Nigerian"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
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
                <!-- Route Dropdown Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Phone</label>
                        <input type="text" name="phone" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Bus Route</label>
                        <select name="route_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Unassigned —</option>
                            <?= $routesOptions ?>
                        </select>
                    </div>
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
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Home Address</label>
                    <input type="text" name="address" id="editAddress" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Photo</label>
                    <div class="flex items-center gap-3">
                        <img id="editPhotoPreview" src="" alt="" class="w-14 h-14 rounded-lg object-cover bg-slate-100 hidden">
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Nationality</label>
                    <input type="text" name="nationality" id="editNationality" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
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
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Phone</label>
                        <input type="text" name="phone" id="editPhone" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Bus Route</label>
                        <select name="route_id" id="editRouteId" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Unassigned —</option>
                            <?= $routesOptions ?>
                        </select>
                    </div>
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

// Search is server-side via form GET q=
function viewStudent(s) {
    const html = `
        <div class="flex items-center gap-4 mb-4">
            ${s.photo_path ? `<img src="../${s.photo_path}" alt="" class="w-20 h-20 rounded-xl object-cover bg-slate-100">` : `<div class="w-20 h-20 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-2xl font-bold">${(s.first_name||'')[0]}</div>`}
            <div>
                <div class="text-lg font-semibold text-slate-800">${s.first_name} ${s.last_name}</div>
                <div class="text-sm text-slate-500">Index No: ${s.index_no}</div>
                ${s.route_name ? `<div class="mt-1 flex items-center gap-1 text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md w-fit"><i data-lucide="map" class="w-3 h-3"></i> ${s.route_name}</div>` : ''}
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
    document.getElementById('editAddress').value = s.address || '';
    if (document.getElementById('editNationality')) {
        document.getElementById('editNationality').value = s.nationality || '';
    }
    document.getElementById('editFingerprint').value = s.fingerprint_data || '';
    document.getElementById('editGender').value = s.gender || '';
    document.getElementById('editClassId').value = s.class_id || '';
    document.getElementById('editPhone').value = s.phone || '';
    document.getElementById('editRouteId').value = s.route_id || '';

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

<?php
$page_title = 'Bus Drivers';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$errors  = [];
$success = null;

// Handle POST: update or delete (new driver via api/create_driver.php, local MySQL auth)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $id        = (int) ($_POST['id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $address   = trim($_POST['address'] ?? '');
        $route_id  = !empty($_POST['route_id']) ? (int) $_POST['route_id'] : null;

        if ($full_name === '') {
            $errors[] = 'Full name is required.';
        }

        if (!$errors && $id) {
            $stmt = $conn->prepare("SELECT email FROM bus_drivers WHERE id=? AND school_id=?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $oldRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $oldEmail = $oldRow['email'] ?? '';

            $photoPath = null;
            $oldPhotoPath = null;
            $res = $conn->query("SHOW COLUMNS FROM bus_drivers LIKE 'photo_path'");
            if ($res && $res->num_rows > 0) {
                $stmtOld = $conn->prepare("SELECT photo_path FROM bus_drivers WHERE id=? AND school_id=?");
                $stmtOld->bind_param('ii', $id, $schoolId);
                $stmtOld->execute();
                $rowOld = $stmtOld->get_result()->fetch_assoc();
                $stmtOld->close();
                $oldPhotoPath = $rowOld['photo_path'] ?? null;

                if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                        $errors[] = 'Photo size cannot exceed 2MB.';
                    } else {
                        $uploadDir = dirname(__DIR__) . '/storage/staff/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                            $filename = 'driver_' . $id . '_' . uniqid() . '.' . $ext;
                            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
                                $photoPath = 'storage/staff/' . $filename;
                            } else {
                                $errors[] = 'Failed to save uploaded photo.';
                            }
                        } else {
                            $errors[] = 'Invalid photo format. Only JPG, PNG, GIF, and WebP are allowed.';
                        }
                    }
                }
            }

            if (!$errors) {
                if ($photoPath) {
                    $stmt = $conn->prepare("UPDATE bus_drivers SET full_name=?, email=?, phone=?, address=?, photo_path=?, route_id=? WHERE id=? AND school_id=?");
                    $stmt->bind_param('sssssiii', $full_name, $email, $phone, $address, $photoPath, $route_id, $id, $schoolId);
                } else {
                    $stmt = $conn->prepare("UPDATE bus_drivers SET full_name=?, email=?, phone=?, address=?, route_id=? WHERE id=? AND school_id=?");
                    $stmt->bind_param('ssssiii', $full_name, $email, $phone, $address, $route_id, $id, $schoolId);
                }
                $stmt->execute();
                $stmt->close();

                if ($photoPath && $oldPhotoPath && file_exists(dirname(__DIR__) . '/' . $oldPhotoPath)) {
                    unlink(dirname(__DIR__) . '/' . $oldPhotoPath);
                }

                $localUid = 'local:driver:' . $id;
                $stmt = $conn->prepare("UPDATE users SET full_name=?, email=? WHERE firebase_uid=? AND school_id=? AND role='driver'");
                $stmt->bind_param('sssi', $full_name, $email, $localUid, $schoolId);
                $stmt->execute();
                $stmt->close();

                $success = 'Driver updated successfully.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("SELECT email FROM bus_drivers WHERE id=? AND school_id=?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $localUid = 'local:driver:' . $id;
            $stmt = $conn->prepare("DELETE FROM users WHERE firebase_uid=? AND school_id=? AND role='driver'");
            $stmt->bind_param('si', $localUid, $schoolId);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM bus_drivers WHERE id=? AND school_id=?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();

            $success = 'Driver removed. Their login access has been revoked.';
        }
    }
}

// Search (server-side)
$searchQ = trim($_GET['q'] ?? '');
$searchParam = $searchQ !== '' ? '%' . $searchQ . '%' : null;

// Pagination
$perPage   = 15;
$page      = max(1, (int) ($_GET['page'] ?? 1));
if ($searchParam !== null) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bus_drivers d WHERE d.school_id = ? AND (d.full_name LIKE ? OR d.email LIKE ? OR d.phone LIKE ?)");
    $stmt->bind_param('isss', $schoolId, $searchParam, $searchParam, $searchParam);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bus_drivers WHERE school_id = ?");
    $stmt->bind_param('i', $schoolId);
}
$stmt->execute();
$totalRows = (int) $stmt->get_result()->fetch_row()[0];
$stmt->close();
$totalPages = $totalRows > 0 ? (int) ceil($totalRows / $perPage) : 1;
$page      = min($page, $totalPages);
$offset    = ($page - 1) * $perPage;

// Fetch drivers with has_login (paginated, optional search)
$drivers = [];
$where = "d.school_id = ?";
$params = [$schoolId];
$types = 'i';
if ($searchParam !== null) {
    $where .= " AND (d.full_name LIKE ? OR d.email LIKE ? OR d.phone LIKE ?)";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';
$stmt = $conn->prepare("
    SELECT d.id, d.full_name, d.email, d.phone, d.address, d.photo_path, d.created_at, d.route_id, r.route_name,
           CASE WHEN u.id IS NOT NULL THEN 1 ELSE 0 END AS has_login
    FROM bus_drivers d
    LEFT JOIN users u ON u.firebase_uid = CONCAT('local:driver:', d.id) AND u.school_id = d.school_id AND u.role = 'driver'
    LEFT JOIN bus_routes r ON d.route_id = r.id AND r.school_id = d.school_id
    WHERE {$where}
    ORDER BY d.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $drivers[] = $row;
$stmt->close();

// Fetch available routes for the dropdowns
$routes = [];
$stmt = $conn->prepare("SELECT id, route_name FROM bus_routes WHERE school_id = ? ORDER BY route_name ASC");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $routes[] = $row;
$stmt->close();

$withLogin = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM bus_drivers d INNER JOIN users u ON u.firebase_uid = CONCAT('local:driver:', d.id) AND u.school_id = d.school_id AND u.role = 'driver' WHERE d.school_id = ?");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$withLogin = (int) $stmt->get_result()->fetch_row()[0];
$stmt->close();

$edit = null;
if (isset($_GET['edit_id'])) {
    $eid  = (int) $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM bus_drivers WHERE id=? AND school_id=?");
    $stmt->bind_param('ii', $eid, $schoolId);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

$total = $totalRows;
?>

<div class="flex items-center justify-between">
    <div>
        <h2 class="text-base font-semibold text-slate-800">Bus Drivers</h2>
        <p class="text-xs text-slate-400 mt-0.5">
            <?= $total ?> driver<?= $total !== 1 ? 's' : '' ?>
            &nbsp;·&nbsp;
            <span class="text-green-600 font-medium"><?= $withLogin ?> with login</span>
            &nbsp;·&nbsp;
            <span class="text-orange-500 font-medium"><?= $total - $withLogin ?> without login</span>
        </p>
    </div>
    <button id="toggleAddBtn" onclick="document.getElementById('addPanel').classList.toggle('hidden')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
        <i data-lucide="user-plus" class="w-4 h-4"></i>
        Add Driver
    </button>
</div>

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

<div id="addPanel" class="<?= $errors ? '' : 'hidden' ?> bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="flex items-center gap-2.5 px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <i data-lucide="user-plus" class="w-4 h-4 text-indigo-600"></i>
        <span class="text-sm font-semibold text-slate-800">Add New Driver</span>
        <span class="ml-auto text-[11px] text-slate-400">Creates local login (MySQL)</span>
    </div>

    <div class="p-5">
        <div id="add-success" class="hidden mb-4 flex items-center gap-2.5 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            <span id="add-success-text"></span>
        </div>
        <div id="add-error" class="hidden mb-4 flex items-center gap-2.5 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600">
            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
            <span id="add-error-text"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Full Name *</label>
                <input type="text" id="new-name" required placeholder="John Doe"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Phone</label>
                <input type="text" id="new-phone" placeholder="+1 555 000 0000"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Address</label>
                <input type="text" id="new-address" placeholder="123 Street"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Route</label>
                <select id="new-route" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">None / Unassigned</option>
                    <?php foreach ($routes as $route): ?>
                        <option value="<?= $route['id'] ?>"><?= htmlspecialchars($route['route_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Login Email *</label>
                <input type="email" id="new-email" required placeholder="driver@school.com"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Password *</label>
                <input type="password" id="new-password" required placeholder="Min. 6 characters"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Confirm Password *</label>
                <input type="password" id="new-password-confirm" required placeholder="Repeat password"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Photo</label>
                <input type="file" id="new-driver-photo" accept="image/jpeg,image/png,image/gif,image/webp"
                       class="block w-full text-sm text-slate-500 file:mr-2 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm file:font-medium hover:file:bg-indigo-100">
            </div>
        </div>

        <div class="flex items-center gap-2 px-3 py-2.5 bg-blue-50 border border-blue-100 rounded-lg mb-4">
            <i data-lucide="info" class="w-3.5 h-3.5 text-blue-500 shrink-0"></i>
            <p class="text-[11px] text-blue-700">
                Creates a local login account. They sign in at the login page with email and password (select Bus Driver).
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button id="add-driver-btn" type="button" onclick="createDriver()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                Create Driver Account
            </button>
            <button type="button" onclick="document.getElementById('addPanel').classList.add('hidden')"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- Edit Driver Modal -->
<div id="editDriverModal" class="hidden fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm overflow-y-auto py-8">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-lg mx-4 my-auto">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
            <span class="text-sm font-semibold text-slate-800">Edit Driver</span>
            <button type="button" onclick="closeEditDriverModal()" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form method="post" enctype="multipart/form-data" id="editDriverForm" class="p-5">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="editDriverId">

            <div class="flex items-center gap-6 mb-4">
                <div id="editDriverPhotoPreview" class="w-20 h-20 rounded-xl border-2 border-slate-200 flex items-center justify-center bg-slate-50 overflow-hidden shrink-0">
                    <i data-lucide="user" class="w-10 h-10 text-slate-300"></i>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Photo</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"
                           class="block w-full text-sm text-slate-500 file:mr-2 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm file:font-medium hover:file:bg-indigo-100">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Full Name *</label>
                    <input type="text" name="full_name" id="editDriverName" required
                           class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Email</label>
                    <input type="email" name="email" id="editDriverEmail"
                           class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Phone</label>
                    <input type="text" name="phone" id="editDriverPhone"
                           class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Address</label>
                    <input type="text" name="address" id="editDriverAddress"
                           class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Assigned Route</label>
                    <select name="route_id" id="editDriverRoute" class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500">
                        <option value="">None / Unassigned</option>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?= $route['id'] ?>"><?= htmlspecialchars($route['route_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" onclick="closeEditDriverModal()" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                    Cancel
                </button>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
        <form method="get" action="drivers.php" class="relative flex items-center gap-2">
            <input type="hidden" name="page" value="1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"><i data-lucide="search" class="w-3.5 h-3.5"></i></span>
            <input type="text" name="q" id="driverSearch" placeholder="Search by name, email, phone…"
                   value="<?= htmlspecialchars($searchQ) ?>"
                   class="pl-8 pr-4 py-1.5 text-xs border border-slate-200 rounded-lg bg-gray-50 w-52 focus:ring-2 focus:ring-indigo-500">
            <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Search</button>
        </form>
        <span class="text-xs text-slate-400"><?= $total ?> total<?= $searchQ !== '' ? ' (search)' : '' ?></span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="driversTable">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50">
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Driver</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Email</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Phone</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Route</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Login</th>
                    <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$drivers): ?>
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center">
                        <div class="flex flex-col items-center text-slate-300">
                            <i data-lucide="bus" class="w-10 h-10 mb-3"></i>
                            <p class="text-sm text-slate-400 font-medium">No drivers yet</p>
                            <p class="text-xs text-slate-400 mt-1">Click "Add Driver" to create a driver with login access.</p>
                        </div>
                    </td>
                </tr>
                <?php else: foreach ($drivers as $d): ?>
                <tr class="hover:bg-slate-50 transition-colors driver-row">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold shrink-0 overflow-hidden">
                                <?php if (!empty($d['photo_path'] ?? '')): ?>
                                    <img src="../<?= htmlspecialchars($d['photo_path']) ?>" alt="<?= htmlspecialchars($d['full_name']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span><?= strtoupper(substr($d['full_name'], 0, 1)) ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="font-medium text-slate-800 driver-name"><?= htmlspecialchars($d['full_name']) ?></div>
                                <div class="text-[11px] text-slate-400">ID #<?= (int) $d['id'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3.5 text-slate-500 text-xs"><?= htmlspecialchars($d['email'] ?? '—') ?></td>
                    <td class="px-4 py-3.5 text-slate-500 text-xs"><?= htmlspecialchars($d['phone'] ?? '—') ?></td>
                    <td class="px-4 py-3.5 text-slate-500 text-xs text-indigo-600 font-medium whitespace-nowrap">
                        <?= $d['route_name'] ? '<i data-lucide="map" class="inline w-3 h-3 mr-1"></i>' . htmlspecialchars($d['route_name']) : '<span class="text-slate-400 font-normal">Unassigned</span>' ?>
                    </td>
                    <td class="px-4 py-3.5">
                        <?php if ($d['has_login']): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-50 text-green-700 border border-green-200">
                            <i data-lucide="check" class="w-2.5 h-2.5"></i> Active
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-orange-50 text-orange-600 border border-orange-200">
                            <i data-lucide="alert-circle" class="w-2.5 h-2.5"></i> No login
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button type="button"
                               onclick='openEditDriverModal(<?= json_encode([
                                   'id' => (int) $d['id'],
                                   'full_name' => $d['full_name'],
                                   'email' => $d['email'] ?? '',
                                   'phone' => $d['phone'] ?? '',
                                   'address' => $d['address'] ?? '',
                                   'route_id' => $d['route_id'] ?? '',
                                   'photo_path' => $d['photo_path'] ?? '',
                               ]) ?>)'
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-slate-200 text-slate-600 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                            </button>
                            <form method="post" class="inline" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($d['full_name'])) ?>? This will revoke their login access.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-red-200 text-red-500 rounded-lg hover:bg-red-50 transition-colors">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i> Remove
                                </button>
                            </form>
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
            $baseUrl = 'drivers.php?';
            $query = $_GET;
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

<?php require_once __DIR__ . '/footer.php'; ?>
<script>
// Search is server-side via form GET q=

function openEditDriverModal(data) {
    document.getElementById('editDriverId').value = data.id;
    document.getElementById('editDriverName').value = data.full_name || '';
    document.getElementById('editDriverEmail').value = data.email || '';
    document.getElementById('editDriverPhone').value = data.phone || '';
    document.getElementById('editDriverAddress').value = data.address || '';
    document.getElementById('editDriverRoute').value = data.route_id || '';
    const preview = document.getElementById('editDriverPhotoPreview');
    if (data.photo_path && data.photo_path.trim() !== '') {
        preview.innerHTML = '<img src="../' + (data.photo_path || '').replace(/"/g, '&quot;') + '" alt="" class="w-full h-full object-cover">';
    } else {
        preview.innerHTML = '<i data-lucide="user" class="w-10 h-10 text-slate-300"></i>';
    }
    document.getElementById('editDriverModal').classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
}
function closeEditDriverModal() {
    document.getElementById('editDriverModal').classList.add('hidden');
}

async function createDriver() {
    const btn     = document.getElementById('add-driver-btn');
    const errEl   = document.getElementById('add-error');
    const errText = document.getElementById('add-error-text');
    const succEl  = document.getElementById('add-success');
    const succText= document.getElementById('add-success-text');

    const name     = document.getElementById('new-name').value.trim();
    const email    = document.getElementById('new-email').value.trim();
    const phone    = document.getElementById('new-phone').value.trim();
    const address  = document.getElementById('new-address').value.trim();
    const route_id = document.getElementById('new-route').value;
    const password = document.getElementById('new-password').value;
    const confirm  = document.getElementById('new-password-confirm').value;

    errEl.classList.add('hidden');
    succEl.classList.add('hidden');

    if (!name || !email || !password) {
        errText.textContent = 'Full name, email and password are required.';
        errEl.classList.remove('hidden');
        return;
    }
    if (password.length < 6) {
        errText.textContent = 'Password must be at least 6 characters.';
        errEl.classList.remove('hidden');
        return;
    }
    if (password !== confirm) {
        errText.textContent = 'Passwords do not match.';
        errEl.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Creating…';

    try {
        const resp = await fetch('../api/create_driver.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ full_name: name, email, phone, address, route_id, password })
        });
        const data = await resp.json();

        if (!resp.ok || !data.success) throw new Error(data.error || 'Server error');

        const photoInput = document.getElementById('new-driver-photo');
        if (photoInput && photoInput.files && photoInput.files[0] && data.driver_id) {
            const fd = new FormData();
            fd.append('type', 'driver');
            fd.append('id', data.driver_id);
            fd.append('photo', photoInput.files[0]);
            try { await fetch('../api/upload_staff_photo.php', { method: 'POST', body: fd }); } catch(e) {}
        }

        succText.textContent = 'Driver "' + name + '" created! They can log in with email and password.';
        succEl.classList.remove('hidden');

        ['new-name','new-phone','new-address','new-email','new-password','new-password-confirm'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        if (photoInput) photoInput.value = '';

        setTimeout(() => location.reload(), 2000);
    } catch (err) {
        errText.textContent = err.message || 'Failed to create driver account.';
        errEl.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="user-plus" class="w-4 h-4"></i> Create Driver Account';
        if (window.lucide) lucide.createIcons();
    }
}
</script>

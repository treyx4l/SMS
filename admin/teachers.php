<?php
$page_title = 'Teachers';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$errors  = [];
$success = null;

// ── Handle POST: edit (name/phone/email only — no password change here)
//    New teacher creation is handled client-side via Firebase + api/create_teacher.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

        if ($action === 'update') {
        $id           = (int) ($_POST['id'] ?? 0);
        $full_name    = trim($_POST['full_name'] ?? '');
        $phone        = trim($_POST['phone']     ?? '');
        $email        = trim($_POST['email']     ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $new_password_confirm = $_POST['new_password_confirm'] ?? '';

        if ($full_name === '') {
            $errors[] = 'Full name is required.';
        }
        if ($new_password !== '' && strlen($new_password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if ($new_password !== '' && $new_password !== $new_password_confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$errors && $id) {
            // Get old email and local uid for users table update
            $stmt = $conn->prepare("SELECT t.email AS old_email, u.firebase_uid FROM teachers t LEFT JOIN users u ON u.firebase_uid = CONCAT('local:teacher:', t.id) AND u.school_id = t.school_id AND u.role = 'teacher' WHERE t.id = ? AND t.school_id = ?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $prev = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $oldEmail    = $prev['old_email'] ?? '';
            $localUid    = 'local:teacher:' . $id;

            // Photo upload
            $photoPath = null;
            $res = $conn->query("SHOW COLUMNS FROM teachers LIKE 'photo_path'");
            if ($res && $res->num_rows > 0 && !empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(__DIR__) . '/storage/staff/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    $filename = 'teacher_' . $id . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
                        $photoPath = 'storage/staff/' . $filename;
                    }
                }
            }
            $res = $conn->query("SHOW COLUMNS FROM teachers LIKE 'password_hash'");
            $hasPasswordCol = $res && $res->num_rows > 0;
            $passwordHash = null;
            if ($hasPasswordCol && $new_password !== '') {
                $passwordHash = password_hash($new_password, PASSWORD_DEFAULT);
            }

            if ($photoPath && $passwordHash !== null) {
                $stmt = $conn->prepare("UPDATE teachers SET full_name=?, email=?, phone=?, photo_path=?, password_hash=? WHERE id=? AND school_id=?");
                $stmt->bind_param('sssssii', $full_name, $email, $phone, $photoPath, $passwordHash, $id, $schoolId);
            } elseif ($photoPath) {
                $stmt = $conn->prepare("UPDATE teachers SET full_name=?, email=?, phone=?, photo_path=? WHERE id=? AND school_id=?");
                $stmt->bind_param('ssssii', $full_name, $email, $phone, $photoPath, $id, $schoolId);
            } elseif ($passwordHash !== null) {
                $stmt = $conn->prepare("UPDATE teachers SET full_name=?, email=?, phone=?, password_hash=? WHERE id=? AND school_id=?");
                $stmt->bind_param('ssssii', $full_name, $email, $phone, $passwordHash, $id, $schoolId);
            } else {
                $stmt = $conn->prepare("UPDATE teachers SET full_name=?, email=?, phone=? WHERE id=? AND school_id=?");
                $stmt->bind_param('sssii', $full_name, $email, $phone, $id, $schoolId);
            }
            $stmt->execute();
            $stmt->close();

            // Update users table (teachers use local MySQL auth)
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=? WHERE firebase_uid=? AND school_id=? AND role='teacher'");
            $stmt->bind_param('sssi', $full_name, $email, $localUid, $schoolId);
            $stmt->execute();
            $stmt->close();

            // Update teacher_class_subjects if table exists
            $tcsExists = (bool) ($conn->query("SHOW TABLES LIKE 'teacher_class_subjects'")->num_rows ?? 0);
            if ($tcsExists) {
                $class_ids   = isset($_POST['class_ids']) && is_array($_POST['class_ids'])
                    ? array_map('intval', array_filter($_POST['class_ids'])) : [];
                $subject_ids = isset($_POST['subject_ids']) && is_array($_POST['subject_ids'])
                    ? array_map('intval', array_filter($_POST['subject_ids'])) : [];
                $stmt = $conn->prepare("DELETE FROM teacher_class_subjects WHERE teacher_id=? AND school_id=?");
                $stmt->bind_param('ii', $id, $schoolId);
                $stmt->execute();
                $stmt->close();
                foreach ($class_ids as $cid) {
                    foreach ($subject_ids as $sid) {
                        if ($cid > 0 && $sid > 0) {
                            $stmt = $conn->prepare("INSERT IGNORE INTO teacher_class_subjects (school_id, teacher_id, class_id, subject_id) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param('iiii', $schoolId, $id, $cid, $sid);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }

            $success = 'Teacher updated successfully.';
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            // Get email before deletion to remove from users table too
            $stmt = $conn->prepare("SELECT email FROM teachers WHERE id=? AND school_id=?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $teacherRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Delete from teachers
            $stmt = $conn->prepare("DELETE FROM teachers WHERE id=? AND school_id=?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();

            // Remove from users (so they can no longer login)
            if ($teacherRow && $teacherRow['email']) {
                $stmt = $conn->prepare("DELETE FROM users WHERE email=? AND school_id=? AND role='teacher'");
                $stmt->bind_param('si', $teacherRow['email'], $schoolId);
                $stmt->execute();
                $stmt->close();
            }

            $success = 'Teacher removed. Their login access has been revoked.';
        }
    }
}

// Search (server-side)
$searchQ = trim($_GET['q'] ?? '');
$searchParam = $searchQ !== '' ? '%' . $searchQ . '%' : null;

// Pagination
$perPage = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

if ($searchParam !== null) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM teachers t WHERE t.school_id = ? AND (t.full_name LIKE ? OR t.email LIKE ? OR t.phone LIKE ?)");
    $stmt->bind_param('isss', $schoolId, $searchParam, $searchParam, $searchParam);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM teachers WHERE school_id = ?");
    $stmt->bind_param('i', $schoolId);
}
$stmt->execute();
$totalRows = (int) $stmt->get_result()->fetch_row()[0];
$stmt->close();
$totalPages = $totalRows ? (int) ceil($totalRows / $perPage) : 1;
$page = min($page, max(1, $totalPages));

// Fetch teachers (photo_path may not exist if migration not run)
$teachers = [];
$hasPhotoPath = false;
$res = $conn->query("SHOW COLUMNS FROM teachers LIKE 'photo_path'");
if ($res && $res->num_rows > 0) $hasPhotoPath = true;

$sel = $hasPhotoPath ? 't.id, t.full_name, t.email, t.phone, t.photo_path, t.created_at' : 't.id, t.full_name, t.email, t.phone, t.created_at';
$where = "t.school_id = ?";
$params = [$schoolId];
$types = 'i';
if ($searchParam !== null) {
    $where .= " AND (t.full_name LIKE ? OR t.email LIKE ? OR t.phone LIKE ?)";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';
$stmt = $conn->prepare("
    SELECT {$sel},
           CASE WHEN u.id IS NOT NULL THEN 1 ELSE 0 END AS has_login
    FROM teachers t
    LEFT JOIN users u ON u.firebase_uid = CONCAT('local:teacher:', t.id) AND u.school_id = t.school_id AND u.role = 'teacher'
    WHERE {$where}
    ORDER BY t.created_at DESC
    LIMIT ? OFFSET ?
");
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $teachers[] = $row;
    $stmt->close();
}

// Fetch classes and subjects for edit modal
$classes  = [];
$subjects = [];
$stmt = $conn->prepare("SELECT id, name, section FROM classes WHERE school_id=? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $classes[] = $row;
$stmt->close();

$stmt = $conn->prepare("SELECT id, name, code FROM subjects WHERE school_id=? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $subjects[] = $row;
$stmt->close();

// Teacher class+subject assignments (for display and edit modal)
$teacherAssignments = [];
$tcsExists = (bool) ($conn->query("SHOW TABLES LIKE 'teacher_class_subjects'")->num_rows ?? 0);
if ($tcsExists) {
    $stmt = $conn->prepare("SELECT teacher_id, class_id, subject_id FROM teacher_class_subjects WHERE school_id=?");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $tid = (int) $row['teacher_id'];
        if (!isset($teacherAssignments[$tid])) $teacherAssignments[$tid] = ['class_ids' => [], 'subject_ids' => []];
        $teacherAssignments[$tid]['class_ids'][] = (int) $row['class_id'];
        $teacherAssignments[$tid]['subject_ids'][] = (int) $row['subject_id'];
    }
    $stmt->close();
    foreach ($teacherAssignments as $tid => $a) {
        $teacherAssignments[$tid]['class_ids'] = array_unique($a['class_ids']);
        $teacherAssignments[$tid]['subject_ids'] = array_unique($a['subject_ids']);
    }
}

$total = $totalRows;
$withLogin = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM teachers t INNER JOIN users u ON u.firebase_uid = CONCAT('local:teacher:', t.id) AND u.school_id = t.school_id AND u.role = 'teacher' WHERE t.school_id = ?");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$withLogin = (int) $stmt->get_result()->fetch_row()[0];
$stmt->close();
?>

<!-- Page header -->
<div class="flex items-center justify-between">
    <div>
        <h2 class="text-base font-semibold text-slate-800">Teachers</h2>
        <p class="text-xs text-slate-400 mt-0.5">
            <?= $total ?> staff member<?= $total !== 1 ? 's' : '' ?>
            &nbsp;·&nbsp;
            <span class="text-green-600 font-medium"><?= $withLogin ?> with login</span>
            &nbsp;·&nbsp;
            <span class="text-orange-500 font-medium"><?= $total - $withLogin ?> without login</span>
        </p>
    </div>
    <button id="toggleAddBtn" onclick="document.getElementById('addPanel').classList.toggle('hidden')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
        <i data-lucide="user-plus" class="w-4 h-4"></i>
        Add Teacher
    </button>
</div>

<!-- Alerts -->
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

<!-- ── ADD TEACHER PANEL (Firebase account creation) ── -->
<div id="addPanel" class="<?= $errors ? '' : 'hidden' ?> bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="flex items-center gap-2.5 px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <i data-lucide="user-plus" class="w-4 h-4 text-indigo-600"></i>
        <span class="text-sm font-semibold text-slate-800">Add New Teacher</span>
        <span class="ml-auto text-[11px] text-slate-400">Creates local login (MySQL)</span>
    </div>

    <!-- JS-driven add form -->
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
            <!-- Full name -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Full Name *</label>
                <input type="text" id="new-name" required placeholder="Jane Doe"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>
            <!-- Phone -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Phone</label>
                <input type="text" id="new-phone" placeholder="+1 555 000 0000"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>
            <!-- Photo -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Photo</label>
                <input type="file" id="new-teacher-photo" accept="image/jpeg,image/png,image/gif,image/webp"
                       class="block w-full text-sm text-slate-500 file:mr-2 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm file:font-medium hover:file:bg-indigo-100">
            </div>
            <!-- Email -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Login Email *</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                        <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                    </span>
                    <input type="email" id="new-email" required placeholder="jane@school.com"
                           class="w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
            </div>
            <!-- Password -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Password *</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                        <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                    </span>
                    <input type="password" id="new-password" required placeholder="Min. 6 characters"
                           class="w-full pl-8 pr-10 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <button type="button" onclick="togglePw('new-password', this)"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
            <!-- Confirm Password -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Confirm Password *</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                        <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                    </span>
                    <input type="password" id="new-password-confirm" required placeholder="Repeat password"
                           class="w-full pl-8 pr-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
            </div>
        </div>

        <!-- Info note -->
        <div class="flex items-start gap-2 px-3 py-2.5 bg-blue-50 border border-blue-100 rounded-lg mb-4">
            <i data-lucide="info" class="w-3.5 h-3.5 text-blue-500 shrink-0 mt-0.5"></i>
            <p class="text-[11px] text-blue-700">
                Creates a local login account. They sign in at the login page with email and password (select <em>Teacher / Staff</em>).
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button id="add-teacher-btn" type="button" onclick="createTeacher()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                Create Teacher Account
            </button>
            <button type="button" onclick="document.getElementById('addPanel').classList.add('hidden')"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- ── TEACHERS TABLE ── -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
        <form method="get" action="teachers.php" class="relative flex items-center gap-2">
            <input type="hidden" name="page" value="1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                <i data-lucide="search" class="w-3.5 h-3.5"></i>
            </span>
            <input type="text" name="q" id="teacherSearch" placeholder="Search by name, email, phone…"
                   value="<?= htmlspecialchars($searchQ) ?>"
                   class="pl-8 pr-4 py-1.5 text-xs border border-slate-200 rounded-lg bg-gray-50 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent w-52 transition">
            <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Search</button>
        </form>
        <span class="text-xs text-slate-400"><?= $total ?> total<?= $searchQ !== '' ? ' (search)' : '' ?></span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="teachersTable">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50">
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Teacher</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Email</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Phone</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Classes / Subjects</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Login</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Joined</th>
                    <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$teachers): ?>
                <tr>
                    <td colspan="7" class="px-5 py-12 text-center">
                        <div class="flex flex-col items-center text-slate-300">
                            <i data-lucide="user-check" class="w-10 h-10 mb-3"></i>
                            <p class="text-sm text-slate-400 font-medium">No teachers yet</p>
                            <p class="text-xs text-slate-400 mt-1">Click "Add Teacher" to create a teacher with login access.</p>
                        </div>
                    </td>
                </tr>
                <?php else: foreach ($teachers as $t): ?>
                <tr class="hover:bg-slate-50 transition-colors teacher-row">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold shrink-0 overflow-hidden">
                                <?php if (!empty($hasPhotoPath) && !empty($t['photo_path'] ?? '')): ?>
                                    <img src="../<?= htmlspecialchars($t['photo_path']) ?>" alt="<?= htmlspecialchars($t['full_name']) ?>"
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span><?= strtoupper(substr($t['full_name'],0,1)) ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="font-medium text-slate-800 teacher-name"><?= htmlspecialchars($t['full_name']) ?></div>
                                <div class="text-[11px] text-slate-400">ID #<?= (int)$t['id'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3.5 text-slate-500 text-xs"><?= htmlspecialchars($t['email'] ?? '—') ?></td>
                    <td class="px-4 py-3.5 text-slate-500 text-xs"><?= htmlspecialchars($t['phone'] ?? '—') ?></td>
                    <td class="px-4 py-3.5 text-xs text-slate-600">
                        <?php
                        $a = $teacherAssignments[(int)$t['id']] ?? ['class_ids'=>[],'subject_ids'=>[]];
                        $cls = array_filter($classes, fn($c)=>in_array((int)$c['id'], $a['class_ids']));
                        $subs = array_filter($subjects, fn($s)=>in_array((int)$s['id'], $a['subject_ids']));
                        $parts = [];
                        if (!empty($cls)) $parts[] = implode(', ', array_map(fn($c)=>$c['name'].($c['section']??''?' '.$c['section']:''), $cls));
                        if (!empty($subs)) $parts[] = implode(', ', array_column($subs, 'name'));
                        echo !empty($parts) ? implode(' · ', $parts) : '—';
                        ?>
                    </td>
                    <td class="px-4 py-3.5">
                        <?php if ($t['has_login']): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-50 text-green-700 border border-green-200">
                            <i data-lucide="check" class="w-2.5 h-2.5"></i> Active
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-orange-50 text-orange-600 border border-orange-200">
                            <i data-lucide="alert-circle" class="w-2.5 h-2.5"></i> No login
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3.5 text-slate-400 text-xs"><?= date('d M Y', strtotime($t['created_at'])) ?></td>
                    <td class="px-5 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" onclick='openEditTeacherModal(<?= json_encode([
                                "id" => (int)$t["id"],
                                "full_name" => $t["full_name"],
                                "email" => $t["email"] ?? "",
                                "phone" => $t["phone"] ?? "",
                                "photo_path" => $t["photo_path"] ?? "",
                                "class_ids" => $teacherAssignments[(int)$t["id"]]["class_ids"] ?? [],
                                "subject_ids" => $teacherAssignments[(int)$t["id"]]["subject_ids"] ?? []
                            ]) ?>)'
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-slate-200 text-slate-600 rounded-lg hover:border-indigo-300 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">
                                <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                            </button>
                            <form method="post" class="inline"
                                  onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($t['full_name'])) ?>? This will revoke their login access.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-red-200 text-red-500 rounded-lg hover:bg-red-50 transition-colors">
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
            $baseUrl = 'teachers.php?';
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

<!-- Edit Teacher Modal -->
<div id="editTeacherModal" class="hidden fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm overflow-y-auto py-8">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-lg mx-4 my-auto">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
            <span class="text-sm font-semibold text-slate-800">Edit Teacher</span>
            <button type="button" onclick="closeEditTeacherModal()" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form method="post" enctype="multipart/form-data" id="editTeacherForm" class="p-5">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="editTeacherId">

            <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 rounded-xl border-2 border-slate-200 flex items-center justify-center bg-slate-50 overflow-hidden shrink-0" id="editTeacherPhotoPreview">
                    <i data-lucide="user" class="w-8 h-8 text-slate-300"></i>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Photo</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"
                           class="block w-full text-sm text-slate-500 file:mr-2 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm file:font-medium hover:file:bg-indigo-100">
                </div>
            </div>

            <div class="space-y-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Full Name *</label>
                    <input type="text" name="full_name" id="editTeacherName" required
                           class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Email</label>
                    <input type="email" name="email" id="editTeacherEmail"
                           class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Phone</label>
                    <input type="text" name="phone" id="editTeacherPhone"
                           class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">New password (leave blank to keep)</label>
                    <input type="password" name="new_password" id="editTeacherNewPassword" placeholder="Min. 6 characters"
                           class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Confirm new password</label>
                    <input type="password" name="new_password_confirm" id="editTeacherNewPasswordConfirm" placeholder="Repeat if changing"
                           class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <?php if ($tcsExists && ($classes || $subjects)): ?>
            <div class="border-t border-slate-200 pt-4 mb-4">
                <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Assign Classes & Subjects</p>
                <p class="text-[11px] text-slate-500 mb-2">Select classes and subjects this teacher teaches. All combinations will be saved.</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 mb-1.5">Classes</label>
                        <div class="max-h-32 overflow-y-auto border border-slate-200 rounded-lg p-2 space-y-1">
                            <?php foreach ($classes as $c): ?>
                            <label class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 rounded px-2 py-1 -mx-2">
                                <input type="checkbox" name="class_ids[]" value="<?= (int)$c['id'] ?>" class="edit-class-cb rounded border-slate-300 text-indigo-600">
                                <span class="text-xs text-slate-700"><?= htmlspecialchars($c['name']) ?><?= !empty($c['section']) ? ' ' . htmlspecialchars($c['section']) : '' ?></span>
                            </label>
                            <?php endforeach; ?>
                            <?php if (!$classes): ?><p class="text-xs text-slate-500 px-2">No classes</p><?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 mb-1.5">Subjects</label>
                        <div class="max-h-32 overflow-y-auto border border-slate-200 rounded-lg p-2 space-y-1">
                            <?php foreach ($subjects as $s): ?>
                            <label class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 rounded px-2 py-1 -mx-2">
                                <input type="checkbox" name="subject_ids[]" value="<?= (int)$s['id'] ?>" class="edit-subject-cb rounded border-slate-300 text-indigo-600">
                                <span class="text-xs text-slate-700"><?= htmlspecialchars($s['name']) ?><?= $s['code'] ? ' (' . htmlspecialchars($s['code']) . ')' : '' ?></span>
                            </label>
                            <?php endforeach; ?>
                            <?php if (!$subjects): ?><p class="text-xs text-slate-500 px-2">No subjects</p><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="flex gap-2">
                <button type="button" onclick="closeEditTeacherModal()" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                    <i data-lucide="save" class="w-4 h-4 inline-block mr-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>
<script>
function openEditTeacherModal(data) {
    document.getElementById('editTeacherId').value = data.id;
    document.getElementById('editTeacherName').value = data.full_name || '';
    document.getElementById('editTeacherEmail').value = data.email || '';
    document.getElementById('editTeacherPhone').value = data.phone || '';
    document.getElementById('editTeacherNewPassword').value = '';
    document.getElementById('editTeacherNewPasswordConfirm').value = '';
    const preview = document.getElementById('editTeacherPhotoPreview');
    if (data.photo_path && data.photo_path.trim() !== '') {
        preview.innerHTML = '<img src="../' + (data.photo_path || '').replace(/"/g, '&quot;') + '" alt="" class="w-full h-full object-cover">';
    } else {
        preview.innerHTML = '<i data-lucide="user" class="w-8 h-8 text-slate-300"></i>';
    }
    document.querySelectorAll('.edit-class-cb').forEach(cb => { cb.checked = (data.class_ids || []).indexOf(parseInt(cb.value, 10)) >= 0; });
    document.querySelectorAll('.edit-subject-cb').forEach(cb => { cb.checked = (data.subject_ids || []).indexOf(parseInt(cb.value, 10)) >= 0; });
    document.getElementById('editTeacherModal').classList.remove('hidden');
    if (window.lucide) lucide.createIcons();
}
function closeEditTeacherModal() {
    document.getElementById('editTeacherModal').classList.add('hidden');
}
</script>
<script>
// Search is server-side via form GET q=

// Toggle password visibility
function togglePw(inputId, btn) {
    const input = document.getElementById(inputId);
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.innerHTML = isText
        ? '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
}

// Create teacher via API (local MySQL auth)
async function createTeacher() {
    const btn        = document.getElementById('add-teacher-btn');
    const errEl      = document.getElementById('add-error');
    const errText    = document.getElementById('add-error-text');
    const succEl     = document.getElementById('add-success');
    const succText   = document.getElementById('add-success-text');

    const name     = document.getElementById('new-name').value.trim();
    const phone    = document.getElementById('new-phone').value.trim();
    const email    = document.getElementById('new-email').value.trim();
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
    btn.innerHTML = `<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Creating account…`;

    try {
        const resp = await fetch('../api/create_teacher.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ full_name: name, email, phone, password })
        });
        const data = await resp.json();

        if (!resp.ok || !data.success) throw new Error(data.error || 'Server error');

        const photoInput = document.getElementById('new-teacher-photo');
        if (photoInput && photoInput.files && photoInput.files[0] && data.teacher_id) {
            const fd = new FormData();
            fd.append('type', 'teacher');
            fd.append('id', data.teacher_id);
            fd.append('photo', photoInput.files[0]);
            try { await fetch('../api/upload_staff_photo.php', { method: 'POST', body: fd }); } catch(e) {}
        }

        succText.textContent = `Teacher "${name}" created! They can log in with email and password.`;
        succEl.classList.remove('hidden');

        // Reset form
        ['new-name','new-phone','new-email','new-password','new-password-confirm'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        if (photoInput) photoInput.value = '';

        // Reload after 2s to show in table
        setTimeout(() => location.reload(), 2000);

    } catch (err) {
        errText.textContent = err.message || 'Failed to create teacher account.';
        errEl.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Create Teacher Account`;
    }
}
</script>

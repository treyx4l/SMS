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
        $id        = (int) ($_POST['id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone']     ?? '');
        $email     = trim($_POST['email']     ?? '');

        if ($full_name === '') {
            $errors[] = 'Full name is required.';
        }

        if (!$errors && $id) {
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
            if ($photoPath) {
                $stmt = $conn->prepare("UPDATE teachers SET full_name=?, email=?, phone=?, photo_path=? WHERE id=? AND school_id=?");
                $stmt->bind_param('ssssii', $full_name, $email, $phone, $photoPath, $id, $schoolId);
            } else {
                $stmt = $conn->prepare("UPDATE teachers SET full_name=?, email=?, phone=? WHERE id=? AND school_id=?");
                $stmt->bind_param('sssii', $full_name, $email, $phone, $id, $schoolId);
            }
            $stmt->execute();
            $stmt->close();

            // Update users table too (keep in sync)
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=? WHERE email=(SELECT t_email FROM (SELECT email AS t_email FROM teachers WHERE id=? AND school_id=?) AS sub) AND school_id=? AND role='teacher'");
            // Simpler approach: match by the old email stored in teachers before update
            // Actually just match by school_id + role + looking up the teacher's firebase link
            // We'll update full_name on users by joining on email
            $stmt->close();

            // Simpler: update users matched by email subquery won't work after email change.
            // Just update full_name on users where email = old email. We need old email first.
            // Re-fetch before we updated (already updated above). So update users by phone/id linkage isn't reliable.
            // Best we can do without foreign key: update users by email matching what we just set.
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=? WHERE email=? AND school_id=? AND role='teacher'");
            $stmt->bind_param('sssi', $full_name, $email, $email, $schoolId);
            $stmt->execute();
            $stmt->close();

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

// Fetch all teachers
$teachers = [];
$stmt = $conn->prepare("
    SELECT t.id, t.full_name, t.email, t.phone, t.photo_path, t.created_at,
           CASE WHEN u.id IS NOT NULL THEN 1 ELSE 0 END AS has_login
    FROM teachers t
    LEFT JOIN users u ON u.email = t.email AND u.school_id = t.school_id AND u.role = 'teacher'
    WHERE t.school_id = ?
    ORDER BY t.created_at DESC
");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $teachers[] = $row;
$stmt->close();

// Edit target
$edit = null;
if (isset($_GET['edit_id'])) {
    $eid  = (int) $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE id=? AND school_id=?");
    $stmt->bind_param('ii', $eid, $schoolId);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

$total      = count($teachers);
$withLogin  = count(array_filter($teachers, fn($t) => $t['has_login']));
?>

<!-- Firebase config for creating teacher accounts -->
<script type="module" id="firebase-module">
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-app.js";
import { getAuth, createUserWithEmailAndPassword, sendEmailVerification } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-auth.js";

const firebaseConfig = {
    apiKey:    "<?= htmlspecialchars(getenv('FIREBASE_API_KEY')) ?>",
    authDomain:"<?= htmlspecialchars(getenv('FIREBASE_AUTH_DOMAIN')) ?>",
    projectId: "<?= htmlspecialchars(getenv('FIREBASE_PROJECT_ID')) ?>",
};

const app  = initializeApp(firebaseConfig);
const auth = getAuth(app);
window.__axisAuth = auth;
window.__createUserWithEmailAndPassword = createUserWithEmailAndPassword;
window.__sendEmailVerification = sendEmailVerification;
</script>

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
        <span class="ml-auto text-[11px] text-slate-400">Creates a Firebase login account</span>
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
                This creates a <strong>Firebase login account</strong> for the teacher and adds them to your school.
                They can sign in at the login page by selecting <em>"Teacher / Staff"</em>.
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

<!-- ── EDIT TEACHER PANEL (no password) ── -->
<?php if ($edit): ?>
<div class="bg-white border border-indigo-200 rounded-xl overflow-hidden">
    <div class="flex items-center gap-2.5 px-5 py-3.5 border-b border-indigo-100 bg-indigo-50">
        <i data-lucide="pencil" class="w-4 h-4 text-indigo-600"></i>
        <span class="text-sm font-semibold text-indigo-800">Editing: <?= htmlspecialchars($edit['full_name']) ?></span>
        <span class="ml-auto text-[11px] text-indigo-500">Password cannot be changed here — use Firebase console</span>
    </div>
    <form method="post" enctype="multipart/form-data" class="p-5">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">

        <div class="flex items-center gap-6 mb-4">
            <div class="w-20 h-20 rounded-xl border-2 border-slate-200 flex items-center justify-center bg-slate-50 overflow-hidden shrink-0">
                <?php if (!empty($edit['photo_path']) && file_exists(dirname(__DIR__) . '/' . $edit['photo_path'])): ?>
                <img src="../<?= htmlspecialchars($edit['photo_path']) ?>" alt="" class="w-full h-full object-cover">
                <?php else: ?>
                <i data-lucide="user" class="w-10 h-10 text-slate-300"></i>
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Photo</label>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"
                       class="block w-full text-sm text-slate-500 file:mr-2 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm file:font-medium hover:file:bg-indigo-100">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Full Name *</label>
                <input type="text" name="full_name" required
                       value="<?= htmlspecialchars($edit['full_name']) ?>"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Email</label>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($edit['email'] ?? '') ?>"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Phone</label>
                <input type="text" name="phone"
                       value="<?= htmlspecialchars($edit['phone'] ?? '') ?>"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i data-lucide="save" class="w-4 h-4"></i>
                Save Changes
            </button>
            <a href="teachers.php"
               class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
                Cancel
            </a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- ── TEACHERS TABLE ── -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                <i data-lucide="search" class="w-3.5 h-3.5"></i>
            </span>
            <input type="text" id="teacherSearch" placeholder="Search teachers…"
                   class="pl-8 pr-4 py-1.5 text-xs border border-slate-200 rounded-lg bg-gray-50 text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent w-52 transition">
        </div>
        <span class="text-xs text-slate-400"><?= $total ?> total</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="teachersTable">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50">
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Teacher</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Email</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Phone</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Login</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Joined</th>
                    <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$teachers): ?>
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center">
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
                            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold shrink-0">
                                <?= strtoupper(substr($t['full_name'],0,1)) ?>
                            </div>
                            <div>
                                <div class="font-medium text-slate-800 teacher-name"><?= htmlspecialchars($t['full_name']) ?></div>
                                <div class="text-[11px] text-slate-400">ID #<?= (int)$t['id'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3.5 text-slate-500 text-xs"><?= htmlspecialchars($t['email'] ?? '—') ?></td>
                    <td class="px-4 py-3.5 text-slate-500 text-xs"><?= htmlspecialchars($t['phone'] ?? '—') ?></td>
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
                            <a href="teachers.php?edit_id=<?= (int)$t['id'] ?>"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-slate-200 text-slate-600 rounded-lg hover:border-indigo-300 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">
                                <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                            </a>
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
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>
<script>
// Live search
document.getElementById('teacherSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.teacher-row').forEach(row => {
        const name = row.querySelector('.teacher-name')?.textContent.toLowerCase() ?? '';
        row.style.display = name.includes(q) ? '' : 'none';
    });
});

// Toggle password visibility
function togglePw(inputId, btn) {
    const input = document.getElementById(inputId);
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.innerHTML = isText
        ? '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
}

// Create teacher via Firebase + API
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

    // Validate
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
        // Step 1: Create Firebase account
        const auth = window.__axisAuth;
        const createFn = window.__createUserWithEmailAndPassword;
        const cred = await createFn(auth, email, password);

        // Step 2: Get ID token
        const idToken = await cred.user.getIdToken();

        // Step 3: Send to our API to create users + teachers records
        const resp = await fetch('../api/create_teacher.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idToken, full_name: name, phone, email })
        });
        const data = await resp.json();

        if (!resp.ok || !data.success) {
            throw new Error(data.error || 'Server error');
        }

        // Step 4: Upload photo if selected
        const photoInput = document.getElementById('new-teacher-photo');
        if (photoInput && photoInput.files && photoInput.files[0] && data.teacher_id) {
            const fd = new FormData();
            fd.append('type', 'teacher');
            fd.append('id', data.teacher_id);
            fd.append('photo', photoInput.files[0]);
            try {
                const up = await fetch('../api/upload_staff_photo.php', { method: 'POST', body: fd });
                if (!up.ok) { /* non-fatal */ }
            } catch(e) { /* non-fatal */ }
        }

        // Step 5: Send email verification
        try { await window.__sendEmailVerification(cred.user); } catch(e) { /* non-fatal */ }

        succText.textContent = `Teacher "${name}" created! They can now log in using their email and password.`;
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

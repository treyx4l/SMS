<?php
$page_title = 'Edit Profile';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$teacher  = null;
$userId   = (int) ($_SESSION['user_id'] ?? 0);
$errors   = [];
$success  = null;

if ($userId && $schoolId) {
    $stmt = $conn->prepare("
        SELECT t.id, t.full_name, t.email, t.phone, t.address, t.password_hash
        FROM teachers t
        JOIN users u
          ON u.firebase_uid = CONCAT('local:teacher:', t.id)
         AND u.school_id = t.school_id
         AND u.role = 'teacher'
        WHERE u.id = ?
          AND u.school_id = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('ii', $userId, $schoolId);
        $stmt->execute();
        $res = $stmt->get_result();
        $teacher = $res->fetch_assoc() ?: null;
        $stmt->close();
    }
}

if (!$teacher): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-800">
        Your account is not linked to a teacher record. Please contact the admin if you believe this is an error.
    </p>
</div>
<?php require __DIR__ . '/footer.php'; return; endif; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['new_password_confirm'] ?? '';

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }
    if ($email === '') {
        $errors[] = 'Email is required.';
    }

    if ($newPassword !== '' || $confirmPassword !== '') {
        if (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match.';
        }
        if (empty($teacher['password_hash']) || !password_verify($currentPassword, $teacher['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }
    }

    if (!$errors) {
        $passwordHash = $teacher['password_hash'];
        if ($newPassword !== '' && strlen($newPassword) >= 6) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        // Update teachers table
        $stmt = $conn->prepare("
            UPDATE teachers
               SET full_name = ?, email = ?, phone = ?, address = ?, password_hash = ?
             WHERE id = ? AND school_id = ?
        ");
        if ($stmt) {
            $tid = (int) $teacher['id'];
            $stmt->bind_param('sssssii', $fullName, $email, $phone, $address, $passwordHash, $tid, $schoolId);
            $stmt->execute();
            $stmt->close();
        }

        // Update users table (local auth row)
        $localUid = 'local:teacher:' . (int) $teacher['id'];
        $stmt = $conn->prepare("
            UPDATE users
               SET full_name = ?, email = ?
             WHERE firebase_uid = ? AND school_id = ? AND role = 'teacher'
        ");
        if ($stmt) {
            $stmt->bind_param('sssi', $fullName, $email, $localUid, $schoolId);
            $stmt->execute();
            $stmt->close();
        }

        $success = 'Profile updated successfully.';
        // Refresh local copy for re-rendering
        $teacher['full_name']     = $fullName;
        $teacher['email']         = $email;
        $teacher['phone']         = $phone;
        $teacher['address']       = $address;
        $teacher['password_hash'] = $passwordHash;
    }
}
?>

<div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
    <div class="flex items-center justify-between mb-2">
        <h2 class="text-sm font-semibold text-slate-800">Edit profile</h2>
        <span class="text-[11px] text-slate-400">Update your personal details and password</span>
    </div>

    <?php if ($errors): ?>
        <div class="mb-3 px-4 py-2.5 bg-red-50 border border-red-200 rounded-lg text-[11px] text-red-700">
            <?= htmlspecialchars(implode(' ', $errors)) ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="mb-3 px-4 py-2.5 bg-green-50 border border-green-200 rounded-lg text-[11px] text-green-700">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-3 text-[11px]">
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Full name</label>
            <input type="text" name="full_name"
                   value="<?= htmlspecialchars($teacher['full_name'] ?? '') ?>"
                   class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" />
        </div>
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Email</label>
            <input type="email" name="email"
                   value="<?= htmlspecialchars($teacher['email'] ?? '') ?>"
                   class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" />
        </div>
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Phone</label>
            <input type="text" name="phone"
                   value="<?= htmlspecialchars($teacher['phone'] ?? '') ?>"
                   class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" />
        </div>
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Address</label>
            <input type="text" name="address"
                   value="<?= htmlspecialchars($teacher['address'] ?? '') ?>"
                   class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" />
        </div>

        <div class="md:col-span-2 border-t border-slate-100 pt-3 mt-1 grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] font-medium text-slate-600 mb-1">Current password</label>
                <input type="password" name="current_password"
                       class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" />
                <p class="mt-1 text-[10px] text-slate-400">Required if you want to set a new password.</p>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-600 mb-1">New password</label>
                <input type="password" name="new_password"
                       class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" />
            </div>
            <div>
                <label class="block text-[11px] font-medium text-slate-600 mb-1">Confirm new password</label>
                <input type="password" name="new_password_confirm"
                       class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" />
            </div>
        </div>

        <div class="md:col-span-2 flex items-center justify-end gap-2 pt-2">
            <a href="profile.php"
               class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-slate-300 hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">
                Save changes
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/footer.php'; ?>


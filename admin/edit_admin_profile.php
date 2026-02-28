<?php
$page_title = 'Edit Admin Profile';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();
$userId   = (int) ($_SESSION['user_id'] ?? 0);

$admin = null;
if ($userId && $schoolId) {
    $stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE id = ? AND school_id = ? AND role = 'admin' LIMIT 1");
    $stmt->bind_param('ii', $userId, $schoolId);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin) {
    $full_name = trim($_POST['full_name'] ?? '');
    if ($full_name === '') {
        $errors[] = 'Full name is required.';
    }
    if (!$errors) {
        $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ? AND school_id = ? AND role = 'admin'");
        $stmt->bind_param('sii', $full_name, $userId, $schoolId);
        $stmt->execute();
        $stmt->close();
        $success = 'Profile updated.';
        $admin['full_name'] = $full_name;
    }
}
?>

<div class="bg-white rounded-xl border border-slate-200 p-5 max-w-xl">
    <h2 class="text-sm font-semibold text-slate-800 mb-4">Edit admin profile</h2>

    <?php if ($errors): ?>
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
        <?= htmlspecialchars(implode(' ', $errors)) ?>
    </div>
    <?php elseif ($success): ?>
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <?php if (!$admin): ?>
    <p class="text-sm text-slate-500">No admin profile found.</p>
    <?php else: ?>
    <form method="post" class="space-y-4">
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Full name *</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($admin['full_name'] ?? '') ?>" required
                   class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Email</label>
            <input type="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" readonly
                   class="w-full px-3 py-2 border border-slate-100 bg-slate-50 rounded-lg text-sm text-slate-500" title="Email is linked to your login and cannot be changed here">
        </div>
        <div class="flex gap-2">
            <a href="admin_profile.php" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Save changes</button>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>

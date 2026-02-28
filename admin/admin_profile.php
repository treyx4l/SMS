<?php
$page_title = 'Admin Profile';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();
$userId   = (int) ($_SESSION['user_id'] ?? 0);

$admin = null;
if ($userId && $schoolId) {
    $stmt = $conn->prepare("SELECT id, full_name, email, role, created_at FROM users WHERE id = ? AND school_id = ? AND role = 'admin' LIMIT 1");
    $stmt->bind_param('ii', $userId, $schoolId);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<div class="bg-white rounded-xl border border-slate-200 p-5 max-w-xl">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-semibold text-slate-800">Admin profile</h2>
        <a href="edit_admin_profile.php"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700">
            <i data-lucide="edit-3" class="w-3 h-3"></i>
            <span>Edit profile</span>
        </a>
    </div>

    <?php if (!$admin): ?>
    <p class="text-sm text-slate-500">No admin profile found.</p>
    <?php else: ?>
    <div class="space-y-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg font-semibold">
                <?= strtoupper(mb_substr($admin['full_name'] ?? 'A', 0, 1, 'UTF-8')) ?>
            </div>
            <div>
                <div class="text-base font-medium text-slate-800"><?= htmlspecialchars($admin['full_name'] ?? '—') ?></div>
                <div class="text-xs text-slate-500"><?= htmlspecialchars($admin['email'] ?? '—') ?></div>
            </div>
        </div>
        <dl class="grid grid-cols-1 gap-3 text-sm">
            <div>
                <dt class="text-[11px] font-medium text-slate-500 uppercase">Role</dt>
                <dd class="text-slate-800"><?= htmlspecialchars($admin['role'] ?? '—') ?></dd>
            </div>
            <div>
                <dt class="text-[11px] font-medium text-slate-500 uppercase">Member since</dt>
                <dd class="text-slate-800"><?= $admin['created_at'] ? date('d M Y', strtotime($admin['created_at'])) : '—' ?></dd>
            </div>
        </dl>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>

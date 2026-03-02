<?php
$page_title = 'Profile';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$teacher  = null;
$userId   = (int) ($_SESSION['user_id'] ?? 0);

if ($userId && $schoolId) {
    $stmt = $conn->prepare("
        SELECT t.id, t.full_name, t.email, t.phone, t.address, t.photo_path
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
?>

<?php if (!$teacher): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <p class="text-sm text-amber-800">
        Your account is not linked to a teacher record. Please contact the admin if you believe this is an error.
    </p>
</div>
<?php else: ?>

<div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-800">My profile</h2>
        <a href="edit_profile.php"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
            <i data-lucide="edit-3" class="w-3 h-3"></i>
            <span>Edit profile</span>
        </a>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-semibold overflow-hidden">
            <?php if (!empty($teacher['photo_path'])): ?>
                <img src="../<?= htmlspecialchars($teacher['photo_path']) ?>" alt="<?= htmlspecialchars($teacher['full_name']) ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <span><?= htmlspecialchars(strtoupper(mb_substr($teacher['full_name'], 0, 1, 'UTF-8'))) ?></span>
            <?php endif; ?>
        </div>
        <div class="space-y-0.5 text-sm">
            <div class="font-semibold text-slate-900"><?= htmlspecialchars($teacher['full_name']) ?></div>
            <div class="text-[11px] text-slate-500">Teacher account</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-[11px]">
        <div class="space-y-1">
            <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Email</div>
            <div class="text-slate-800"><?= htmlspecialchars($teacher['email'] ?? '') ?></div>
        </div>
        <div class="space-y-1">
            <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Phone</div>
            <div class="text-slate-800"><?= htmlspecialchars($teacher['phone'] ?? '—') ?></div>
        </div>
        <div class="space-y-1 md:col-span-2">
            <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Address</div>
            <div class="text-slate-800"><?= htmlspecialchars($teacher['address'] ?? '—') ?></div>
        </div>
    </div>

    <p class="text-[11px] text-slate-400">
        These details are stored in the teacher records for your school. Use the edit button above to update your information.
    </p>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>


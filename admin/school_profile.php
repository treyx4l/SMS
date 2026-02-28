<?php
$page_title = 'School Profile';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$stmt = $conn->prepare("SELECT * FROM schools WHERE id = ?");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$school = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$school) {
    $school = ['name' => '—', 'code' => '—', 'logo_path' => null, 'accent_color' => '#1e88e5', 'address' => null, 'phone' => null, 'email' => null];
}

$hasAddress = false;
$hasPhone = false;
$hasEmail = false;
$res = $conn->query("SHOW COLUMNS FROM schools");
if ($res) {
    while ($col = $res->fetch_assoc()) {
        if ($col['Field'] === 'address') $hasAddress = true;
        if ($col['Field'] === 'phone') $hasPhone = true;
        if ($col['Field'] === 'email') $hasEmail = true;
    }
}
?>

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden max-w-2xl">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <span class="text-sm font-semibold text-slate-800">School profile</span>
        <span class="text-xs text-slate-500 ml-2">Read-only view — edit in Settings</span>
    </div>

    <div class="p-6 space-y-6">
        <div class="flex items-start gap-6">
            <div class="w-24 h-24 rounded-xl border-2 border-slate-200 flex items-center justify-center bg-slate-50 overflow-hidden shrink-0">
                <?php if (!empty($school['logo_path']) && file_exists(dirname(__DIR__) . '/' . ($school['logo_path'] ?? ''))): ?>
                <img src="../<?= htmlspecialchars($school['logo_path']) ?>" alt="Logo" class="w-full h-full object-contain">
                <?php else: ?>
                <i data-lucide="building-2" class="w-12 h-12 text-slate-300"></i>
                <?php endif; ?>
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($school['name'] ?? '—') ?></h2>
                <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars($school['code'] ?? '—') ?></p>
                <?php if (!empty($school['accent_color'])): ?>
                <div class="flex items-center gap-2 mt-2">
                    <span class="w-4 h-4 rounded border border-slate-200" style="background:<?= htmlspecialchars($school['accent_color']) ?>"></span>
                    <span class="text-xs text-slate-500"><?= htmlspecialchars($school['accent_color']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100">
            <?php if ($hasAddress && !empty($school['address'])): ?>
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase mb-1">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                    Address
                </div>
                <p class="text-sm text-slate-800"><?= nl2br(htmlspecialchars($school['address'])) ?></p>
            </div>
            <?php endif; ?>
            <?php if ($hasPhone && !empty($school['phone'])): ?>
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase mb-1">
                    <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                    Phone
                </div>
                <a href="tel:<?= htmlspecialchars($school['phone']) ?>" class="text-sm text-indigo-600 hover:underline"><?= htmlspecialchars($school['phone']) ?></a>
            </div>
            <?php endif; ?>
            <?php if ($hasEmail && !empty($school['email'])): ?>
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase mb-1">
                    <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                    Email
                </div>
                <a href="mailto:<?= htmlspecialchars($school['email']) ?>" class="text-sm text-indigo-600 hover:underline"><?= htmlspecialchars($school['email']) ?></a>
            </div>
            <?php endif; ?>
        </div>

        <?php
        $hasContact = ($hasAddress && !empty($school['address'])) || ($hasPhone && !empty($school['phone'])) || ($hasEmail && !empty($school['email']));
        if (!$hasContact): ?>
        <div class="pt-4 border-t border-slate-100">
            <p class="text-sm text-slate-500">Add address, phone, and email in <a href="settings.php" class="text-indigo-600 hover:underline">Settings</a> <?= (!$hasAddress || !$hasPhone || !$hasEmail) ? 'after running the migration.' : '.' ?></p>
        </div>
        <?php endif; ?>

        <div class="pt-4 border-t border-slate-100">
            <a href="settings.php" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                <i data-lucide="settings" class="w-4 h-4"></i>
                Edit in Settings
            </a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>

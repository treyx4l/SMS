<?php
$page_title = 'Staff Directory';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

// Check if photo_path columns exist
$hasTeacherPhoto   = $conn->query("SHOW COLUMNS FROM teachers LIKE 'photo_path'")->num_rows > 0;
$hasDriverPhoto    = false;
$hasAccountantPhoto= false;
$res = $conn->query("SHOW TABLES LIKE 'bus_drivers'");
if ($res && $res->num_rows > 0) {
    $hasDriverPhoto = $conn->query("SHOW COLUMNS FROM bus_drivers LIKE 'photo_path'")->num_rows > 0;
}
$res = $conn->query("SHOW TABLES LIKE 'accountants'");
if ($res && $res->num_rows > 0) {
    $hasAccountantPhoto = $conn->query("SHOW COLUMNS FROM accountants LIKE 'photo_path'")->num_rows > 0;
}

// Combine teachers, bus_drivers, accountants
$staff = [];

$tPhotoSel = $hasTeacherPhoto ? ', photo_path' : "'' AS photo_path";
$stmt = $conn->prepare("SELECT id, full_name, email, phone {$tPhotoSel}, 'teacher' AS role FROM teachers WHERE school_id = ?");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    if (!isset($row['photo_path'])) $row['photo_path'] = '';
    $staff[] = $row;
}
$stmt->close();

$driverTableExists = $conn->query("SHOW TABLES LIKE 'bus_drivers'")->num_rows > 0;
if ($driverTableExists) {
    $dPhotoSel = $hasDriverPhoto ? ', photo_path' : "'' AS photo_path";
    $stmt = $conn->prepare("SELECT id, full_name, email, phone {$dPhotoSel}, 'driver' AS role FROM bus_drivers WHERE school_id = ?");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (!isset($row['photo_path'])) $row['photo_path'] = '';
        $staff[] = $row;
    }
    $stmt->close();
}

$accountantTableExists = $conn->query("SHOW TABLES LIKE 'accountants'")->num_rows > 0;
if ($accountantTableExists) {
    $aPhotoSel = $hasAccountantPhoto ? ', photo_path' : "'' AS photo_path";
    $stmt = $conn->prepare("SELECT id, full_name, email, phone {$aPhotoSel}, 'accountant' AS role FROM accountants WHERE school_id = ?");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (!isset($row['photo_path'])) $row['photo_path'] = '';
        $staff[] = $row;
    }
    $stmt->close();
}

// Sort by name
usort($staff, fn($a, $b) => strcasecmp($a['full_name'], $b['full_name']));

$filterRole = trim($_GET['role'] ?? '');
if ($filterRole && in_array($filterRole, ['teacher', 'driver', 'accountant'])) {
    $staff = array_filter($staff, fn($s) => $s['role'] === $filterRole);
}

$total = count($staff);
$roleLabels = ['teacher' => 'Teacher', 'driver' => 'Bus Driver', 'accountant' => 'Accountant'];

// Avatar initials helper
function getInitials($name) {
    $parts = explode(' ', trim($name));
    if (count($parts) >= 2) return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts)-1], 0, 1));
    return strtoupper(substr($name, 0, 2));
}

// Role avatar bg colors
$roleBg = ['teacher' => 'bg-indigo-100 text-indigo-700', 'driver' => 'bg-amber-100 text-amber-700', 'accountant' => 'bg-emerald-100 text-emerald-700'];
?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h2 class="text-base font-semibold text-slate-800">Staff Directory</h2>
        <p class="text-xs text-slate-400 mt-0.5"><?= $total ?> staff member<?= $total !== 1 ? 's' : '' ?> — teachers, drivers, accountants</p>
    </div>
    <div class="flex items-center gap-2">
        <select id="roleFilter" onchange="window.location.href='staff_directory.php' + (this.value ? '?role=' + this.value : '')"
                class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500">
            <option value="">All roles</option>
            <option value="teacher" <?= $filterRole === 'teacher' ? 'selected' : '' ?>>Teachers</option>
            <option value="driver" <?= $filterRole === 'driver' ? 'selected' : '' ?>>Bus Drivers</option>
            <option value="accountant" <?= $filterRole === 'accountant' ? 'selected' : '' ?>>Accountants</option>
        </select>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><i data-lucide="search" class="w-3.5 h-3.5"></i></span>
            <input type="text" id="staffSearch" placeholder="Search by name, email…"
                   class="pl-8 pr-4 py-2 border border-slate-200 rounded-lg text-sm w-52 focus:ring-2 focus:ring-indigo-500">
        </div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php foreach ($staff as $s): ?>
    <?php
        $label = $roleLabels[$s['role']] ?? $s['role'];
        $color = $s['role'] === 'teacher'
            ? 'bg-blue-50 text-blue-700 border border-blue-100'
            : ($s['role'] === 'driver'
                ? 'bg-amber-50 text-amber-700 border border-amber-100'
                : 'bg-emerald-50 text-emerald-700 border border-emerald-100');
        $avatarBg = $roleBg[$s['role']] ?? 'bg-slate-100 text-slate-600';
        $initials = getInitials($s['full_name']);
        $hasPhoto = !empty($s['photo_path']);
    ?>
    <div class="staff-card bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-md transition-all duration-200 hover:-translate-y-0.5"
         data-name="<?= htmlspecialchars(strtolower($s['full_name'])) ?>"
         data-email="<?= htmlspecialchars(strtolower($s['email'] ?? '')) ?>"
         data-role="<?= $s['role'] ?>">

        <!-- Photo / Avatar section -->
        <div class="relative h-28 bg-gradient-to-br <?= $s['role'] === 'teacher' ? 'from-indigo-50 to-blue-50' : ($s['role'] === 'driver' ? 'from-amber-50 to-orange-50' : 'from-emerald-50 to-teal-50') ?> flex items-center justify-center">
            <?php if ($hasPhoto): ?>
            <img src="../<?= htmlspecialchars($s['photo_path']) ?>"
                 alt="<?= htmlspecialchars($s['full_name']) ?>"
                 class="w-20 h-20 rounded-full object-cover ring-4 ring-white shadow-md">
            <?php else: ?>
            <div class="w-20 h-20 rounded-full <?= $avatarBg ?> ring-4 ring-white shadow-md flex items-center justify-center text-xl font-bold">
                <?= $initials ?>
            </div>
            <?php endif; ?>
            <!-- Role badge -->
            <span class="absolute top-2 right-2 inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold <?= $color ?>">
                <?= $label ?>
            </span>
        </div>

        <!-- Info section -->
        <div class="p-4">
            <div class="font-semibold text-slate-800 text-sm leading-tight"><?= htmlspecialchars($s['full_name']) ?></div>
            <?php if (!empty($s['email'])): ?>
            <a href="mailto:<?= htmlspecialchars($s['email']) ?>"
               class="text-xs text-indigo-600 hover:underline mt-1 block truncate">
                <?= htmlspecialchars($s['email']) ?>
            </a>
            <?php endif; ?>
            <?php if (!empty($s['phone'])): ?>
            <a href="tel:<?= htmlspecialchars($s['phone']) ?>"
               class="flex items-center gap-1 text-xs text-slate-500 hover:text-slate-700 mt-0.5">
                <i data-lucide="phone" class="w-3 h-3 shrink-0"></i>
                <?= htmlspecialchars($s['phone']) ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (!$staff): ?>
<div class="bg-white border border-slate-200 rounded-xl p-12 text-center">
    <i data-lucide="users" class="w-12 h-12 text-slate-300 mx-auto mb-3"></i>
    <p class="text-sm text-slate-500 font-medium">No staff found</p>
    <p class="text-xs text-slate-400 mt-1">Add teachers, drivers, or accountants from their respective pages.</p>
</div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>
<script>
document.getElementById('staffSearch')?.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.staff-card').forEach(card => {
        const name = card.dataset.name || '';
        const email = card.dataset.email || '';
        card.style.display = (!q || name.includes(q) || email.includes(q)) ? '' : 'none';
    });
});
</script>

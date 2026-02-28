<?php
$page_title = 'Staff Directory';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

// Combine teachers, bus_drivers, accountants
$staff = [];

$stmt = $conn->prepare("SELECT id, full_name, email, phone, 'teacher' AS role FROM teachers WHERE school_id = ?");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $staff[] = $row;
$stmt->close();

$stmt = $conn->prepare("SELECT id, full_name, email, phone, 'driver' AS role FROM bus_drivers WHERE school_id = ?");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $staff[] = $row;
$stmt->close();

$stmt = $conn->prepare("SELECT id, full_name, email, phone, 'accountant' AS role FROM accountants WHERE school_id = ?");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $staff[] = $row;
$stmt->close();

// Sort by name
usort($staff, fn($a, $b) => strcasecmp($a['full_name'], $b['full_name']));

$filterRole = trim($_GET['role'] ?? '');
if ($filterRole && in_array($filterRole, ['teacher', 'driver', 'accountant'])) {
    $staff = array_filter($staff, fn($s) => $s['role'] === $filterRole);
}

$total = count($staff);
$roleLabels = ['teacher' => 'Teacher', 'driver' => 'Bus Driver', 'accountant' => 'Accountant'];
?>

<div class="flex items-center justify-between">
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
    <div class="staff-card bg-white border border-slate-200 rounded-xl p-4 hover:shadow-md transition-shadow"
         data-name="<?= htmlspecialchars(strtolower($s['full_name'])) ?>"
         data-email="<?= htmlspecialchars(strtolower($s['email'] ?? '')) ?>"
         data-role="<?= $s['role'] ?>">
        <div class="flex items-start gap-3">
            <div class="w-12 h-12 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold shrink-0">
                <?= strtoupper(substr($s['full_name'], 0, 1)) ?>
            </div>
            <div class="min-w-0 flex-1">
                <div class="font-semibold text-slate-800"><?= htmlspecialchars($s['full_name']) ?></div>
                <div class="text-xs text-slate-500 mt-0.5">
                    <?php
                    $label = $roleLabels[$s['role']] ?? $s['role'];
                    $color = $s['role'] === 'teacher' ? 'bg-blue-50 text-blue-700' : ($s['role'] === 'driver' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700');
                    ?>
                    <span class="inline-flex px-2 py-0.5 rounded-full font-medium <?= $color ?>"><?= $label ?></span>
                </div>
                <?php if (!empty($s['email'])): ?>
                <a href="mailto:<?= htmlspecialchars($s['email']) ?>" class="text-xs text-indigo-600 hover:underline mt-1 block truncate"><?= htmlspecialchars($s['email']) ?></a>
                <?php endif; ?>
                <?php if (!empty($s['phone'])): ?>
                <a href="tel:<?= htmlspecialchars($s['phone']) ?>" class="text-xs text-slate-500 hover:text-slate-700 mt-0.5 block"><?= htmlspecialchars($s['phone']) ?></a>
                <?php endif; ?>
            </div>
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

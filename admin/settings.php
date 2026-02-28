<?php
$page_title = 'Settings';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$errors  = [];
$success = null;

$stmt = $conn->prepare("SELECT * FROM schools WHERE id = ?");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$school = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$school) {
    $stmt = $conn->prepare("INSERT INTO schools (name, code) VALUES ('My School', 'SCHOOL1')");
    $stmt->execute();
    $schoolId = (int) $stmt->insert_id;
    $_SESSION['school_id'] = $schoolId;
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM schools WHERE id = ?");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $school = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Check for address, phone, email columns
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name'] ?? '');
    $code         = trim($_POST['code'] ?? '');
    $accent_color = trim($_POST['accent_color'] ?? '#1e88e5');
    $address      = trim($_POST['address'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $email        = trim($_POST['email'] ?? '');

    if ($name === '' || $code === '') {
        $errors[] = 'School name and code are required.';
    }

    if (!$errors) {
        $logoPath = $school['logo_path'] ?? null;

        // Logo upload
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = dirname(__DIR__) . '/storage/schools/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                $filename = 'school_' . $schoolId . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $filename)) {
                    $logoPath = 'storage/schools/' . $filename;
                }
            }
        }

        $cols = "name = ?, code = ?, accent_color = ?, logo_path = ?";
        $params = [$name, $code, $accent_color, $logoPath];
        $types = 'ssss';

        if ($hasAddress) { $cols .= ", address = ?"; $params[] = $address; $types .= 's'; }
        if ($hasPhone)  { $cols .= ", phone = ?";   $params[] = $phone;   $types .= 's'; }
        if ($hasEmail)  { $cols .= ", email = ?";   $params[] = $email;   $types .= 's'; }

        $params[] = $schoolId;
        $types .= 'i';

        $stmt = $conn->prepare("UPDATE schools SET {$cols} WHERE id = ?");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();

        $success = 'Settings saved.';

        $stmt = $conn->prepare("SELECT * FROM schools WHERE id = ?");
        $stmt->bind_param('i', $schoolId);
        $stmt->execute();
        $school = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
?>

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden max-w-2xl">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <span class="text-sm font-semibold text-slate-800">School settings</span>
        <span class="text-xs text-slate-500 ml-2">Branding and configuration</span>
    </div>

    <?php if ($errors): ?>
    <div class="mx-5 mt-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
        <?= htmlspecialchars(implode(' ', $errors)) ?>
    </div>
    <?php elseif ($success): ?>
    <div class="mx-5 mt-4 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="p-5 space-y-6">
        <div class="flex items-center gap-6">
            <div class="shrink-0">
                <div class="w-20 h-20 rounded-xl border-2 border-slate-200 flex items-center justify-center bg-slate-50 overflow-hidden">
                    <?php if (!empty($school['logo_path']) && file_exists(dirname(__DIR__) . '/' . $school['logo_path'])): ?>
                    <img src="../<?= htmlspecialchars($school['logo_path']) ?>" alt="Logo" class="w-full h-full object-contain">
                    <?php else: ?>
                    <i data-lucide="building-2" class="w-10 h-10 text-slate-300"></i>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-2">School logo</label>
                <input type="file" name="logo" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml"
                       class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">School name *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($school['name'] ?? '') ?>" required
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">School code *</label>
                <input type="text" name="code" value="<?= htmlspecialchars($school['code'] ?? '') ?>" required
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Accent color</label>
            <div class="flex items-center gap-3">
                <input type="color" name="accent_color" value="<?= htmlspecialchars($school['accent_color'] ?? '#1e88e5') ?>"
                       class="w-12 h-10 rounded border border-slate-200 cursor-pointer">
                <input type="text" value="<?= htmlspecialchars($school['accent_color'] ?? '#1e88e5') ?>"
                       class="px-3 py-2 border border-slate-200 rounded-lg text-sm w-24" readonly id="colorHex">
            </div>
        </div>

        <?php if ($hasAddress || $hasPhone || $hasEmail): ?>
        <div class="pt-4 border-t border-slate-100">
            <h3 class="text-sm font-semibold text-slate-800 mb-3">Contact info</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if ($hasAddress): ?>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Address</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($school['address'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <?php endif; ?>
                <?php if ($hasPhone): ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($school['phone'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <?php endif; ?>
                <?php if ($hasEmail): ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($school['email'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="flex pt-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                Save settings
            </button>
        </div>
    </form>
</div>

<script>lucide.createIcons();</script>
<script>
document.querySelector('input[name="accent_color"]')?.addEventListener('input', function() {
    document.getElementById('colorHex').value = this.value;
});
</script>

<?php require __DIR__ . '/footer.php'; ?>

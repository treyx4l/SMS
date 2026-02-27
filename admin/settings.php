<?php
$page_title = 'Settings';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$errors  = [];
$success = null;

// Load school record
$stmt = $conn->prepare("SELECT * FROM schools WHERE id = ?");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$school = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$school) {
    // In case the seed data is missing
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name'] ?? '');
    $code         = trim($_POST['code'] ?? '');
    $accent_color = trim($_POST['accent_color'] ?? '#1e88e5');

    if ($name === '' || $code === '') {
        $errors[] = 'School name and code are required.';
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            "UPDATE schools SET name = ?, code = ?, accent_color = ? WHERE id = ?"
        );
        $stmt->bind_param('sssi', $name, $code, $accent_color, $schoolId);
        $stmt->execute();
        $stmt->close();

        $success = 'Settings updated.';

        // Refresh school data
        $stmt = $conn->prepare("SELECT * FROM schools WHERE id = ?");
        $stmt->bind_param('i', $schoolId);
        $stmt->execute();
        $school = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">School Settings</div>
        <span class="text-muted">Branding and configuration per tenant</span>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars(implode(' ', $errors)) ?>
        </div>
    <?php elseif ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="form-grid">
            <div class="form-group">
                <label>School Name *</label>
                <input type="text" name="name"
                       value="<?= htmlspecialchars($school['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>School Code *</label>
                <input type="text" name="code"
                       value="<?= htmlspecialchars($school['code'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Accent Color</label>
                <input type="color" name="accent_color"
                       value="<?= htmlspecialchars($school['accent_color'] ?? '#1e88e5') ?>">
            </div>
        </div>

        <p class="text-muted" style="margin-top:0.75rem;">
            The school logo and accent color can be used on the sidebar and on terminal exam results.
            File upload for logo can be added here later.
        </p>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/footer.php'; ?>


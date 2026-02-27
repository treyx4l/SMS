<?php
$page_title = 'Classes';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id      = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $name    = trim($_POST['name'] ?? '');
        $section = trim($_POST['section'] ?? '');

        if ($name === '') {
            $errors[] = 'Class name is required.';
        }

        if (!$errors) {
            if ($id) {
                $stmt = $conn->prepare(
                    "UPDATE classes SET name = ?, section = ? WHERE id = ? AND school_id = ?"
                );
                $stmt->bind_param('ssii', $name, $section, $id, $schoolId);
                $stmt->execute();
                $stmt->close();
                $success = 'Class updated.';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO classes (school_id, name, section) VALUES (?, ?, ?)"
                );
                $stmt->bind_param('iss', $schoolId, $name, $section);
                $stmt->execute();
                $stmt->close();
                $success = 'Class added.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM classes WHERE id = ? AND school_id = ?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();
            $success = 'Class deleted.';
        }
    }
}

// Fetch classes
$classes = [];
$stmt    = $conn->prepare("SELECT * FROM classes WHERE school_id = ? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $classes[] = $row;
}
$stmt->close();

// Editing
$edit_class = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];
    $stmt    = $conn->prepare("SELECT * FROM classes WHERE id = ? AND school_id = ?");
    $stmt->bind_param('ii', $edit_id, $schoolId);
    $stmt->execute();
    $edit_class = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}
?>

<div class="card">
    <div class="card-header">
        <div class="card-title"><?= $edit_class ? 'Edit Class' : 'Add Class' ?></div>
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
        <input type="hidden" name="action" value="save">
        <?php if ($edit_class): ?>
            <input type="hidden" name="id" value="<?= (int) $edit_class['id'] ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <label>Class Name *</label>
                <input type="text" name="name"
                       value="<?= htmlspecialchars($edit_class['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Section</label>
                <input type="text" name="section"
                       value="<?= htmlspecialchars($edit_class['section'] ?? '') ?>">
            </div>
        </div>

        <div class="form-actions">
            <?php if ($edit_class): ?>
                <a href="classes.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">
                <?= $edit_class ? 'Update Class' : 'Add Class' ?>
            </button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Classes</div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Section</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$classes): ?>
                <tr>
                    <td colspan="4" class="text-muted">No classes.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($classes as $index => $class): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($class['name']) ?></td>
                        <td><?= htmlspecialchars($class['section'] ?? '-') ?></td>
                        <td>
                            <a href="classes.php?edit_id=<?= (int) $class['id'] ?>"
                               class="btn-sm btn-secondary">Edit</a>
                            <form method="post" style="display:inline;"
                                  onsubmit="return confirm('Delete this class?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $class['id'] ?>">
                                <button type="submit" class="btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>


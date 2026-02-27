<?php
$page_title = 'Subjects';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id   = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');

        if ($name === '') {
            $errors[] = 'Subject name is required.';
        }

        if (!$errors) {
            if ($id) {
                $stmt = $conn->prepare(
                    "UPDATE subjects SET name = ?, code = ? WHERE id = ? AND school_id = ?"
                );
                $stmt->bind_param('ssii', $name, $code, $id, $schoolId);
                $stmt->execute();
                $stmt->close();
                $success = 'Subject updated.';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO subjects (school_id, name, code) VALUES (?, ?, ?)"
                );
                $stmt->bind_param('iss', $schoolId, $name, $code);
                $stmt->execute();
                $stmt->close();
                $success = 'Subject added.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ? AND school_id = ?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();
            $success = 'Subject deleted.';
        }
    }
}

// Fetch subjects
$subjects = [];
$stmt     = $conn->prepare("SELECT * FROM subjects WHERE school_id = ? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();

// Editing
$edit_subject = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];
    $stmt    = $conn->prepare("SELECT * FROM subjects WHERE id = ? AND school_id = ?");
    $stmt->bind_param('ii', $edit_id, $schoolId);
    $stmt->execute();
    $edit_subject = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}
?>

<div class="card">
    <div class="card-header">
        <div class="card-title"><?= $edit_subject ? 'Edit Subject' : 'Add Subject' ?></div>
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
        <?php if ($edit_subject): ?>
            <input type="hidden" name="id" value="<?= (int) $edit_subject['id'] ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <label>Subject Name *</label>
                <input type="text" name="name"
                       value="<?= htmlspecialchars($edit_subject['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Code</label>
                <input type="text" name="code"
                       value="<?= htmlspecialchars($edit_subject['code'] ?? '') ?>">
            </div>
        </div>

        <div class="form-actions">
            <?php if ($edit_subject): ?>
                <a href="subjects.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">
                <?= $edit_subject ? 'Update Subject' : 'Add Subject' ?>
            </button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Subjects</div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Code</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$subjects): ?>
                <tr>
                    <td colspan="4" class="text-muted">No subjects.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($subjects as $index => $subject): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($subject['name']) ?></td>
                        <td><?= htmlspecialchars($subject['code'] ?? '-') ?></td>
                        <td>
                            <a href="subjects.php?edit_id=<?= (int) $subject['id'] ?>"
                               class="btn-sm btn-secondary">Edit</a>
                            <form method="post" style="display:inline;"
                                  onsubmit="return confirm('Delete this subject?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $subject['id'] ?>">
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


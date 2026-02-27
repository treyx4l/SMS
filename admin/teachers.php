<?php
$page_title = 'Teachers';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id        = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');

        if ($full_name === '') {
            $errors[] = 'Full name is required.';
        }

        if (!$errors) {
            if ($id) {
                $stmt = $conn->prepare(
                    "UPDATE teachers SET full_name = ?, email = ?, phone = ? WHERE id = ? AND school_id = ?"
                );
                $stmt->bind_param('sssii', $full_name, $email, $phone, $id, $schoolId);
                $stmt->execute();
                $stmt->close();
                $success = 'Teacher updated.';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO teachers (school_id, full_name, email, phone) VALUES (?, ?, ?, ?)"
                );
                $stmt->bind_param('isss', $schoolId, $full_name, $email, $phone);
                $stmt->execute();
                $stmt->close();
                $success = 'Teacher added.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ? AND school_id = ?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();
            $success = 'Teacher deleted.';
        }
    }
}

// Fetch teachers
$teachers = [];
$stmt     = $conn->prepare("SELECT * FROM teachers WHERE school_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $teachers[] = $row;
}
$stmt->close();

// Editing
$edit_teacher = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];
    $stmt    = $conn->prepare("SELECT * FROM teachers WHERE id = ? AND school_id = ?");
    $stmt->bind_param('ii', $edit_id, $schoolId);
    $stmt->execute();
    $edit_teacher = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}
?>

<div class="card">
    <div class="card-header">
        <div class="card-title"><?= $edit_teacher ? 'Edit Teacher' : 'Add Teacher' ?></div>
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
        <?php if ($edit_teacher): ?>
            <input type="hidden" name="id" value="<?= (int) $edit_teacher['id'] ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name"
                       value="<?= htmlspecialchars($edit_teacher['full_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($edit_teacher['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone"
                       value="<?= htmlspecialchars($edit_teacher['phone'] ?? '') ?>">
            </div>
        </div>

        <div class="form-actions">
            <?php if ($edit_teacher): ?>
                <a href="teachers.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">
                <?= $edit_teacher ? 'Update Teacher' : 'Add Teacher' ?>
            </button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Teachers</div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$teachers): ?>
                <tr>
                    <td colspan="5" class="text-muted">No teachers.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($teachers as $index => $teacher): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($teacher['full_name']) ?></td>
                        <td><?= htmlspecialchars($teacher['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($teacher['phone'] ?? '-') ?></td>
                        <td>
                            <a href="teachers.php?edit_id=<?= (int) $teacher['id'] ?>"
                               class="btn-sm btn-secondary">Edit</a>
                            <form method="post" style="display:inline;"
                                  onsubmit="return confirm('Delete this teacher?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $teacher['id'] ?>">
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


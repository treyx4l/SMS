<?php
$page_title = 'Parents';
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
        $address   = trim($_POST['address'] ?? '');

        if ($full_name === '') {
            $errors[] = 'Full name is required.';
        }

        if (!$errors) {
            if ($id) {
                $stmt = $conn->prepare(
                    "UPDATE parents SET full_name = ?, email = ?, phone = ?, address = ?
                     WHERE id = ? AND school_id = ?"
                );
                $stmt->bind_param('ssssii', $full_name, $email, $phone, $address, $id, $schoolId);
                $stmt->execute();
                $stmt->close();
                $success = 'Parent updated.';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO parents (school_id, full_name, email, phone, address)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('issss', $schoolId, $full_name, $email, $phone, $address);
                $stmt->execute();
                $stmt->close();
                $success = 'Parent added.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM parents WHERE id = ? AND school_id = ?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();
            $success = 'Parent deleted.';
        }
    }
}

// Fetch parents
$parents = [];
$stmt    = $conn->prepare("SELECT * FROM parents WHERE school_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $parents[] = $row;
}
$stmt->close();

// Editing
$edit_parent = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];
    $stmt    = $conn->prepare("SELECT * FROM parents WHERE id = ? AND school_id = ?");
    $stmt->bind_param('ii', $edit_id, $schoolId);
    $stmt->execute();
    $edit_parent = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}
?>

<div class="card">
    <div class="card-header">
        <div class="card-title"><?= $edit_parent ? 'Edit Parent' : 'Add Parent' ?></div>
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
        <?php if ($edit_parent): ?>
            <input type="hidden" name="id" value="<?= (int) $edit_parent['id'] ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name"
                       value="<?= htmlspecialchars($edit_parent['full_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($edit_parent['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone"
                       value="<?= htmlspecialchars($edit_parent['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address"
                       value="<?= htmlspecialchars($edit_parent['address'] ?? '') ?>">
            </div>
        </div>

        <div class="form-actions">
            <?php if ($edit_parent): ?>
                <a href="parents.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">
                <?= $edit_parent ? 'Update Parent' : 'Add Parent' ?>
            </button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Parents</div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$parents): ?>
                <tr>
                    <td colspan="6" class="text-muted">No parents.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($parents as $index => $parent): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($parent['full_name']) ?></td>
                        <td><?= htmlspecialchars($parent['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($parent['phone'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($parent['address'] ?? '-') ?></td>
                        <td>
                            <a href="parents.php?edit_id=<?= (int) $parent['id'] ?>"
                               class="btn-sm btn-secondary">Edit</a>
                            <form method="post" style="display:inline;"
                                  onsubmit="return confirm('Delete this parent?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $parent['id'] ?>">
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


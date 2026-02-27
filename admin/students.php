<?php
$page_title = 'Students';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$errors  = [];
$success = null;

// Handle create / update / delete via simple POST (page-level API)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id           = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $first_name   = trim($_POST['first_name'] ?? '');
        $last_name    = trim($_POST['last_name'] ?? '');
        $admission_no = trim($_POST['admission_no'] ?? '');
        $gender       = $_POST['gender'] ?: null;
        $class_id     = $_POST['class_id'] !== '' ? (int) $_POST['class_id'] : null;
        $phone        = trim($_POST['phone'] ?? '');

        if ($first_name === '' || $last_name === '' || $admission_no === '') {
            $errors[] = 'First name, last name and admission number are required.';
        }

        if (!$errors) {
            if ($id) {
                $stmt = $conn->prepare(
                    "UPDATE students
                     SET first_name = ?, last_name = ?, gender = ?, admission_no = ?, class_id = ?, phone = ?
                     WHERE id = ? AND school_id = ?"
                );
                $stmt->bind_param(
                    'ssssiiii',
                    $first_name,
                    $last_name,
                    $gender,
                    $admission_no,
                    $class_id,
                    $phone,
                    $id,
                    $schoolId
                );
                $stmt->execute();
                $stmt->close();
                $success = 'Student updated successfully.';
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO students (school_id, first_name, last_name, gender, admission_no, class_id, phone)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param(
                    'issssiss',
                    $schoolId,
                    $first_name,
                    $last_name,
                    $gender,
                    $admission_no,
                    $class_id,
                    $phone
                );
                $stmt->execute();
                $stmt->close();
                $success = 'Student added successfully.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ? AND school_id = ?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();
            $success = 'Student deleted.';
        }
    }
}

// Fetch classes for dropdown
$classes = [];
$stmt    = $conn->prepare("SELECT id, name, section FROM classes WHERE school_id = ? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $classes[] = $row;
}
$stmt->close();

// Fetch students
$students = [];
$sql      = "
    SELECT s.id, s.first_name, s.last_name, s.gender, s.admission_no, s.phone,
           c.name AS class_name, c.section
    FROM students s
    LEFT JOIN classes c ON c.id = s.class_id
    WHERE s.school_id = ?
    ORDER BY s.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

// Editing?
$edit_student = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];
    $stmt    = $conn->prepare("SELECT * FROM students WHERE id = ? AND school_id = ?");
    $stmt->bind_param('ii', $edit_id, $schoolId);
    $stmt->execute();
    $edit_student = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}
?>

<div class="card">
    <div class="card-header">
        <div class="card-title"><?= $edit_student ? 'Edit Student' : 'Add Student' ?></div>
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
        <?php if ($edit_student): ?>
            <input type="hidden" name="id" value="<?= (int) $edit_student['id'] ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-group">
                <label>First Name *</label>
                <input type="text" name="first_name"
                       value="<?= htmlspecialchars($edit_student['first_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Last Name *</label>
                <input type="text" name="last_name"
                       value="<?= htmlspecialchars($edit_student['last_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Admission No *</label>
                <input type="text" name="admission_no"
                       value="<?= htmlspecialchars($edit_student['admission_no'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Gender</label>
                <select name="gender">
                    <option value="">-- Select --</option>
                    <?php
                    $genders  = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];
                    $selected = $edit_student['gender'] ?? '';
                    foreach ($genders as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $selected === $value ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Class</label>
                <select name="class_id">
                    <option value="">-- Unassigned --</option>
                    <?php foreach ($classes as $class): ?>
                        <?php
                        $sel = isset($edit_student['class_id']) && (int) $edit_student['class_id'] === (int) $class['id']
                            ? 'selected'
                            : '';
                        ?>
                        <option value="<?= (int) $class['id'] ?>" <?= $sel ?>>
                            <?= htmlspecialchars($class['name'] . ($class['section'] ? ' - ' . $class['section'] : '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone"
                       value="<?= htmlspecialchars($edit_student['phone'] ?? '') ?>">
            </div>
        </div>

        <div class="form-actions">
            <?php if ($edit_student): ?>
                <a href="students.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">
                <?= $edit_student ? 'Update Student' : 'Add Student' ?>
            </button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Students</div>
        <span class="text-muted">All students in this school</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Admission No</th>
                <th>Gender</th>
                <th>Class</th>
                <th>Phone</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$students): ?>
                <tr>
                    <td colspan="7" class="text-muted">No students yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($students as $index => $student): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                        <td><?= htmlspecialchars($student['admission_no']) ?></td>
                        <td><?= htmlspecialchars($student['gender'] ?? '-') ?></td>
                        <td>
                            <?= htmlspecialchars(
                                $student['class_name']
                                    ? $student['class_name'] . ($student['section'] ? ' - ' . $student['section'] : '')
                                    : 'Unassigned'
                            ) ?>
                        </td>
                        <td><?= htmlspecialchars($student['phone'] ?? '-') ?></td>
                        <td>
                            <a href="students.php?edit_id=<?= (int) $student['id'] ?>"
                               class="btn-sm btn-secondary">Edit</a>
                            <form method="post" style="display:inline;"
                                  onsubmit="return confirm('Delete this student?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $student['id'] ?>">
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


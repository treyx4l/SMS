<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$entities = ['students', 'teachers', 'parents', 'classes'];
$counts   = [];
foreach ($entities as $entity) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM {$entity} WHERE school_id = ?");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $row             = $stmt->get_result()->fetch_assoc();
    $counts[$entity] = (int) ($row['c'] ?? 0);
    $stmt->close();
}
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">School Snapshot</div>
        <span class="text-muted">Tenant School ID <?= (int) $schoolId ?></span>
    </div>
    <div class="form-grid">
        <div>
            <div class="text-muted">Students</div>
            <div style="font-size:1.4rem;font-weight:600;"><?= $counts['students'] ?></div>
        </div>
        <div>
            <div class="text-muted">Teachers</div>
            <div style="font-size:1.4rem;font-weight:600;"><?= $counts['teachers'] ?></div>
        </div>
        <div>
            <div class="text-muted">Parents</div>
            <div style="font-size:1.4rem;font-weight:600;"><?= $counts['parents'] ?></div>
        </div>
        <div>
            <div class="text-muted">Classes</div>
            <div style="font-size:1.4rem;font-weight:600;"><?= $counts['classes'] ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Quick Links</div>
    </div>
    <div class="form-grid">
        <a class="btn btn-primary" href="students.php">Manage Students</a>
        <a class="btn btn-primary" href="teachers.php">Manage Teachers</a>
        <a class="btn btn-primary" href="classes.php">Manage Classes</a>
        <a class="btn btn-primary" href="subjects.php">Manage Subjects</a>
    </div>
    <p class="text-muted" style="margin-top:0.75rem;">
        You can extend this dashboard with widgets for attendance, grades, lesson plans, bus routes, and more.
    </p>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>


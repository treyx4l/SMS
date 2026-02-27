<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/layout_admin.php';
require_once __DIR__ . '/config.php';

$conn     = get_db_connection();
$schoolId = current_school_id() ?? 1; // TODO: replace with real tenant resolution

$entities = ['students', 'teachers', 'parents', 'classes'];
$counts   = [];
foreach ($entities as $entity) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM {$entity} WHERE school_id = ?");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $row            = $stmt->get_result()->fetch_assoc();
    $counts[$entity] = (int) ($row['c'] ?? 0);
    $stmt->close();
}
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">School Snapshot</div>
        <span class="text-muted">School ID <?= (int) $schoolId ?> (tenant)</span>
    </div>
    <div class="form-grid">
        <div>
            <div class="text-muted">Total Students</div>
            <div style="font-size:1.4rem;font-weight:600;"><?= $counts['students'] ?></div>
        </div>
        <div>
            <div class="text-muted">Total Teachers</div>
            <div style="font-size:1.4rem;font-weight:600;"><?= $counts['teachers'] ?></div>
        </div>
        <div>
            <div class="text-muted">Total Parents</div>
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
        <a class="btn btn-primary" href="admin_students.php">Manage Students</a>
        <a class="btn btn-primary" href="admin_teachers.php">Manage Teachers</a>
        <a class="btn btn-primary" href="admin_classes.php">Manage Classes</a>
        <a class="btn btn-primary" href="admin_subjects.php">Manage Subjects</a>
    </div>
    <p class="text-muted" style="margin-top:0.75rem;">
        This is a starting point. Additional widgets for attendance, grades, lesson plans and buses can be added here.
    </p>
</div>

<?php require_once __DIR__ . '/layout_admin_footer.php'; ?>


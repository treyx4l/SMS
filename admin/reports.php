<?php
$page_title = 'Reports';
require_once __DIR__ . '/layout.php';
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Reports</div>
        <span class="text-muted">Attendance, grades, fee reports and more</span>
    </div>
    <p class="text-muted">
        This page will aggregate key reports for the school, such as:
    </p>
    <ul class="text-muted" style="margin-left:1.2rem;font-size:0.85rem;">
        <li>Attendance summaries per class and term.</li>
        <li>Grade distributions per subject and class.</li>
        <li>Fee payment status by student.</li>
        <li>Custom CSV / PDF exports.</li>
    </ul>
</div>

<?php require __DIR__ . '/footer.php'; ?>


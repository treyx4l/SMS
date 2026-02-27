<?php
$page_title = 'Timetable';
require_once __DIR__ . '/layout.php';

// Later, you can persist this into a timetable_entries table.
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Timetable Management</div>
        <span class="text-muted">Design class timetables per day</span>
    </div>
    <p class="text-muted">
        This page will manage timeslots for each class and subject.
        You can extend it to:
    </p>
    <ul class="text-muted" style="margin-left:1.2rem;font-size:0.85rem;">
        <li>Create periods (e.g. 8:00–8:45, 8:50–9:35).</li>
        <li>Assign subjects and teachers to each period per class.</li>
        <li>Generate printable timetables for students and teachers.</li>
    </ul>
</div>

<?php require __DIR__ . '/footer.php'; ?>


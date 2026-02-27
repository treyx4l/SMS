<?php
$page_title = 'Accountants';
require_once __DIR__ . '/layout.php';

// For now, this page focuses on linking Firebase users with role "accountant" to this school.
// You can later extend with full finance modules (fees, invoices, payments).
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Accountants</div>
        <span class="text-muted">Attach accountant users to this school</span>
    </div>
    <p class="text-muted">
        This is a placeholder page. Once Firebase auth is wired, you can:
    </p>
    <ul class="text-muted" style="margin-left:1.2rem;font-size:0.85rem;">
        <li>Search Firebase users by email.</li>
        <li>Assign them as accountants for this school.</li>
        <li>Grant access to finance modules.</li>
    </ul>
</div>

<?php require __DIR__ . '/footer.php'; ?>


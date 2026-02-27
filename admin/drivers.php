<?php
$page_title = 'Bus Drivers';
require_once __DIR__ . '/layout.php';

// This page will manage bus drivers, routes and assigning students to routes.
// For now, it's a skeleton UI ready to be wired to tables like:
// - bus_drivers, bus_routes, bus_route_students
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">Bus Drivers & Routes</div>
        <span class="text-muted">Configure routes and assign students</span>
    </div>
    <p class="text-muted">
        This is a starting point UI. Next steps:
    </p>
    <ul class="text-muted" style="margin-left:1.2rem;font-size:0.85rem;">
        <li>Add database tables for bus drivers, routes, and route-student mapping.</li>
        <li>Create forms to add/edit drivers and their routes.</li>
        <li>Allow assigning students who use the bus to the nearest route.</li>
        <li>Expose a mobile-friendly view for drivers to see daily manifests.</li>
    </ul>
</div>

<?php require __DIR__ . '/footer.php'; ?>


<?php
$page_title = 'Activity';
require_once __DIR__ . '/layout.php';
?>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4">
    <div class="flex items-center justify-between mb-2">
        <h2 class="text-sm font-semibold">Recent activity</h2>
        <span class="text-xs text-slate-500">Detailed</span>
    </div>
    <p class="text-xs text-slate-600">
        Here you can later show a log of recent actions by admins, teachers and other roles (such as student updates,
        timetable changes, and new routes created).
    </p>
</div>

<?php require __DIR__ . '/footer.php'; ?>


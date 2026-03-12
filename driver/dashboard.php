<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

// Resolve driver_id from logged-in user (local:driver:{id})
$driverId = null;
$userId   = (int) ($_SESSION['user_id'] ?? 0);
if ($userId && $schoolId) {
    $stmt = $conn->prepare("
        SELECT d.id
        FROM bus_drivers d
        JOIN users u
          ON u.firebase_uid = CONCAT('local:driver:', d.id)
         AND u.school_id = d.school_id
         AND u.role = 'driver'
        WHERE u.id = ?
          AND u.school_id = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('ii', $userId, $schoolId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $driverId = (int) $row['id'];
        }
    }
}

// Optional tables for transport module (these may not exist yet)
$hasRoutes      = (bool) ($conn->query("SHOW TABLES LIKE 'bus_routes'")->num_rows ?? 0);
$hasMisconducts = (bool) ($conn->query("SHOW TABLES LIKE 'student_misconducts'")->num_rows ?? 0);

$today          = date('Y-m-d');
$driverRoute    = null;
$assignedStudents = [];

// Load this driver's route (if any)
if ($driverId && $hasRoutes) {
    $stmt = $conn->prepare("
        SELECT d.route_id, r.route_name, r.description
        FROM bus_drivers d
        LEFT JOIN bus_routes r
          ON r.id = d.route_id
         AND r.school_id = d.school_id
        WHERE d.id = ?
          AND d.school_id = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('ii', $driverId, $schoolId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row && (int) $row['route_id'] > 0) {
            $driverRoute = [
                'id'          => (int) $row['route_id'],
                'route_name'  => $row['route_name'],
                'description' => $row['description'],
            ];
        }
    }
}

// Handle misconduct report submission if table exists
$errors  = [];
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $driverId && $hasMisconducts) {
    $action = $_POST['action'] ?? '';
    if ($action === 'report_misconduct') {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $routeId   = (int) ($_POST['route_id'] ?? 0);
        $note      = trim($_POST['note'] ?? '');

        if (!$studentId || $note === '') {
            $errors[] = 'Please select a student and enter a note.';
        } else {
            $stmt = $conn->prepare("
                INSERT INTO student_misconducts (school_id, student_id, driver_id, route_id, note, reported_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            if ($stmt) {
                $stmt->bind_param('iiiis', $schoolId, $studentId, $driverId, $routeId, $note);
                $stmt->execute();
                $stmt->close();
                $success = 'Misconduct report submitted.';
            } else {
                $errors[] = 'Unable to save misconduct report at the moment.';
            }
        }
    }
}

// Load students assigned to this driver's route (via students.route_id)
if ($driverRoute) {
    $stmt = $conn->prepare("
        SELECT s.id, s.first_name, s.last_name, s.route_id
        FROM students s
        WHERE s.school_id = ?
          AND s.route_id = ?
        ORDER BY s.first_name, s.last_name
    ");
    if ($stmt) {
        $rid = (int) $driverRoute['id'];
        $stmt->bind_param('ii', $schoolId, $rid);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $assignedStudents[] = $row;
        }
        $stmt->close();
    }
}
?>

<div class="space-y-6">

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Route -->
        <div class="bg-white border border-slate-200 rounded-xl p-5 flex items-start gap-4 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-600"><path d="M14.106 5.553a2 2 0 0 0 1.788 0l3.659-1.83A1 1 0 0 1 21 4.619v12.764a1 1 0 0 1-.553.894l-4.553 2.277a2 2 0 0 1-1.788 0l-4.212-2.106a2 2 0 0 0-1.788 0l-3.659 1.83A1 1 0 0 1 3 19.381V6.618a1 1 0 0 1 .553-.894l4.553-2.277a2 2 0 0 1 1.788 0z"/><path d="M15 5.764v15"/><path d="M9 3.236v15"/></svg>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-800"><?= $driverRoute ? 1 : 0 ?></div>
                <div class="text-sm font-medium text-slate-500 mt-0.5">Routes assigned</div>
            </div>
        </div>
        <!-- Students -->
        <div class="bg-white border border-slate-200 rounded-xl p-5 flex items-start gap-4 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-sky-600"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-800"><?= count($assignedStudents) ?></div>
                <div class="text-sm font-medium text-slate-500 mt-0.5">Students on manifest</div>
            </div>
        </div>
        <!-- Incidents -->
        <div class="bg-white border border-slate-200 rounded-xl p-5 flex items-start gap-4 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-600"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-800"><?= $hasMisconducts ? '0' : '–' ?></div>
                <div class="text-sm font-medium text-slate-500 mt-0.5">Incidents reported</div>
            </div>
        </div>
        <!-- Today's date -->
        <div class="bg-white border border-slate-200 rounded-xl p-5 flex items-start gap-4 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
            </div>
            <div>
                <div class="text-base font-bold text-slate-800"><?= date('d M Y') ?></div>
                <div class="text-sm font-medium text-slate-500 mt-0.5">Today's date</div>
            </div>
        </div>
    </div>

    <?php if (!$driverRoute): ?>
    <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 p-4 rounded-xl">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-600 mt-0.5 shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
        <p class="text-sm text-amber-700">No bus route is assigned to your driver profile yet. Ask the administrator to link you to a route so your manifest appears here.</p>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Manifest -->
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl flex flex-col shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-sky-500"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>
                    <span class="text-base font-bold text-slate-800">Today's Manifest</span>
                </div>
                <?php if ($driverRoute): ?>
                <span class="text-xs font-medium px-2.5 py-1 bg-sky-50 text-sky-600 rounded-lg border border-sky-100"><?= count($assignedStudents) ?> students</span>
                <?php endif; ?>
            </div>
            <div class="flex-1 overflow-y-auto max-h-[500px]">
                <?php if (!$driverId): ?>
                    <div class="flex flex-col items-center justify-center text-center p-10">
                        <div class="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center mb-3 border border-amber-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-700 mb-1">Account Not Linked</p>
                        <p class="text-xs text-slate-500 max-w-xs">Your login is not linked to a driver record. Contact the admin to fix this mapping.</p>
                    </div>
                <?php elseif (!$driverRoute): ?>
                    <div class="flex flex-col items-center justify-center text-center p-10">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3 border border-slate-200">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400"><path d="M14.106 5.553a2 2 0 0 0 1.788 0l3.659-1.83A1 1 0 0 1 21 4.619v12.764a1 1 0 0 1-.553.894l-4.553 2.277a2 2 0 0 1-1.788 0l-4.212-2.106a2 2 0 0 0-1.788 0l-3.659 1.83A1 1 0 0 1 3 19.381V6.618a1 1 0 0 1 .553-.894l4.553-2.277a2 2 0 0 1 1.788 0z"/><path d="M15 5.764v15"/><path d="M9 3.236v15"/></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-700 mb-1">No Route Assigned</p>
                        <p class="text-xs text-slate-500 max-w-xs">Once the admin assigns you a bus route, your student manifest will appear here.</p>
                    </div>
                <?php elseif (empty($assignedStudents)): ?>
                    <div class="flex flex-col items-center justify-center text-center p-10">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3 border border-slate-200">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-700 mb-1">No Students on this Route</p>
                        <p class="text-xs text-slate-500 max-w-xs">Students will appear here once they are linked to your route by the admin.</p>
                    </div>
                <?php else: ?>
                    <div class="px-5 py-3 bg-slate-50 border-b border-slate-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-bold text-slate-800"><?= htmlspecialchars($driverRoute['route_name'] ?? ('Route #' . $driverRoute['id'])) ?></div>
                                <div class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($driverRoute['description'] ?? 'Bus route') ?></div>
                            </div>
                            <span class="text-xs font-medium px-2.5 py-1 bg-white text-slate-600 rounded-lg border border-slate-200"><?= count($assignedStudents) ?> on board</span>
                        </div>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($assignedStudents as $i => $s): ?>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-sm font-bold shrink-0">
                                    <?= strtoupper(mb_substr($s['first_name'], 0, 1)) ?>
                                </div>
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></div>
                                        <?php if ($i % 5 === 0): /* Mock medical alert for some students */ ?>
                                            <div class="group relative flex items-center justify-center cursor-help">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-rose-500"><path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9M19.1 4.9c3.9 3.9 3.9 10.3 0 14.2"/><path d="M14.6 14.6c1.6-1.6 1.6-4.2 0-5.8M9.4 9.4c-1.6 1.6-1.6 4.2 0 5.8"/><path d="M12 12v.01"/></svg>
                                                <div class="opacity-0 group-hover:opacity-100 transition-opacity absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden md:block w-max bg-slate-800 text-white text-[10px] px-2 py-1 rounded shadow-lg pointer-events-none z-10">Medical Alert: Peanut Allergy</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-500">
                                        <span>Student #<?= (int) $s['id'] ?></span>
                                        <span>&bull;</span>
                                        <div class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                            <a href="tel:5550123" class="hover:text-emerald-600 hover:underline">(555) 0123</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 sm:ml-auto pl-12 sm:pl-0 mt-2 sm:mt-0">
                                <button type="button" class="flex-1 sm:flex-none px-3 py-1.5 rounded-lg border border-emerald-200 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-colors shadow-sm">
                                    Present
                                </button>
                                <button type="button" class="flex-1 sm:flex-none px-3 py-1.5 rounded-lg border border-rose-200 text-xs font-medium text-rose-700 bg-rose-50 hover:bg-rose-100 transition-colors shadow-sm">
                                    Absent
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right column: My route + Incident reporting -->
        <div class="flex flex-col gap-5">
            <!-- My Route -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-500"><path d="M14.106 5.553a2 2 0 0 0 1.788 0l3.659-1.83A1 1 0 0 1 21 4.619v12.764a1 1 0 0 1-.553.894l-4.553 2.277a2 2 0 0 1-1.788 0l-4.212-2.106a2 2 0 0 0-1.788 0l-3.659 1.83A1 1 0 0 1 3 19.381V6.618a1 1 0 0 1 .553-.894l4.553-2.277a2 2 0 0 1 1.788 0z"/><path d="M15 5.764v15"/><path d="M9 3.236v15"/></svg>
                        <h3 class="text-sm font-bold text-slate-800">My Route Details</h3>
                    </div>
                    <?php if ($driverRoute): ?>
                        <a href="#" class="text-xs font-medium text-emerald-600 hover:text-emerald-700">View Map</a>
                    <?php endif; ?>
                </div>
                <?php if (!$driverRoute): ?>
                    <div class="text-center py-6 border border-dashed border-slate-200 rounded-xl bg-slate-50">
                        <p class="text-xs text-slate-500">No route assigned yet.</p>
                    </div>
                <?php else: ?>
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <div class="p-4 bg-slate-50 border-b border-slate-200">
                            <div class="text-sm font-bold text-slate-800 mb-1"><?= htmlspecialchars($driverRoute['route_name']) ?></div>
                            <div class="text-xs text-slate-500"><?= htmlspecialchars($driverRoute['description'] ?? 'Morning Pickup Route') ?></div>
                            <div class="flex items-center gap-1.5 mt-3">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span class="text-xs text-emerald-600 font-medium">Active · 12 Stops</span>
                            </div>
                        </div>
                        <div class="p-4 bg-white">
                            <h4 class="text-xs font-semibold text-slate-600 mb-3 uppercase tracking-wider">Upcoming Stops</h4>
                            <div class="relative pl-4 space-y-4 border-l-2 border-slate-100 ml-2">
                                <div class="relative">
                                    <div class="absolute -left-[21px] top-1 w-3 h-3 rounded-full bg-emerald-500 ring-4 ring-white"></div>
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-medium text-slate-800">Stop 1: Oak St & 4th Ave</p>
                                            <p class="text-xs text-slate-500">3 Students boarding</p>
                                        </div>
                                        <span class="text-xs font-bold text-slate-700">07:15 AM</span>
                                    </div>
                                </div>
                                <div class="relative">
                                    <div class="absolute -left-[21px] top-1 w-3 h-3 rounded-full bg-slate-300 ring-4 ring-white"></div>
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-medium text-slate-700">Stop 2: Maple Dr & Park Rd</p>
                                            <p class="text-xs text-slate-500">5 Students boarding</p>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-600">07:25 AM</span>
                                    </div>
                                </div>
                                <div class="relative">
                                    <div class="absolute -left-[21px] top-1 w-3 h-3 rounded-full bg-slate-300 ring-4 ring-white"></div>
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-medium text-slate-700">School Arrival</p>
                                            <p class="text-xs text-slate-500">Drop-off</p>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-600">08:00 AM</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>


            <!-- Report Incident -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex-1">
                <div class="flex items-center gap-2 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                    <h3 class="text-sm font-bold text-slate-800">Report Incident</h3>
                </div>
                <?php if (!$hasMisconducts): ?>
                    <div class="flex flex-col items-center justify-center text-center p-6 border border-dashed border-slate-200 rounded-xl bg-slate-50">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center mb-3 border border-slate-200">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400"><path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
                        </div>
                        <h4 class="text-sm font-bold text-slate-700 mb-1">Feature Not Enabled</h4>
                        <p class="text-xs text-slate-500 max-w-xs">Transport incident logging hasn't been configured. Contact your system administrator to enable it.</p>
                    </div>
                <?php else: ?>
                    <?php if ($errors): ?>
                        <div class="mb-3 px-4 py-2.5 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                            <?= htmlspecialchars(implode(' ', $errors)) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="mb-3 px-4 py-2.5 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-700">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" class="space-y-3">
                        <input type="hidden" name="action" value="report_misconduct">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Student</label>
                            <select name="student_id" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-amber-300">
                                <option value="">Select student</option>
                                <?php foreach ($assignedStudents as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Route <span class="font-normal text-slate-400">(optional)</span></label>
                            <select name="route_id" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-amber-300">
                                <option value="">Select route</option>
                                <?php if ($driverRoute): ?>
                                    <option value="<?= (int) $driverRoute['id'] ?>"><?= htmlspecialchars($driverRoute['route_name']) ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Incident Details</label>
                            <textarea name="note" rows="3"
                                      class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-amber-300 resize-none"
                                      placeholder="Briefly describe what happened..."></textarea>
                        </div>
                        <div class="flex justify-end pt-1">
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-rose-600 text-white text-sm font-semibold hover:bg-rose-700 transition-colors shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                                Submit Report
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

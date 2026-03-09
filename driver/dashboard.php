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

<div class="space-y-4">
    <!-- KPI strip -->
    <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Bus Driver Portal</h2>
                <p class="text-[11px] text-slate-500">
                    View your assigned students, routes, and record attendance or incidents.
                </p>
            </div>
            <div class="flex flex-wrap gap-2 text-[11px]">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Today: <?= htmlspecialchars($today) ?>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="border border-slate-100 rounded-lg px-3 py-3">
                <div class="text-xs text-slate-500 mb-1">Routes assigned</div>
                <div class="text-xl font-bold text-emerald-600"><?= $driverRoute ? 1 : 0 ?></div>
            </div>
            <div class="border border-slate-100 rounded-lg px-3 py-3">
                <div class="text-xs text-slate-500 mb-1">Students on manifest</div>
                <div class="text-xl font-bold text-sky-600"><?= count($assignedStudents) ?></div>
            </div>
            <div class="border border-slate-100 rounded-lg px-3 py-3">
                <div class="text-xs text-slate-500 mb-1">Misconduct reports</div>
                <div class="text-xl font-bold text-amber-500">–</div>
            </div>
            <div class="border border-slate-100 rounded-lg px-3 py-3">
                <div class="text-xs text-slate-500 mb-1">Absent marked today</div>
                <div class="text-xl font-bold text-rose-500">–</div>
            </div>
        </div>
        <?php if (!$driverRoute): ?>
            <p class="mt-3 text-[11px] text-amber-700">
                No bus route is assigned to your driver profile yet. Ask the admin to link you to a route so your manifest appears here.
            </p>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Manifest (students assigned to bus) -->
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl flex flex-col">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-800">Today’s manifest</span>
                <span class="text-[11px] text-slate-400">Students assigned to your bus routes.</span>
            </div>
            <div class="p-4 space-y-3 overflow-y-auto max-h-[420px]">
                <?php if (!$driverId): ?>
                    <p class="text-[11px] text-amber-700">
                        Your login is not linked to a bus driver record. Contact the admin to fix this mapping.
                    </p>
                <?php elseif (!$driverRoute): ?>
                    <p class="text-[11px] text-slate-500">
                        No route is currently linked to you. Once the admin assigns you a bus route, you will see your manifest here.
                    </p>
                <?php elseif (empty($assignedStudents)): ?>
                    <p class="text-[11px] text-slate-500">
                        No students are currently assigned to your route. Once students are linked to this route, they will appear here.
                    </p>
                <?php else: ?>
                    <div class="border border-slate-100 rounded-lg">
                        <div class="px-3 py-2 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <div>
                                <div class="text-xs font-semibold text-slate-800">
                                    <?= htmlspecialchars($driverRoute['route_name'] ?? ('Route #' . $driverRoute['id'])) ?>
                                </div>
                                <div class="text-[10px] text-slate-500">
                                    <?= htmlspecialchars($driverRoute['description'] ?? 'Bus route') ?>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 space-y-1.5 text-[11px]">
                            <?php foreach ($assignedStudents as $s): ?>
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <div class="font-medium text-slate-800">
                                        <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button type="button"
                                            class="px-2 py-0.5 rounded-full border border-emerald-200 text-[10px] text-emerald-700 bg-emerald-50">
                                        Present
                                    </button>
                                    <button type="button"
                                            class="px-2 py-0.5 rounded-full border border-rose-200 text-[10px] text-rose-700 bg-rose-50">
                                        Mark absent
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Routes & misconduct reporting -->
        <div class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-5">
                <h3 class="text-xs font-semibold text-slate-800 mb-2">My routes</h3>
                <?php if (!$driverRoute): ?>
                    <p class="text-[11px] text-slate-500">
                        No routes are linked to you yet. When the admin sets up bus routes and assigns you as driver, they will be listed here.
                    </p>
                <?php else: ?>
                    <div class="space-y-2 text-[11px]">
                        <div class="border border-slate-100 rounded-lg px-3 py-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-slate-800"><?= htmlspecialchars($driverRoute['route_name']) ?></div>
                                    <div class="text-[10px] text-slate-500">
                                        <?= htmlspecialchars($driverRoute['description'] ?? 'Bus route') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-5">
                <h3 class="text-xs font-semibold text-slate-800 mb-2">Report student misconduct</h3>
                <?php if (!$hasMisconducts): ?>
                    <p class="text-[11px] text-amber-700">
                        The student_misconducts table is not set up yet. Ask the admin to enable transport incident logging.
                    </p>
                <?php else: ?>
                    <?php if ($errors): ?>
                        <div class="mb-2 px-3 py-1.5 bg-red-50 border border-red-200 rounded-lg text-[11px] text-red-700">
                            <?= htmlspecialchars(implode(' ', $errors)) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="mb-2 px-3 py-1.5 bg-green-50 border border-green-200 rounded-lg text-[11px] text-green-700">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" class="space-y-2 text-[11px]">
                        <input type="hidden" name="action" value="report_misconduct">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Student</label>
                            <select name="student_id"
                                    class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700">
                                <option value="">Select student</option>
                                <?php foreach ($assignedStudents as $s): ?>
                                    <option value="<?= (int) $s['id'] ?>">
                                        <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Route (optional)</label>
                            <select name="route_id"
                                    class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700">
                                <option value="">Select route</option>
                                <?php if ($driverRoute): ?>
                                    <option value="<?= (int) $driverRoute['id'] ?>"><?= htmlspecialchars($driverRoute['route_name']) ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Details</label>
                            <textarea name="note" rows="3"
                                      class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-700"
                                      placeholder="Describe the incident briefly (e.g. repeated standing while bus is moving, fighting, vandalism)..."></textarea>
                        </div>
                        <div class="flex items-center justify-end gap-2 pt-1">
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-600 text-white text-xs font-medium hover:bg-rose-700">
                                Submit report
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

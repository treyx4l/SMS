<?php
$page_title = "Manage Bus Routes";
require_once __DIR__ . '/layout.php';

$conn = get_db_connection();
$schoolId = current_school_id();

// Handle CRUD operations
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add' && !empty($_POST['route_name'])) {
            $name = trim($_POST['route_name']);
            $desc = trim($_POST['description'] ?? '');
            $stmt = $conn->prepare("INSERT INTO bus_routes (school_id, route_name, description) VALUES (?, ?, ?)");
            $stmt->bind_param('iss', $schoolId, $name, $desc);
            $stmt->execute();
            $stmt->close();
            $success = "Bus route added successfully.";
        } 
        elseif ($action === 'edit' && !empty($_POST['route_id']) && !empty($_POST['route_name'])) {
            $routeId = (int)$_POST['route_id'];
            $name = trim($_POST['route_name']);
            $desc = trim($_POST['description'] ?? '');
            $stmt = $conn->prepare("UPDATE bus_routes SET route_name = ?, description = ? WHERE id = ? AND school_id = ?");
            $stmt->bind_param('ssii', $name, $desc, $routeId, $schoolId);
            $stmt->execute();
            $stmt->close();
            $success = "Bus route updated successfully.";
        } 
        elseif ($action === 'delete' && !empty($_POST['route_id'])) {
            $routeId = (int)$_POST['route_id'];
            $stmt = $conn->prepare("DELETE FROM bus_routes WHERE id = ? AND school_id = ?");
            $stmt->bind_param('ii', $routeId, $schoolId);
            $stmt->execute();
            $stmt->close();
            $success = "Bus route deleted successfully.";
        }
    }
}

// Fetch existing routes
$routes = [];
$stmt = $conn->prepare("SELECT * FROM bus_routes WHERE school_id = ? ORDER BY route_name ASC");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $routes[] = $row;
$stmt->close();
?>

<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
            <i data-lucide="map" class="w-6 h-6 text-indigo-600"></i>
            Bus Routes
        </h1>
        <p class="text-sm text-slate-500 mt-1">Manage school bus routes and locations.</p>
    </div>
    <div class="flex items-center gap-3">
        <button onclick="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl font-medium text-sm transition-all flex items-center gap-2 shadow-sm shadow-indigo-200">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Route
        </button>
    </div>
</div>

<?php if ($success): ?>
<div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 flex items-center gap-3">
    <i data-lucide="check-circle-2" class="w-5 h-5 text-green-500"></i>
    <p class="text-sm font-medium"><?= htmlspecialchars($success) ?></p>
</div>
<?php endif; ?>

<!-- List Routes -->
<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Route Name</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Description/Locations</th>
                    <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($routes)): ?>
                <tr>
                    <td colspan="3" class="px-6 py-10 text-center text-slate-500">
                        <i data-lucide="map" class="w-10 h-10 mx-auto text-slate-300 mb-3"></i>
                        <p class="text-sm">No bus routes configured yet.</p>
                        <button onclick="openAddModal()" class="text-indigo-600 font-medium hover:underline text-sm mt-2">Add your first route</button>
                    </td>
                </tr>
                <?php else: foreach ($routes as $route): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <span class="font-medium text-slate-900"><?= htmlspecialchars($route['route_name']) ?></span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-slate-500"><?= htmlspecialchars($route['description'] ?: '—') ?></span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($route)) ?>)" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit">
                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this route?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="route_id" value="<?= $route['id'] ?>">
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="routeModal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm overflow-y-auto">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden transform transition-all">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 id="modalTitle" class="text-lg font-bold text-slate-800">Add Route</h3>
                <button onclick="closeRouteModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" id="routeForm" class="p-6">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="route_id" id="formRouteId" value="">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Route Name *</label>
                        <input type="text" name="route_name" id="routeName" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-colors" placeholder="e.g. North Side Express">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Description / Stops</label>
                        <textarea name="description" id="routeDesc" rows="3" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-colors" placeholder="e.g. Stops at Main St, Oak Ave, and Pine Rd"></textarea>
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeRouteModal()" class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors shadow-sm">Save Route</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
lucide.createIcons();

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Route';
    document.getElementById('formAction').value = 'add';
    document.getElementById('formRouteId').value = '';
    document.getElementById('routeName').value = '';
    document.getElementById('routeDesc').value = '';
    document.getElementById('routeModal').classList.remove('hidden');
}

function openEditModal(route) {
    document.getElementById('modalTitle').textContent = 'Edit Route';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formRouteId').value = route.id;
    document.getElementById('routeName').value = route.route_name;
    document.getElementById('routeDesc').value = route.description;
    document.getElementById('routeModal').classList.remove('hidden');
}

function closeRouteModal() {
    document.getElementById('routeModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>

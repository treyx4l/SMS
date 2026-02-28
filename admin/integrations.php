<?php
$page_title = 'Integrations';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$tablesExist = false;
$res = $conn->query("SHOW TABLES LIKE 'school_integrations'");
$tablesExist = $res && $res->num_rows > 0;

$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tablesExist) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id       = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $provider = trim($_POST['provider'] ?? '');
        $name     = trim($_POST['name'] ?? '');
        $config   = trim($_POST['config_json'] ?? '{}');
        $is_active = !empty($_POST['is_active']) ? 1 : 0;

        if ($provider === '' || $name === '') {
            $errors[] = 'Provider and name are required.';
        }
        if (json_decode($config) === null && $config !== '{}') {
            $errors[] = 'Config must be valid JSON.';
        }

        if (!$errors) {
            if ($id) {
                $stmt = $conn->prepare("UPDATE school_integrations SET provider=?, name=?, config_json=?, is_active=? WHERE id=? AND school_id=?");
                $stmt->bind_param('sssiii', $provider, $name, $config, $is_active, $id, $schoolId);
                $stmt->execute();
                $stmt->close();
                $success = 'Integration updated.';
            } else {
                $stmt = $conn->prepare("INSERT INTO school_integrations (school_id, provider, name, config_json, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('isssi', $schoolId, $provider, $name, $config, $is_active);
                $stmt->execute();
                $stmt->close();
                $success = 'Integration added.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM school_integrations WHERE id=? AND school_id=?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();
            $success = 'Integration removed.';
        }
    }
}

$integrations = [];
if ($tablesExist) {
    $stmt = $conn->prepare("SELECT * FROM school_integrations WHERE school_id = ? ORDER BY provider, name");
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $integrations[] = $row;
    $stmt->close();
}

$edit = null;
if (isset($_GET['edit_id']) && $tablesExist) {
    $eid = (int) $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM school_integrations WHERE id=? AND school_id=?");
    $stmt->bind_param('ii', $eid, $schoolId);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

$providers = ['sms' => 'SMS Gateway', 'payment' => 'Payment Provider', 'reporting' => 'Reporting / Analytics', 'other' => 'Other'];
?>

<?php if (!$tablesExist): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <div class="flex items-start gap-3">
        <i data-lucide="alert-triangle" class="w-6 h-6 text-amber-600 shrink-0"></i>
        <div>
            <h3 class="text-sm font-semibold text-amber-800">Run migration first</h3>
            <p class="text-sm text-amber-700 mt-1">Execute <code class="bg-amber-100 px-1 rounded">database_migration_schools_profile_timetable_integrations.sql</code> to create the integrations table.</p>
        </div>
    </div>
</div>
<?php else: ?>

<?php if ($errors): ?>
<div class="flex items-center gap-2.5 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600 mb-4">
    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
    <?= htmlspecialchars(implode(' ', $errors)) ?>
</div>
<?php elseif ($success): ?>
<div class="flex items-center gap-2.5 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700 mb-4">
    <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-base font-semibold text-slate-800">Integrations</h2>
        <p class="text-xs text-slate-400 mt-0.5">SMS gateways, payment providers, and external tools</p>
    </div>
    <button type="button" onclick="document.getElementById('addPanel').classList.toggle('hidden')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Add integration
    </button>
</div>

<!-- Add/Edit form -->
<div id="addPanel" class="<?= $edit || $errors ? '' : 'hidden' ?> bg-white border border-slate-200 rounded-xl overflow-hidden mb-6">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <span class="text-sm font-semibold text-slate-800"><?= $edit ? 'Edit integration' : 'Add integration' ?></span>
    </div>
    <form method="post" class="p-5">
        <input type="hidden" name="action" value="save">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Provider type</label>
                <select name="provider" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <?php foreach ($providers as $k => $v): ?>
                    <option value="<?= htmlspecialchars($k) ?>" <?= ($edit['provider'] ?? '') === $k ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Display name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required
                       placeholder="e.g. Twilio SMS" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Config (JSON)</label>
            <textarea name="config_json" rows="4" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm font-mono text-sm focus:ring-2 focus:ring-indigo-500"
                      placeholder='{"api_key": "...", "from": "..."}'><?= htmlspecialchars($edit['config_json'] ?? '{}') ?></textarea>
            <p class="text-xs text-slate-500 mt-1">Store API keys, endpoints, etc. as JSON. Keep credentials secure.</p>
        </div>

        <div class="flex items-center gap-4 mb-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>
                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-slate-700">Active</span>
            </label>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">Save</button>
            <?php if ($edit): ?>
            <a href="integrations.php" class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- List -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <span class="text-sm font-semibold text-slate-800">Configured integrations</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50">
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Provider</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Name</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Status</th>
                    <th class="text-right px-4 py-3 text-[11px] font-semibold uppercase text-slate-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($integrations)): ?>
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-slate-400">
                        No integrations. Add SMS, payment, or reporting providers above.
                    </td>
                </tr>
                <?php else: foreach ($integrations as $i): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($providers[$i['provider']] ?? $i['provider']) ?></td>
                    <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($i['name']) ?></td>
                    <td class="px-4 py-3">
                        <?php if ($i['is_active']): ?>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-50 text-green-700 border border-green-200">Active</span>
                        <?php else: ?>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="integrations.php?edit_id=<?= (int)$i['id'] ?>" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium mr-2">Edit</a>
                        <form method="post" class="inline" onsubmit="return confirm('Remove this integration?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>

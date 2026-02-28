<?php
$page_title = 'Bus Drivers';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$errors  = [];
$success = null;

// Handle POST: update or delete (new driver via Firebase + api/create_driver.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $id        = (int) ($_POST['id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $address   = trim($_POST['address'] ?? '');

        if ($full_name === '') {
            $errors[] = 'Full name is required.';
        }

        if (!$errors && $id) {
            $stmt = $conn->prepare("SELECT email FROM bus_drivers WHERE id=? AND school_id=?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $oldRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $oldEmail = $oldRow['email'] ?? '';

            $stmt = $conn->prepare("UPDATE bus_drivers SET full_name=?, email=?, phone=?, address=? WHERE id=? AND school_id=?");
            $stmt->bind_param('ssssii', $full_name, $email, $phone, $address, $id, $schoolId);
            $stmt->execute();
            $stmt->close();

            if ($oldEmail !== '') {
                $stmt = $conn->prepare("UPDATE users SET full_name=?, email=? WHERE email=? AND school_id=? AND role='driver'");
                $stmt->bind_param('sssi', $full_name, $email, $oldEmail, $schoolId);
                $stmt->execute();
                $stmt->close();
            }

            $success = 'Driver updated successfully.';
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("SELECT email FROM bus_drivers WHERE id=? AND school_id=?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM bus_drivers WHERE id=? AND school_id=?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();

            if ($row && $row['email']) {
                $stmt = $conn->prepare("DELETE FROM users WHERE email=? AND school_id=? AND role='driver'");
                $stmt->bind_param('si', $row['email'], $schoolId);
                $stmt->execute();
                $stmt->close();
            }

            $success = 'Driver removed. Their login access has been revoked.';
        }
    }
}

// Fetch drivers with has_login
$drivers = [];
$stmt = $conn->prepare("
    SELECT d.id, d.full_name, d.email, d.phone, d.address, d.created_at,
           CASE WHEN u.id IS NOT NULL THEN 1 ELSE 0 END AS has_login
    FROM bus_drivers d
    LEFT JOIN users u ON u.email = d.email AND u.school_id = d.school_id AND u.role = 'driver'
    WHERE d.school_id = ?
    ORDER BY d.created_at DESC
");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $drivers[] = $row;
$stmt->close();

$edit = null;
if (isset($_GET['edit_id'])) {
    $eid  = (int) $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM bus_drivers WHERE id=? AND school_id=?");
    $stmt->bind_param('ii', $eid, $schoolId);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}

$total     = count($drivers);
$withLogin = count(array_filter($drivers, fn($d) => $d['has_login']));
?>

<script type="module" id="firebase-module">
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-app.js";
import { getAuth, createUserWithEmailAndPassword, sendEmailVerification } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-auth.js";

const firebaseConfig = {
    apiKey:    "<?= htmlspecialchars(getenv('FIREBASE_API_KEY')) ?>",
    authDomain:"<?= htmlspecialchars(getenv('FIREBASE_AUTH_DOMAIN')) ?>",
    projectId: "<?= htmlspecialchars(getenv('FIREBASE_PROJECT_ID')) ?>",
};

const app  = initializeApp(firebaseConfig);
const auth = getAuth(app);
window.__axisAuth = auth;
window.__createUserWithEmailAndPassword = createUserWithEmailAndPassword;
window.__sendEmailVerification = sendEmailVerification;
</script>

<div class="flex items-center justify-between">
    <div>
        <h2 class="text-base font-semibold text-slate-800">Bus Drivers</h2>
        <p class="text-xs text-slate-400 mt-0.5">
            <?= $total ?> driver<?= $total !== 1 ? 's' : '' ?>
            &nbsp;·&nbsp;
            <span class="text-green-600 font-medium"><?= $withLogin ?> with login</span>
            &nbsp;·&nbsp;
            <span class="text-orange-500 font-medium"><?= $total - $withLogin ?> without login</span>
        </p>
    </div>
    <button id="toggleAddBtn" onclick="document.getElementById('addPanel').classList.toggle('hidden')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
        <i data-lucide="user-plus" class="w-4 h-4"></i>
        Add Driver
    </button>
</div>

<?php if ($errors): ?>
<div class="flex items-center gap-2.5 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600">
    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
    <?= htmlspecialchars(implode(' ', $errors)) ?>
</div>
<?php elseif ($success): ?>
<div class="flex items-center gap-2.5 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
    <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<div id="addPanel" class="<?= $errors ? '' : 'hidden' ?> bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="flex items-center gap-2.5 px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <i data-lucide="user-plus" class="w-4 h-4 text-indigo-600"></i>
        <span class="text-sm font-semibold text-slate-800">Add New Driver</span>
        <span class="ml-auto text-[11px] text-slate-400">Creates a Firebase login account</span>
    </div>

    <div class="p-5">
        <div id="add-success" class="hidden mb-4 flex items-center gap-2.5 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            <span id="add-success-text"></span>
        </div>
        <div id="add-error" class="hidden mb-4 flex items-center gap-2.5 px-4 py-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600">
            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
            <span id="add-error-text"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Full Name *</label>
                <input type="text" id="new-name" required placeholder="John Doe"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Phone</label>
                <input type="text" id="new-phone" placeholder="+1 555 000 0000"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Address</label>
                <input type="text" id="new-address" placeholder="123 Street"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Login Email *</label>
                <input type="email" id="new-email" required placeholder="driver@school.com"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Password *</label>
                <input type="password" id="new-password" required placeholder="Min. 6 characters"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Confirm Password *</label>
                <input type="password" id="new-password-confirm" required placeholder="Repeat password"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div class="flex items-center gap-2 px-3 py-2.5 bg-blue-50 border border-blue-100 rounded-lg mb-4">
            <i data-lucide="info" class="w-3.5 h-3.5 text-blue-500 shrink-0"></i>
            <p class="text-[11px] text-blue-700">
                This creates a <strong>Firebase login account</strong> for the driver. They can sign in at the login page.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button id="add-driver-btn" type="button" onclick="createDriver()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                Create Driver Account
            </button>
            <button type="button" onclick="document.getElementById('addPanel').classList.add('hidden')"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
                Cancel
            </button>
        </div>
    </div>
</div>

<?php if ($edit): ?>
<div class="bg-white border border-indigo-200 rounded-xl overflow-hidden">
    <div class="flex items-center gap-2.5 px-5 py-3.5 border-b border-indigo-100 bg-indigo-50">
        <i data-lucide="pencil" class="w-4 h-4 text-indigo-600"></i>
        <span class="text-sm font-semibold text-indigo-800">Editing: <?= htmlspecialchars($edit['full_name']) ?></span>
    </div>
    <form method="post" class="p-5">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Full Name *</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($edit['full_name']) ?>"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($edit['email'] ?? '') ?>"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($edit['phone'] ?? '') ?>"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($edit['address'] ?? '') ?>"
                       class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i data-lucide="save" class="w-4 h-4"></i>
                Save Changes
            </button>
            <a href="drivers.php" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
                Cancel
            </a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
        <div class="relative">
            <input type="text" id="driverSearch" placeholder="Search drivers…"
                   class="pl-8 pr-4 py-1.5 text-xs border border-slate-200 rounded-lg bg-gray-50 w-52 focus:ring-2 focus:ring-indigo-500">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><i data-lucide="search" class="w-3.5 h-3.5"></i></span>
        </div>
        <span class="text-xs text-slate-400"><?= $total ?> total</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="driversTable">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50">
                    <th class="text-left px-5 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Driver</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Email</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Phone</th>
                    <th class="text-left px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Login</th>
                    <th class="text-right px-5 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (!$drivers): ?>
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center">
                        <div class="flex flex-col items-center text-slate-300">
                            <i data-lucide="bus" class="w-10 h-10 mb-3"></i>
                            <p class="text-sm text-slate-400 font-medium">No drivers yet</p>
                            <p class="text-xs text-slate-400 mt-1">Click "Add Driver" to create a driver with login access.</p>
                        </div>
                    </td>
                </tr>
                <?php else: foreach ($drivers as $d): ?>
                <tr class="hover:bg-slate-50 transition-colors driver-row">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold shrink-0">
                                <?= strtoupper(substr($d['full_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="font-medium text-slate-800 driver-name"><?= htmlspecialchars($d['full_name']) ?></div>
                                <div class="text-[11px] text-slate-400">ID #<?= (int) $d['id'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3.5 text-slate-500 text-xs"><?= htmlspecialchars($d['email'] ?? '—') ?></td>
                    <td class="px-4 py-3.5 text-slate-500 text-xs"><?= htmlspecialchars($d['phone'] ?? '—') ?></td>
                    <td class="px-4 py-3.5">
                        <?php if ($d['has_login']): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-50 text-green-700 border border-green-200">
                            <i data-lucide="check" class="w-2.5 h-2.5"></i> Active
                        </span>
                        <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-orange-50 text-orange-600 border border-orange-200">
                            <i data-lucide="alert-circle" class="w-2.5 h-2.5"></i> No login
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="drivers.php?edit_id=<?= (int) $d['id'] ?>"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-slate-200 text-slate-600 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                            </a>
                            <form method="post" class="inline" onsubmit="return confirm('Remove <?= htmlspecialchars(addslashes($d['full_name'])) ?>? This will revoke their login access.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border border-red-200 text-red-500 rounded-lg hover:bg-red-50 transition-colors">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i> Remove
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

<?php require_once __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>
<script>
document.getElementById('driverSearch')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.driver-row').forEach(row => {
        const name = row.querySelector('.driver-name')?.textContent.toLowerCase() ?? '';
        row.style.display = name.includes(q) ? '' : 'none';
    });
});

async function createDriver() {
    const btn     = document.getElementById('add-driver-btn');
    const errEl   = document.getElementById('add-error');
    const errText = document.getElementById('add-error-text');
    const succEl  = document.getElementById('add-success');
    const succText= document.getElementById('add-success-text');

    const name     = document.getElementById('new-name').value.trim();
    const email    = document.getElementById('new-email').value.trim();
    const phone    = document.getElementById('new-phone').value.trim();
    const address  = document.getElementById('new-address').value.trim();
    const password = document.getElementById('new-password').value;
    const confirm  = document.getElementById('new-password-confirm').value;

    errEl.classList.add('hidden');
    succEl.classList.add('hidden');

    if (!name || !email || !password) {
        errText.textContent = 'Full name, email and password are required.';
        errEl.classList.remove('hidden');
        return;
    }
    if (password.length < 6) {
        errText.textContent = 'Password must be at least 6 characters.';
        errEl.classList.remove('hidden');
        return;
    }
    if (password !== confirm) {
        errText.textContent = 'Passwords do not match.';
        errEl.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> Creating…';

    try {
        const auth = window.__axisAuth;
        const createFn = window.__createUserWithEmailAndPassword;
        const cred = await createFn(auth, email, password);
        const idToken = await cred.user.getIdToken();

        const resp = await fetch('../api/create_driver.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idToken, full_name: name, email, phone, address })
        });
        const data = await resp.json();

        if (!resp.ok || !data.success) {
            throw new Error(data.error || 'Server error');
        }

        try { await window.__sendEmailVerification(cred.user); } catch(e) {}

        succText.textContent = 'Driver "' + name + '" created! They can log in using their email and password.';
        succEl.classList.remove('hidden');

        ['new-name','new-phone','new-address','new-email','new-password','new-password-confirm'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });

        setTimeout(() => location.reload(), 2000);
    } catch (err) {
        errText.textContent = err.message || 'Failed to create driver account.';
        errEl.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="user-plus" class="w-4 h-4"></i> Create Driver Account';
        if (window.lucide) lucide.createIcons();
    }
}
</script>

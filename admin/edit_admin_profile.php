<?php
$page_title = 'Edit Admin Profile';
require_once __DIR__ . '/layout.php';
require_once dirname(__DIR__) . '/api/firebase_helpers.php';

$conn     = get_db_connection();
$schoolId = current_school_id();
$userId   = (int) ($_SESSION['user_id'] ?? 0);

$admin = null;
if ($userId && $schoolId) {
    $stmt = $conn->prepare("SELECT id, full_name, email, firebase_uid FROM users WHERE id = ? AND school_id = ? AND role = 'admin' LIMIT 1");
    $stmt->bind_param('ii', $userId, $schoolId);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$errors  = [];
$success = null;
$firebaseWarning = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin) {
    $full_name        = trim($_POST['full_name'] ?? '');
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($full_name === '') {
        $errors[] = 'Full name is required.';
    }

    $changePassword = $new_password !== '';
    if ($changePassword) {
        if (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($new_password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
    }

    if (!$errors) {
        // Update display name
        $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ? AND school_id = ? AND role = 'admin'");
        $stmt->bind_param('sii', $full_name, $userId, $schoolId);
        $stmt->execute();
        $stmt->close();

        $passwordUpdated = false;
        if ($changePassword) {
            $firebaseUid = $admin['firebase_uid'] ?? '';

            // Attempt Firebase password update via Admin SDK
            if ($firebaseUid && !str_starts_with($firebaseUid, 'local:')) {
                $fbResult = update_firebase_user_password($firebaseUid, $new_password);
                if ($fbResult) {
                    $passwordUpdated = true;
                } else {
                    // Firebase Admin SDK not configured — warn but don't block
                    $firebaseWarning = 'Firebase service account is not configured on this server, so the Firebase password could not be updated. Configure FIREBASE_SERVICE_ACCOUNT_PATH and FIREBASE_PROJECT_ID in your .env to enable this.';
                }
            } else {
                $firebaseWarning = 'This account uses local auth and has no Firebase UID. Password change via Firebase is not applicable.';
            }
        }

        $admin['full_name'] = $full_name;

        if ($changePassword && $passwordUpdated) {
            $success = 'Profile and Firebase password updated successfully.';
        } elseif ($changePassword && $firebaseWarning) {
            $success = 'Profile name updated. Password change: see notice below.';
        } else {
            $success = 'Profile updated.';
        }
    }
}
?>

<div class="bg-white rounded-xl border border-slate-200 p-6 max-w-xl">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold shrink-0">
            <?= strtoupper(substr($admin['full_name'] ?? 'A', 0, 1)) ?>
        </div>
        <div>
            <h2 class="text-sm font-semibold text-slate-800">Edit admin profile</h2>
            <p class="text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($admin['email'] ?? '') ?></p>
        </div>
    </div>

    <?php if ($errors): ?>
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">
        <?= htmlspecialchars(implode(' ', $errors)) ?>
    </div>
    <?php elseif ($success): ?>
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 flex items-center gap-2">
        <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <?php if ($firebaseWarning): ?>
    <div class="mb-4 px-4 py-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700 flex items-start gap-2">
        <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5"></i>
        <span><?= htmlspecialchars($firebaseWarning) ?></span>
    </div>
    <?php endif; ?>

    <?php if (!$admin): ?>
    <p class="text-sm text-slate-500">No admin profile found.</p>
    <?php else: ?>
    <form method="post" class="space-y-5">

        <!-- Profile info section -->
        <div class="space-y-4">
            <div class="text-xs font-bold uppercase text-slate-400 tracking-wider">Profile Information</div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Full name *</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($admin['full_name'] ?? '') ?>" required
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Email</label>
                <input type="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" readonly
                       class="w-full px-3 py-2 border border-slate-100 bg-slate-50 rounded-lg text-sm text-slate-500 cursor-not-allowed"
                       title="Email is linked to your Firebase login and cannot be changed here">
                <p class="text-[11px] text-slate-400 mt-1">Email is managed through Firebase and cannot be changed here.</p>
            </div>
        </div>

        <!-- Divider -->
        <div class="border-t border-slate-100"></div>

        <!-- Password section -->
        <div class="space-y-4">
            <div class="text-xs font-bold uppercase text-slate-400 tracking-wider">Change Firebase Password</div>
            <div class="flex items-start gap-2 px-3 py-2.5 bg-indigo-50 border border-indigo-100 rounded-lg">
                <i data-lucide="flame" class="w-4 h-4 text-orange-500 shrink-0 mt-0.5"></i>
                <p class="text-xs text-indigo-700">This will update the password on your Firebase Authentication account — the same one you use to log in.</p>
            </div>
            <p class="text-xs text-slate-400 -mt-1">Leave blank to keep your current password unchanged.</p>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">New password</label>
                <div class="relative">
                    <input type="password" name="new_password" id="newPassword" placeholder="Min. 8 characters"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 pr-10">
                    <button type="button" onclick="togglePwd('newPassword', 'eyeNew')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <i data-lucide="eye" class="w-4 h-4" id="eyeNew"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1.5">Confirm new password</label>
                <div class="relative">
                    <input type="password" name="confirm_password" id="confirmPassword" placeholder="Re-enter new password"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 pr-10">
                    <button type="button" onclick="togglePwd('confirmPassword', 'eyeConfirm')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <i data-lucide="eye" class="w-4 h-4" id="eyeConfirm"></i>
                    </button>
                </div>
            </div>

            <!-- Strength meter -->
            <div id="strengthWrap" class="hidden">
                <div class="flex gap-1">
                    <div class="h-1.5 flex-1 rounded-full bg-slate-200" id="s1"></div>
                    <div class="h-1.5 flex-1 rounded-full bg-slate-200" id="s2"></div>
                    <div class="h-1.5 flex-1 rounded-full bg-slate-200" id="s3"></div>
                    <div class="h-1.5 flex-1 rounded-full bg-slate-200" id="s4"></div>
                </div>
                <p class="text-[11px] text-slate-400 mt-1" id="strengthLabel"></p>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-2 pt-1">
            <a href="admin_profile.php" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                Save changes
            </button>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>
<script>
function togglePwd(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
    } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
    }
    lucide.createIcons();
}

const pwdInput      = document.getElementById('newPassword');
const strengthWrap  = document.getElementById('strengthWrap');
const segments      = ['s1','s2','s3','s4'];
const colors        = ['bg-red-400','bg-orange-400','bg-yellow-400','bg-green-500'];
const strengthLabels = ['Too short','Weak','Fair','Strong'];

pwdInput?.addEventListener('input', function() {
    const val = this.value;
    if (!val) {
        strengthWrap.classList.add('hidden');
        segments.forEach(id => document.getElementById(id).className = 'h-1.5 flex-1 rounded-full bg-slate-200');
        return;
    }
    strengthWrap.classList.remove('hidden');
    let score = 0;
    if (val.length >= 8)          score++;
    if (/[A-Z]/.test(val))        score++;
    if (/[0-9]/.test(val))        score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    segments.forEach((id, i) => {
        document.getElementById(id).className = 'h-1.5 flex-1 rounded-full ' + (i < score ? colors[score - 1] : 'bg-slate-200');
    });
    document.getElementById('strengthLabel').textContent = strengthLabels[score - 1] || 'Too short';
});
</script>

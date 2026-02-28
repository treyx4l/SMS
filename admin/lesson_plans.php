<?php
$page_title = 'Lesson Plans';
require_once __DIR__ . '/layout.php';

$conn     = get_db_connection();
$schoolId = current_school_id();

$tablesExist = false;
$res = $conn->query("SHOW TABLES LIKE 'lesson_plans'");
$tablesExist = $res && $res->num_rows > 0;

$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tablesExist) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id         = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $teacher_id = (int) ($_POST['teacher_id'] ?? 0);
        $class_id   = (int) ($_POST['class_id'] ?? 0);
        $subject_id = (int) ($_POST['subject_id'] ?? 0);
        $week_start = trim($_POST['week_start'] ?? '');
        $topic      = trim($_POST['topic'] ?? '');
        $objectives = trim($_POST['objectives'] ?? '');
        $content    = trim($_POST['content'] ?? '');
        $resources  = trim($_POST['resources'] ?? '');
        $status     = in_array($_POST['status'] ?? '', ['draft','submitted','approved']) ? $_POST['status'] : 'draft';

        if (!$teacher_id || !$class_id || !$subject_id || !$week_start || !$topic) {
            $errors[] = 'Teacher, class, subject, week start, and topic are required.';
        }
        $weekDate = $week_start ? date('Y-m-d', strtotime($week_start)) : null;
        if ($weekDate && date('N', strtotime($weekDate)) != 1) {
            $errors[] = 'Week start must be a Monday.';
        }

        if (!$errors && $weekDate) {
            if ($id) {
                $stmt = $conn->prepare("UPDATE lesson_plans SET teacher_id=?, class_id=?, subject_id=?, week_start=?, topic=?, objectives=?, content=?, resources=?, status=? WHERE id=? AND school_id=?");
                $stmt->bind_param('iiissssssii', $teacher_id, $class_id, $subject_id, $weekDate, $topic, $objectives, $content, $resources, $status, $id, $schoolId);
                $stmt->execute();
                $stmt->close();
                $success = 'Lesson plan updated.';
            } else {
                $stmt = $conn->prepare("INSERT INTO lesson_plans (school_id, teacher_id, class_id, subject_id, week_start, topic, objectives, content, resources, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('iiiissssss', $schoolId, $teacher_id, $class_id, $subject_id, $weekDate, $topic, $objectives, $content, $resources, $status);
                $stmt->execute();
                $stmt->close();
                $success = 'Lesson plan added.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM lesson_plans WHERE id=? AND school_id=?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();
            $success = 'Lesson plan removed.';
        }
    } elseif ($action === 'approve') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("UPDATE lesson_plans SET status='approved' WHERE id=? AND school_id=?");
            $stmt->bind_param('ii', $id, $schoolId);
            $stmt->execute();
            $stmt->close();
            $success = 'Lesson plan approved.';
        }
    }
}

$classes  = [];
$subjects = [];
$teachers = [];
$stmt = $conn->prepare("SELECT id, name, section FROM classes WHERE school_id=? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $classes[] = $row;
$stmt->close();

$stmt = $conn->prepare("SELECT id, name FROM subjects WHERE school_id=? ORDER BY name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $subjects[] = $row;
$stmt->close();

$stmt = $conn->prepare("SELECT id, full_name FROM teachers WHERE school_id=? ORDER BY full_name");
$stmt->bind_param('i', $schoolId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $teachers[] = $row;
$stmt->close();

$filterClass = (int) ($_GET['class_id'] ?? 0);
$filterSubj  = (int) ($_GET['subject_id'] ?? 0);
$filterWeek  = trim($_GET['week_start'] ?? '');

$plans = [];
if ($tablesExist) {
    $sql = "SELECT p.id, p.teacher_id, p.class_id, p.subject_id, p.week_start, p.topic, p.objectives, p.content, p.resources, p.status, p.created_at,
                   t.full_name AS teacher_name, c.name AS class_name, c.section AS class_section, s.name AS subject_name
            FROM lesson_plans p
            LEFT JOIN teachers t ON t.id=p.teacher_id AND t.school_id=p.school_id
            LEFT JOIN classes c ON c.id=p.class_id AND c.school_id=p.school_id
            LEFT JOIN subjects s ON s.id=p.subject_id AND s.school_id=p.school_id
            WHERE p.school_id=?";
    $params = [$schoolId];
    $types  = 'i';
    if ($filterClass) { $sql .= " AND p.class_id=?"; $params[] = $filterClass; $types .= 'i'; }
    if ($filterSubj)  { $sql .= " AND p.subject_id=?"; $params[] = $filterSubj; $types .= 'i'; }
    if ($filterWeek)  { $sql .= " AND p.week_start=?"; $params[] = $filterWeek; $types .= 's'; }
    $sql .= " ORDER BY p.week_start DESC, c.name, s.name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $plans[] = $row;
    $stmt->close();
}

$edit = null;
if (isset($_GET['edit_id']) && $tablesExist) {
    $eid = (int) $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM lesson_plans WHERE id=? AND school_id=?");
    $stmt->bind_param('ii', $eid, $schoolId);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}
?>

<?php if (!$tablesExist): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
    <div class="flex items-start gap-3">
        <i data-lucide="alert-triangle" class="w-6 h-6 text-amber-600 shrink-0"></i>
        <div>
            <h3 class="text-sm font-semibold text-amber-800">Run migration first</h3>
            <p class="text-sm text-amber-700 mt-1">Execute <code class="bg-amber-100 px-1 rounded">database_migration_grades_lesson_plans.sql</code> to create the lesson_plans table.</p>
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

<div class="flex items-center justify-between flex-wrap gap-4 mb-4">
    <h2 class="text-base font-semibold text-slate-800">Lesson plans</h2>
    <form method="get" class="flex flex-wrap gap-2 items-center">
        <select name="class_id" onchange="this.form.submit()" class="px-3 py-2 border border-slate-200 rounded-lg text-sm">
            <option value="">All classes</option>
            <?php foreach ($classes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $filterClass === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name'] . ($c['section'] ? ' ' . $c['section'] : '')) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="subject_id" onchange="this.form.submit()" class="px-3 py-2 border border-slate-200 rounded-lg text-sm">
            <option value="">All subjects</option>
            <?php foreach ($subjects as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= $filterSubj === (int)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="week_start" value="<?= htmlspecialchars($filterWeek) ?>" onchange="this.form.submit()" class="px-3 py-2 border border-slate-200 rounded-lg text-sm" title="Week start (Monday)">
        <button type="submit" class="px-3 py-2 border border-slate-200 rounded-lg text-sm hover:bg-slate-50">Filter</button>
    </form>
</div>

<!-- Add/Edit form -->
<div id="addPanel" class="<?= $edit || $errors ? '' : 'hidden' ?> bg-white border border-slate-200 rounded-xl overflow-hidden mb-6">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <span class="text-sm font-semibold text-slate-800"><?= $edit ? 'Edit lesson plan' : 'Add lesson plan' ?></span>
    </div>
    <form method="post" class="p-5">
        <input type="hidden" name="action" value="save">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Teacher *</label>
                <select name="teacher_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm">
                    <?php foreach ($teachers as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= ($edit['teacher_id'] ?? 0) == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Class *</label>
                <select name="class_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm">
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ($edit['class_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name'] . ($c['section'] ? ' ' . $c['section'] : '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Subject *</label>
                <select name="subject_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm">
                    <?php foreach ($subjects as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= ($edit['subject_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Week start (Monday) *</label>
                <input type="date" name="week_start" value="<?= htmlspecialchars($edit['week_start'] ?? date('Y-m-d', strtotime('monday this week'))) ?>" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Topic *</label>
            <input type="text" name="topic" value="<?= htmlspecialchars($edit['topic'] ?? '') ?>" required placeholder="Lesson topic" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Objectives</label>
                <textarea name="objectives" rows="3" placeholder="Learning objectives" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"><?= htmlspecialchars($edit['objectives'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Resources</label>
                <textarea name="resources" rows="3" placeholder="Materials, links, etc." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"><?= htmlspecialchars($edit['resources'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Content</label>
            <textarea name="content" rows="4" placeholder="Lesson content outline" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"><?= htmlspecialchars($edit['content'] ?? '') ?></textarea>
        </div>
        <div class="flex items-center gap-4 mb-4">
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Status</label>
            <select name="status" class="px-3 py-2 border border-slate-200 rounded-lg text-sm">
                <option value="draft" <?= ($edit['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="submitted" <?= ($edit['status'] ?? '') === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                <option value="approved" <?= ($edit['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">Save</button>
            <?php if ($edit): ?>
            <a href="lesson_plans.php" class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if (!$edit && !$errors): ?>
<div class="mb-4">
    <button type="button" onclick="document.getElementById('addPanel').classList.toggle('hidden')" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Add lesson plan
    </button>
</div>
<?php endif; ?>

<!-- List -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50">
        <span class="text-sm font-semibold text-slate-800">Lesson plans</span>
    </div>
    <div class="divide-y divide-slate-100">
        <?php if (empty($plans)): ?>
        <div class="px-5 py-12 text-center text-slate-400">
            <i data-lucide="file-text" class="w-10 h-10 mx-auto mb-3 text-slate-300"></i>
            <p class="text-sm font-medium">No lesson plans yet</p>
            <p class="text-xs mt-1">Add a lesson plan above or filter to see existing plans.</p>
        </div>
        <?php else: foreach ($plans as $p): ?>
        <div class="px-5 py-4 hover:bg-slate-50">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="font-semibold text-slate-800"><?= htmlspecialchars($p['topic']) ?></div>
                    <div class="text-xs text-slate-500 mt-1">
                        <?= htmlspecialchars($p['class_name'] . ($p['class_section'] ? ' ' . $p['class_section'] : '')) ?>
                        · <?= htmlspecialchars($p['subject_name']) ?>
                        · <?= htmlspecialchars($p['teacher_name']) ?>
                        · Week of <?= date('d M Y', strtotime($p['week_start'])) ?>
                    </div>
                    <?php if (!empty($p['objectives'])): ?>
                    <p class="text-xs text-slate-600 mt-2 line-clamp-2"><?= htmlspecialchars($p['objectives']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium
                        <?= $p['status'] === 'approved' ? 'bg-green-50 text-green-700 border border-green-200' : ($p['status'] === 'submitted' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-500') ?>">
                        <?= ucfirst($p['status']) ?>
                    </span>
                    <a href="lesson_plans.php?edit_id=<?= (int)$p['id'] ?>" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Edit</a>
                    <?php if ($p['status'] !== 'approved'): ?>
                    <form method="post" class="inline">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-medium">Approve</button>
                    </form>
                    <?php endif; ?>
                    <form method="post" class="inline" onsubmit="return confirm('Remove this lesson plan?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Remove</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
<script>lucide.createIcons();</script>

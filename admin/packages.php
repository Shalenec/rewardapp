<?php
require_once '../includes/config.php';
requireAdmin();
$db = getDB();

// Add / Edit package
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = (int)($_POST['id'] ?? 0);
    $name        = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $min         = (float)$_POST['min_amount'];
    $max         = (float)$_POST['max_amount'];
    $rate        = (float)$_POST['daily_return_percent'];
    $days        = (int)$_POST['duration_days'];
    $active      = isset($_POST['is_active']) ? 1 : 0;

    if ($id > 0) {
        $db->prepare("UPDATE packages SET name=?,description=?,min_amount=?,max_amount=?,daily_return_percent=?,duration_days=?,is_active=? WHERE id=?")
           ->execute([$name, $description, $min, $max, $rate, $days, $active, $id]);
        redirect(SITE_URL . '/admin/packages.php', 'Package updated!', 'success');
    } else {
        $db->prepare("INSERT INTO packages (name,description,min_amount,max_amount,daily_return_percent,duration_days,is_active) VALUES (?,?,?,?,?,?,?)")
           ->execute([$name, $description, $min, $max, $rate, $days, $active]);
        redirect(SITE_URL . '/admin/packages.php', 'Package created!', 'success');
    }
}

// Toggle active
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->prepare("UPDATE packages SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
    redirect(SITE_URL . '/admin/packages.php', 'Package status toggled.', 'success');
}

// Edit mode
$editPkg = null;
if (isset($_GET['edit'])) {
    $editStmt = $db->prepare("SELECT * FROM packages WHERE id = ?");
    $editStmt->execute([(int)$_GET['edit']]);
    $editPkg = $editStmt->fetch();
}

$packages = $db->query("SELECT * FROM packages ORDER BY min_amount ASC")->fetchAll();

$pageTitle = 'Investment Packages';
include 'includes/admin_header.php';
?>

<div class="page-header">
    <h1>Investment Packages</h1>
    <p>Manage available investment packages</p>
</div>

<div class="grid-2">
    <!-- Form -->
    <div class="card">
        <div class="card-title" style="margin-bottom:20px;">
            <i class="fas fa-<?php echo $editPkg ? 'edit' : 'plus-circle'; ?>" style="color:var(--primary);"></i>
            <?php echo $editPkg ? 'Edit Package' : 'Add New Package'; ?>
        </div>
        <form method="POST">
            <?php if ($editPkg): ?><input type="hidden" name="id" value="<?php echo $editPkg['id']; ?>"><?php endif; ?>
            <div class="form-group">
                <label class="form-label">Package Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo sanitize($editPkg['name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2"><?php echo sanitize($editPkg['description'] ?? ''); ?></textarea>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Min Amount (KES)</label>
                    <input type="number" name="min_amount" class="form-control" value="<?php echo $editPkg['min_amount'] ?? ''; ?>" step="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Max Amount (KES)</label>
                    <input type="number" name="max_amount" class="form-control" value="<?php echo $editPkg['max_amount'] ?? ''; ?>" step="1" required>
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Daily Return (%)</label>
                    <input type="number" name="daily_return_percent" class="form-control" value="<?php echo $editPkg['daily_return_percent'] ?? ''; ?>" step="0.1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Duration (Days)</label>
                    <input type="number" name="duration_days" class="form-control" value="<?php echo $editPkg['duration_days'] ?? ''; ?>" step="1" required>
                </div>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_active" <?php echo (!$editPkg || $editPkg['is_active']) ? 'checked' : ''; ?>> 
                    <span class="form-label" style="margin:0;">Active (visible to users)</span>
                </label>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary"><?php echo $editPkg ? 'Update Package' : 'Add Package'; ?></button>
                <?php if ($editPkg): ?><a href="packages.php" class="btn btn-gray">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="card">
        <div class="card-title" style="margin-bottom:16px;">All Packages</div>
        <?php foreach ($packages as $pkg): ?>
        <div style="border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:12px;background:<?php echo $pkg['is_active']?'white':'var(--bg)'; ?>;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <div style="font-weight:700;"><?php echo sanitize($pkg['name']); ?></div>
                    <div style="font-size:0.78rem;color:var(--gray);margin:3px 0;"><?php echo sanitize($pkg['description']); ?></div>
                    <div style="font-size:0.8rem;margin-top:6px;">
                        <span style="color:var(--success);font-weight:700;"><?php echo $pkg['daily_return_percent']; ?>%/day</span> &bull;
                        <?php echo $pkg['duration_days']; ?> days &bull;
                        <?php echo formatKES($pkg['min_amount']); ?>–<?php echo formatKES($pkg['max_amount']); ?>
                    </div>
                </div>
                <span class="status-badge status-<?php echo $pkg['is_active']?'active':'suspended'; ?>"><?php echo $pkg['is_active']?'Active':'Inactive'; ?></span>
            </div>
            <div style="display:flex;gap:8px;margin-top:10px;">
                <a href="?edit=<?php echo $pkg['id']; ?>" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i> Edit</a>
                <a href="?toggle=1&id=<?php echo $pkg['id']; ?>" class="btn btn-gray btn-sm"><?php echo $pkg['is_active']?'Deactivate':'Activate'; ?></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>

<?php
require_once '../includes/config.php';
requireAdmin();
$db = getDB();

// Add / Edit ad
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int)($_POST['id'] ?? 0);
    $title    = sanitize($_POST['title'] ?? '');
    $desc     = sanitize($_POST['description'] ?? '');
    $sponsor  = sanitize($_POST['sponsor'] ?? '');
    $videoUrl = sanitize($_POST['video_url'] ?? '');
    $reward   = (float)$_POST['reward_amount'];
    $duration = (int)$_POST['duration_seconds'];
    $active   = isset($_POST['is_active']) ? 1 : 0;

    if ($id > 0) {
        $db->prepare("UPDATE ads SET title=?,description=?,sponsor=?,video_url=?,reward_amount=?,duration_seconds=?,is_active=? WHERE id=?")
           ->execute([$title, $desc, $sponsor, $videoUrl, $reward, $duration, $active, $id]);
        redirect(SITE_URL . '/admin/ads.php', 'Ad updated!', 'success');
    } else {
        $db->prepare("INSERT INTO ads (title,description,sponsor,video_url,reward_amount,duration_seconds,is_active) VALUES (?,?,?,?,?,?,?)")
           ->execute([$title, $desc, $sponsor, $videoUrl, $reward, $duration, $active]);
        redirect(SITE_URL . '/admin/ads.php', 'Ad created!', 'success');
    }
}

if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $db->prepare("UPDATE ads SET is_active = NOT is_active WHERE id = ?")->execute([(int)$_GET['id']]);
    redirect(SITE_URL . '/admin/ads.php', 'Ad status toggled.', 'success');
}

if (isset($_GET['delete']) && isset($_GET['id'])) {
    $db->prepare("DELETE FROM ads WHERE id = ?")->execute([(int)$_GET['id']]);
    redirect(SITE_URL . '/admin/ads.php', 'Ad deleted.', 'warning');
}

$editAd = null;
if (isset($_GET['edit'])) {
    $editStmt = $db->prepare("SELECT * FROM ads WHERE id = ?");
    $editStmt->execute([(int)$_GET['edit']]);
    $editAd = $editStmt->fetch();
}

$ads = $db->query("SELECT a.*, (SELECT COUNT(*) FROM ad_views WHERE ad_id=a.id) as total_views FROM ads a ORDER BY a.created_at DESC")->fetchAll();

$pageTitle = 'Manage Ads';
include 'includes/admin_header.php';
?>

<div class="page-header">
    <h1>Manage Ads</h1>
    <p>Control which ads appear for users and track view counts</p>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title" style="margin-bottom:20px;">
            <i class="fas fa-<?php echo $editAd ? 'edit' : 'plus'; ?>" style="color:var(--primary);"></i>
            <?php echo $editAd ? 'Edit Ad' : 'Add New Ad'; ?>
        </div>
        <form method="POST">
            <?php if ($editAd): ?><input type="hidden" name="id" value="<?php echo $editAd['id']; ?>"><?php endif; ?>
            <div class="form-group">
                <label class="form-label">Ad Title</label>
                <input type="text" name="title" class="form-control" value="<?php echo sanitize($editAd['title'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Sponsor Name</label>
                <input type="text" name="sponsor" class="form-control" placeholder="e.g. Safaricom" value="<?php echo sanitize($editAd['sponsor'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">YouTube Embed URL</label>
                <input type="url" name="video_url" class="form-control" placeholder="https://www.youtube.com/embed/VIDEO_ID" value="<?php echo sanitize($editAd['video_url'] ?? ''); ?>" required>
                <div class="form-text">Use YouTube embed format: youtube.com/embed/VIDEO_ID</div>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2"><?php echo sanitize($editAd['description'] ?? ''); ?></textarea>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Reward (KES)</label>
                    <input type="number" name="reward_amount" class="form-control" value="<?php echo $editAd['reward_amount'] ?? 5; ?>" step="0.5" min="0.5" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Duration (seconds)</label>
                    <input type="number" name="duration_seconds" class="form-control" value="<?php echo $editAd['duration_seconds'] ?? 30; ?>" min="5" required>
                </div>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_active" <?php echo (!$editAd || $editAd['is_active']) ? 'checked' : ''; ?>>
                    <span class="form-label" style="margin:0;">Active</span>
                </label>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary"><?php echo $editAd ? 'Update Ad' : 'Add Ad'; ?></button>
                <?php if ($editAd): ?><a href="ads.php" class="btn btn-gray">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-title" style="margin-bottom:16px;">All Ads (<?php echo count($ads); ?>)</div>
        <?php foreach ($ads as $ad): ?>
        <div style="border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:12px;background:<?php echo $ad['is_active']?'white':'var(--bg)'; ?>;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:0.9rem;"><?php echo sanitize($ad['title']); ?></div>
                    <div style="font-size:0.78rem;color:var(--gray);margin:3px 0;">by <?php echo sanitize($ad['sponsor']); ?></div>
                    <div style="font-size:0.78rem;margin-top:5px;">
                        <span style="color:var(--success);font-weight:700;">KES <?php echo $ad['reward_amount']; ?></span> &bull;
                        <?php echo $ad['duration_seconds']; ?>s &bull;
                        <i class="fas fa-eye"></i> <?php echo number_format($ad['total_views']); ?> views
                    </div>
                </div>
                <span class="status-badge status-<?php echo $ad['is_active']?'active':'suspended'; ?>"><?php echo $ad['is_active']?'Active':'Off'; ?></span>
            </div>
            <div style="display:flex;gap:6px;margin-top:10px;flex-wrap:wrap;">
                <a href="?edit=<?php echo $ad['id']; ?>" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i> Edit</a>
                <a href="?toggle=1&id=<?php echo $ad['id']; ?>" class="btn btn-gray btn-sm"><?php echo $ad['is_active']?'Disable':'Enable'; ?></a>
                <a href="?delete=1&id=<?php echo $ad['id']; ?>" class="btn btn-danger btn-sm" data-confirm="Delete this ad permanently?"><i class="fas fa-trash"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>

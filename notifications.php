<?php
require_once 'includes/config.php';
requireLogin();
$db = getDB();
$userId = $_SESSION['user_id'];

// Mark all as read
$db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);

$notifs = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$notifs->execute([$userId]);
$notifs = $notifs->fetchAll();

$pageTitle = 'Notifications';
include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1><i class="fas fa-bell" style="color:var(--primary);"></i> Notifications</h1>
            <p>Stay updated on your earnings and account activity</p>
        </div>
        <a href="dashboard.php" class="btn btn-gray btn-sm"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<div class="card">
<?php if (empty($notifs)): ?>
<div class="empty-state"><i class="fas fa-bell-slash"></i><p>No notifications yet.</p></div>
<?php else: ?>
<?php foreach ($notifs as $n): ?>
<div style="display:flex;gap:14px;padding:14px 0;border-bottom:1px solid var(--border-light);align-items:flex-start;">
    <?php
    $iconMap = ['success'=>'check-circle','danger'=>'times-circle','warning'=>'exclamation-circle','info'=>'info-circle'];
    $colMap  = ['success'=>'var(--success)','danger'=>'var(--danger)','warning'=>'var(--accent)','info'=>'var(--primary)'];
    $ico = $iconMap[$n['type']] ?? 'info-circle';
    $col = $colMap[$n['type']] ?? 'var(--primary)';
    ?>
    <div style="width:36px;height:36px;background:var(--bg);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:<?php echo $col; ?>;font-size:1.1rem;">
        <i class="fas fa-<?php echo $ico; ?>"></i>
    </div>
    <div style="flex:1;">
        <div style="font-weight:700;font-size:0.9rem;margin-bottom:3px;"><?php echo sanitize($n['title']); ?></div>
        <div style="font-size:0.85rem;color:var(--gray);"><?php echo sanitize($n['message']); ?></div>
        <div style="font-size:0.75rem;color:var(--gray-light);margin-top:5px;"><i class="fas fa-clock"></i> <?php echo date('d M Y, g:ia', strtotime($n['created_at'])); ?></div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

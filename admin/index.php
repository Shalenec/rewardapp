<?php
require_once '../includes/config.php';
requireAdmin();
processDailyReturns();
$db = getDB();

// Quick stats
$totalUsers   = $db->query("SELECT COUNT(*) as c FROM users WHERE is_admin = 0")->fetch()['c'];
$totalDeposits= $db->query("SELECT COALESCE(SUM(amount),0) as s FROM deposits WHERE status='approved'")->fetch()['s'];
$totalWithdrawn=$db->query("SELECT COALESCE(SUM(amount),0) as s FROM withdrawals WHERE status='approved'")->fetch()['s'];
$totalInvested= $db->query("SELECT COALESCE(SUM(amount),0) as s FROM investments")->fetch()['s'];
$pendingDeps  = $db->query("SELECT COUNT(*) as c FROM deposits WHERE status='pending'")->fetch()['c'];
$pendingWithd = $db->query("SELECT COUNT(*) as c FROM withdrawals WHERE status='pending'")->fetch()['c'];
$totalAdViews = $db->query("SELECT COUNT(*) as c FROM ad_views")->fetch()['c'];
$adRewards    = $db->query("SELECT COALESCE(SUM(reward_amount),0) as s FROM ad_views")->fetch()['s'];

// Recent users
$recentUsers = $db->query("SELECT * FROM users WHERE is_admin=0 ORDER BY created_at DESC LIMIT 6")->fetchAll();

// Recent transactions
$recentTx = $db->query("SELECT t.*, u.full_name FROM transactions t JOIN users u ON t.user_id=u.id ORDER BY t.created_at DESC LIMIT 8")->fetchAll();

$pageTitle = 'Admin Dashboard';
include 'includes/admin_header.php';
?>

<div class="page-header">
    <div class="page-header-row">
        <div><h1>Admin Dashboard</h1><p><?php echo date('l, d F Y'); ?></p></div>
        <div style="display:flex;gap:10px;">
            <?php if ($pendingDeps > 0): ?>
            <a href="deposits.php" class="btn btn-warning btn-sm"><i class="fas fa-exclamation-circle"></i> <?php echo $pendingDeps; ?> Pending Deposits</a>
            <?php endif; ?>
            <?php if ($pendingWithd > 0): ?>
            <a href="withdrawals.php" class="btn btn-danger btn-sm"><i class="fas fa-exclamation-circle"></i> <?php echo $pendingWithd; ?> Pending Withdrawals</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Stats Row 1 -->
<div class="stats-grid" style="margin-bottom:16px;">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
            <div class="stat-label">Total Members</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-arrow-down"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo formatKES($totalDeposits); ?></div>
            <div class="stat-label">Total Deposits</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo formatKES($totalWithdrawn); ?></div>
            <div class="stat-label">Total Withdrawn</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-seedling"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo formatKES($totalInvested); ?></div>
            <div class="stat-label">Total Invested</div>
        </div>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo $pendingDeps; ?></div>
            <div class="stat-label">Pending Deposits</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo $pendingWithd; ?></div>
            <div class="stat-label">Pending Withdrawals</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-play"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo number_format($totalAdViews); ?></div>
            <div class="stat-label">Total Ad Views</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-gift"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo formatKES($adRewards); ?></div>
            <div class="stat-label">Ad Rewards Paid</div>
        </div>
    </div>
</div>

<!-- Recent Users & Transactions -->
<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Members</div>
            <a href="users.php" class="btn btn-gray btn-sm">View All</a>
        </div>
        <?php foreach ($recentUsers as $u): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-light);">
            <div style="width:36px;height:36px;background:var(--primary-xlight);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);flex-shrink:0;"><?php echo strtoupper(substr($u['full_name'],0,1)); ?></div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:600;font-size:0.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo sanitize($u['full_name']); ?></div>
                <div style="font-size:0.75rem;color:var(--gray);"><?php echo sanitize($u['phone']); ?> &bull; <?php echo date('d M', strtotime($u['created_at'])); ?></div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:0.82rem;font-weight:700;color:var(--primary);"><?php echo formatKES($u['wallet_balance']); ?></div>
                <span class="status-badge status-<?php echo $u['status']; ?>" style="font-size:0.68rem;"><?php echo $u['status']; ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Transactions</div>
        </div>
        <?php foreach ($recentTx as $tx):
            $isCredit = in_array($tx['type'], ['deposit','return','referral','ad_reward']);
        ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border-light);">
            <div>
                <div style="font-size:0.85rem;font-weight:600;"><?php echo sanitize($tx['full_name']); ?></div>
                <div style="font-size:0.75rem;color:var(--gray);"><?php echo ucfirst(str_replace('_',' ',$tx['type'])); ?> &bull; <?php echo date('d M, g:ia', strtotime($tx['created_at'])); ?></div>
            </div>
            <span style="font-weight:700;font-size:0.88rem;color:<?php echo $isCredit?'var(--success)':'var(--danger)'; ?>;">
                <?php echo $isCredit?'+':'-'; ?><?php echo formatKES($tx['amount']); ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>

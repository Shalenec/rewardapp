<?php
require_once 'includes/config.php';
requireLogin();
processDailyReturns();
$user = getCurrentUser();
$db = getDB();

// Stats
$activeInv = $db->prepare("SELECT COUNT(*) as cnt, SUM(amount) as total FROM investments WHERE user_id = ? AND status = 'active'");
$activeInv->execute([$user['id']]);
$invStats = $activeInv->fetch();

$totalRefs = $db->prepare("SELECT COUNT(*) as cnt FROM users WHERE referred_by = ?");
$totalRefs->execute([$user['id']]);
$refCount = $totalRefs->fetch();

$todayAds = $db->prepare("SELECT COUNT(*) as cnt FROM ad_views WHERE user_id = ? AND DATE(watched_at) = CURDATE()");
$todayAds->execute([$user['id']]);
$adsToday = $todayAds->fetch();
$maxAds = (int)getSetting('max_daily_ads');

// Recent transactions
$txStmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 8");
$txStmt->execute([$user['id']]);
$transactions = $txStmt->fetchAll();

// Active investments
$myInv = $db->prepare("SELECT i.*, p.name as pkg_name FROM investments i JOIN packages p ON i.package_id = p.id WHERE i.user_id = ? AND i.status = 'active' ORDER BY i.created_at DESC LIMIT 3");
$myInv->execute([$user['id']]);
$myInvestments = $myInv->fetchAll();

$pageTitle = 'Dashboard';
include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Hello, <?php echo sanitize(explode(' ', $user['full_name'])[0]); ?>! 👋</h1>
            <p>Here's your earning summary for today, <?php echo date('l, d F Y'); ?></p>
        </div>
        <a href="ads.php" class="btn btn-primary"><i class="fas fa-play-circle"></i> Watch Ads & Earn</a>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-wallet"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo formatKES($user['wallet_balance']); ?></div>
            <div class="stat-label">Wallet Balance</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-chart-line"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo formatKES($user['total_earned']); ?></div>
            <div class="stat-label">Total Earned</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-seedling"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo formatKES($invStats['total'] ?? 0); ?></div>
            <div class="stat-label">Active Investments (<?php echo $invStats['cnt']; ?>)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-users"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo $refCount['cnt']; ?></div>
            <div class="stat-label">Total Referrals</div>
        </div>
    </div>
</div>

<!-- Ads Progress & Quick Referral -->
<div class="grid-2" style="margin-bottom:24px;">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title"><i class="fas fa-play-circle" style="color:var(--primary);"></i> Today's Ad Rewards</div>
                <div class="card-subtitle"><?php echo $adsToday['cnt']; ?> of <?php echo $maxAds; ?> ads watched today</div>
            </div>
            <a href="ads.php" class="btn btn-primary btn-sm">Watch Now</a>
        </div>
        <div class="progress" style="height:12px;margin-bottom:8px;">
            <div class="progress-bar" style="width:<?php echo min(($adsToday['cnt'] / $maxAds) * 100, 100); ?>%"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:0.82rem;color:var(--gray);">
            <span>Earn KES 5 per ad</span>
            <span><?php echo $maxAds - $adsToday['cnt']; ?> remaining today</span>
        </div>
        <div style="margin-top:14px;padding:12px;background:var(--primary-xlight);border-radius:8px;font-size:0.85rem;">
            <i class="fas fa-star" style="color:var(--accent);"></i>
            Potential today: <strong style="color:var(--primary);">KES <?php echo number_format(($maxAds - $adsToday['cnt']) * 5, 2); ?></strong> remaining
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title"><i class="fas fa-share-alt" style="color:#7c3aed;"></i> Referral Program</div>
                <div class="card-subtitle">Earn KES <?php echo getSetting('referral_bonus'); ?> per referral</div>
            </div>
            <a href="referrals.php" class="btn btn-gray btn-sm">View All</a>
        </div>
        <div style="background:var(--bg);border-radius:8px;padding:14px;display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;">
            <div style="font-family:var(--font);font-weight:800;font-size:1.3rem;letter-spacing:2px;color:var(--dark);"><?php echo $user['referral_code']; ?></div>
            <button class="btn btn-outline btn-sm copy-btn" data-copy="<?php echo $user['referral_code']; ?>"><i class="fas fa-copy"></i> Copy</button>
        </div>
        <div style="font-size:0.82rem;color:var(--gray);">Your referral link:</div>
        <div style="font-size:0.78rem;color:var(--primary);word-break:break-all;margin-top:4px;"><?php echo SITE_URL; ?>/register.php?ref=<?php echo $user['referral_code']; ?></div>
        <button class="btn btn-outline btn-sm copy-btn" style="margin-top:10px;width:100%;" data-copy="<?php echo SITE_URL; ?>/register.php?ref=<?php echo $user['referral_code']; ?>"><i class="fas fa-link"></i> Copy Referral Link</button>
    </div>
</div>

<!-- Active Investments -->
<?php if (!empty($myInvestments)): ?>
<div class="card mb-5">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-chart-line" style="color:var(--success);"></i> Active Investments</div>
        <a href="invest.php" class="btn btn-gray btn-sm">Manage All</a>
    </div>
    <?php foreach ($myInvestments as $inv): 
        $progress = $inv['total_return'] > 0 ? ($inv['earned_so_far'] / $inv['total_return']) * 100 : 0;
        $daysLeft = max(0, (strtotime($inv['end_date']) - time()) / 86400);
    ?>
    <div style="padding:14px 0;border-bottom:1px solid var(--border-light);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
            <div>
                <strong style="font-size:0.9rem;"><?php echo sanitize($inv['pkg_name']); ?> Package</strong>
                <span style="font-size:0.78rem;color:var(--gray);margin-left:8px;">Invested <?php echo formatKES($inv['amount']); ?></span>
            </div>
            <div style="text-align:right;">
                <div style="font-size:0.8rem;color:var(--success);font-weight:700;"><?php echo formatKES($inv['earned_so_far']); ?> earned</div>
                <div style="font-size:0.75rem;color:var(--gray);"><?php echo round($daysLeft); ?> days left</div>
            </div>
        </div>
        <div class="progress">
            <div class="progress-bar" style="width:<?php echo min($progress, 100); ?>%"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:var(--gray);margin-top:4px;">
            <span><?php echo number_format($progress, 1); ?>% complete</span>
            <span>Goal: <?php echo formatKES($inv['total_return']); ?></span>
        </div>
    </div>
    <?php endforeach; ?>
    <div style="padding-top:14px;"><a href="invest.php" style="font-size:0.85rem;font-weight:600;">+ Invest More <i class="fas fa-arrow-right"></i></a></div>
</div>
<?php endif; ?>

<!-- Recent Transactions -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-history" style="color:var(--gray);"></i> Recent Transactions</div>
        <a href="wallet.php" class="btn btn-gray btn-sm">View All</a>
    </div>
    <?php if (empty($transactions)): ?>
    <div class="empty-state"><i class="fas fa-receipt"></i><p>No transactions yet. Start earning by watching ads!</p></div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Type</th><th>Description</th><th>Amount</th><th>Balance</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($transactions as $tx): 
                $txColors = ['deposit'=>'blue','withdrawal'=>'orange','investment'=>'purple','return'=>'green','referral'=>'green','ad_reward'=>'yellow'];
                $txIcons  = ['deposit'=>'arrow-down','withdrawal'=>'arrow-up','investment'=>'seedling','return'=>'chart-line','referral'=>'users','ad_reward'=>'play'];
                $col = $txColors[$tx['type']] ?? 'gray';
                $ico = $txIcons[$tx['type']] ?? 'circle';
            ?>
            <tr>
                <td>
                    <span class="status-badge status-<?php echo in_array($tx['type'],['deposit','return','referral','ad_reward'])?'active':'pending'; ?>">
                        <i class="fas fa-<?php echo $ico; ?>"></i>
                        <?php echo ucfirst(str_replace('_',' ',$tx['type'])); ?>
                    </span>
                </td>
                <td style="font-size:0.85rem;"><?php echo sanitize($tx['description']); ?></td>
                <td style="font-weight:700;color:<?php echo in_array($tx['type'],['withdrawal','investment'])?'var(--danger)':'var(--success)'; ?>;">
                    <?php echo in_array($tx['type'],['withdrawal','investment'])?'-':'+'; ?><?php echo formatKES($tx['amount']); ?>
                </td>
                <td><?php echo formatKES($tx['balance_after']); ?></td>
                <td style="font-size:0.8rem;color:var(--gray);"><?php echo date('d M, g:ia', strtotime($tx['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

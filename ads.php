<?php
require_once 'includes/config.php';
requireLogin();
$user = getCurrentUser();
$db = getDB();
$maxAds = (int)getSetting('max_daily_ads');

$todayCount = $db->prepare("SELECT COUNT(*) as cnt FROM ad_views WHERE user_id = ? AND DATE(watched_at) = CURDATE()");
$todayCount->execute([$user['id']]);
$watched = (int)$todayCount->fetch()['cnt'];

// Get all active ads and mark watched today
$adsStmt = $db->prepare("
    SELECT a.*, 
    (SELECT COUNT(*) FROM ad_views WHERE user_id = ? AND ad_id = a.id AND DATE(watched_at) = CURDATE()) as watched_today
    FROM ads a WHERE a.is_active = 1 ORDER BY a.id ASC
");
$adsStmt->execute([$user['id']]);
$ads = $adsStmt->fetchAll();

// Ad icons by sponsor
function adIcon($sponsor) {
    $icons = [
        'Safaricom' => '📱', 'Airtel Kenya' => '📡', 'Naivas' => '🛒',
        'Khetias' => '🏪', 'QuickMart' => '🛍️', 'TotalEnergies' => '⛽', 'Shell Kenya' => '🐚',
    ];
    return $icons[$sponsor] ?? '🎬';
}

$pageTitle = 'Watch Ads & Earn';
include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1><i class="fas fa-play-circle" style="color:var(--primary);"></i> Watch Ads & Earn</h1>
            <p>Watch 30-second ads from top Kenyan brands and earn KES 5 each</p>
        </div>
        <a href="dashboard.php" class="btn btn-gray btn-sm"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<!-- Progress Bar -->
<div class="card" style="margin-bottom:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
        <div>
            <div style="font-weight:700;font-size:1rem;margin-bottom:2px;">Today's Ad Progress</div>
            <div style="font-size:0.85rem;color:var(--gray);"><?php echo $watched; ?> of <?php echo $maxAds; ?> ads watched</div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:1.3rem;font-weight:800;color:var(--success);">KES <?php echo number_format($watched * 5, 2); ?></div>
            <div style="font-size:0.78rem;color:var(--gray);">earned today</div>
        </div>
    </div>
    <div class="progress" style="height:14px;">
        <div class="progress-bar" style="width:<?php echo min(($watched / $maxAds) * 100, 100); ?>%;"></div>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:0.8rem;color:var(--gray);margin-top:6px;">
        <span>0 / <?php echo $maxAds; ?></span>
        <span><?php echo $maxAds; ?></span>
    </div>
    <?php if ($watched >= $maxAds): ?>
    <div style="margin-top:12px;padding:12px 16px;background:#ecfdf5;border-radius:8px;color:#065f46;font-size:0.88rem;font-weight:600;">
        <i class="fas fa-check-circle"></i> Great job! You've reached today's limit. Come back tomorrow for more rewards!
    </div>
    <?php else: ?>
    <div style="margin-top:12px;padding:12px 16px;background:var(--primary-xlight);border-radius:8px;color:var(--primary);font-size:0.88rem;">
        <i class="fas fa-info-circle"></i> <?php echo $maxAds - $watched; ?> more ads available — potential earnings: <strong>KES <?php echo number_format(($maxAds - $watched) * 5, 2); ?></strong>
    </div>
    <?php endif; ?>
</div>

<!-- Ads Grid -->
<div class="ads-grid">
<?php foreach ($ads as $ad): 
    $watchedToday = (bool)$ad['watched_today'];
    $canWatch = !$watchedToday && $watched < $maxAds;
?>
<div class="ad-card <?php echo $watchedToday ? 'ad-watched' : ''; ?>">
    <div class="ad-thumb">
        <span style="font-size:3.5rem;"><?php echo adIcon($ad['sponsor']); ?></span>
        <div class="ad-reward-badge">+KES <?php echo number_format($ad['reward_amount'], 0); ?></div>
        <?php if ($watchedToday): ?>
        <div style="position:absolute;inset:0;background:rgba(255,255,255,.6);display:flex;align-items:center;justify-content:center;">
            <span style="background:#10b981;color:white;padding:6px 14px;border-radius:20px;font-weight:700;font-size:0.85rem;"><i class="fas fa-check"></i> Watched Today</span>
        </div>
        <?php endif; ?>
    </div>
    <div class="ad-body">
        <div class="ad-sponsor"><?php echo sanitize($ad['sponsor']); ?></div>
        <div class="ad-title"><?php echo sanitize($ad['title']); ?></div>
        <div class="ad-duration"><i class="fas fa-clock"></i> <?php echo $ad['duration_seconds']; ?> seconds &bull; <i class="fas fa-eye"></i> <?php echo number_format($ad['views_count']); ?> views</div>
        <?php if ($watchedToday): ?>
            <button class="btn btn-gray btn-block" disabled><i class="fas fa-check"></i> Reward Claimed</button>
        <?php elseif ($watched >= $maxAds): ?>
            <button class="btn btn-gray btn-block" disabled><i class="fas fa-lock"></i> Daily Limit Reached</button>
        <?php else: ?>
            <button class="btn btn-primary btn-block" onclick="openAdModal(<?php echo $ad['id']; ?>, '<?php echo addslashes(sanitize($ad['title'])); ?>', '<?php echo addslashes(sanitize($ad['sponsor'])); ?>', '<?php echo addslashes($ad['video_url']); ?>', '<?php echo $ad['reward_amount']; ?>', <?php echo $ad['duration_seconds']; ?>)">
                <i class="fas fa-play"></i> Watch & Earn KES <?php echo number_format($ad['reward_amount'], 0); ?>
            </button>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- How it works -->
<div class="card" style="margin-top:28px;">
    <div class="card-title" style="margin-bottom:16px;"><i class="fas fa-question-circle" style="color:var(--primary);"></i> How It Works</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
        <?php
        $steps = [
            ['icon'=>'play-circle','color'=>'var(--primary)','title'=>'Click Watch','desc'=>'Click any ad card to open the video player'],
            ['icon'=>'eye','color'=>'var(--success)','title'=>'Watch 30 Seconds','desc'=>'Watch the full 30-second ad without skipping'],
            ['icon'=>'gift','color'=>'#7c3aed','title'=>'Claim Reward','desc'=>'Click "Claim Reward" once the timer completes'],
            ['icon'=>'wallet','color'=>'var(--accent)','title'=>'Get Paid','desc'=>'KES 5 is instantly credited to your wallet'],
        ];
        foreach ($steps as $i => $s): ?>
        <div style="text-align:center;padding:16px;">
            <div style="width:48px;height:48px;background:var(--bg);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:1.3rem;color:<?php echo $s['color']; ?>;border:2px solid <?php echo $s['color']; ?>;">
                <i class="fas fa-<?php echo $s['icon']; ?>"></i>
            </div>
            <div style="font-weight:700;font-size:0.9rem;margin-bottom:5px;">Step <?php echo $i+1; ?>: <?php echo $s['title']; ?></div>
            <div style="font-size:0.82rem;color:var(--gray);"><?php echo $s['desc']; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

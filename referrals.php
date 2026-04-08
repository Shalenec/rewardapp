<?php
require_once 'includes/config.php';
requireLogin();
$user = getCurrentUser();
$db = getDB();

$refLink = SITE_URL . '/register.php?ref=' . $user['referral_code'];
$bonusPerRef = getSetting('referral_bonus');

// Get all referrals
$refsStmt = $db->prepare("
    SELECT u.full_name, u.created_at, u.status,
           r.reward_amount, r.created_at as reward_date
    FROM users u
    LEFT JOIN referral_rewards r ON r.referred_id = u.id
    WHERE u.referred_by = ?
    ORDER BY u.created_at DESC
");
$refsStmt->execute([$user['id']]);
$referrals = $refsStmt->fetchAll();

$totalEarned = $db->prepare("SELECT SUM(reward_amount) as total FROM referral_rewards WHERE referrer_id = ?");
$totalEarned->execute([$user['id']]);
$earned = $totalEarned->fetch()['total'] ?? 0;

$pageTitle = 'Referral Program';
include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-users" style="color:#7c3aed;"></i> Referral Program</h1>
    <p>Invite friends and earn KES <?php echo number_format($bonusPerRef); ?> for every successful referral</p>
</div>

<!-- Referral Hero -->
<div class="referral-hero" style="margin-bottom:24px;">
    <h2>🎉 Invite & Earn KES <?php echo number_format($bonusPerRef); ?> Per Referral</h2>
    <p>Share your referral code with friends. When they register and join <?php echo SITE_NAME; ?>, you both benefit!</p>
    <div class="referral-code-box">
        <div class="referral-code"><?php echo $user['referral_code']; ?></div>
        <button class="referral-copy-btn" data-copy="<?php echo $user['referral_code']; ?>">Copy Code</button>
    </div>
    <div class="referral-link-box">
        <i class="fas fa-link" style="flex-shrink:0;"></i>
        <span><?php echo $refLink; ?></span>
        <button class="referral-copy-btn" data-copy="<?php echo $refLink; ?>" style="flex-shrink:0;font-size:0.75rem;padding:5px 10px;">Copy Link</button>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-user-plus"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo count($referrals); ?></div>
            <div class="stat-label">Total Referrals</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-coins"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo formatKES($earned); ?></div>
            <div class="stat-label">Total Referral Earnings</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="fas fa-award"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo formatKES($bonusPerRef); ?></div>
            <div class="stat-label">Bonus Per Referral</div>
        </div>
    </div>
</div>

<!-- Share Options -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-title" style="margin-bottom:16px;"><i class="fas fa-share-alt" style="color:var(--primary);"></i> Share Your Link</div>
    <div style="display:flex;flex-wrap:wrap;gap:10px;">
        <a href="https://wa.me/?text=Join%20<?php echo urlencode(SITE_NAME); ?>%20and%20earn%20money%20watching%20ads%20%26%20investing!%20Use%20my%20code%20<?php echo $user['referral_code']; ?>%20or%20click:%20<?php echo urlencode($refLink); ?>" target="_blank" class="btn btn-success btn-sm"><i class="fab fa-whatsapp"></i> Share on WhatsApp</a>
        <a href="sms:?body=Join%20<?php echo SITE_NAME; ?>%20-%20Earn%20from%20ads%20%26%20investments!%20Use%20code%20<?php echo $user['referral_code']; ?>%20<?php echo $refLink; ?>" class="btn btn-primary btn-sm"><i class="fas fa-sms"></i> Share via SMS</a>
        <a href="mailto:?subject=Earn%20Money%20with%20<?php echo SITE_NAME; ?>&body=Hi!%20I%20use%20<?php echo SITE_NAME; ?>%20to%20earn%20money.%20Join%20using%20my%20code%20<?php echo $user['referral_code']; ?>%20or%20link:%20<?php echo $refLink; ?>" class="btn btn-gray btn-sm"><i class="fas fa-envelope"></i> Share via Email</a>
        <button class="btn btn-outline btn-sm copy-btn" data-copy="<?php echo $refLink; ?>"><i class="fas fa-copy"></i> Copy Link</button>
    </div>
</div>

<!-- Referrals List -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-list" style="color:var(--gray);"></i> My Referrals</div>
        <span class="status-badge status-active"><?php echo count($referrals); ?> total</span>
    </div>
    <?php if (empty($referrals)): ?>
    <div class="empty-state">
        <i class="fas fa-user-friends"></i>
        <p>No referrals yet. Share your code and start earning!</p>
        <button class="btn btn-primary btn-sm copy-btn" data-copy="<?php echo $refLink; ?>" style="margin-top:12px;"><i class="fas fa-copy"></i> Copy Your Link</button>
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>#</th><th>Name</th><th>Joined</th><th>Bonus Earned</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($referrals as $i => $ref): ?>
            <tr>
                <td style="color:var(--gray);"><?php echo $i + 1; ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:32px;height:32px;background:var(--primary-xlight);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);font-size:0.8rem;"><?php echo strtoupper(substr($ref['full_name'], 0, 1)); ?></div>
                        <span style="font-weight:600;"><?php echo sanitize($ref['full_name']); ?></span>
                    </div>
                </td>
                <td style="font-size:0.82rem;color:var(--gray);"><?php echo date('d M Y', strtotime($ref['created_at'])); ?></td>
                <td style="color:var(--success);font-weight:700;"><?php echo formatKES($ref['reward_amount'] ?? 0); ?></td>
                <td><span class="status-badge status-<?php echo $ref['status']; ?>"><?php echo ucfirst($ref['status']); ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- How it works -->
<div class="card" style="margin-top:24px;">
    <div class="card-title" style="margin-bottom:16px;"><i class="fas fa-question-circle" style="color:var(--primary);"></i> How the Referral Program Works</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
        <div style="padding:16px;background:var(--bg);border-radius:10px;text-align:center;">
            <div style="font-size:2rem;margin-bottom:8px;">📋</div>
            <div style="font-weight:700;margin-bottom:6px;">1. Get Your Code</div>
            <div style="font-size:0.82rem;color:var(--gray);">Copy your unique referral code or link above</div>
        </div>
        <div style="padding:16px;background:var(--bg);border-radius:10px;text-align:center;">
            <div style="font-size:2rem;margin-bottom:8px;">📤</div>
            <div style="font-weight:700;margin-bottom:6px;">2. Share With Friends</div>
            <div style="font-size:0.82rem;color:var(--gray);">Share via WhatsApp, SMS, email or social media</div>
        </div>
        <div style="padding:16px;background:var(--bg);border-radius:10px;text-align:center;">
            <div style="font-size:2rem;margin-bottom:8px;">👤</div>
            <div style="font-weight:700;margin-bottom:6px;">3. Friend Registers</div>
            <div style="font-size:0.82rem;color:var(--gray);">They sign up using your referral code or link</div>
        </div>
        <div style="padding:16px;background:var(--bg);border-radius:10px;text-align:center;">
            <div style="font-size:2rem;margin-bottom:8px;">💰</div>
            <div style="font-weight:700;margin-bottom:6px;">4. You Earn KES <?php echo number_format($bonusPerRef); ?></div>
            <div style="font-size:0.82rem;color:var(--gray);">Bonus instantly credited to your wallet</div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

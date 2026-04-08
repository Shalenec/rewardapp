<?php
require_once '../includes/config.php';
requireAdmin();
$db = getDB();

$refs = $db->query("
    SELECT r.*, 
           u1.full_name as referrer_name, u1.phone as referrer_phone,
           u2.full_name as referred_name, u2.created_at as joined_at
    FROM referral_rewards r
    JOIN users u1 ON r.referrer_id = u1.id
    JOIN users u2 ON r.referred_id = u2.id
    ORDER BY r.created_at DESC LIMIT 200
")->fetchAll();

// Top referrers
$topRef = $db->query("
    SELECT u.full_name, u.phone, COUNT(r.id) as ref_count, SUM(r.reward_amount) as total_earned
    FROM referral_rewards r JOIN users u ON r.referrer_id = u.id
    GROUP BY r.referrer_id ORDER BY ref_count DESC LIMIT 10
")->fetchAll();

$pageTitle = 'Referral Activity';
include 'includes/admin_header.php';
?>

<div class="page-header">
    <h1>Referral Activity</h1>
    <p>Track all referral rewards and top performers</p>
</div>

<div class="grid-2" style="margin-bottom:24px;">
    <!-- Top Referrers -->
    <div class="card">
        <div class="card-title" style="margin-bottom:16px;"><i class="fas fa-trophy" style="color:var(--accent);"></i> Top Referrers</div>
        <?php foreach ($topRef as $i => $tr): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-light);">
            <div style="width:28px;height:28px;background:<?php echo $i===0?'var(--accent)':($i===1?'#94a3b8':($i===2?'#d97706':'var(--bg)')); ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.8rem;flex-shrink:0;"><?php echo $i+1; ?></div>
            <div style="flex:1;">
                <div style="font-weight:600;font-size:0.88rem;"><?php echo sanitize($tr['full_name']); ?></div>
                <div style="font-size:0.75rem;color:var(--gray);"><?php echo sanitize($tr['phone']); ?></div>
            </div>
            <div style="text-align:right;">
                <div style="font-weight:700;color:var(--primary);"><?php echo $tr['ref_count']; ?> refs</div>
                <div style="font-size:0.78rem;color:var(--success);"><?php echo formatKES($tr['total_earned']); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($topRef)): ?>
        <div class="empty-state"><i class="fas fa-trophy"></i><p>No referrals yet.</p></div>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="card">
        <div class="card-title" style="margin-bottom:16px;">Referral Stats</div>
        <?php
        $totalRefs   = $db->query("SELECT COUNT(*) as c FROM referral_rewards")->fetch()['c'];
        $totalPaid   = $db->query("SELECT COALESCE(SUM(reward_amount),0) as s FROM referral_rewards")->fetch()['s'];
        $todayRefs   = $db->query("SELECT COUNT(*) as c FROM referral_rewards WHERE DATE(created_at) = CURDATE()")->fetch()['c'];
        ?>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div style="background:var(--primary-xlight);border-radius:8px;padding:16px;">
                <div style="font-size:0.78rem;color:var(--gray);text-transform:uppercase;font-weight:700;letter-spacing:.05em;margin-bottom:4px;">Total Referrals</div>
                <div style="font-size:2rem;font-weight:800;color:var(--primary);"><?php echo number_format($totalRefs); ?></div>
            </div>
            <div style="background:#ecfdf5;border-radius:8px;padding:16px;">
                <div style="font-size:0.78rem;color:var(--gray);text-transform:uppercase;font-weight:700;letter-spacing:.05em;margin-bottom:4px;">Total Paid Out</div>
                <div style="font-size:2rem;font-weight:800;color:var(--success);"><?php echo formatKES($totalPaid); ?></div>
            </div>
            <div style="background:#fffbeb;border-radius:8px;padding:16px;">
                <div style="font-size:0.78rem;color:var(--gray);text-transform:uppercase;font-weight:700;letter-spacing:.05em;margin-bottom:4px;">Today's Referrals</div>
                <div style="font-size:2rem;font-weight:800;color:var(--accent);"><?php echo $todayRefs; ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Full list -->
<div class="card">
    <div class="card-title" style="margin-bottom:16px;">All Referral Rewards</div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>#</th><th>Referrer</th><th>Referred User</th><th>Reward</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($refs as $r): ?>
            <tr>
                <td style="color:var(--gray);">#<?php echo $r['id']; ?></td>
                <td>
                    <div style="font-weight:600;font-size:0.88rem;"><?php echo sanitize($r['referrer_name']); ?></div>
                    <div style="font-size:0.75rem;color:var(--gray);"><?php echo sanitize($r['referrer_phone']); ?></div>
                </td>
                <td>
                    <div style="font-size:0.88rem;"><?php echo sanitize($r['referred_name']); ?></div>
                    <div style="font-size:0.75rem;color:var(--gray);">Joined <?php echo date('d M Y', strtotime($r['joined_at'])); ?></div>
                </td>
                <td style="color:var(--success);font-weight:700;"><?php echo formatKES($r['reward_amount']); ?></td>
                <td style="font-size:0.82rem;color:var(--gray);"><?php echo date('d M Y, g:ia', strtotime($r['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($refs)): ?>
            <tr><td colspan="5"><div class="empty-state"><i class="fas fa-share-alt"></i><p>No referrals yet.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>

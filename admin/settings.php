<?php
require_once '../includes/config.php';
requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['site_name','referral_bonus','min_deposit','min_withdrawal','max_daily_ads','mpesa_paybill','mpesa_account','withdrawal_fee_percent','currency'];
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $val = sanitize(trim($_POST[$key]));
            $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?")->execute([$val, $key]);
        }
    }
    redirect(SITE_URL . '/admin/settings.php', 'Settings saved successfully!', 'success');
}

// Load all settings
$allSettings = $db->query("SELECT * FROM settings")->fetchAll();
$settings = [];
foreach ($allSettings as $s) $settings[$s['setting_key']] = $s['setting_value'];

$pageTitle = 'System Settings';
include 'includes/admin_header.php';
?>

<div class="page-header">
    <h1>System Settings</h1>
    <p>Configure platform-wide settings</p>
</div>

<form method="POST">
<div class="grid-2">
    <!-- General -->
    <div class="card">
        <div class="card-title" style="margin-bottom:20px;"><i class="fas fa-sliders-h" style="color:var(--primary);"></i> General Settings</div>
        <div class="form-group">
            <label class="form-label">Site Name</label>
            <input type="text" name="site_name" class="form-control" value="<?php echo sanitize($settings['site_name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Currency</label>
            <input type="text" name="currency" class="form-control" value="<?php echo sanitize($settings['currency'] ?? 'KES'); ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Referral Bonus (KES)</label>
            <div style="position:relative;"><span class="input-prefix">KES</span>
            <input type="number" name="referral_bonus" class="form-control" style="padding-left:52px;" value="<?php echo $settings['referral_bonus'] ?? 100; ?>" step="1" min="0" required></div>
            <div class="form-text">Amount credited when a user refers a new member</div>
        </div>
        <div class="form-group">
            <label class="form-label">Max Daily Ad Views Per User</label>
            <input type="number" name="max_daily_ads" class="form-control" value="<?php echo $settings['max_daily_ads'] ?? 5; ?>" step="1" min="1" required>
        </div>
    </div>

    <!-- Financial -->
    <div class="card">
        <div class="card-title" style="margin-bottom:20px;"><i class="fas fa-money-bill-wave" style="color:var(--success);"></i> Financial Settings</div>
        <div class="form-group">
            <label class="form-label">Minimum Deposit (KES)</label>
            <div style="position:relative;"><span class="input-prefix">KES</span>
            <input type="number" name="min_deposit" class="form-control" style="padding-left:52px;" value="<?php echo $settings['min_deposit'] ?? 500; ?>" step="1" min="1" required></div>
        </div>
        <div class="form-group">
            <label class="form-label">Minimum Withdrawal (KES)</label>
            <div style="position:relative;"><span class="input-prefix">KES</span>
            <input type="number" name="min_withdrawal" class="form-control" style="padding-left:52px;" value="<?php echo $settings['min_withdrawal'] ?? 500; ?>" step="1" min="1" required></div>
        </div>
        <div class="form-group">
            <label class="form-label">Withdrawal Fee (%)</label>
            <div style="position:relative;"><span class="input-prefix">%</span>
            <input type="number" name="withdrawal_fee_percent" class="form-control" style="padding-left:36px;" value="<?php echo $settings['withdrawal_fee_percent'] ?? 2; ?>" step="0.1" min="0" max="100" required></div>
        </div>
    </div>

    <!-- M-Pesa -->
    <div class="card">
        <div class="card-title" style="margin-bottom:20px;"><i class="fas fa-mobile-alt" style="color:#10b981;"></i> M-Pesa Settings</div>
        <div class="form-group">
            <label class="form-label">Paybill Number</label>
            <input type="text" name="mpesa_paybill" class="form-control" value="<?php echo sanitize($settings['mpesa_paybill'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label">Account Number</label>
            <input type="text" name="mpesa_account" class="form-control" value="<?php echo sanitize($settings['mpesa_account'] ?? ''); ?>" required>
        </div>
        <div style="background:#ecfdf5;border:1px solid #6ee7b7;border-radius:8px;padding:14px;font-size:0.85rem;color:#065f46;">
            <i class="fas fa-info-circle"></i> Users will be shown this paybill and account when making deposits.
        </div>
    </div>

    <!-- Quick Summary -->
    <div class="card">
        <div class="card-title" style="margin-bottom:20px;"><i class="fas fa-chart-bar" style="color:var(--accent);"></i> Platform Summary</div>
        <?php
        $summary = [
            ['label'=>'Total Users','value'=>$db->query("SELECT COUNT(*) as c FROM users WHERE is_admin=0")->fetch()['c'],'format'=>false],
            ['label'=>'Active Investments','value'=>$db->query("SELECT COUNT(*) as c FROM investments WHERE status='active'")->fetch()['c'],'format'=>false],
            ['label'=>'Total Deposits (Approved)','value'=>$db->query("SELECT COALESCE(SUM(amount),0) as s FROM deposits WHERE status='approved'")->fetch()['s'],'format'=>true],
            ['label'=>'Total Withdrawals (Approved)','value'=>$db->query("SELECT COALESCE(SUM(amount),0) as s FROM withdrawals WHERE status='approved'")->fetch()['s'],'format'=>true],
            ['label'=>'Total Ad Rewards Paid','value'=>$db->query("SELECT COALESCE(SUM(reward_amount),0) as s FROM ad_views")->fetch()['s'],'format'=>true],
            ['label'=>'Total Referral Rewards','value'=>$db->query("SELECT COALESCE(SUM(reward_amount),0) as s FROM referral_rewards")->fetch()['s'],'format'=>true],
        ];
        foreach ($summary as $row): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border-light);">
            <span style="font-size:0.85rem;color:var(--gray);"><?php echo $row['label']; ?></span>
            <strong style="color:var(--dark);"><?php echo $row['format'] ? formatKES($row['value']) : number_format($row['value']); ?></strong>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div style="margin-top:20px;">
    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Save All Settings</button>
</div>
</form>

<?php include 'includes/admin_footer.php'; ?>

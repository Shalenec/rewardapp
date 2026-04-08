<?php
require_once 'includes/config.php';
requireLogin();
$user = getCurrentUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize(trim($_POST['full_name'] ?? ''));
    $phone    = sanitize(trim($_POST['phone'] ?? ''));

    if (empty($fullName) || empty($phone)) {
        redirect('profile.php', 'Name and phone cannot be empty.', 'danger');
    }

    // Change password
    if (!empty($_POST['new_password'])) {
        if (!password_verify($_POST['current_password'] ?? '', $user['password'])) {
            redirect('profile.php', 'Current password is incorrect.', 'danger');
        }
        if (strlen($_POST['new_password']) < 6) {
            redirect('profile.php', 'New password must be at least 6 characters.', 'danger');
        }
        $hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $db->prepare("UPDATE users SET full_name=?, phone=?, password=? WHERE id=?")->execute([$fullName, $phone, $hash, $user['id']]);
    } else {
        $db->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?")->execute([$fullName, $phone, $user['id']]);
    }
    redirect('profile.php', 'Profile updated successfully!', 'success');
}

$pageTitle = 'My Profile';
include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-user-circle" style="color:var(--primary);"></i> My Profile</h1>
    <p>Manage your account details and security settings</p>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title" style="margin-bottom:20px;">Account Information</div>
        <div style="text-align:center;margin-bottom:24px;">
            <div style="width:80px;height:80px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;color:white;font-weight:800;margin:0 auto 12px;">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <div style="font-weight:700;font-size:1.1rem;"><?php echo sanitize($user['full_name']); ?></div>
            <div style="font-size:0.85rem;color:var(--gray);"><?php echo sanitize($user['email']); ?></div>
            <div style="margin-top:8px;"><span class="status-badge status-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></div>
        </div>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?php echo sanitize($user['full_name']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?php echo sanitize($user['phone']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email <span style="color:var(--gray);font-weight:400;">(cannot change)</span></label>
                <input type="email" class="form-control" value="<?php echo sanitize($user['email']); ?>" disabled style="background:var(--bg);cursor:not-allowed;">
            </div>
            <div style="border-top:1px solid var(--border);padding-top:18px;margin-top:8px;">
                <div style="font-weight:700;margin-bottom:14px;font-size:0.9rem;">Change Password (optional)</div>
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" placeholder="Enter current password">
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Save Changes</button>
        </form>
    </div>
    <div>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-title" style="margin-bottom:16px;">Account Summary</div>
            <?php
            $rows = [
                ['label'=>'Referral Code','value'=>$user['referral_code'],'copy'=>true],
                ['label'=>'Member Since','value'=>date('d M Y', strtotime($user['created_at'])),'copy'=>false],
                ['label'=>'Wallet Balance','value'=>formatKES($user['wallet_balance']),'copy'=>false],
                ['label'=>'Total Earned','value'=>formatKES($user['total_earned']),'copy'=>false],
                ['label'=>'Total Withdrawn','value'=>formatKES($user['total_withdrawn']),'copy'=>false],
            ];
            foreach ($rows as $r): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border-light);">
                <span style="font-size:0.85rem;color:var(--gray);"><?php echo $r['label']; ?></span>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-weight:600;font-size:0.9rem;"><?php echo $r['value']; ?></span>
                    <?php if ($r['copy']): ?>
                    <button class="btn btn-gray btn-sm copy-btn" data-copy="<?php echo $r['value']; ?>" style="padding:3px 8px;font-size:0.75rem;"><i class="fas fa-copy"></i></button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="card">
            <div class="card-title" style="margin-bottom:12px;color:var(--danger);"><i class="fas fa-sign-out-alt"></i> Account Actions</div>
            <a href="logout.php" class="btn btn-danger btn-block" data-confirm="Are you sure you want to logout?"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

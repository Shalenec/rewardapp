<?php
require_once 'includes/config.php';
if (isLoggedIn()) { redirect(SITE_URL . '/dashboard.php'); }

$errors = [];
$refCode = sanitize($_GET['ref'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $refInput = trim($_POST['ref_code'] ?? '');

    if (empty($fullName)) $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (empty($phone) || strlen($phone) < 10) $errors[] = 'Valid phone number is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $db = getDB();
        // Check duplicates
        $dup = $db->prepare("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $dup->execute([$email, $phone]);
        if ($dup->fetch()) {
            $errors[] = 'Email or phone number already registered.';
        } else {
            // Find referrer
            $referrerId = null;
            if (!empty($refInput)) {
                $refStmt = $db->prepare("SELECT id FROM users WHERE referral_code = ? LIMIT 1");
                $refStmt->execute([$refInput]);
                $refUser = $refStmt->fetch();
                if ($refUser) $referrerId = $refUser['id'];
            }

            // Generate unique referral code
            do {
                $newCode = generateReferralCode();
                $codeCheck = $db->prepare("SELECT id FROM users WHERE referral_code = ?");
                $codeCheck->execute([$newCode]);
            } while ($codeCheck->fetch());

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (full_name, email, phone, password, referral_code, referred_by) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$fullName, $email, $phone, $hash, $newCode, $referrerId]);
            $newUserId = $db->lastInsertId();

            // Credit referral bonus
            if ($referrerId) {
                $bonus = (float)getSetting('referral_bonus');
                $db->prepare("UPDATE users SET wallet_balance = wallet_balance + ?, total_earned = total_earned + ? WHERE id = ?")->execute([$bonus, $bonus, $referrerId]);
                $balStmt = $db->prepare("SELECT wallet_balance FROM users WHERE id = ?");
                $balStmt->execute([$referrerId]);
                $ref = $balStmt->fetch();
                $db->prepare("INSERT INTO referral_rewards (referrer_id, referred_id, reward_amount) VALUES (?,?,?)")->execute([$referrerId, $newUserId, $bonus]);
                addTransaction($referrerId, 'referral', $bonus, $ref['wallet_balance'], 'Referral bonus for inviting ' . $fullName, $newUserId);
                addNotification($referrerId, 'Referral Bonus!', 'You earned KES ' . number_format($bonus, 2) . ' for referring ' . $fullName . '!', 'success');
            }

            addNotification($newUserId, 'Welcome to ' . SITE_NAME . '!', 'Your account has been created. Start earning by watching ads and referring friends!', 'success');

            $_SESSION['user_id'] = $newUserId;
            $_SESSION['is_admin'] = 0;
            redirect(SITE_URL . '/dashboard.php', 'Account created successfully! Welcome to ' . SITE_NAME . '!', 'success');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand"><a href="<?php echo SITE_URL; ?>/index.php"><span class="brand-icon"><i class="fas fa-award"></i></span><span class="brand-text"><?php echo SITE_NAME; ?></span></a></div>
    <div class="navbar-right">
        <a href="<?php echo SITE_URL; ?>/login.php" class="btn-outline-sm">Sign In</a>
    </div>
</nav>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width:500px;">
        <div class="auth-logo">
            <div class="brand-icon"><i class="fas fa-award"></i></div>
            <h2>Create Account</h2>
            <p>Start earning with <?php echo SITE_NAME; ?> today</p>
        </div>
        <?php if (!empty($errors)): ?>
        <div class="flash-alert flash-danger">
            <i class="fas fa-times-circle"></i>
            <div><?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?></div>
        </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Jane Wanjiku" value="<?php echo sanitize($_POST['full_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="0712345678" value="<?php echo sanitize($_POST['phone'] ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?php echo sanitize($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Referral Code <span style="color:#94a3b8;font-weight:400;">(Optional)</span></label>
                <input type="text" name="ref_code" class="form-control" placeholder="Enter referral code" value="<?php echo sanitize($_POST['ref_code'] ?? $refCode); ?>" style="text-transform:uppercase;" maxlength="20">
                <div class="form-text"><i class="fas fa-gift" style="color:#10b981;"></i> Your referrer earns KES <?php echo getSetting('referral_bonus'); ?> when you join!</div>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:4px;">
                <i class="fas fa-user-plus"></i> Create My Account
            </button>
        </form>
        <div class="auth-footer">Already have an account? <a href="<?php echo SITE_URL; ?>/login.php" style="font-weight:700;">Sign In</a></div>
    </div>
</div>
<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
<script>
    document.querySelector('[name="ref_code"]').addEventListener('input', function(){
        this.value = this.value.toUpperCase();
    });
</script>
</body>
</html>

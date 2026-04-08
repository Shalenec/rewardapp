<?php
// includes/header.php
$flash = getFlash();
$user = getCurrentUser();
$unread = $user ? getUnreadNotifications($user['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? sanitize($pageTitle) . ' | ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>

<!-- Top Nav -->
<nav class="navbar">
    <div class="navbar-brand">
        <a href="<?php echo SITE_URL; ?>/dashboard.php">
            <span class="brand-icon"><i class="fas fa-award"></i></span>
            <span class="brand-text"><?php echo SITE_NAME; ?></span>
        </a>
    </div>
    <div class="navbar-menu" id="navMenu">
        <?php if (isLoggedIn()): ?>
        <a href="<?php echo SITE_URL; ?>/dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="<?php echo SITE_URL; ?>/ads.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ads.php' ? 'active' : ''; ?>"><i class="fas fa-play-circle"></i> Watch Ads</a>
        <a href="<?php echo SITE_URL; ?>/invest.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'invest.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Invest</a>
        <a href="<?php echo SITE_URL; ?>/referrals.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'referrals.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Referrals</a>
        <a href="<?php echo SITE_URL; ?>/wallet.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'wallet.php' ? 'active' : ''; ?>"><i class="fas fa-wallet"></i> Wallet</a>
        <?php if (isAdmin()): ?>
        <a href="<?php echo SITE_URL; ?>/admin/" class="nav-link admin-link"><i class="fas fa-cog"></i> Admin</a>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="navbar-right">
        <?php if (isLoggedIn()): ?>
        <a href="<?php echo SITE_URL; ?>/notifications.php" class="notif-btn">
            <i class="fas fa-bell"></i>
            <?php if ($unread > 0): ?><span class="badge"><?php echo $unread; ?></span><?php endif; ?>
        </a>
        <div class="user-dropdown">
            <button class="user-btn" id="userDropBtn">
                <span class="avatar-circle"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></span>
                <span class="user-name-short"><?php echo explode(' ', $user['full_name'])[0]; ?></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="dropdown-menu" id="userDropMenu">
                <div class="drop-header">
                    <strong><?php echo sanitize($user['full_name']); ?></strong>
                    <small><?php echo sanitize($user['email']); ?></small>
                </div>
                <a href="<?php echo SITE_URL; ?>/profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="<?php echo SITE_URL; ?>/wallet.php"><i class="fas fa-wallet"></i> Wallet: <?php echo formatKES($user['wallet_balance']); ?></a>
                <div class="drop-divider"></div>
                <a href="<?php echo SITE_URL; ?>/logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        <?php else: ?>
        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline-sm">Login</a>
        <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary-sm">Sign Up</a>
        <?php endif; ?>
        <button class="hamburger" id="hamburger"><i class="fas fa-bars"></i></button>
    </div>
</nav>

<div class="main-content">
<?php if ($flash): ?>
<div class="flash-alert flash-<?php echo $flash['type']; ?>" id="flashAlert">
    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'times-circle' : 'info-circle'); ?>"></i>
    <?php echo sanitize($flash['msg']); ?>
    <button class="flash-close" onclick="document.getElementById('flashAlert').remove()"><i class="fas fa-times"></i></button>
</div>
<?php endif; ?>

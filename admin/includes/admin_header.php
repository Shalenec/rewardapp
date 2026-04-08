<?php
// admin/includes/admin_header.php
$flash = getFlash();
$adminUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? sanitize($pageTitle) . ' | Admin – ' . SITE_NAME : 'Admin – ' . SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
</head>
<body class="admin-body">

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-brand">
            <span class="brand-icon"><i class="fas fa-award"></i></span>
            <div>
                <div class="brand-text"><?php echo SITE_NAME; ?></div>
                <div style="font-size:0.7rem;opacity:.6;font-weight:500;">Admin Panel</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label">Overview</div>
            <a href="<?php echo SITE_URL; ?>/admin/" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='index.php'?'active':''; ?>"><i class="fas fa-th-large"></i> Dashboard</a>

            <div class="nav-section-label">Financial</div>
            <?php
            $pDep = (int)(getDB()->query("SELECT COUNT(*) as c FROM deposits WHERE status='pending'")->fetch()['c']);
            $pWith = (int)(getDB()->query("SELECT COUNT(*) as c FROM withdrawals WHERE status='pending'")->fetch()['c']);
            ?>
            <a href="<?php echo SITE_URL; ?>/admin/deposits.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='deposits.php'?'active':''; ?>">
                <i class="fas fa-arrow-down"></i> Deposits
                <?php if ($pDep): ?><span class="sidebar-badge"><?php echo $pDep; ?></span><?php endif; ?>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/withdrawals.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='withdrawals.php'?'active':''; ?>">
                <i class="fas fa-arrow-up"></i> Withdrawals
                <?php if ($pWith): ?><span class="sidebar-badge sidebar-badge-red"><?php echo $pWith; ?></span><?php endif; ?>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/investments.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='investments.php'?'active':''; ?>"><i class="fas fa-seedling"></i> Investments</a>
            <a href="<?php echo SITE_URL; ?>/admin/packages.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='packages.php'?'active':''; ?>"><i class="fas fa-box"></i> Packages</a>

            <div class="nav-section-label">Users & Rewards</div>
            <a href="<?php echo SITE_URL; ?>/admin/users.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='users.php'?'active':''; ?>"><i class="fas fa-users"></i> Users</a>
            <a href="<?php echo SITE_URL; ?>/admin/referrals.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='referrals.php'?'active':''; ?>"><i class="fas fa-share-alt"></i> Referrals</a>
            <a href="<?php echo SITE_URL; ?>/admin/ads.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='ads.php'?'active':''; ?>"><i class="fas fa-play-circle"></i> Manage Ads</a>

            <div class="nav-section-label">System</div>
            <a href="<?php echo SITE_URL; ?>/admin/settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF'])=='settings.php'?'active':''; ?>"><i class="fas fa-cog"></i> Settings</a>
            <a href="<?php echo SITE_URL; ?>/dashboard.php" class="sidebar-link"><i class="fas fa-external-link-alt"></i> User Area</a>
            <a href="<?php echo SITE_URL; ?>/logout.php" class="sidebar-link" style="color:#ef4444;" data-confirm="Logout?"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main -->
    <div class="admin-main">
        <!-- Admin Top Bar -->
        <div class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <div style="font-size:0.85rem;color:var(--gray);font-weight:500;"><?php echo isset($pageTitle) ? sanitize($pageTitle) : 'Admin'; ?></div>
            <div style="margin-left:auto;display:flex;align-items:center;gap:12px;">
                <span style="font-size:0.85rem;color:var(--gray);">Hello, <strong><?php echo sanitize(explode(' ', $adminUser['full_name'])[0]); ?></strong></span>
                <div style="width:32px;height:32px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.8rem;"><?php echo strtoupper(substr($adminUser['full_name'],0,1)); ?></div>
            </div>
        </div>

        <div class="admin-content">
        <?php if ($flash): ?>
        <div class="flash-alert flash-<?php echo $flash['type']; ?>" id="flashAlert">
            <i class="fas fa-<?php echo $flash['type']==='success'?'check-circle':($flash['type']==='danger'?'times-circle':'info-circle'); ?>"></i>
            <?php echo sanitize($flash['msg']); ?>
            <button class="flash-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        </div>
        <?php endif; ?>

<?php // includes/footer.php ?>
</div><!-- end main-content -->

<!-- Floating Action Buttons -->
<?php if (isLoggedIn()): ?>
<div class="fab-container">
    <button class="fab-main" id="fabMain" title="Quick Actions">
        <i class="fas fa-plus"></i>
    </button>
    <div class="fab-options" id="fabOptions">
        <a href="<?php echo SITE_URL; ?>/wallet.php?tab=deposit" class="fab-item" title="Deposit">
            <span class="fab-label">Deposit</span>
            <button class="fab-btn fab-green"><i class="fas fa-plus-circle"></i></button>
        </a>
        <a href="<?php echo SITE_URL; ?>/wallet.php?tab=withdraw" class="fab-item" title="Withdraw">
            <span class="fab-label">Withdraw</span>
            <button class="fab-btn fab-orange"><i class="fas fa-minus-circle"></i></button>
        </a>
        <a href="<?php echo SITE_URL; ?>/ads.php" class="fab-item" title="Watch Ad">
            <span class="fab-label">Watch Ad</span>
            <button class="fab-btn fab-blue"><i class="fas fa-play"></i></button>
        </a>
        <a href="<?php echo SITE_URL; ?>/referrals.php" class="fab-item" title="Refer Friend">
            <span class="fab-label">Refer</span>
            <button class="fab-btn fab-purple"><i class="fas fa-share-alt"></i></button>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Footer -->
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <span class="brand-icon-sm"><i class="fas fa-award"></i></span>
            <strong><?php echo SITE_NAME; ?></strong>
        </div>
        <p class="footer-copy">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved. | Kenya</p>
        <div class="footer-links">
            <a href="#">Terms</a>
            <a href="#">Privacy</a>
            <a href="#">Support</a>
        </div>
    </div>
</footer>

<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>

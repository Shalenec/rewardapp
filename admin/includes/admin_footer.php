        </div><!-- end admin-content -->
    </div><!-- end admin-main -->
</div><!-- end admin-layout -->

<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }
});
</script>
</body>
</html>

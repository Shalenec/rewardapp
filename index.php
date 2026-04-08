<?php
require_once 'includes/config.php';
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/dashboard.php');
} else {
    header('Location: ' . SITE_URL . '/login.php');
}
exit;

<?php
require_once 'includes/config.php';
$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE email = 'admin@rewardke.com'");
$stmt->execute();
$user = $stmt->fetch();
if ($user) {
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['is_admin'] = 1;
    echo "✅ Logged in! <a href='/admin/'>Click here to go to Admin Panel</a>";
} else {
    echo "❌ Admin user not found";
}

<?php
require_once 'includes/config.php';

echo "<pre>";
echo "Logged in: "    . (isLoggedIn() ? '✅ YES' : '❌ NO') . "\n";
echo "Is admin: "     . (isAdmin()    ? '✅ YES' : '❌ NO') . "\n";
echo "User ID: "      . ($_SESSION['user_id']  ?? 'NOT SET') . "\n";
echo "Session admin: ". ($_SESSION['is_admin'] ?? 'NOT SET') . "\n";

if (isLoggedIn()) {
    $db   = getDB();
    $stmt = $db->prepare("SELECT id, email, is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    echo "DB is_admin: " . $u['is_admin'] . "\n";
    echo "Email: "       . $u['email']    . "\n";
}
echo "</pre>";

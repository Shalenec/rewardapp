<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
$db = getDB();

echo "<pre>";

// Check all tables exist
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables: \n";
foreach ($tables as $t) echo "  ✅ $t\n";

// Check settings
echo "\nSettings:\n";
$settings = $db->query("SELECT * FROM settings")->fetchAll();
foreach ($settings as $s) echo "  - {$s['setting_key']} = {$s['setting_value']}\n";

// Check admin user
echo "\nAdmin user:\n";
$admin = $db->query("SELECT id, full_name, email, is_admin FROM users")->fetchAll();
foreach ($admin as $u) echo "  - {$u['email']} (admin={$u['is_admin']})\n";

echo "</pre>";

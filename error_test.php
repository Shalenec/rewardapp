<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
$db = getDB();

$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

echo "<pre>";
if (empty($tables)) {
    echo "❌ NO TABLES FOUND — Database is empty!";
} else {
    echo "✅ Tables found:\n";
    foreach ($tables as $t) echo "  - $t\n";
}
echo "</pre>";

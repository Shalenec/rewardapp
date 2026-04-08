<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/config.php';

echo "<pre>";

// 1. Check DB & tables
$db = getDB();
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "✅ Tables: " . implode(', ', $tables) . "\n\n";

// 2. Check settings
echo "min_deposit: " . getSetting('min_deposit') . "\n\n";

// 3. Check invest.php specific tables
echo "--- invest.php table check ---\n";
$needed = ['packages', 'investments'];
foreach ($needed as $table) {
    try {
        $count = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "✅ $table: $count rows\n";
    } catch(Exception $e) {
        echo "❌ $table: " . $e->getMessage() . "\n";
    }
}

// 4. Check packages columns
echo "\n--- packages columns ---\n";
$cols = $db->query("DESCRIBE packages")->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ', $cols) . "\n";

// 5. Check investments columns
echo "\n--- investments columns ---\n";
try {
    $cols = $db->query("DESCRIBE investments")->fetchAll(PDO::FETCH_COLUMN);
    echo implode(', ', $cols) . "\n";
} catch(Exception $e) {
    echo "❌ " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

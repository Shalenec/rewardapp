// Check invest.php specific tables
echo "\n--- invest.php table check ---\n";
$needed = ['packages', 'investments'];
foreach ($needed as $table) {
    try {
        $count = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "✅ $table: $count rows\n";
    } catch(Exception $e) {
        echo "❌ $table: " . $e->getMessage() . "\n";
    }
}

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../includes/config.php';

echo "<pre>";

$db = getDB();

// Check withdrawals table
echo "--- withdrawals columns ---\n";
$cols = $db->query("DESCRIBE withdrawals")->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ', $cols) . "\n\n";

// Check a pending withdrawal exists
$w = $db->query("SELECT * FROM withdrawals WHERE status = 'pending' LIMIT 1")->fetch();
echo "Pending withdrawal: ";
print_r($w);

// Simulate approval
if ($w) {
    try {
        $db->prepare("UPDATE withdrawals SET status = 'approved', processed_by = 1, processed_at = NOW() WHERE id = ?")
           ->execute([$w['id']]);
        echo "✅ Approval update worked\n";

        // Update user total_withdrawn
        $db->prepare("UPDATE users SET total_withdrawn = total_withdrawn + ? WHERE id = ?")
           ->execute([$w['amount'], $w['user_id']]);
        echo "✅ total_withdrawn updated\n";

        addNotification($w['user_id'], 'Withdrawal Approved', 'Your withdrawal of ' . formatKES($w['amount']) . ' has been approved.', 'success');
        echo "✅ Notification sent\n";

    } catch(Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️ No pending withdrawals found to test\n";
}

echo "</pre>";
?>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/config.php';

echo "<pre>";

$db = getDB();
$user_id = 1;
$amount = 500;
$phone = '0700000000';

try {
    // Step 1: check withdrawals columns
    echo "--- withdrawals columns ---\n";
    $cols = $db->query("DESCRIBE withdrawals")->fetchAll(PDO::FETCH_COLUMN);
    echo implode(', ', $cols) . "\n\n";

    // Step 2: insert withdrawal
    $db->prepare("INSERT INTO withdrawals (user_id, amount, phone_number) VALUES (?,?,?)")
       ->execute([$user_id, $amount, $phone]);
    echo "✅ Withdrawal inserted\n";

    // Step 3: deduct balance
    $db->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?")
       ->execute([$amount, $user_id]);
    echo "✅ Balance deducted\n";

    // Step 4: get new balance
    $balStmt = $db->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $balStmt->execute([$user_id]);
    $newBal = $balStmt->fetch()['wallet_balance'];
    echo "✅ New balance: $newBal\n";

    // Step 5: add transaction
    addTransaction($user_id, 'withdrawal', $amount, $newBal, 'Withdrawal request to M-Pesa ' . $phone);
    echo "✅ Transaction logged\n";

    // Step 6: notification
    addNotification($user_id, 'Withdrawal Requested', 'Your withdrawal of ' . formatKES($amount) . ' is pending.', 'info');
    echo "✅ Notification added\n";

    echo "\n✅ ALL STEPS PASSED!\n";

} catch(Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

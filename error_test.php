<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/config.php';

echo "<pre>";

$db = getDB();
$user_id = 2; // change to a real user id who has a deposit

// Simulate exact withdrawal validation flow
try {
    // Check deposits
    $depCheck = $db->prepare("SELECT COALESCE(SUM(amount),0) as total FROM deposits WHERE user_id = ? AND status = 'approved'");
    $depCheck->execute([$user_id]);
    $totalDeposited = (float)$depCheck->fetch()['total'];
    echo "✅ Total deposited: $totalDeposited\n";

    // Check wallet balance
    $userStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $userStmt->execute([$user_id]);
    $user = $userStmt->fetch();
    echo "✅ Wallet balance: " . $user['wallet_balance'] . "\n";
    echo "✅ Total withdrawn: " . $user['total_withdrawn'] . "\n";

    // Simulate withdrawal insert
    $amount = 500;
    $phone = '0700000000';
    $db->prepare("INSERT INTO withdrawals (user_id, amount, phone_number) VALUES (?,?,?)")
       ->execute([$user_id, $amount, $phone]);
    echo "✅ Withdrawal inserted\n";

    // Deduct balance
    $db->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?")
       ->execute([$amount, $user_id]);
    echo "✅ Balance deducted\n";

    // Get new balance
    $balStmt = $db->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $balStmt->execute([$user_id]);
    $newBal = $balStmt->fetch()['wallet_balance'];
    echo "✅ New balance: $newBal\n";

    // Add transaction
    addTransaction($user_id, 'withdrawal', $amount, $newBal, 'Withdrawal request to M-Pesa ' . $phone);
    echo "✅ Transaction logged\n";

    // Add notification
    addNotification($user_id, 'Withdrawal Requested', 'Your withdrawal of ' . formatKES($amount) . ' is pending.', 'info');
    echo "✅ Notification added\n";

    echo "\n✅ ALL STEPS PASSED!\n";

} catch(Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

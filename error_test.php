<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/config.php';

echo "<pre>";

$db = getDB();
$user_id = 1; // admin user
$pkgId = 1;
$amount = 500;

// Simulate exact invest.php flow
try {
    // Step 1: fetch package
    $pkgStmt = $db->prepare("SELECT * FROM packages WHERE id = ? AND is_active = 1 LIMIT 1");
    $pkgStmt->execute([$pkgId]);
    $pkg = $pkgStmt->fetch();
    echo "✅ Package fetched: " . $pkg['name'] . "\n";

    // Step 2: calculate
    $dailyReturn = $amount * ($pkg['daily_return_percent'] / 100);
    $totalReturn = $dailyReturn * $pkg['duration_days'];
    $startDate   = date('Y-m-d');
    $endDate     = date('Y-m-d', strtotime('+' . $pkg['duration_days'] . ' days'));
    echo "✅ Daily: $dailyReturn | Total: $totalReturn | End: $endDate\n";

    // Step 3: begin transaction
    $db->beginTransaction();
    echo "✅ Transaction started\n";

    // Step 4: insert investment
    $db->prepare("INSERT INTO investments (user_id, package_id, amount, daily_return, total_return, start_date, end_date) VALUES (?,?,?,?,?,?,?)")
       ->execute([$user_id, $pkgId, $amount, $dailyReturn, $totalReturn, $startDate, $endDate]);
    $invId = $db->lastInsertId();
    echo "✅ Investment inserted: ID $invId\n";

    // Step 5: deduct balance
    $db->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?")
       ->execute([$amount, $user_id]);
    echo "✅ Balance deducted\n";

    // Step 6: get new balance
    $balStmt = $db->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $balStmt->execute([$user_id]);
    $newBal = $balStmt->fetch()['wallet_balance'];
    echo "✅ New balance: $newBal\n";

    // Step 7: add transaction
    addTransaction($user_id, 'investment', $amount, $newBal, 'Invested in ' . $pkg['name'] . ' Package', $invId);
    echo "✅ Transaction logged\n";

    // Step 8: add notification
    addNotification($user_id, 'Investment Active!', 'Your investment of ' . formatKES($amount) . ' in ' . $pkg['name'] . ' Package is now active.', 'success');
    echo "✅ Notification added\n";

    // Step 9: commit
    $db->commit();
    echo "✅ Committed! All good!\n";

    // Cleanup test data
    $db->prepare("DELETE FROM investments WHERE id = ?")->execute([$invId]);
    echo "✅ Test data cleaned up\n";

} catch(Exception $e) {
    $db->rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

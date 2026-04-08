<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$adId = (int)($_POST['ad_id'] ?? 0);
$userId = $_SESSION['user_id'];
$db = getDB();

// Check ad exists and is active
$adStmt = $db->prepare("SELECT * FROM ads WHERE id = ? AND is_active = 1 LIMIT 1");
$adStmt->execute([$adId]);
$ad = $adStmt->fetch();

if (!$ad) {
    echo json_encode(['success' => false, 'message' => 'Ad not found.']);
    exit;
}

// Check if already watched today
$watchedStmt = $db->prepare("SELECT id FROM ad_views WHERE user_id = ? AND ad_id = ? AND DATE(watched_at) = CURDATE() LIMIT 1");
$watchedStmt->execute([$userId, $adId]);
if ($watchedStmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You already claimed this ad reward today.']);
    exit;
}

// Check daily limit
$maxAds = (int)getSetting('max_daily_ads');
$todayStmt = $db->prepare("SELECT COUNT(*) as cnt FROM ad_views WHERE user_id = ? AND DATE(watched_at) = CURDATE()");
$todayStmt->execute([$userId]);
$todayCount = (int)$todayStmt->fetch()['cnt'];

if ($todayCount >= $maxAds) {
    echo json_encode(['success' => false, 'message' => 'You have reached today\'s ad limit. Come back tomorrow!']);
    exit;
}

$reward = (float)$ad['reward_amount'];

try {
    $db->beginTransaction();

    // Record view
    $db->prepare("INSERT INTO ad_views (user_id, ad_id, reward_amount) VALUES (?,?,?)")->execute([$userId, $adId, $reward]);

    // Credit wallet
    $db->prepare("UPDATE users SET wallet_balance = wallet_balance + ?, total_earned = total_earned + ? WHERE id = ?")->execute([$reward, $reward, $userId]);

    // Update ad view count
    $db->prepare("UPDATE ads SET views_count = views_count + 1 WHERE id = ?")->execute([$adId]);

    // Get new balance
    $balStmt = $db->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $balStmt->execute([$userId]);
    $newBal = $balStmt->fetch()['wallet_balance'];

    addTransaction($userId, 'ad_reward', $reward, $newBal, 'Ad reward: ' . $ad['title'] . ' by ' . $ad['sponsor'], $adId);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Reward credited!', 'reward' => $reward, 'balance' => $newBal]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error processing reward. Please try again.']);
}

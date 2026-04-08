<?php
// includes/config.php

// Railway injects these env vars automatically when you add a MySQL service
// Falls back to XAMPP defaults for local development
define('DB_HOST',    getenv('MYSQLHOST')     ?: 'localhost');
define('DB_USER',    getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS',    getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME',    getenv('MYSQLDATABASE') ?: 'rewardapp');
define('DB_PORT',    getenv('MYSQLPORT')     ?: '3306');
define('DB_CHARSET', 'utf8mb4');

// Auto-detect URL: Railway provides RAILWAY_PUBLIC_DOMAIN in production
if (getenv('RAILWAY_PUBLIC_DOMAIN')) {
    define('SITE_URL', 'https://' . getenv('RAILWAY_PUBLIC_DOMAIN'));
} else {
    define('SITE_URL', 'http://localhost/rewardapp');
}
define('SITE_NAME', 'RewardKe');
define('CURRENCY', 'KES');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto error reporting: verbose locally, silent on Railway
if (getenv('RAILWAY_PUBLIC_DOMAIN')) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:Arial;padding:20px;background:#fee;border:1px solid #f00;margin:20px;border-radius:8px;"><strong>Database Error:</strong> ' . htmlspecialchars($e->getMessage()) . '<br><small>Check your database settings in includes/config.php</small></div>');
        }
    }
    return $pdo;
}

function getSetting($key) {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : null;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/dashboard.php');
        exit;
    }
}

function formatKES($amount) {
    return 'KES ' . number_format((float)$amount, 2);
}

function generateReferralCode($length = 8) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function addTransaction($userId, $type, $amount, $balanceAfter, $description, $refId = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, balance_after, description, reference_id) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$userId, $type, $amount, $balanceAfter, $description, $refId]);
}

function addNotification($userId, $title, $message, $type = 'info') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)");
    $stmt->execute([$userId, $title, $message, $type]);
}

function getUnreadNotifications($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return (int)$row['cnt'];
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function redirect($url, $msg = '', $type = 'success') {
    if ($msg) {
        $_SESSION['flash_msg'] = $msg;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . $url);
    exit;
}

function getFlash() {
    if (isset($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        return ['msg' => $msg, 'type' => $type];
    }
    return null;
}

// Process daily investment returns — runs ONCE per day only
function processDailyReturns() {
    $db    = getDB();
    $today = date('Y-m-d');

    // Fast PHP-session check first (avoids DB hit on every page load)
    if (isset($_SESSION['returns_processed_date']) && $_SESSION['returns_processed_date'] === $today) {
        return;
    }

    // Double-check against DB using INSERT ... ON DUPLICATE KEY
    // This atomically ensures only one process wins even under concurrent requests
    try {
        $inserted = $db->prepare(
            "INSERT INTO settings (setting_key, setting_value)
             VALUES ('last_returns_run', ?)
             ON DUPLICATE KEY UPDATE
               setting_value = IF(setting_value = ?, setting_value, VALUES(setting_value))"
        );
        // Only update the row if the stored value is NOT already today
        $db->prepare(
            "INSERT INTO settings (setting_key, setting_value) VALUES ('last_returns_run', '1970-01-01')
             ON DUPLICATE KEY UPDATE setting_value = setting_value"
        )->execute(); // ensure row exists

        $lastRun = getSetting('last_returns_run');
        if ($lastRun === $today) {
            $_SESSION['returns_processed_date'] = $today;
            return;
        }

        // Mark as running FIRST before processing (prevents double-run)
        $db->prepare(
            "UPDATE settings SET setting_value = ? WHERE setting_key = 'last_returns_run'"
        )->execute([$today]);

    } catch (Exception $e) {
        return; // Fail silently — try again next load
    }

    // Mark in session so we skip DB check for rest of this session today
    $_SESSION['returns_processed_date'] = $today;

    // Fetch all active investments due for a return today
    // Only investments that started BEFORE today (not same-day investments)
    $stmt = $db->prepare(
        "SELECT * FROM investments
         WHERE status = 'active'
           AND start_date < ?
           AND end_date >= ?"
    );
    $stmt->execute([$today, $today]);
    $investments = $stmt->fetchAll();

    foreach ($investments as $inv) {
        $dailyReturn = (float)$inv['daily_return'];
        $newEarned   = (float)$inv['earned_so_far'] + $dailyReturn;

        // Cap earned at total_return
        if ($newEarned > (float)$inv['total_return']) {
            $dailyReturn = (float)$inv['total_return'] - (float)$inv['earned_so_far'];
            $newEarned   = (float)$inv['total_return'];
        }

        if ($dailyReturn <= 0) continue;

        // Credit to user wallet
        $db->prepare(
            "UPDATE users SET wallet_balance = wallet_balance + ?, total_earned = total_earned + ? WHERE id = ?"
        )->execute([$dailyReturn, $dailyReturn, $inv['user_id']]);

        // Get updated balance for transaction log
        $userStmt = $db->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $userStmt->execute([$inv['user_id']]);
        $user = $userStmt->fetch();

        // Mark completed if fully paid out
        $status = ($newEarned >= (float)$inv['total_return']) ? 'completed' : 'active';
        $db->prepare(
            "UPDATE investments SET earned_so_far = ?, status = ? WHERE id = ?"
        )->execute([$newEarned, $status, $inv['id']]);

        addTransaction(
            $inv['user_id'], 'return', $dailyReturn,
            $user['wallet_balance'],
            'Daily return from investment #' . $inv['id'],
            $inv['id']
        );

        if ($status === 'completed') {
            addNotification(
                $inv['user_id'],
                'Investment Completed!',
                'Your investment of ' . formatKES($inv['amount']) . ' has completed. Total earned: ' . formatKES($inv['total_return']),
                'success'
            );
        }
    }
}

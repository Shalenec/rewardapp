<?php
require_once 'includes/config.php';
$db = getDB();

$tables = [

"CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    referral_code VARCHAR(20) UNIQUE NOT NULL,
    referred_by INT NULL,
    wallet_balance DECIMAL(10,2) DEFAULT 0.00,
    total_earned DECIMAL(10,2) DEFAULT 0.00,
    is_admin TINYINT(1) DEFAULT 0,
    status ENUM('active','suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL
)",

"CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)",

"CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('deposit','withdrawal','referral','return','bonus') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,
    description TEXT,
    reference_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)",

"CREATE TABLE IF NOT EXISTS investments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    daily_return DECIMAL(10,2) NOT NULL,
    total_return DECIMAL(10,2) NOT NULL,
    earned_so_far DECIMAL(10,2) DEFAULT 0.00,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active','completed','cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)",

"CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info','success','warning','error') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)",

"CREATE TABLE IF NOT EXISTS referral_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    referred_id INT NOT NULL,
    reward_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE
)",

"CREATE TABLE IF NOT EXISTS investment_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    min_amount DECIMAL(10,2) NOT NULL,
    max_amount DECIMAL(10,2) NOT NULL,
    daily_return_percent DECIMAL(5,2) NOT NULL,
    duration_days INT NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",

"CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)"

];

echo "<pre style='font-family:monospace;padding:20px'>";

$success = true;
foreach ($tables as $sql) {
    preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $sql, $m);
    $name = $m[1] ?? 'unknown';
    try {
        $db->exec($sql);
        echo "✅ Table '$name' created\n";
    } catch (PDOException $e) {
        echo "❌ Table '$name' failed: " . $e->getMessage() . "\n";
        $success = false;
    }
}

// Insert default settings
$defaults = [
    ['referral_bonus',        '100'],
    ['min_withdrawal',        '500'],
    ['max_withdrawal',        '50000'],
    ['withdrawal_fee',        '0'],
    ['min_deposit',           '500'],
    ['last_returns_run',      '1970-01-01'],
    ['site_name',             'RewardKe'],
    ['maintenance_mode',      '0'],
];

echo "\n--- Default Settings ---\n";
$ins = $db->prepare(
    "INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)"
);
foreach ($defaults as [$key, $val]) {
    $ins->execute([$key, $val]);
    echo "✅ Setting '$key' = '$val'\n";
}

// Create default admin account
echo "\n--- Admin Account ---\n";
try {
    $adminCheck = $db->prepare("SELECT id FROM users WHERE email = 'admin@rewardke.com'");
    $adminCheck->execute();
    if (!$adminCheck->fetch()) {
        $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
        $db->prepare(
            "INSERT INTO users (full_name, email, phone, password, referral_code, is_admin)
             VALUES (?, ?, ?, ?, ?, 1)"
        )->execute(['Admin', 'admin@rewardke.com', '0700000000', $adminHash, 'ADMIN0001']);
        echo "✅ Admin created — email: admin@rewardke.com / pass: admin123\n";
    } else {
        echo "ℹ️ Admin already exists\n";
    }
} catch (PDOException $e) {
    echo "❌ Admin failed: " . $e->getMessage() . "\n";
}

echo "\n" . ($success ? "🎉 ALL DONE! Database is ready." : "⚠️ Some tables failed — check errors above.");
echo "\n\n⚠️  DELETE setup.php NOW for security!";
echo "</pre>";

-- RewardApp Kenya Database Schema
-- Import this file in phpMyAdmin

CREATE DATABASE IF NOT EXISTS rewardapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rewardapp;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    referral_code VARCHAR(20) UNIQUE NOT NULL,
    referred_by INT NULL,
    wallet_balance DECIMAL(12,2) DEFAULT 0.00,
    total_earned DECIMAL(12,2) DEFAULT 0.00,
    total_withdrawn DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('active','suspended','pending') DEFAULT 'active',
    is_admin TINYINT(1) DEFAULT 0,
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Investment packages
CREATE TABLE IF NOT EXISTS packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    min_amount DECIMAL(12,2) NOT NULL,
    max_amount DECIMAL(12,2) NOT NULL,
    daily_return_percent DECIMAL(5,2) NOT NULL,
    duration_days INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- User investments
CREATE TABLE IF NOT EXISTS investments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    daily_return DECIMAL(12,2) NOT NULL,
    total_return DECIMAL(12,2) NOT NULL,
    earned_so_far DECIMAL(12,2) DEFAULT 0.00,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active','completed','cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Deposits
CREATE TABLE IF NOT EXISTS deposits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    method VARCHAR(50) DEFAULT 'M-Pesa',
    transaction_id VARCHAR(100) NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    notes TEXT NULL,
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Withdrawals
CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    method VARCHAR(50) DEFAULT 'M-Pesa',
    phone_number VARCHAR(20) NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    notes TEXT NULL,
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Referral rewards
CREATE TABLE IF NOT EXISTS referral_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    referred_id INT NOT NULL,
    reward_amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending','credited') DEFAULT 'credited',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Ads
CREATE TABLE IF NOT EXISTS ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    sponsor VARCHAR(100) NOT NULL,
    video_url VARCHAR(500) NOT NULL,
    thumbnail VARCHAR(255) NULL,
    reward_amount DECIMAL(8,2) DEFAULT 5.00,
    duration_seconds INT DEFAULT 30,
    is_active TINYINT(1) DEFAULT 1,
    views_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Ad views (tracks who watched what)
CREATE TABLE IF NOT EXISTS ad_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ad_id INT NOT NULL,
    reward_amount DECIMAL(8,2) NOT NULL,
    watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Transactions log
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('deposit','withdrawal','investment','return','referral','ad_reward') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    balance_after DECIMAL(12,2) NOT NULL,
    description VARCHAR(255),
    reference_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    type ENUM('info','success','warning','danger') DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Settings
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'RewardKe'),
('referral_bonus', '100'),
('min_deposit', '500'),
('min_withdrawal', '500'),
('max_daily_ads', '5'),
('mpesa_paybill', '247247'),
('mpesa_account', 'RewardKe'),
('withdrawal_fee_percent', '2'),
('currency', 'KES');

-- Seed investment packages
INSERT INTO packages (name, description, min_amount, max_amount, daily_return_percent, duration_days) VALUES
('Starter', 'Perfect for beginners. Low risk entry into passive income.', 500, 4999, 2.50, 30),
('Silver', 'Steady growth with moderate returns for serious investors.', 5000, 19999, 3.50, 45),
('Gold', 'High-performance package for committed investors.', 20000, 99999, 5.00, 60),
('Platinum', 'Premium package with maximum daily returns.', 100000, 999999, 7.50, 90);

-- Seed ads (Kenya brands)
INSERT INTO ads (title, description, sponsor, video_url, thumbnail, reward_amount, duration_seconds) VALUES
('Safaricom M-Pesa Lipa na M-Pesa', 'Pay bills, buy goods and services with M-Pesa', 'Safaricom', 'https://www.youtube.com/embed/dQw4w9WgXcQ', NULL, 5.00, 30),
('Airtel Money - Tuma Pesa Bila Shida', 'Send money instantly across Kenya with Airtel Money', 'Airtel Kenya', 'https://www.youtube.com/embed/dQw4w9WgXcQ', NULL, 5.00, 30),
('Naivas Supermarket - Fresh & Affordable', 'Shop fresh produce and groceries at Naivas near you', 'Naivas', 'https://www.youtube.com/embed/dQw4w9WgXcQ', NULL, 5.00, 30),
('Khetias Supermarket - Family Shopping', 'Your trusted family supermarket across Kenya', 'Khetias', 'https://www.youtube.com/embed/dQw4w9WgXcQ', NULL, 5.00, 30),
('QuickMart - Shop Smart, Save More', 'Discover amazing deals every day at QuickMart', 'QuickMart', 'https://www.youtube.com/embed/dQw4w9WgXcQ', NULL, 5.00, 30),
('Total Energies - Fueling Kenya Forward', 'Trusted fuel and lubricants for every Kenyan road', 'TotalEnergies', 'https://www.youtube.com/embed/dQw4w9WgXcQ', NULL, 5.00, 30),
('Shell Kenya - Go Well, Go Shell', 'Premium fuels and services at Shell stations nationwide', 'Shell Kenya', 'https://www.youtube.com/embed/dQw4w9WgXcQ', NULL, 5.00, 30);

-- Seed admin user (password: Admin@1234)
INSERT INTO users (full_name, email, phone, password, referral_code, wallet_balance, is_admin) VALUES
('System Admin', 'admin@rewardke.co.ke', '0700000000', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN001', 0.00, 1);

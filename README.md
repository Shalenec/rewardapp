# RewardKe – Installation Guide

## Requirements
- XAMPP (Apache + MySQL + PHP 7.4+)
- phpMyAdmin

---

## Step-by-Step Setup

### 1. Copy Files
Place the entire `rewardapp` folder into your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\rewardapp\
```

### 2. Create Database
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click **"New"** to create a database
3. Name it: `rewardapp`
4. Set collation: `utf8mb4_unicode_ci`
5. Click **Create**

### 3. Import SQL
1. Select the `rewardapp` database
2. Click the **"Import"** tab
3. Choose file → select `rewardapp/database.sql`
4. Click **Go** to import

### 4. Configure Database (if needed)
Edit `includes/config.php` if your MySQL settings differ:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password (blank for XAMPP default)
define('DB_NAME', 'rewardapp');
define('SITE_URL', 'http://localhost/rewardapp');
```

### 5. Launch
- Visit: http://localhost/rewardapp
- You'll be redirected to the login page

---

## Default Admin Login
| Field    | Value                     |
|----------|---------------------------|
| Email    | admin@rewardke.co.ke      |
| Password | Admin@1234                |

---

## Features
- ✅ User registration with referral code
- ✅ Referral rewards (KES 100 per referral)
- ✅ Watch 30-second ads from 7 Kenyan brands & earn KES 5 each
- ✅ Investment packages (Starter, Silver, Gold, Platinum)
- ✅ Daily investment returns (auto-credited)
- ✅ Wallet: deposit & withdraw via M-Pesa
- ✅ Full admin panel (approve/reject deposits & withdrawals)
- ✅ Admin: manage users, ads, packages, settings
- ✅ Notifications system
- ✅ Animated floating action button
- ✅ Fully responsive mobile-friendly

---

## Admin Panel
Access at: http://localhost/rewardapp/admin/

---

## Notes
- Ads use YouTube embeds. Replace the video URLs in the `ads` table with real brand videos.
- M-Pesa deposits are manually approved by admin (no Daraja API needed).
- Daily investment returns run once per day automatically on first page load.
- Change the admin password immediately after setup.

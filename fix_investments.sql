-- ============================================================
-- RewardKe - Investment Fix Script
-- Run this in phpMyAdmin SQL tab to fix over-credited data
-- ============================================================
USE rewardapp;

-- Step 1: Insert the last_returns_run setting if missing
INSERT INTO settings (setting_key, setting_value)
VALUES ('last_returns_run', '1970-01-01')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- Step 2: Cap earned_so_far so it never exceeds total_return
UPDATE investments
SET earned_so_far = total_return,
    status = 'completed'
WHERE earned_so_far > total_return;

-- Step 3: Fix investments that earned too many times today
-- (cap them back to max 1 day's worth per day since start)
UPDATE investments
SET earned_so_far = LEAST(
    earned_so_far,
    daily_return * GREATEST(DATEDIFF(CURDATE(), start_date), 0)
)
WHERE status IN ('active', 'completed');

-- Step 4: Recalculate user wallet balances from scratch
-- (only run this if wallets are clearly wrong)
-- WARNING: This resets wallets to 0 then rebuilds from transactions
-- Uncomment ONLY if needed:

-- UPDATE users SET wallet_balance = 0, total_earned = 0 WHERE is_admin = 0;
-- UPDATE users u
-- JOIN (
--     SELECT user_id,
--         SUM(CASE WHEN type IN ('deposit','return','referral','ad_reward') THEN amount ELSE 0 END) -
--         SUM(CASE WHEN type IN ('withdrawal','investment') THEN amount ELSE 0 END) AS net
--     FROM transactions GROUP BY user_id
-- ) t ON u.id = t.user_id
-- SET u.wallet_balance = t.net;

-- Step 5: Mark today as already processed so it won't run again today
UPDATE settings SET setting_value = CURDATE() WHERE setting_key = 'last_returns_run';

SELECT 'Fix applied successfully!' AS result;
SELECT setting_key, setting_value FROM settings WHERE setting_key = 'last_returns_run';

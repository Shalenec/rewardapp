<?php
require_once 'includes/config.php';
requireLogin();
$user = getCurrentUser();
$db = getDB();

// Handle investment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invest'])) {
    $pkgId  = (int)($_POST['package_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);

    $pkgStmt = $db->prepare("SELECT * FROM packages WHERE id = ? AND is_active = 1 LIMIT 1");
    $pkgStmt->execute([$pkgId]);
    $pkg = $pkgStmt->fetch();

    if (!$pkg) {
        redirect('invest.php', 'Invalid package selected.', 'danger');
    } elseif ($amount < $pkg['min_amount'] || $amount > $pkg['max_amount']) {
        redirect('invest.php', 'Amount must be between ' . formatKES($pkg['min_amount']) . ' and ' . formatKES($pkg['max_amount']) . '.', 'danger');
    } elseif ($user['wallet_balance'] < $amount) {
        redirect('invest.php', 'Insufficient wallet balance. Please deposit first.', 'danger');
    } else {
        $dailyReturn = $amount * ($pkg['daily_return_percent'] / 100);
        $totalReturn = $dailyReturn * $pkg['duration_days'];
        $startDate   = date('Y-m-d');
        $endDate     = date('Y-m-d', strtotime('+' . $pkg['duration_days'] . ' days'));

        $db->beginTransaction();
        $db->prepare("INSERT INTO investments (user_id, package_id, amount, daily_return, total_return, start_date, end_date) VALUES (?,?,?,?,?,?,?)")->execute([$user['id'], $pkgId, $amount, $dailyReturn, $totalReturn, $startDate, $endDate]);
        $invId = $db->lastInsertId();
        $db->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?")->execute([$amount, $user['id']]);
        $balStmt = $db->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $balStmt->execute([$user['id']]);
        $newBal = $balStmt->fetch()['wallet_balance'];
        addTransaction($user['id'], 'investment', $amount, $newBal, 'Invested in ' . $pkg['name'] . ' Package', $invId);
        addNotification($user['id'], 'Investment Active!', 'Your investment of ' . formatKES($amount) . ' in ' . $pkg['name'] . ' Package is now active. Daily returns: ' . formatKES($dailyReturn), 'success');
        $db->commit();
        redirect('invest.php', 'Investment of ' . formatKES($amount) . ' placed successfully! Daily returns start tomorrow.', 'success');
    }
}

// Get packages
$pkgs = $db->query("SELECT * FROM packages WHERE is_active = 1 ORDER BY min_amount ASC")->fetchAll();

// My investments
$myInvStmt = $db->prepare("SELECT i.*, p.name as pkg_name, p.daily_return_percent FROM investments i JOIN packages p ON i.package_id = p.id WHERE i.user_id = ? ORDER BY i.created_at DESC");
$myInvStmt->execute([$user['id']]);
$myInvestments = $myInvStmt->fetchAll();

$pageTitle = 'Investment Packages';
$pkgIcons = ['Starter' => '🌱', 'Silver' => '🥈', 'Gold' => '🥇', 'Platinum' => '💎'];
include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1><i class="fas fa-chart-line" style="color:var(--primary);"></i> Investment Packages</h1>
            <p>Grow your money with our high-yield investment packages</p>
        </div>
        <div style="background:white;border:1px solid var(--border);border-radius:8px;padding:10px 16px;font-size:0.85rem;">
            <span style="color:var(--gray);">Available Balance:</span>
            <strong style="color:var(--primary);margin-left:6px;"><?php echo formatKES($user['wallet_balance']); ?></strong>
        </div>
    </div>
</div>

<!-- Packages -->
<div class="packages-grid" style="margin-bottom:28px;">
<?php foreach ($pkgs as $i => $pkg): 
    $featured = $i === 1;
?>
<div class="package-card <?php echo $featured ? 'featured' : ''; ?>">
    <?php if ($featured): ?><div class="package-badge">Most Popular</div><?php endif; ?>
    <div class="package-icon"><?php echo $pkgIcons[$pkg['name']] ?? '📈'; ?></div>
    <div class="package-name"><?php echo sanitize($pkg['name']); ?> Package</div>
    <div class="package-return"><?php echo $pkg['daily_return_percent']; ?><span>% / day</span></div>
    <div class="package-range"><?php echo formatKES($pkg['min_amount']); ?> – <?php echo formatKES($pkg['max_amount']); ?></div>
    <div class="package-meta">
        <div class="package-meta-item"><i class="fas fa-calendar"></i> <?php echo $pkg['duration_days']; ?>-day package</div>
        <div class="package-meta-item"><i class="fas fa-clock"></i> Daily returns credited</div>
        <div class="package-meta-item"><i class="fas fa-shield-alt"></i> Capital protected</div>
        <div class="package-meta-item"><i class="fas fa-calculator"></i>
            Max total: <strong style="color:var(--primary);margin-left:4px;"><?php echo formatKES($pkg['max_amount'] * ($pkg['daily_return_percent']/100) * $pkg['duration_days']); ?></strong>
        </div>
    </div>
    <button class="btn btn-primary btn-block" onclick="document.getElementById('investSection').scrollIntoView({behavior:'smooth'}); document.getElementById('packageSelect').value=<?php echo $pkg['id']; ?>; document.getElementById('packageSelect').dispatchEvent(new Event('change'));">
        <i class="fas fa-seedling"></i> Invest Now
    </button>
</div>
<?php endforeach; ?>
</div>

<!-- Investment Form -->
<div class="grid-2" style="margin-bottom:28px;" id="investSection">
    <div class="card">
        <div class="card-title" style="margin-bottom:20px;"><i class="fas fa-plus-circle" style="color:var(--primary);"></i> Place Investment</div>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Select Package</label>
                <select name="package_id" id="packageSelect" class="form-control" required>
                    <?php foreach ($pkgs as $pkg): ?>
                    <option value="<?php echo $pkg['id']; ?>" data-rate="<?php echo $pkg['daily_return_percent']; ?>" data-days="<?php echo $pkg['duration_days']; ?>" data-min="<?php echo $pkg['min_amount']; ?>" data-max="<?php echo $pkg['max_amount']; ?>">
                        <?php echo sanitize($pkg['name']); ?> (<?php echo $pkg['daily_return_percent']; ?>%/day, <?php echo $pkg['duration_days']; ?> days)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Investment Amount (KES)</label>
                <div style="position:relative;">
                    <span class="input-prefix">KES</span>
                    <input type="number" name="amount" id="investAmount" class="form-control" style="padding-left:52px;" placeholder="Enter amount" step="1" min="1" required>
                </div>
                <div class="form-text" id="rangeHint">Min: KES <?php echo number_format($pkgs[0]['min_amount']); ?> | Max: KES <?php echo number_format($pkgs[0]['max_amount']); ?></div>
            </div>
            <button type="submit" name="invest" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-seedling"></i> Confirm Investment
            </button>
        </form>
    </div>
    <div class="card" style="background:var(--primary-xlight);border-color:#bfdbfe;">
        <div class="card-title" style="margin-bottom:16px;"><i class="fas fa-calculator" style="color:var(--primary);"></i> Return Calculator</div>
        <div style="display:flex;flex-direction:column;gap:14px;">
            <div style="background:white;border-radius:8px;padding:14px;border:1px solid var(--border);">
                <div style="font-size:0.78rem;color:var(--gray);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Daily Return</div>
                <div style="font-size:1.5rem;font-weight:800;color:var(--success);" id="calcDaily">KES 0.00</div>
            </div>
            <div style="background:white;border-radius:8px;padding:14px;border:1px solid var(--border);">
                <div style="font-size:0.78rem;color:var(--gray);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Total Returns</div>
                <div style="font-size:1.5rem;font-weight:800;color:var(--primary);" id="calcTotal">KES 0.00</div>
            </div>
            <div style="background:white;border-radius:8px;padding:14px;border:1px solid var(--border);">
                <div style="font-size:0.78rem;color:var(--gray);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Completion Date</div>
                <div style="font-size:1rem;font-weight:700;color:var(--dark);" id="calcEnd">—</div>
            </div>
        </div>
    </div>
</div>

<!-- My Investments -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-list" style="color:var(--gray);"></i> My Investments</div>
    </div>
    <?php if (empty($myInvestments)): ?>
    <div class="empty-state"><i class="fas fa-seedling"></i><p>No investments yet. Choose a package above to start earning!</p></div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Package</th><th>Amount</th><th>Daily Return</th><th>Earned</th><th>Total Return</th><th>End Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($myInvestments as $inv):
                $progress = $inv['total_return'] > 0 ? ($inv['earned_so_far'] / $inv['total_return']) * 100 : 0;
            ?>
            <tr>
                <td><strong><?php echo sanitize($inv['pkg_name']); ?></strong><br><span style="font-size:0.75rem;color:var(--gray);"><?php echo $inv['daily_return_percent']; ?>% / day</span></td>
                <td><?php echo formatKES($inv['amount']); ?></td>
                <td style="color:var(--success);font-weight:700;"><?php echo formatKES($inv['daily_return']); ?></td>
                <td>
                    <?php echo formatKES($inv['earned_so_far']); ?>
                    <div class="progress" style="height:4px;margin-top:4px;"><div class="progress-bar" style="width:<?php echo min($progress, 100); ?>%"></div></div>
                </td>
                <td><?php echo formatKES($inv['total_return']); ?></td>
                <td style="font-size:0.82rem;"><?php echo date('d M Y', strtotime($inv['end_date'])); ?></td>
                <td><span class="status-badge status-<?php echo $inv['status']; ?>"><?php echo ucfirst($inv['status']); ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('packageSelect').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const hint = document.getElementById('rangeHint');
    if (hint && opt) {
        const min = parseFloat(opt.dataset.min);
        const max = parseFloat(opt.dataset.max);
        hint.textContent = 'Min: KES ' + min.toLocaleString() + ' | Max: KES ' + max.toLocaleString();
    }
});
</script>
<?php include 'includes/footer.php'; ?>

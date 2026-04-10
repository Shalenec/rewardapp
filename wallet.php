<?php
require_once 'includes/config.php';
requireLogin();
$user = getCurrentUser();
$db = getDB();

// Handle Deposit Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit'])) {
    $amount  = (float)($_POST['amount'] ?? 0);
    $txId    = sanitize($_POST['transaction_id'] ?? '');
    $method  = sanitize($_POST['method'] ?? 'M-Pesa');
    $minDep  = (float)getSetting('min_deposit');

    // Check duplicate transaction ID
    $txCheck = $db->prepare("SELECT id FROM deposits WHERE transaction_id = ? AND transaction_id != '' LIMIT 1");
    $txCheck->execute([$txId]);
    $txExists = $txCheck->fetch();

    if ($amount < $minDep) {
        redirect('wallet.php?tab=deposit', 'Minimum deposit is ' . formatKES($minDep) . '.', 'danger');
    } elseif (!empty($txId) && $txExists) {
        redirect('wallet.php?tab=deposit', 'This transaction ID has already been used. Please check and try again.', 'danger');
    } else {
        $db->prepare("INSERT INTO deposits (user_id, amount, transaction_id, method) VALUES (?,?,?,?)")->execute([$user['id'], $amount, $txId, $method]);
        addNotification($user['id'], 'Deposit Request Received', 'Your deposit of ' . formatKES($amount) . ' via ' . $method . ' is pending approval. We will process it within 24 hours.', 'info');
        redirect('wallet.php', 'Deposit request submitted! Pending admin approval.', 'success');
    }
}

// Handle Withdrawal Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    $amount  = (float)($_POST['amount'] ?? 0);
    $phone   = sanitize($_POST['phone'] ?? '');
    $minWith = (float)getSetting('min_withdrawal');

    // Check total approved deposits
    $depCheck = $db->prepare("SELECT COALESCE(SUM(amount),0) as total FROM deposits WHERE user_id = ? AND status = 'approved'");
    $depCheck->execute([$user['id']]);
    $totalDeposited = (float)$depCheck->fetch()['total'];

    if ($totalDeposited < 500) {
        redirect('wallet.php?tab=withdraw', 'You must have at least one approved deposit of KES 500 or more before withdrawing.', 'danger');
    } elseif ($amount < $minWith) {
        redirect('wallet.php?tab=withdraw', 'Minimum withdrawal is ' . formatKES($minWith) . '.', 'danger');
    } elseif ($user['wallet_balance'] < $amount) {
        redirect('wallet.php?tab=withdraw', 'Insufficient wallet balance.', 'danger');
    } elseif (empty($phone)) {
        redirect('wallet.php?tab=withdraw', 'M-Pesa phone number is required.', 'danger');
    } else {
        $feePercent = (float)getSetting('withdrawal_fee_percent');
        $fee = $amount * ($feePercent / 100);
        $net = $amount - $fee;
        $db->prepare("INSERT INTO withdrawals (user_id, amount, phone_number) VALUES (?,?,?)")->execute([$user['id'], $amount, $phone]);
        $db->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?")->execute([$amount, $user['id']]);
        $balStmt = $db->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $balStmt->execute([$user['id']]);
        $newBal = $balStmt->fetch()['wallet_balance'];
        addTransaction($user['id'], 'withdrawal', $amount, $newBal, 'Withdrawal request to M-Pesa ' . $phone);
        addNotification($user['id'], 'Withdrawal Requested', 'Your withdrawal of ' . formatKES($amount) . ' is pending. Net amount: ' . formatKES($net) . '. Processing within 24h.', 'info');
        redirect('wallet.php', 'Withdrawal request of ' . formatKES($amount) . ' submitted!', 'success');
    }
}

// History
$deposits = $db->prepare("SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$deposits->execute([$user['id']]);
$deposits = $deposits->fetchAll();

$withdrawals = $db->prepare("SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$withdrawals->execute([$user['id']]);
$withdrawals = $withdrawals->fetchAll();

$minDep  = getSetting('min_deposit');
$minWith = getSetting('min_withdrawal');
$feeP    = getSetting('withdrawal_fee_percent');
$paybill = getSetting('mpesa_paybill');
$account = getSetting('mpesa_account');
$usdtAddress = getSetting('usdt_wallet_address');
$usdtNetwork = getSetting('usdt_network');
$usdtRate    = (float)getSetting('usdt_rate');

$pageTitle = 'Wallet';
include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-wallet" style="color:var(--primary);"></i> My Wallet</h1>
    <p>Manage your deposits, withdrawals and transaction history</p>
</div>

<!-- Wallet Hero -->
<div class="wallet-hero" style="margin-bottom:24px;">
    <div class="wallet-label">Available Balance</div>
    <div class="wallet-amount"><span class="wallet-currency">KES</span><?php echo number_format($user['wallet_balance'], 2); ?></div>
    <div style="display:flex;gap:24px;margin-top:16px;font-size:0.85rem;opacity:.85;">
        <div><div style="opacity:.7;font-size:.78rem;margin-bottom:3px;">Total Earned</div><strong><?php echo formatKES($user['total_earned']); ?></strong></div>
        <div><div style="opacity:.7;font-size:.78rem;margin-bottom:3px;">Total Withdrawn</div><strong><?php echo formatKES($user['total_withdrawn']); ?></strong></div>
    </div>
    <div class="wallet-actions" style="margin-top:20px;">
        <button class="wallet-btn" onclick="document.querySelector('[data-tab=deposit]').click()"><i class="fas fa-arrow-down"></i> Deposit</button>
        <button class="wallet-btn" onclick="document.querySelector('[data-tab=withdraw]').click()"><i class="fas fa-arrow-up"></i> Withdraw</button>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <button class="tab-btn active" data-tab="deposit">Deposit</button>
    <button class="tab-btn" data-tab="withdraw">Withdraw</button>
    <button class="tab-btn" data-tab="history">Transaction History</button>
</div>

<!-- Deposit Tab -->
<div class="tab-pane active" id="tab-deposit">
    <div class="grid-2">
        <div class="card">
            <div class="card-title" style="margin-bottom:20px;"><i class="fas fa-arrow-down" style="color:var(--success);"></i> Deposit Funds</div>
            <form method="POST">

                <!-- Payment Method Selector -->
<div class="form-group">
    <label class="form-label">Payment Method</label>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
        <label style="flex:1 1 120px;min-width:0;cursor:pointer;">
            <input type="radio" name="method" value="M-Pesa" checked onchange="toggleMethod(this.value)" style="display:none;">
            <div id="btn-mpesa" style="border:2px solid var(--primary);border-radius:8px;padding:10px;text-align:center;font-weight:600;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                📱 M-Pesa
            </div>
        </label>
        <label style="flex:1 1 120px;min-width:0;cursor:pointer;">
            <input type="radio" name="method" value="USDT" onchange="toggleMethod(this.value)" style="display:none;">
            <div id="btn-usdt" style="border:2px solid var(--border);border-radius:8px;padding:10px;text-align:center;font-weight:600;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                💎 USDT (BEP20)
            </div>
        </label>
                    </div>
                </div>

                <!-- Amount -->
                <div class="form-group">
                    <label class="form-label">Amount (KES)</label>
                    <div style="position:relative;">
                        <span class="input-prefix">KES</span>
                        <input type="number" name="amount" id="depositAmount" class="form-control" style="padding-left:52px;" placeholder="Enter amount" min="<?php echo $minDep; ?>" step="1" oninput="calcUsdt(this.value)" required>
                    </div>
                    <div class="form-text">Minimum deposit: <?php echo formatKES($minDep); ?></div>
                </div>

                <!-- M-Pesa Details -->
                <div id="mpesa-details">
                    <div style="background:#ecfdf5;border:1px solid #6ee7b7;border-radius:8px;padding:14px;margin-bottom:14px;font-size:0.85rem;color:#065f46;">
                        <div style="font-weight:700;margin-bottom:6px;"><i class="fas fa-mobile-alt"></i> M-Pesa Payment Details</div>
                        <div>📱 <strong>Paybill Number:</strong> <?php echo $paybill; ?></div>
                        <div>🔢 <strong>Account Number:</strong> <?php echo $account; ?></div>
                        <div style="margin-top:6px;font-size:0.78rem;opacity:.85;">After payment, enter the M-Pesa transaction ID below and submit. Your wallet will be credited after admin verification.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">M-Pesa Transaction ID</label>
                        <input type="text" name="transaction_id" id="txIdMpesa" class="form-control" placeholder="e.g. RBC1234XYZ" style="text-transform:uppercase;">
                    </div>
                </div>

                <!-- USDT Details -->
                <div id="usdt-details" style="display:none;">
                    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px;margin-bottom:14px;font-size:0.85rem;color:#1e40af;">
                        <div style="font-weight:700;margin-bottom:8px;"><i class="fas fa-coins"></i> USDT Payment Details</div>
                        <div>🌐 <strong>Network:</strong> <?php echo sanitize($usdtNetwork); ?></div>
                        <div style="margin-top:6px;">💼 <strong>Wallet Address:</strong></div>
                        <div onclick="copyAddress()" title="Click to copy" style="background:white;border:1px solid #bfdbfe;border-radius:6px;padding:8px;margin-top:4px;font-family:monospace;font-size:0.78rem;word-break:break-all;cursor:pointer;">
                            <?php echo sanitize($usdtAddress); ?>
                            <i class="fas fa-copy" style="margin-left:6px;color:var(--primary);"></i>
                        </div>
                        <div style="margin-top:8px;" id="usdtEquiv">Enter amount above to see USDT equivalent</div>
                        <div style="margin-top:6px;font-size:0.78rem;opacity:.8;">⚠️ Rate: 1 USDT = KES <?php echo number_format($usdtRate, 2); ?> | Send exact amount to avoid delays</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">USDT Transaction Hash</label>
                        <input type="text" name="transaction_id" id="txIdUsdt" class="form-control" placeholder="e.g. 0x1234abcd...">
                        <div class="form-text">Paste your BEP20 transaction hash after sending</div>
                    </div>
                </div>

                <button type="submit" name="deposit" class="btn btn-success btn-block btn-lg">
                    <i class="fas fa-paper-plane"></i> Submit Deposit Request
                </button>
            </form>
        </div>

        <div class="card">
            <div class="card-title" style="margin-bottom:16px;"><i class="fas fa-history" style="color:var(--gray);"></i> Deposit History</div>
            <?php if (empty($deposits)): ?>
            <div class="empty-state"><i class="fas fa-receipt"></i><p>No deposits yet.</p></div>
            <?php else: ?>
            <?php foreach (array_slice($deposits, 0, 8) as $dep): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border-light);">
                <div>
                    <div style="font-weight:600;font-size:0.88rem;"><?php echo formatKES($dep['amount']); ?></div>
                    <div style="font-size:0.75rem;color:var(--gray);"><?php echo date('d M Y, g:ia', strtotime($dep['created_at'])); ?></div>
                    <div style="font-size:0.72rem;color:var(--gray);">Via: <?php echo sanitize($dep['method'] ?? 'M-Pesa'); ?></div>
                    <?php if ($dep['transaction_id']): ?><div style="font-size:0.72rem;color:var(--gray);">Ref: <?php echo sanitize($dep['transaction_id']); ?></div><?php endif; ?>
                </div>
                <span class="status-badge status-<?php echo $dep['status']; ?>"><?php echo ucfirst($dep['status']); ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Withdraw Tab -->
<div class="tab-pane" id="tab-withdraw">
    <div class="grid-2">
        <div class="card">
            <div class="card-title" style="margin-bottom:20px;"><i class="fas fa-arrow-up" style="color:#ea580c;"></i> Withdraw to M-Pesa</div>
            <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:14px;margin-bottom:18px;font-size:0.85rem;color:#7c2d12;">
                <i class="fas fa-info-circle"></i>
                Withdrawal fee: <?php echo $feeP; ?>% | Min: <?php echo formatKES($minWith); ?> | Available: <strong><?php echo formatKES($user['wallet_balance']); ?></strong>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Withdrawal Amount (KES)</label>
                    <div style="position:relative;">
                        <span class="input-prefix">KES</span>
                        <input type="number" name="amount" class="form-control" style="padding-left:52px;" id="withdrawAmount" placeholder="Enter amount" min="<?php echo $minWith; ?>" max="<?php echo $user['wallet_balance']; ?>" step="1" oninput="updateNet(this.value)" required>
                    </div>
                    <div class="form-text" id="netAmount">Enter amount to see net payout</div>
                </div>
                <div class="form-group">
                    <label class="form-label">M-Pesa Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" value="<?php echo sanitize($user['phone']); ?>" required>
                </div>
                <button type="submit" name="withdraw" class="btn btn-warning btn-block btn-lg" data-confirm="Confirm withdrawal? A <?php echo $feeP; ?>% fee will be deducted.">
                    <i class="fas fa-paper-plane"></i> Request Withdrawal
                </button>
            </form>
        </div>
        <div class="card">
            <div class="card-title" style="margin-bottom:16px;"><i class="fas fa-history" style="color:var(--gray);"></i> Withdrawal History</div>
            <?php if (empty($withdrawals)): ?>
            <div class="empty-state"><i class="fas fa-money-bill-wave"></i><p>No withdrawals yet.</p></div>
            <?php else: ?>
            <?php foreach (array_slice($withdrawals, 0, 8) as $wd): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border-light);">
                <div>
                    <div style="font-weight:600;font-size:0.88rem;"><?php echo formatKES($wd['amount']); ?></div>
                    <div style="font-size:0.75rem;color:var(--gray);"><?php echo date('d M Y, g:ia', strtotime($wd['created_at'])); ?></div>
                    <div style="font-size:0.72rem;color:var(--gray);">To: <?php echo sanitize($wd['phone_number']); ?></div>
                </div>
                <span class="status-badge status-<?php echo $wd['status']; ?>"><?php echo ucfirst($wd['status']); ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- History Tab -->
<div class="tab-pane" id="tab-history">
    <div class="card">
        <div class="card-title" style="margin-bottom:20px;"><i class="fas fa-list-alt" style="color:var(--gray);"></i> All Transactions</div>
        <?php
        $allTx = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
        $allTx->execute([$user['id']]);
        $allTx = $allTx->fetchAll();
        ?>
        <?php if (empty($allTx)): ?>
        <div class="empty-state"><i class="fas fa-receipt"></i><p>No transactions yet.</p></div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Type</th><th>Description</th><th>Amount</th><th>Balance After</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($allTx as $tx):
                    $isCredit = in_array($tx['type'], ['deposit','return','referral','ad_reward']);
                ?>
                <tr>
                    <td><span class="status-badge status-<?php echo $isCredit?'active':'pending'; ?>"><?php echo ucfirst(str_replace('_',' ',$tx['type'])); ?></span></td>
                    <td style="font-size:0.85rem;"><?php echo sanitize($tx['description']); ?></td>
                    <td style="font-weight:700;color:<?php echo $isCredit?'var(--success)':'var(--danger)'; ?>;">
                        <?php echo $isCredit?'+':'-'; ?><?php echo formatKES($tx['amount']); ?>
                    </td>
                    <td><?php echo formatKES($tx['balance_after']); ?></td>
                    <td style="font-size:0.8rem;color:var(--gray);"><?php echo date('d M Y, g:ia', strtotime($tx['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleMethod(val) {
    const mpesa = document.getElementById('mpesa-details');
    const usdt  = document.getElementById('usdt-details');
    const btnM  = document.getElementById('btn-mpesa');
    const btnU  = document.getElementById('btn-usdt');
    if (val === 'USDT') {
        mpesa.style.display = 'none';
        usdt.style.display  = 'block';
        btnM.style.border = '2px solid var(--border)';
        btnU.style.border = '2px solid var(--primary)';
    } else {
        mpesa.style.display = 'block';
        usdt.style.display  = 'none';
        btnM.style.border = '2px solid var(--primary)';
        btnU.style.border = '2px solid var(--border)';
    }
}

function calcUsdt(kes) {
    const rate = <?php echo $usdtRate ?: 130; ?>;
    const amt  = parseFloat(kes) || 0;
    const usdt = amt / rate;
    const el   = document.getElementById('usdtEquiv');
    if (el && amt > 0) {
        el.innerHTML = '💱 Send: <strong>' + usdt.toFixed(4) + ' USDT</strong> (at rate KES <?php echo $usdtRate ?: 130; ?>/USDT)';
    } else if (el) {
        el.textContent = 'Enter amount above to see USDT equivalent';
    }
}

function copyAddress() {
    const addr = '<?php echo addslashes($usdtAddress); ?>';
    navigator.clipboard.writeText(addr).then(() => {
        alert('✅ Wallet address copied!');
    }).catch(() => {
        prompt('Copy this address:', addr);
    });
}

function updateNet(val) {
    const amt = parseFloat(val) || 0;
    const fee = amt * <?php echo $feeP/100; ?>;
    const net = amt - fee;
    const el = document.getElementById('netAmount');
    if (amt > 0) {
        el.innerHTML = 'Fee: <strong>KES ' + fee.toFixed(2) + '</strong> | You receive: <strong style="color:var(--success)">KES ' + net.toFixed(2) + '</strong>';
    } else {
        el.textContent = 'Enter amount to see net payout';
    }
}
</script>
<?php include 'includes/footer.php'; ?>

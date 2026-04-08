<?php
require_once '../includes/config.php';
requireAdmin();
$db = getDB();

// Toggle user status
if (isset($_GET['toggle']) && isset($_GET['uid'])) {
    $uid = (int)$_GET['uid'];
    $statusStmt = $db->prepare("SELECT status FROM users WHERE id = ? AND is_admin = 0");
    $statusStmt->execute([$uid]);
    $u = $statusStmt->fetch();
    if ($u) {
        $newStatus = $u['status'] === 'active' ? 'suspended' : 'active';
        $db->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $uid]);
        redirect(SITE_URL . '/admin/users.php', 'User status updated to ' . $newStatus . '.', 'success');
    }
}

// Manual wallet credit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credit_wallet'])) {
    $uid    = (int)$_POST['user_id'];
    $amount = (float)$_POST['credit_amount'];
    $note   = sanitize($_POST['credit_note'] ?? 'Admin credit');
    if ($uid > 0 && $amount > 0) {
        $db->prepare("UPDATE users SET wallet_balance=wallet_balance+?, total_earned=total_earned+? WHERE id=?")->execute([$amount, $amount, $uid]);
        $balStmt = $db->prepare("SELECT wallet_balance FROM users WHERE id=?");
        $balStmt->execute([$uid]);
        $newBal = $balStmt->fetch()['wallet_balance'];
        addTransaction($uid, 'deposit', $amount, $newBal, 'Admin credit: ' . $note);
        addNotification($uid, 'Wallet Credited', 'Admin has credited ' . formatKES($amount) . ' to your wallet. Note: ' . $note, 'success');
        redirect(SITE_URL . '/admin/users.php', 'Wallet credited ' . formatKES($amount) . ' to user #' . $uid, 'success');
    }
}

$search = sanitize($_GET['q'] ?? '');
$whereClause = "WHERE is_admin = 0";
$params = [];
if ($search) {
    $whereClause .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$users = $db->prepare("SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT 200");
$users->execute($params);
$users = $users->fetchAll();

$pageTitle = 'Manage Users';
include 'includes/admin_header.php';
?>

<div class="page-header">
    <div class="page-header-row">
        <div><h1>Manage Users</h1><p><?php echo count($users); ?> members found</p></div>
        <form method="GET" style="display:flex;gap:8px;">
            <input type="text" name="q" class="form-control" placeholder="Search name, email, phone..." value="<?php echo $search; ?>" style="width:250px;">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
        </form>
    </div>
</div>

<!-- Credit Wallet Modal Trigger -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-title" style="margin-bottom:14px;"><i class="fas fa-plus-circle" style="color:var(--success);"></i> Manual Wallet Credit</div>
    <form method="POST" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <div class="form-group" style="margin:0;flex:1;min-width:180px;">
            <label class="form-label">User ID</label>
            <input type="number" name="user_id" class="form-control" placeholder="User ID" required>
        </div>
        <div class="form-group" style="margin:0;flex:1;min-width:160px;">
            <label class="form-label">Amount (KES)</label>
            <input type="number" name="credit_amount" class="form-control" placeholder="Amount" step="1" min="1" required>
        </div>
        <div class="form-group" style="margin:0;flex:2;min-width:200px;">
            <label class="form-label">Note</label>
            <input type="text" name="credit_note" class="form-control" placeholder="Reason for credit" value="Admin credit">
        </div>
        <button type="submit" name="credit_wallet" class="btn btn-success" data-confirm="Credit wallet?"><i class="fas fa-coins"></i> Credit Wallet</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>ID</th><th>Name</th><th>Contact</th><th>Balance</th><th>Earned</th><th>Refs</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u):
                $refCnt = $db->prepare("SELECT COUNT(*) as c FROM users WHERE referred_by = ?");
                $refCnt->execute([$u['id']]);
                $refs = $refCnt->fetch()['c'];
            ?>
            <tr>
                <td style="color:var(--gray);font-size:0.8rem;">#<?php echo $u['id']; ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:30px;height:30px;background:var(--primary-xlight);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);font-size:0.75rem;flex-shrink:0;"><?php echo strtoupper(substr($u['full_name'],0,1)); ?></div>
                        <div>
                            <div style="font-weight:600;font-size:0.88rem;"><?php echo sanitize($u['full_name']); ?></div>
                            <div style="font-size:0.72rem;color:var(--gray);">Code: <?php echo $u['referral_code']; ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-size:0.82rem;"><?php echo sanitize($u['email']); ?></div>
                    <div style="font-size:0.78rem;color:var(--gray);"><?php echo sanitize($u['phone']); ?></div>
                </td>
                <td style="font-weight:700;color:var(--primary);"><?php echo formatKES($u['wallet_balance']); ?></td>
                <td style="color:var(--success);"><?php echo formatKES($u['total_earned']); ?></td>
                <td style="text-align:center;"><?php echo $refs; ?></td>
                <td style="font-size:0.8rem;color:var(--gray);"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                <td><span class="status-badge status-<?php echo $u['status']; ?>"><?php echo ucfirst($u['status']); ?></span></td>
                <td>
                    <a href="?toggle=1&uid=<?php echo $u['id']; ?>" class="btn btn-sm <?php echo $u['status']==='active'?'btn-warning':'btn-success'; ?>" data-confirm="<?php echo $u['status']==='active'?'Suspend':'Activate'; ?> this user?">
                        <i class="fas fa-<?php echo $u['status']==='active'?'ban':'check'; ?>"></i>
                        <?php echo $u['status']==='active'?'Suspend':'Activate'; ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
            <tr><td colspan="9"><div class="empty-state"><i class="fas fa-users"></i><p>No users found.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>

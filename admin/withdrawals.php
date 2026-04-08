<?php
require_once '../includes/config.php';
requireAdmin();
$db = getDB();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $action = $_GET['action'];

    $wdStmt = $db->prepare("SELECT * FROM withdrawals WHERE id = ? LIMIT 1");
    $wdStmt->execute([$id]);
    $wd = $wdStmt->fetch();

    if ($wd && $wd['status'] === 'pending') {
        if ($action === 'approve') {
            $db->prepare("UPDATE withdrawals SET status='approved', processed_by=?, processed_at=NOW() WHERE id=?")->execute([$_SESSION['user_id'], $id]);
            $db->prepare("UPDATE users SET total_withdrawn=total_withdrawn+? WHERE id=?")->execute([$wd['amount'], $wd['user_id']]);
            addNotification($wd['user_id'], 'Withdrawal Approved!', 'Your withdrawal of ' . formatKES($wd['amount']) . ' to ' . $wd['phone_number'] . ' has been sent.', 'success');
            redirect(SITE_URL . '/admin/withdrawals.php', 'Withdrawal approved and processed!', 'success');
        } elseif ($action === 'reject') {
            // Refund user wallet
            $db->prepare("UPDATE withdrawals SET status='rejected', processed_by=?, processed_at=NOW() WHERE id=?")->execute([$_SESSION['user_id'], $id]);
            $db->prepare("UPDATE users SET wallet_balance=wallet_balance+? WHERE id=?")->execute([$wd['amount'], $wd['user_id']]);
            $balStmt = $db->prepare("SELECT wallet_balance FROM users WHERE id=?");
            $balStmt->execute([$wd['user_id']]);
            $newBal = $balStmt->fetch()['wallet_balance'];
            addTransaction($wd['user_id'], 'deposit', $wd['amount'], $newBal, 'Withdrawal rejected — amount refunded', $id);
            addNotification($wd['user_id'], 'Withdrawal Rejected', 'Your withdrawal of ' . formatKES($wd['amount']) . ' was rejected. Amount refunded to wallet.', 'danger');
            redirect(SITE_URL . '/admin/withdrawals.php', 'Withdrawal rejected and amount refunded to user.', 'warning');
        }
    }
}

$wds = $db->query("SELECT w.*, u.full_name, u.phone FROM withdrawals w JOIN users u ON w.user_id=u.id ORDER BY w.created_at DESC LIMIT 100")->fetchAll();
$filter = $_GET['filter'] ?? 'all';

$pageTitle = 'Manage Withdrawals';
include 'includes/admin_header.php';
?>

<div class="page-header">
    <div class="page-header-row">
        <div><h1>Manage Withdrawals</h1><p>Review and process member withdrawal requests</p></div>
        <div style="display:flex;gap:8px;">
            <a href="?filter=all" class="btn <?php echo $filter==='all'?'btn-primary':'btn-gray'; ?> btn-sm">All</a>
            <a href="?filter=pending" class="btn <?php echo $filter==='pending'?'btn-warning':'btn-gray'; ?> btn-sm">Pending</a>
            <a href="?filter=approved" class="btn <?php echo $filter==='approved'?'btn-success':'btn-gray'; ?> btn-sm">Approved</a>
            <a href="?filter=rejected" class="btn <?php echo $filter==='rejected'?'btn-danger':'btn-gray'; ?> btn-sm">Rejected</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>#</th><th>Member</th><th>Amount</th><th>M-Pesa Phone</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php
            $filtered = $filter === 'all' ? $wds : array_filter($wds, fn($w) => $w['status'] === $filter);
            foreach ($filtered as $wd):
            ?>
            <tr>
                <td style="color:var(--gray);font-size:0.8rem;">#<?php echo $wd['id']; ?></td>
                <td>
                    <div style="font-weight:600;font-size:0.88rem;"><?php echo sanitize($wd['full_name']); ?></div>
                    <div style="font-size:0.75rem;color:var(--gray);"><?php echo sanitize($wd['phone']); ?></div>
                </td>
                <td style="font-weight:700;font-size:1rem;color:var(--danger);"><?php echo formatKES($wd['amount']); ?></td>
                <td style="font-size:0.88rem;font-weight:600;"><?php echo sanitize($wd['phone_number']); ?></td>
                <td style="font-size:0.82rem;color:var(--gray);"><?php echo date('d M Y, g:ia', strtotime($wd['created_at'])); ?></td>
                <td><span class="status-badge status-<?php echo $wd['status']; ?>"><?php echo ucfirst($wd['status']); ?></span></td>
                <td>
                    <?php if ($wd['status'] === 'pending'): ?>
                    <div class="admin-table-actions">
                        <a href="?action=approve&id=<?php echo $wd['id']; ?>&filter=<?php echo $filter; ?>" class="btn btn-success btn-sm" data-confirm="Approve withdrawal of <?php echo formatKES($wd['amount']); ?> to <?php echo sanitize($wd['phone_number']); ?>?"><i class="fas fa-check"></i> Approve</a>
                        <a href="?action=reject&id=<?php echo $wd['id']; ?>&filter=<?php echo $filter; ?>" class="btn btn-danger btn-sm" data-confirm="Reject and refund this withdrawal?"><i class="fas fa-times"></i> Reject</a>
                    </div>
                    <?php else: ?>
                    <span style="font-size:0.78rem;color:var(--gray);">Processed <?php echo $wd['processed_at'] ? date('d M', strtotime($wd['processed_at'])) : ''; ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($filtered)): ?>
            <tr><td colspan="7"><div class="empty-state"><i class="fas fa-inbox"></i><p>No withdrawals found.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>

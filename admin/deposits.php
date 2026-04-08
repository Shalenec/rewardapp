<?php
require_once '../includes/config.php';
requireAdmin();
$db = getDB();

// Approve / Reject deposit
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $action = $_GET['action'];

    $depStmt = $db->prepare("SELECT * FROM deposits WHERE id = ? LIMIT 1");
    $depStmt->execute([$id]);
    $dep = $depStmt->fetch();

    if ($dep && $dep['status'] === 'pending') {
        if ($action === 'approve') {
            $db->prepare("UPDATE deposits SET status='approved', processed_by=?, processed_at=NOW() WHERE id=?")->execute([$_SESSION['user_id'], $id]);
            $db->prepare("UPDATE users SET wallet_balance=wallet_balance+? WHERE id=?")->execute([$dep['amount'], $dep['user_id']]);
            $balStmt = $db->prepare("SELECT wallet_balance FROM users WHERE id=?");
            $balStmt->execute([$dep['user_id']]);
            $newBal = $balStmt->fetch()['wallet_balance'];
            addTransaction($dep['user_id'], 'deposit', $dep['amount'], $newBal, 'Deposit approved by admin', $id);
            addNotification($dep['user_id'], 'Deposit Approved!', 'Your deposit of ' . formatKES($dep['amount']) . ' has been approved and credited to your wallet.', 'success');
            redirect(SITE_URL . '/admin/deposits.php', 'Deposit approved and wallet credited!', 'success');
        } elseif ($action === 'reject') {
            $db->prepare("UPDATE deposits SET status='rejected', processed_by=?, processed_at=NOW() WHERE id=?")->execute([$_SESSION['user_id'], $id]);
            addNotification($dep['user_id'], 'Deposit Rejected', 'Your deposit of ' . formatKES($dep['amount']) . ' was rejected. Contact support for help.', 'danger');
            redirect(SITE_URL . '/admin/deposits.php', 'Deposit rejected.', 'warning');
        }
    }
}

$filter = $_GET['filter'] ?? 'all';
$where  = $filter !== 'all' ? "WHERE d.status = '" . $db->quote($filter) . "'" : '';
$deps   = $db->query("SELECT d.*, u.full_name, u.phone FROM deposits d JOIN users u ON d.user_id=u.id ORDER BY d.created_at DESC LIMIT 100")->fetchAll();

$pageTitle = 'Manage Deposits';
include 'includes/admin_header.php';
?>

<div class="page-header">
    <div class="page-header-row">
        <div><h1>Manage Deposits</h1><p>Review and approve member deposit requests</p></div>
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
            <thead><tr><th>#</th><th>Member</th><th>Amount</th><th>M-Pesa Ref</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php
            $filtered = $filter === 'all' ? $deps : array_filter($deps, fn($d) => $d['status'] === $filter);
            foreach ($filtered as $dep):
            ?>
            <tr>
                <td style="color:var(--gray);font-size:0.8rem;">#<?php echo $dep['id']; ?></td>
                <td>
                    <div style="font-weight:600;font-size:0.88rem;"><?php echo sanitize($dep['full_name']); ?></div>
                    <div style="font-size:0.75rem;color:var(--gray);"><?php echo sanitize($dep['phone']); ?></div>
                </td>
                <td style="font-weight:700;font-size:1rem;color:var(--success);"><?php echo formatKES($dep['amount']); ?></td>
                <td style="font-size:0.82rem;font-family:monospace;"><?php echo $dep['transaction_id'] ? sanitize($dep['transaction_id']) : '<span style="color:var(--gray);">N/A</span>'; ?></td>
                <td style="font-size:0.82rem;color:var(--gray);"><?php echo date('d M Y, g:ia', strtotime($dep['created_at'])); ?></td>
                <td><span class="status-badge status-<?php echo $dep['status']; ?>"><?php echo ucfirst($dep['status']); ?></span></td>
                <td>
                    <?php if ($dep['status'] === 'pending'): ?>
                    <div class="admin-table-actions">
                        <a href="?action=approve&id=<?php echo $dep['id']; ?>" class="btn btn-success btn-sm" data-confirm="Approve this deposit of <?php echo formatKES($dep['amount']); ?>?"><i class="fas fa-check"></i> Approve</a>
                        <a href="?action=reject&id=<?php echo $dep['id']; ?>" class="btn btn-danger btn-sm" data-confirm="Reject this deposit?"><i class="fas fa-times"></i> Reject</a>
                    </div>
                    <?php else: ?>
                    <span style="font-size:0.78rem;color:var(--gray);">Processed <?php echo $dep['processed_at'] ? date('d M', strtotime($dep['processed_at'])) : ''; ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($filtered)): ?>
            <tr><td colspan="7"><div class="empty-state"><i class="fas fa-inbox"></i><p>No deposits found.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>

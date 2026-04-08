<?php
require_once '../includes/config.php';
requireAdmin();
$db = getDB();

$filter = $_GET['filter'] ?? 'all';
$where  = $filter !== 'all' ? "WHERE i.status = '$filter'" : '';

$invs = $db->query("
    SELECT i.*, u.full_name, u.phone, p.name as pkg_name, p.daily_return_percent
    FROM investments i 
    JOIN users u ON i.user_id = u.id
    JOIN packages p ON i.package_id = p.id
    $where
    ORDER BY i.created_at DESC LIMIT 200
")->fetchAll();

// Summary stats
$stats = $db->query("SELECT status, COUNT(*) as cnt, SUM(amount) as total FROM investments GROUP BY status")->fetchAll();
$statMap = [];
foreach ($stats as $s) $statMap[$s['status']] = $s;

$pageTitle = 'Investments';
include 'includes/admin_header.php';
?>

<div class="page-header">
    <div class="page-header-row">
        <div><h1>Investments</h1><p>All member investment activity</p></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="?filter=all" class="btn <?php echo $filter==='all'?'btn-primary':'btn-gray'; ?> btn-sm">All</a>
            <a href="?filter=active" class="btn <?php echo $filter==='active'?'btn-success':'btn-gray'; ?> btn-sm">Active</a>
            <a href="?filter=completed" class="btn <?php echo $filter==='completed'?'btn-primary':'btn-gray'; ?> btn-sm">Completed</a>
            <a href="?filter=cancelled" class="btn <?php echo $filter==='cancelled'?'btn-danger':'btn-gray'; ?> btn-sm">Cancelled</a>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:24px;">
    <?php foreach (['active'=>'green','completed'=>'blue','cancelled'=>'orange'] as $status => $col): ?>
    <div class="stat-card">
        <div class="stat-icon <?php echo $col; ?>"><i class="fas fa-seedling"></i></div>
        <div class="stat-body">
            <div class="stat-value"><?php echo formatKES($statMap[$status]['total'] ?? 0); ?></div>
            <div class="stat-label"><?php echo ucfirst($status); ?> (<?php echo $statMap[$status]['cnt'] ?? 0; ?>)</div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>#</th><th>Member</th><th>Package</th><th>Invested</th><th>Daily Return</th><th>Earned</th><th>Total Return</th><th>End Date</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($invs as $inv):
                $progress = $inv['total_return'] > 0 ? ($inv['earned_so_far'] / $inv['total_return']) * 100 : 0;
            ?>
            <tr>
                <td style="color:var(--gray);font-size:0.8rem;">#<?php echo $inv['id']; ?></td>
                <td>
                    <div style="font-weight:600;font-size:0.85rem;"><?php echo sanitize($inv['full_name']); ?></div>
                    <div style="font-size:0.75rem;color:var(--gray);"><?php echo sanitize($inv['phone']); ?></div>
                </td>
                <td>
                    <strong><?php echo sanitize($inv['pkg_name']); ?></strong><br>
                    <span style="font-size:0.75rem;color:var(--gray);"><?php echo $inv['daily_return_percent']; ?>%/day</span>
                </td>
                <td style="font-weight:700;"><?php echo formatKES($inv['amount']); ?></td>
                <td style="color:var(--success);"><?php echo formatKES($inv['daily_return']); ?></td>
                <td>
                    <?php echo formatKES($inv['earned_so_far']); ?>
                    <div class="progress" style="height:4px;margin-top:4px;"><div class="progress-bar" style="width:<?php echo min($progress,100); ?>%"></div></div>
                </td>
                <td><?php echo formatKES($inv['total_return']); ?></td>
                <td style="font-size:0.82rem;"><?php echo date('d M Y', strtotime($inv['end_date'])); ?></td>
                <td><span class="status-badge status-<?php echo $inv['status']; ?>"><?php echo ucfirst($inv['status']); ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($invs)): ?>
            <tr><td colspan="9"><div class="empty-state"><i class="fas fa-seedling"></i><p>No investments found.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>

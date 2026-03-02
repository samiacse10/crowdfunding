<?php
require_once '../config.php';

if (!isAdminLoggedIn()) {
    $_SESSION['error'] = "Please login as admin first";
    redirect('admin_login.php');
}

// Handle approve/reject actions (same as before)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    if ($action == 'approve' || $action == 'reject') {
        $status = ($action == 'approve') ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE campaigns SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $id])) {
            $_SESSION['success'] = "Campaign $status successfully.";
        } else {
            $_SESSION['error'] = "Failed to update campaign.";
        }
        redirect('admin_campaigns.php');
    }
}

// Fetch all campaigns with category and user info
$stmt = $pdo->query("SELECT campaigns.*, users.username, categories.name as category_name FROM campaigns 
                      JOIN users ON campaigns.user_id = users.id 
                      LEFT JOIN categories ON campaigns.category_id = categories.id 
                      ORDER BY campaigns.created_at DESC");
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'admin_header.php';
?>

<h2>Manage Campaigns</h2>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Category</th>
            <th>User</th>
            <th>Target</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($campaigns as $campaign): ?>
            <tr>
                <td><?php echo $campaign['id']; ?></td>
                <td><?php echo htmlspecialchars($campaign['title']); ?></td>
                <td><?php echo htmlspecialchars($campaign['category_name'] ?? 'Uncategorized'); ?></td>
                <td><?php echo htmlspecialchars($campaign['username']); ?></td>
                <td>$<?php echo number_format($campaign['target_amount'], 2); ?></td>
                <td>
                    <?php
                    $badge_class = '';
                    if ($campaign['status'] == 'approved') $badge_class = 'bg-success';
                    elseif ($campaign['status'] == 'pending') $badge_class = 'bg-warning';
                    elseif ($campaign['status'] == 'rejected') $badge_class = 'bg-danger';
                    ?>
                    <span class="badge <?php echo $badge_class; ?>"><?php echo $campaign['status']; ?></span>
                </td>
                <td><?php echo date('d M Y', strtotime($campaign['created_at'])); ?></td>
                <td>
                    <a href="../campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-info" target="_blank">View</a>
                    <?php if ($campaign['status'] == 'pending'): ?>
                        <a href="?action=approve&id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this campaign?')">Approve</a>
                        <a href="?action=reject&id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this campaign?')">Reject</a>
                    <?php elseif ($campaign['status'] == 'approved'): ?>
                        <a href="?action=reject&id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this campaign?')">Reject</a>
                    <?php elseif ($campaign['status'] == 'rejected'): ?>
                        <a href="?action=approve&id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this campaign?')">Approve</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include 'admin_footer.php'; ?>
<?php
// Start output buffering
ob_start();

require_once '../config.php';
require_once 'admin_header.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id === 0) {
    header('Location: admin_user_chart.php');
    exit();
}

// Get user details
$user_query = "SELECT u.*, 
    COUNT(DISTINCT c.id) as campaign_count,
    COUNT(DISTINCT d.id) as donation_count,
    COALESCE(SUM(d.amount), 0) as total_donated
    FROM users u
    LEFT JOIN campaigns c ON u.id = c.user_id
    LEFT JOIN donations d ON u.id = d.user_id
    WHERE u.id = ?
    GROUP BY u.id";

$user_stmt = $pdo->prepare($user_query);
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error'] = "User not found!";
    header('Location: admin_user_chart.php');
    exit();
}

// Get user's campaigns
$campaigns_query = "SELECT c.*, cat.name as category_name,
    COUNT(d.id) as donation_count,
    COALESCE(SUM(d.amount), 0) as raised_amount
    FROM campaigns c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN donations d ON c.id = d.campaign_id AND d.status = 'completed'
    WHERE c.user_id = ?
    GROUP BY c.id
    ORDER BY c.created_at DESC";

$campaigns_stmt = $pdo->prepare($campaigns_query);
$campaigns_stmt->execute([$user_id]);
$campaigns = $campaigns_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's donations
$donations_query = "SELECT d.*, c.title as campaign_title
    FROM donations d
    LEFT JOIN campaigns c ON d.campaign_id = c.id
    WHERE d.user_id = ?
    ORDER BY d.created_at DESC";

$donations_stmt = $pdo->prepare($donations_query);
$donations_stmt->execute([$user_id]);
$donations = $donations_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle user deletion
if (isset($_POST['delete_user'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete user's donations first
        $stmt = $pdo->prepare("DELETE FROM donations WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Delete user's campaigns
        $stmt = $pdo->prepare("DELETE FROM campaigns WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Finally delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success'] = "User account deleted successfully!";
        header('Location: admin_user_chart.php');
        exit();
    } catch (PDOException $e) {
        // Rollback on error
        $pdo->rollBack();
        $error_message = "Error deleting user: " . $e->getMessage();
    }
}

// Handle status update
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $user_id]);
        $_SESSION['success'] = "User status updated successfully!";
        header('Location: view.php?id=' . $user_id);
        exit();
    } catch (PDOException $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Handle user type update
if (isset($_POST['update_type'])) {
    $new_type = $_POST['user_type'];
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET user_type = ? WHERE id = ?");
        $stmt->execute([$new_type, $user_id]);
        $_SESSION['success'] = "User type updated successfully!";
        header('Location: view.php?id=' . $user_id);
        exit();
    } catch (PDOException $e) {
        $error_message = "Error updating user type: " . $e->getMessage();
    }
}

// Handle success/error messages
$success_message = $_SESSION['success'] ?? '';
$error_message = $error_message ?? $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!-- Display success/error messages -->
<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-regular fa-circle-check me-2"></i>
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-regular fa-circle-exclamation me-2"></i>
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fa-regular fa-user me-2"></i>User Details</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="admin_user_chart.php">All Users</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($user['username']); ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="admin_user_chart.php" class="btn btn-outline-secondary rounded-pill">
            <i class="fa-solid fa-arrow-left me-2"></i>Back to Users
        </a>
        <button type="button" class="btn btn-danger rounded-pill" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
            <i class="fa-regular fa-trash-can me-2"></i>Delete User
        </button>
    </div>
</div>

<!-- User Profile Section -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center p-4">
                <div class="avatar-circle-large bg-<?php 
                    echo $user['status'] == 'active' ? 'success' : 
                        ($user['status'] == 'pending' ? 'warning' : 
                        ($user['status'] == 'suspended' ? 'danger' : 'secondary')); 
                ?> text-white mx-auto mb-3 d-flex align-items-center justify-content-center" 
                style="width: 100px; height: 100px; font-size: 3rem; border-radius: 50%;">
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
                <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($user['username']); ?></h3>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-<?php 
                        echo $user['status'] == 'active' ? 'success' : 
                            ($user['status'] == 'pending' ? 'warning' : 
                            ($user['status'] == 'suspended' ? 'danger' : 'secondary')); 
                    ?> rounded-pill p-2">
                        <?php echo ucfirst($user['status']); ?>
                    </span>
                    <span class="badge bg-<?php 
                        echo $user['user_type'] == 'donor' ? 'info' : 
                            ($user['user_type'] == 'organizer' ? 'primary' : 'warning'); 
                    ?> rounded-pill p-2">
                        <?php echo ucfirst($user['user_type']); ?>
                    </span>
                </div>
                
                <p class="text-muted small mb-0">
                    <i class="fa-regular fa-calendar me-1"></i>
                    Joined: <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 bg-primary text-white">
                    <div class="card-body text-center p-4">
                        <h2 class="fw-bold mb-1"><?php echo $user['campaign_count']; ?></h2>
                        <p class="mb-0 opacity-75">Campaigns Created</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 bg-success text-white">
                    <div class="card-body text-center p-4">
                        <h2 class="fw-bold mb-1"><?php echo $user['donation_count']; ?></h2>
                        <p class="mb-0 opacity-75">Donations Made</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 bg-info text-white">
                    <div class="card-body text-center p-4">
                        <h2 class="fw-bold mb-1">$<?php echo number_format($user['total_donated'], 2); ?></h2>
                        <p class="mb-0 opacity-75">Total Donated</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Update Forms -->
        <div class="row g-4 mt-2">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-tag me-2 text-primary"></i>Update Status</h6>
                        <form method="POST" class="d-flex gap-2">
                            <select name="status" class="form-select">
                                <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="pending" <?php echo $user['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="suspended" <?php echo $user['status'] == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="rejected" <?php echo $user['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-user-tag me-2 text-primary"></i>Update User Type</h6>
                        <form method="POST" class="d-flex gap-2">
                            <select name="user_type" class="form-select">
                                <option value="donor" <?php echo $user['user_type'] == 'donor' ? 'selected' : ''; ?>>Donor</option>
                                <option value="organizer" <?php echo $user['user_type'] == 'organizer' ? 'selected' : ''; ?>>Organizer</option>
                                <option value="both" <?php echo $user['user_type'] == 'both' ? 'selected' : ''; ?>>Both</option>
                            </select>
                            <button type="submit" name="update_type" class="btn btn-primary">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User's Campaigns -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-transparent border-0 pt-4 px-4">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-rocket text-primary me-2"></i>
            Campaigns Created (<?php echo count($campaigns); ?>)
        </h5>
    </div>
    <div class="card-body p-4">
        <?php if (count($campaigns) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Target</th>
                            <th>Raised</th>
                            <th>Donations</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $campaign): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($campaign['title']); ?></td>
                            <td><?php echo htmlspecialchars($campaign['category_name'] ?? 'Uncategorized'); ?></td>
                            <td>$<?php echo number_format($campaign['target_amount'], 2); ?></td>
                            <td><span class="fw-bold text-success">$<?php echo number_format($campaign['raised_amount'], 2); ?></span></td>
                            <td class="text-center"><?php echo $campaign['donation_count']; ?></td>
                            <td>
                                <?php if ($campaign['status'] == 'approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php elseif ($campaign['status'] == 'pending'): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($campaign['created_at'])); ?></td>
                            <td>
                                <a href="../campaign.php?id=<?php echo $campaign['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted text-center py-4">No campaigns created yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- User's Donations -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-transparent border-0 pt-4 px-4">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-hand-holding-heart text-primary me-2"></i>
            Donations Made (<?php echo count($donations); ?>)
        </h5>
    </div>
    <div class="card-body p-4">
        <?php if (count($donations) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Campaign</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donations as $donation): ?>
                        <tr>
                            <td>
                                <a href="../campaign.php?id=<?php echo $donation['campaign_id']; ?>" target="_blank">
                                    <?php echo htmlspecialchars($donation['campaign_title'] ?: 'Unknown Campaign'); ?>
                                </a>
                            </td>
                            <td><span class="fw-bold text-success">$<?php echo number_format($donation['amount'], 2); ?></span></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $donation['payment_method'] ?? 'Unknown')); ?></td>
                            <td>
                                <?php if ($donation['status'] == 'completed'): ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php elseif ($donation['status'] == 'pending'): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Failed</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($donation['created_at'])); ?></td>
                            <td>
                                <a href="admin_donations.php?highlight=<?php echo $donation['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted text-center py-4">No donations made yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete user <strong id="deleteUserName"></strong>?</p>
                <p class="text-danger mb-0"><i class="fa-solid fa-circle-exclamation me-1"></i> This action cannot be undone. All campaigns and donations by this user will also be deleted.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Confirm delete
function confirmDelete(id, username) {
    document.getElementById('deleteUserName').textContent = username;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<style>
.avatar-circle-large {
    font-weight: 600;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.stat-card {
    border-radius: 16px;
    padding: 1.5rem;
    color: white;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.2);
}

.table th {
    font-weight: 600;
    color: #495057;
}

.table td {
    vertical-align: middle;
}

.modal-header.bg-danger {
    background: linear-gradient(135deg, #dc3545, #bb2d3b);
}

body.dark-mode .table th {
    color: #fff;
}

body.dark-mode .table td {
    color: #ddd;
}
</style>

<?php
require_once 'admin_footer.php';
ob_end_flush();
?>
<?php
// Start output buffering
ob_start();

require_once '../config.php';
require_once 'admin_header.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    
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
    } catch (PDOException $e) {
        // Rollback on error
        $pdo->rollBack();
        $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
    }
    
    header('Location: admin_user_chart.php');
    exit();
}

// Get all users with their stats
$users_query = "SELECT u.*, 
    COUNT(DISTINCT c.id) as campaign_count,
    COUNT(DISTINCT d.id) as donation_count,
    COALESCE(SUM(d.amount), 0) as total_donated
    FROM users u
    LEFT JOIN campaigns c ON u.id = c.user_id
    LEFT JOIN donations d ON u.id = d.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC";

$users_stmt = $pdo->prepare($users_query);
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics for charts
$stats_query = "SELECT 
    COUNT(*) as total_users,
    COUNT(CASE WHEN user_type = 'donor' THEN 1 END) as donors,
    COUNT(CASE WHEN user_type = 'organizer' THEN 1 END) as organizers,
    COUNT(CASE WHEN user_type = 'both' THEN 1 END) as both_types,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'suspended' THEN 1 END) as suspended
    FROM users";

$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get user type distribution for pie chart
$type_query = "SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type";
$type_result = $pdo->query($type_query);
$type_labels = [];
$type_counts = [];
while ($row = $type_result->fetch(PDO::FETCH_ASSOC)) {
    $type_labels[] = ucfirst($row['user_type']);
    $type_counts[] = $row['count'];
}

// Get status distribution for pie chart
$status_query = "SELECT status, COUNT(*) as count FROM users GROUP BY status";
$status_result = $pdo->query($status_query);
$status_labels = [];
$status_counts = [];
while ($row = $status_result->fetch(PDO::FETCH_ASSOC)) {
    $status_labels[] = ucfirst($row['status']);
    $status_counts[] = $row['count'];
}

// Get monthly registration data for line chart
$monthly_query = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as count
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC";

$monthly_result = $pdo->query($monthly_query);
$monthly_labels = [];
$monthly_counts = [];
while ($row = $monthly_result->fetch(PDO::FETCH_ASSOC)) {
    $monthly_labels[] = date('M Y', strtotime($row['month'] . '-01'));
    $monthly_counts[] = $row['count'];
}

// Handle success/error messages
$success_message = $_SESSION['success'] ?? '';
$error_message = $_SESSION['error'] ?? '';
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
        <h1><i class="fa-solid fa-users me-2"></i>User Management</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">All Users</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="admin_dashboard.php" class="btn btn-outline-secondary rounded-pill">
            <i class="fa-solid fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card bg-gradient-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0"><?php echo number_format($stats['total_users']); ?></h3>
                    <p class="mb-0 opacity-75">Total Users</p>
                </div>
                <i class="fa-solid fa-users fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0"><?php echo number_format($stats['active']); ?></h3>
                    <p class="mb-0 opacity-75">Active Users</p>
                </div>
                <i class="fa-regular fa-circle-check fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0"><?php echo number_format($stats['pending']); ?></h3>
                    <p class="mb-0 opacity-75">Pending Approval</p>
                </div>
                <i class="fa-regular fa-clock fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0"><?php echo number_format($stats['organizers'] + $stats['both_types']); ?></h3>
                    <p class="mb-0 opacity-75">Total Organizers</p>
                </div>
                <i class="fa-solid fa-rocket fa-3x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-chart-line text-primary me-2"></i>
                    User Registrations (Last 6 Months)
                </h5>
            </div>
            <div class="card-body p-4">
                <canvas id="userTrendChart" style="height: 250px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-chart-pie text-primary me-2"></i>
                    User Types
                </h5>
            </div>
            <div class="card-body p-4">
                <canvas id="userTypeChart" style="height: 180px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-chart-pie text-primary me-2"></i>
                    User Status
                </h5>
            </div>
            <div class="card-body p-4">
                <canvas id="userStatusChart" style="height: 180px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ===== ALL USERS TABLE ===== -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-transparent border-0 pt-4 px-4">
        <h5 class="card-title mb-0">
            <i class="fa-solid fa-table text-primary me-2"></i>
            All Users (<?php echo count($users); ?> total)
        </h5>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Campaigns</th>
                        <th>Donations</th>
                        <th>Total Donated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><span class="fw-semibold">#<?php echo $user['id']; ?></span></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle bg-<?php 
                                    echo $user['status'] == 'active' ? 'success' : 
                                        ($user['status'] == 'pending' ? 'warning' : 
                                        ($user['status'] == 'suspended' ? 'danger' : 'secondary')); 
                                ?> text-white rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                style="width: 35px; height: 35px;">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php if ($user['user_type'] == 'donor'): ?>
                                <span class="badge bg-info">Donor</span>
                            <?php elseif ($user['user_type'] == 'organizer'): ?>
                                <span class="badge bg-primary">Organizer</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Both</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status_colors = [
                                'active' => 'success',
                                'pending' => 'warning',
                                'suspended' => 'danger',
                                'rejected' => 'secondary'
                            ];
                            $color = $status_colors[$user['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?> rounded-pill">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold"><?php echo $user['campaign_count']; ?></span>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold"><?php echo $user['donation_count']; ?></span>
                        </td>
                        <td>
                            <span class="fw-bold text-success">$<?php echo number_format($user['total_donated'], 2); ?></span>
                        </td>
                       <td>
    <div class="btn-group">
        <a href="view.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
            <i class="fa-regular fa-eye"></i> View
        </a>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
            <i class="fa-regular fa-trash-can"></i> Delete
        </button>
    </div>
</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-green text-white">
                <h5 class="modal-title"><i class="fa-regular fa-user me-2"></i>User Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <div class="text-center py-4">
                    <i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2">Loading user details...</p>
                </div>
            </div>
        </div>
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
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// User Trend Chart
const trendCtx = document.getElementById('userTrendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($monthly_labels); ?>,
        datasets: [{
            label: 'New Users',
            data: <?php echo json_encode($monthly_counts); ?>,
            borderColor: '#2d6a4f',
            backgroundColor: 'rgba(46, 125, 50, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        }
    }
});

// User Type Chart
const typeCtx = document.getElementById('userTypeChart').getContext('2d');
new Chart(typeCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($type_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($type_counts); ?>,
            backgroundColor: ['#2d6a4f', '#17a2b8', '#ffc107'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// User Status Chart
const statusCtx = document.getElementById('userStatusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($status_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($status_counts); ?>,
            backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#6c757d'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// View user details
function viewUserDetails(id) {
    // Show loading in modal
    document.getElementById('userDetailsContent').innerHTML = '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">Loading user details...</p></div>';
    
    // Show the modal immediately with loading state
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    modal.show();
    
    // Fetch user details
    fetch('ajax/get_user_details.php?id=' + id)
        .then(response => response.text())
        .then(data => {
            document.getElementById('userDetailsContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('userDetailsContent').innerHTML = '<p class="text-danger text-center py-4">Error loading user details. Please try again.</p>';
        });
}

// Confirm delete
function confirmDelete(id, username) {
    document.getElementById('deleteUserName').textContent = username;
    document.getElementById('deleteUserId').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<style>
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

.bg-gradient-info { background: linear-gradient(135deg, #17a2b8, #00c6ff); }
.bg-gradient-success { background: linear-gradient(135deg, #28a745, #20c997); }
.bg-gradient-warning { background: linear-gradient(135deg, #ffc107, #fd7e14); }
.bg-gradient-primary { background: linear-gradient(135deg, #1a472a, #2d6a4f); }

.avatar-circle {
    font-weight: 600;
    font-size: 1rem;
}

.table th {
    font-weight: 600;
    color: #495057;
}

.table td {
    vertical-align: middle;
}

.btn-group {
    gap: 5px;
}

.modal-header.bg-green {
    background: linear-gradient(135deg, #1a472a, #2d6a4f);
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
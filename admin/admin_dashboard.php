<?php
// Start output buffering to prevent header errors
ob_start();

// Include database configuration first
require_once '../config.php';

// Check if admin is logged in - MUST be before any output
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Now include the admin header
require_once 'admin_header.php';

// Get current date for greetings
$hour = date('H');
$greeting = '';
if ($hour < 12) {
    $greeting = 'Good Morning';
} elseif ($hour < 17) {
    $greeting = 'Good Afternoon';
} else {
    $greeting = 'Good Evening';
}

// Fetch admin name
$admin_name = $_SESSION['admin_username'] ?? 'Admin';

// Get today's date range
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$this_month_start = date('Y-m-01');
$this_month_end = date('Y-m-t');

// Handle organizer approval/rejection
if (isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    try {
        if ($action == 'approve') {
            $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success'] = "Organizer approved successfully.";
        } elseif ($action == 'reject') {
            $stmt = $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success'] = "Organizer rejected.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating organizer status: " . $e->getMessage();
    }
    
    // Redirect to refresh the page
    header('Location: admin_dashboard.php');
    exit();
}

// Fetch statistics
try {
    // Total campaigns
    $total_campaigns = $pdo->query("SELECT COUNT(*) FROM campaigns")->fetchColumn();
    $pending_campaigns = $pdo->query("SELECT COUNT(*) FROM campaigns WHERE status = 'pending'")->fetchColumn();
    $approved_campaigns = $pdo->query("SELECT COUNT(*) FROM campaigns WHERE status = 'approved'")->fetchColumn();
    $rejected_campaigns = $pdo->query("SELECT COUNT(*) FROM campaigns WHERE status = 'rejected'")->fetchColumn();
    
    // Total users with user_type and status
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $new_users_today = $pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?");
    $new_users_today->execute([$today]);
    $new_users_today = $new_users_today->fetchColumn();
    
    // Organizer stats
    $pending_organizers = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type IN ('organizer', 'both') AND status = 'pending'")->fetchColumn();
    $total_organizers = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type IN ('organizer', 'both') AND status = 'active'")->fetchColumn();
    $total_donors = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'donor' AND status = 'active'")->fetchColumn();
    
    // Donation stats
    $total_donations = $pdo->query("SELECT COUNT(*) FROM donations")->fetchColumn();
    $total_donation_amount = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM donations WHERE status = 'completed'")->fetchColumn();
    $donations_today = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM donations WHERE DATE(created_at) = ? AND status = 'completed'");
    $donations_today->execute([$today]);
    $donations_today = $donations_today->fetchColumn();
    $pending_donations = $pdo->query("SELECT COUNT(*) FROM donations WHERE status = 'pending'")->fetchColumn();
    
    // Average donation
    $avg_donation = $pdo->query("SELECT COALESCE(AVG(amount), 0) FROM donations WHERE status = 'completed'")->fetchColumn();
    
    // Fetch pending organizers for approval
    $pending_organizers_list = $pdo->query("
        SELECT id, username, email, user_type, created_at 
        FROM users 
        WHERE user_type IN ('organizer', 'both') 
        AND status = 'pending' 
        ORDER BY created_at DESC
        LIMIT 10
    ")->fetchAll();
    
} catch (PDOException $e) {
    // Tables might not exist yet
    $total_campaigns = $pending_campaigns = $approved_campaigns = $rejected_campaigns = 0;
    $total_users = $new_users_today = $pending_organizers = $total_organizers = $total_donors = 0;
    $total_donations = $total_donation_amount = $donations_today = $pending_donations = $avg_donation = 0;
    $pending_organizers_list = [];
}

// Fetch monthly stats for chart
$monthly_labels = [];
$monthly_donations = [];
$monthly_campaigns = [];
$monthly_users = [];

for ($i = 5; $i >= 0; $i--) {
    $month = date('M', strtotime("-$i months"));
    $month_start = date('Y-m-01', strtotime("-$i months"));
    $month_end = date('Y-m-t', strtotime("-$i months"));
    
    $monthly_labels[] = $month;
    
    try {
        // Donations
        $donation_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM donations WHERE DATE(created_at) BETWEEN ? AND ? AND status = 'completed'");
        $donation_stmt->execute([$month_start, $month_end]);
        $monthly_donations[] = $donation_stmt->fetchColumn();
        
        // Campaigns
        $campaign_stmt = $pdo->prepare("SELECT COUNT(*) FROM campaigns WHERE DATE(created_at) BETWEEN ? AND ?");
        $campaign_stmt->execute([$month_start, $month_end]);
        $monthly_campaigns[] = $campaign_stmt->fetchColumn();
        
        // Users
        $user_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN ? AND ?");
        $user_stmt->execute([$month_start, $month_end]);
        $monthly_users[] = $user_stmt->fetchColumn();
    } catch (PDOException $e) {
        $monthly_donations[] = 0;
        $monthly_campaigns[] = 0;
        $monthly_users[] = 0;
    }
}

// Fetch recent campaigns
try {
    $recent_campaigns = $pdo->query("
        SELECT c.*, u.username, cat.name as category_name 
        FROM campaigns c 
        LEFT JOIN users u ON c.user_id = u.id 
        LEFT JOIN categories cat ON c.category_id = cat.id 
        ORDER BY c.created_at DESC 
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    $recent_campaigns = [];
}

// Fetch recent donations
try {
    $recent_donations = $pdo->query("
        SELECT d.*, c.title as campaign_title, u.username as donor_name 
        FROM donations d 
        LEFT JOIN campaigns c ON d.campaign_id = c.id 
        LEFT JOIN users u ON d.user_id = u.id 
        ORDER BY d.created_at DESC 
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    $recent_donations = [];
}

// Fetch top campaigns by donations
try {
    $top_campaigns = $pdo->query("
        SELECT 
            c.id, 
            c.title, 
            c.target_amount,
            COUNT(d.id) as donation_count,
            COALESCE(SUM(d.amount), 0) as raised_amount
        FROM campaigns c 
        LEFT JOIN donations d ON c.id = d.campaign_id AND d.status = 'completed'
        GROUP BY c.id 
        ORDER BY raised_amount DESC 
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    $top_campaigns = [];
}
?>

<!-- Display success/error messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-regular fa-circle-check me-2"></i>
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-regular fa-circle-exclamation me-2"></i>
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Welcome Section -->
<div class="welcome-section mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="display-6 fw-bold mb-2">
                <?php echo $greeting; ?>, <?php echo htmlspecialchars($admin_name); ?>! 👋
            </h1>
            <p class="text-muted mb-0">
                <i class="fa-regular fa-calendar me-2"></i>
                <?php echo date('l, F j, Y'); ?>
                <span class="mx-2">|</span>
                <i class="fa-regular fa-clock me-2"></i>
                <?php echo date('h:i A'); ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="admin_donations_chart.php" class="btn btn-primary rounded-pill px-4">
                <i class="fa-solid fa-chart-line me-2"></i>View Analytics
            </a>
            <a href="admin_campaigns.php" class="btn btn-outline-primary rounded-pill px-4">
                <i class="fa-solid fa-rocket me-2"></i>Manage Campaigns
            </a>
        </div>
    </div>
</div>

<!-- Quick Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-gradient-primary">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Total Donations</span>
                    <h3 class="stat-value"><?php echo number_format($total_donation_amount, 2); ?></h3>
                    <span class="stat-trend text-white-50">
                        <i class="fa-regular fa-circle-check me-1"></i>
                        <?php echo number_format($total_donations); ?> donations
                    </span>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                </div>
            </div>
            <div class="stat-footer mt-3">
                <div class="progress bg-white-20" style="height: 4px;">
                    <div class="progress-bar bg-white" style="width: 75%"></div>
                </div>
                <div class="d-flex justify-content-between mt-2 small">
                    <span>Today: <strong>$<?php echo number_format($donations_today, 2); ?></strong></span>
                    <span>Avg: <strong>$<?php echo number_format($avg_donation, 2); ?></strong></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-gradient-success">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Active Campaigns</span>
                    <h3 class="stat-value"><?php echo number_format($approved_campaigns); ?></h3>
                    <span class="stat-trend text-white-50">
                        <i class="fa-regular fa-clock me-1"></i>
                        <?php echo number_format($pending_campaigns); ?> pending
                    </span>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-rocket"></i>
                </div>
            </div>
            <div class="stat-footer mt-3">
                <div class="progress bg-white-20" style="height: 4px;">
                    <div class="progress-bar bg-white" style="width: <?php echo $total_campaigns ? ($approved_campaigns/$total_campaigns)*100 : 0; ?>%"></div>
                </div>
                <div class="d-flex justify-content-between mt-2 small">
                    <span>Total: <strong><?php echo number_format($total_campaigns); ?></strong></span>
                    <span>Rejected: <strong><?php echo number_format($rejected_campaigns); ?></strong></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-gradient-info">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Total Users</span>
                    <h3 class="stat-value"><?php echo number_format($total_users); ?></h3>
                    <span class="stat-trend text-white-50">
                        <i class="fa-regular fa-circle-check me-1"></i>
                        <?php echo number_format($new_users_today); ?> new today
                    </span>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="stat-footer mt-3">
                <div class="d-flex justify-content-between mt-2 small">
                    <span>Organizers: <strong><?php echo number_format($total_organizers); ?></strong></span>
                    <span>Donors: <strong><?php echo number_format($total_donors); ?></strong></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card bg-gradient-warning">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Pending Actions</span>
                    <h3 class="stat-value"><?php echo $pending_campaigns + $pending_donations + $pending_organizers; ?></h3>
                    <span class="stat-trend text-white-50">
                        <i class="fa-regular fa-clock me-1"></i>
                        Needs attention
                    </span>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-bell"></i>
                </div>
            </div>
            <div class="stat-footer mt-3">
                <div class="d-flex justify-content-between mt-2 small">
                    <span>Campaigns: <strong><?php echo $pending_campaigns; ?></strong></span>
                    <span>Organizers: <strong><?php echo $pending_organizers; ?></strong></span>
                </div>
                <div class="d-flex justify-content-between mt-1 small">
                    <span>Donations: <strong><?php echo $pending_donations; ?></strong></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Organizers Section -->
<?php if (count($pending_organizers_list) > 0): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-user-clock text-warning me-2"></i>
                        Pending Organizer Approvals
                        <span class="badge bg-warning ms-2"><?php echo count($pending_organizers_list); ?></span>
                    </h5>
                    <a href="admin_organizers.php" class="btn btn-sm btn-outline-primary rounded-pill">
                        View All <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Requested As</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_organizers_list as $organizer): ?>
                            <tr>
                                <td>
                                    <i class="fa-regular fa-circle-user text-primary me-2"></i>
                                    <strong><?php echo htmlspecialchars($organizer['username']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($organizer['email']); ?></td>
                                <td>
                                    <?php if ($organizer['user_type'] == 'both'): ?>
                                        <span class="badge bg-info">Organizer & Donor</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Organizer</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fa-regular fa-calendar me-1"></i>
                                    <?php echo date('M d, Y', strtotime($organizer['created_at'])); ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Approve this organizer?');">
                                        <input type="hidden" name="user_id" value="<?php echo $organizer['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">
                                            <i class="fa-regular fa-circle-check me-1"></i>Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Reject this organizer?');">
                                        <input type="hidden" name="user_id" value="<?php echo $organizer['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-3">
                                            <i class="fa-regular fa-circle-xmark me-1"></i>Reject
                                        </button>
                                    </form>
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="viewOrganizer(<?php echo $organizer['id']; ?>)">
                                        <i class="fa-regular fa-eye me-1"></i>View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- User Registration Chart Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-users text-primary me-2"></i>
                        User Registration Overview
                    </h5>
                    <a href="admin_user_chart.php" class="btn btn-green rounded-pill px-4">
                        <i class="fa-solid fa-chart-line me-2"></i>View Detailed User Chart
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-lg-8">
                        <canvas id="userChart" style="height: 300px;"></canvas>
                    </div>
                    <div class="col-lg-4">
                        <div class="user-stats p-3">
                            <h6 class="fw-bold mb-3">User Statistics</h6>
                            <div class="stat-item d-flex justify-content-between mb-2">
                                <span>Total Users:</span>
                                <strong class="text-green"><?php echo number_format($total_users); ?></strong>
                            </div>
                            <div class="stat-item d-flex justify-content-between mb-2">
                                <span>New Today:</span>
                                <strong class="text-green"><?php echo number_format($new_users_today); ?></strong>
                            </div>
                            <div class="stat-item d-flex justify-content-between mb-2">
                                <span>Donors:</span>
                                <strong class="text-green"><?php echo number_format($total_donors); ?></strong>
                            </div>
                            <div class="stat-item d-flex justify-content-between mb-2">
                                <span>Organizers:</span>
                                <strong class="text-green"><?php echo number_format($total_organizers); ?></strong>
                            </div>
                            <div class="stat-item d-flex justify-content-between mb-2">
                                <span>Pending Organizers:</span>
                                <strong class="text-warning"><?php echo number_format($pending_organizers); ?></strong>
                            </div>
                            <hr>
                            <div class="user-growth mt-3">
                                <h6 class="fw-bold mb-2">Growth Rate</h6>
                                <?php
                                // Calculate growth rate (compare last month with previous month)
                                $last_month_users = 0;
                                $prev_month_users = 0;
                                
                                if (count($monthly_users) >= 2) {
                                    $last_month_users = end($monthly_users);
                                    $prev_month_users = prev($monthly_users);
                                }
                                
                                $growth_rate = 0;
                                if ($prev_month_users > 0) {
                                    $growth_rate = round((($last_month_users - $prev_month_users) / $prev_month_users) * 100, 1);
                                }
                                ?>
                                <div class="d-flex align-items-center">
                                    <span class="display-6 fw-bold text-green me-2"><?php echo $growth_rate; ?>%</span>
                                    <?php if ($growth_rate > 0): ?>
                                        <span class="badge bg-success"><i class="fa-solid fa-arrow-up me-1"></i>Increase</span>
                                    <?php elseif ($growth_rate < 0): ?>
                                        <span class="badge bg-danger"><i class="fa-solid fa-arrow-down me-1"></i>Decrease</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No Change</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-muted small mt-2">Compared to previous month</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-chart-line text-primary me-2"></i>
                        Monthly Overview
                    </h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary active">Donations</button>
                        <button type="button" class="btn btn-sm btn-outline-primary">Campaigns</button>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <canvas id="monthlyChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-trophy text-primary me-2"></i>
                    Top Campaigns
                </h5>
            </div>
            <div class="card-body p-4">
                <?php if (count($top_campaigns) > 0): ?>
                    <?php foreach ($top_campaigns as $index => $camp): ?>
                        <div class="top-campaign-item d-flex align-items-center mb-3">
                            <div class="rank-circle bg-<?php 
                                echo $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'light'); 
                            ?> me-3">
                                <?php echo $index + 1; ?>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars(substr($camp['title'], 0, 30)); ?>...</h6>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">Raised: $<?php echo number_format($camp['raised_amount'], 2); ?></span>
                                    <span class="text-primary"><?php echo $camp['donation_count']; ?> donors</span>
                                </div>
                                <div class="progress mt-1" style="height: 4px;">
                                    <?php $progress = ($camp['raised_amount'] / $camp['target_amount']) * 100; ?>
                                    <div class="progress-bar bg-success" style="width: <?php echo min($progress, 100); ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No campaign data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities Row -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-rocket text-primary me-2"></i>
                        Recent Campaigns
                    </h5>
                    <a href="admin_campaigns.php" class="btn btn-sm btn-outline-primary rounded-pill">
                        View All <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if (count($recent_campaigns) > 0): ?>
                    <?php foreach ($recent_campaigns as $campaign): ?>
                        <div class="activity-item d-flex align-items-start mb-3 pb-3 border-bottom">
                            <div class="activity-icon bg-light rounded-3 p-2 me-3">
                                <i class="fa-solid fa-rocket text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($campaign['title']); ?></h6>
                                        <p class="small text-muted mb-1">
                                            <i class="fa-regular fa-user me-1"></i>
                                            <?php echo htmlspecialchars($campaign['username'] ?? 'Unknown'); ?>
                                            <span class="mx-2">|</span>
                                            <i class="fa-regular fa-tag me-1"></i>
                                            <?php echo htmlspecialchars($campaign['category_name'] ?? 'Uncategorized'); ?>
                                        </p>
                                    </div>
                                    <?php
                                    $badge_class = '';
                                    $badge_text = $campaign['status'];
                                    if ($campaign['status'] == 'approved') {
                                        $badge_class = 'success';
                                    } elseif ($campaign['status'] == 'pending') {
                                        $badge_class = 'warning';
                                    } elseif ($campaign['status'] == 'rejected') {
                                        $badge_class = 'danger';
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?> rounded-pill">
                                        <?php echo ucfirst($badge_text); ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fa-regular fa-clock me-1"></i>
                                        <?php echo timeAgo($campaign['created_at']); ?>
                                    </small>
                                    <a href="../campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-link p-0" target="_blank">
                                        View <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No recent campaigns</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-hand-holding-heart text-primary me-2"></i>
                        Recent Donations
                    </h5>
                    <a href="admin_donations.php" class="btn btn-sm btn-outline-primary rounded-pill">
                        View All <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if (count($recent_donations) > 0): ?>
                    <?php foreach ($recent_donations as $donation): ?>
                        <div class="activity-item d-flex align-items-start mb-3 pb-3 border-bottom">
                            <div class="activity-icon bg-light rounded-3 p-2 me-3">
                                <i class="fa-solid fa-hand-holding-heart text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold mb-1">
                                            $<?php echo number_format($donation['amount'], 2); ?> 
                                            <span class="fw-normal text-muted">to</span>
                                            <?php echo htmlspecialchars(substr($donation['campaign_title'], 0, 30)); ?>...
                                        </h6>
                                        <p class="small text-muted mb-1">
                                            <i class="fa-regular fa-user me-1"></i>
                                            <?php echo $donation['is_anonymous'] ? 'Anonymous' : htmlspecialchars($donation['donor_name'] ?? 'Unknown'); ?>
                                            <span class="mx-2">|</span>
                                            <i class="fa-regular fa-credit-card me-1"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $donation['payment_method'] ?? 'Unknown')); ?>
                                        </p>
                                    </div>
                                    <?php if ($donation['status'] == 'pending'): ?>
                                        <span class="badge bg-warning rounded-pill">Pending</span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fa-regular fa-clock me-1"></i>
                                        <?php echo timeAgo($donation['created_at']); ?>
                                    </small>
                                    <button class="btn btn-sm btn-link p-0" onclick="viewDonation(<?php echo $donation['id']; ?>)">
                                        Details <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No recent donations</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions mt-4">
    <div class="row g-3">
        <div class="col-md-3">
            <a href="admin_campaigns.php?status=pending" class="action-card">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <i class="fa-regular fa-clock fa-2x text-warning mb-3"></i>
                        <h6 class="fw-bold mb-1">Pending Campaigns</h6>
                        <p class="text-muted small mb-0"><?php echo $pending_campaigns; ?> campaigns need review</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="admin_organizers.php" class="action-card">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <i class="fa-regular fa-user-clock fa-2x text-info mb-3"></i>
                        <h6 class="fw-bold mb-1">Pending Organizers</h6>
                        <p class="text-muted small mb-0"><?php echo $pending_organizers; ?> organizers need approval</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="admin_donations.php?status=pending" class="action-card">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <i class="fa-regular fa-bell fa-2x text-info mb-3"></i>
                        <h6 class="fw-bold mb-1">Pending Donations</h6>
                        <p class="text-muted small mb-0"><?php echo $pending_donations; ?> donations pending</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="admin_settings.php" class="action-card">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <i class="fa-regular fa-gear fa-2x text-secondary mb-3"></i>
                        <h6 class="fw-bold mb-1">Settings</h6>
                        <p class="text-muted small mb-0">Configure platform</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Helper function for time ago -->
<?php
function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "Just Now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "yesterday" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}
?>

<style>
/* Dashboard Styles */
.welcome-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
}

/* Stat Cards */
.stat-card {
    border-radius: 20px;
    padding: 1.5rem;
    color: white;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    transform: rotate(30deg);
    transition: all 0.5s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.2) !important;
}

.stat-card:hover::before {
    transform: rotate(30deg) scale(1.2);
}

.stat-icon {
    opacity: 0.3;
    font-size: 3rem;
    transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.1) rotate(5deg);
    opacity: 0.5;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    margin: 0.5rem 0 0.2rem;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
}

.stat-trend {
    font-size: 0.85rem;
    display: block;
}

/* Gradient backgrounds */
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #00c6ff 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
}

.bg-white-20 {
    background-color: rgba(255,255,255,0.2);
}

/* User Stats Styles */
.user-stats {
    background: #f8f9fa;
    border-radius: 16px;
    padding: 1.5rem;
    height: 100%;
}

.user-stats .stat-item {
    padding: 0.5rem 0;
    border-bottom: 1px dashed rgba(0,0,0,0.1);
}

.user-stats .stat-item:last-child {
    border-bottom: none;
}

.user-stats .text-green {
    color: #2d6a4f;
    font-weight: 600;
}

.user-growth {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
}

/* Button style */
.btn-green {
    background: linear-gradient(135deg, #1a472a, #2d6a4f);
    border: none;
    color: white;
    transition: all 0.3s ease;
}

.btn-green:hover {
    background: linear-gradient(135deg, #2d6a4f, #40916c);
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(46, 125, 50, 0.4);
    color: white;
}

/* Activity Items */
.activity-item {
    transition: all 0.3s ease;
}

.activity-item:hover {
    transform: translateX(5px);
    background: #f8f9fa;
    border-radius: 10px;
    padding-left: 10px !important;
}

.activity-icon {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Top Campaigns */
.top-campaign-item {
    padding: 0.5rem;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.top-campaign-item:hover {
    background: #f8f9fa;
}

.rank-circle {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* Quick Actions */
.action-card {
    text-decoration: none;
    color: inherit;
    display: block;
    transition: all 0.3s ease;
}

.action-card:hover {
    transform: translateY(-5px);
}

.action-card .card {
    transition: all 0.3s ease;
}

.action-card:hover .card {
    box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important;
    border-left: 4px solid #00c6ff !important;
}

/* Dark mode */
body.dark-mode .welcome-section {
    background: linear-gradient(135deg, #2d2d2d 0%, #1e1e1e 100%);
}

body.dark-mode .activity-item:hover {
    background: #2d2d2d;
}

body.dark-mode .top-campaign-item:hover {
    background: #2d2d2d;
}

body.dark-mode .bg-light {
    background: #2d2d2d !important;
}

body.dark-mode .text-muted {
    color: #aaa !important;
}

body.dark-mode .user-stats {
    background: #2d2d2d;
}

body.dark-mode .user-stats .stat-item {
    border-bottom-color: #444;
}

body.dark-mode .user-growth {
    background: #1e1e1e;
}

/* Responsive */
@media (max-width: 768px) {
    .stat-value {
        font-size: 1.8rem;
    }
    
    .welcome-section {
        padding: 1.5rem;
    }
    
    .welcome-section h1 {
        font-size: 1.5rem;
    }
}
</style>

<!-- Chart Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Monthly Chart
const ctx = document.getElementById('monthlyChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($monthly_labels); ?>,
        datasets: [{
            label: 'Donations ($)',
            data: <?php echo json_encode($monthly_donations); ?>,
            borderColor: 'rgba(0, 198, 255, 1)',
            backgroundColor: 'rgba(0, 198, 255, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y'
        }, {
            label: 'New Campaigns',
            data: <?php echo json_encode($monthly_campaigns); ?>,
            borderColor: 'rgba(40, 167, 69, 1)',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                display: true,
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) label += ': ';
                        if (context.dataset.label.includes('Donations')) {
                            label += '$' + context.raw.toFixed(2);
                        } else {
                            label += context.raw;
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                ticks: {
                    callback: function(value) {
                        return '$' + value;
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                },
            },
        }
    }
});

// User Registration Chart
const userCtx = document.getElementById('userChart').getContext('2d');
new Chart(userCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($monthly_labels); ?>,
        datasets: [{
            label: 'New Users',
            data: <?php echo json_encode($monthly_users); ?>,
            backgroundColor: 'rgba(46, 125, 50, 0.7)',
            borderColor: '#2d6a4f',
            borderWidth: 2,
            borderRadius: 5,
            barPercentage: 0.7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.raw + ' new users';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 5,
                    callback: function(value) {
                        return value + ' users';
                    }
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// View donation details
function viewDonation(id) {
    window.location.href = 'admin_donations.php?highlight=' + id;
}

// View organizer details
function viewOrganizer(id) {
    window.location.href = 'admin_organizers.php?view=' + id;
}
</script>

<?php
require_once 'admin_footer.php';
ob_end_flush();
?>
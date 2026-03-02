<?php
// Start output buffering
ob_start();

// Include database configuration first
require_once '../config.php';

// Now include the admin header
require_once 'admin_header.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Handle status update
if (isset($_POST['update_status'])) {
    $donation_id = intval($_POST['donation_id']);
    $new_status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE donations SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $donation_id])) {
            $_SESSION['success'] = "Donation status updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update donation status.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    header('Location: admin_donations.php');
    exit();
}

// Handle donation deletion
if (isset($_POST['delete_donation'])) {
    $donation_id = intval($_POST['donation_id']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM donations WHERE id = ?");
        if ($stmt->execute([$donation_id])) {
            $_SESSION['success'] = "Donation deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete donation.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    header('Location: admin_donations.php');
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$payment_filter = isset($_GET['payment_method']) ? $_GET['payment_method'] : 'all';
$campaign_filter = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
$offset = ($page - 1) * $per_page;

// Build the base query (without LIMIT)
$base_sql = "SELECT d.*, 
        c.title as campaign_title, 
        c.user_id as campaign_owner_id,
        u.username as donor_username,
        creator.username as creator_name,
        creator.email as creator_email
        FROM donations d 
        LEFT JOIN campaigns c ON d.campaign_id = c.id 
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN users creator ON c.user_id = creator.id
        WHERE 1=1";

// We'll use bindValue approach, so we don't need to build params array
// Instead we'll track which parameters we need to bind

$has_status_filter = ($status_filter != 'all');
$has_payment_filter = ($payment_filter != 'all');
$has_campaign_filter = ($campaign_filter > 0);
$has_search = !empty($search);

// Build the SQL with conditions
if ($has_status_filter) {
    $base_sql .= " AND d.status = ?";
}
if ($has_payment_filter) {
    $base_sql .= " AND d.payment_method = ?";
}
if ($has_campaign_filter) {
    $base_sql .= " AND d.campaign_id = ?";
}
$base_sql .= " AND DATE(d.created_at) BETWEEN ? AND ?";
if ($has_search) {
    $base_sql .= " AND (d.donor_name LIKE ? OR d.donor_email LIKE ? OR d.transaction_id LIKE ? OR c.title LIKE ?)";
}
$base_sql .= " ORDER BY d.created_at DESC";

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM donations d 
              LEFT JOIN campaigns c ON d.campaign_id = c.id 
              WHERE 1=1";

if ($has_status_filter) {
    $count_sql .= " AND d.status = ?";
}
if ($has_payment_filter) {
    $count_sql .= " AND d.payment_method = ?";
}
if ($has_campaign_filter) {
    $count_sql .= " AND d.campaign_id = ?";
}
$count_sql .= " AND DATE(d.created_at) BETWEEN ? AND ?";
if ($has_search) {
    $count_sql .= " AND (d.donor_name LIKE ? OR d.donor_email LIKE ? OR d.transaction_id LIKE ? OR c.title LIKE ?)";
}

$count_stmt = $pdo->prepare($count_sql);

// Bind parameters for count query
$param_index = 1;
if ($has_status_filter) {
    $count_stmt->bindValue($param_index++, $status_filter, PDO::PARAM_STR);
}
if ($has_payment_filter) {
    $count_stmt->bindValue($param_index++, $payment_filter, PDO::PARAM_STR);
}
if ($has_campaign_filter) {
    $count_stmt->bindValue($param_index++, $campaign_filter, PDO::PARAM_INT);
}
$count_stmt->bindValue($param_index++, $date_from, PDO::PARAM_STR);
$count_stmt->bindValue($param_index++, $date_to, PDO::PARAM_STR);
if ($has_search) {
    $search_term = "%$search%";
    $count_stmt->bindValue($param_index++, $search_term, PDO::PARAM_STR);
    $count_stmt->bindValue($param_index++, $search_term, PDO::PARAM_STR);
    $count_stmt->bindValue($param_index++, $search_term, PDO::PARAM_STR);
    $count_stmt->bindValue($param_index++, $search_term, PDO::PARAM_STR);
}

$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Prepare main query with LIMIT
$sql = $base_sql . " LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);

// Bind parameters for main query
$param_index = 1;
if ($has_status_filter) {
    $stmt->bindValue($param_index++, $status_filter, PDO::PARAM_STR);
}
if ($has_payment_filter) {
    $stmt->bindValue($param_index++, $payment_filter, PDO::PARAM_STR);
}
if ($has_campaign_filter) {
    $stmt->bindValue($param_index++, $campaign_filter, PDO::PARAM_INT);
}
$stmt->bindValue($param_index++, $date_from, PDO::PARAM_STR);
$stmt->bindValue($param_index++, $date_to, PDO::PARAM_STR);
if ($has_search) {
    $search_term = "%$search%";
    $stmt->bindValue($param_index++, $search_term, PDO::PARAM_STR);
    $stmt->bindValue($param_index++, $search_term, PDO::PARAM_STR);
    $stmt->bindValue($param_index++, $search_term, PDO::PARAM_STR);
    $stmt->bindValue($param_index++, $search_term, PDO::PARAM_STR);
}
// CRITICAL: Bind limit and offset as integers
$stmt->bindValue($param_index++, (int)$per_page, PDO::PARAM_INT);
$stmt->bindValue($param_index++, (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_donations,
    COALESCE(SUM(amount), 0) as total_amount,
    COALESCE(AVG(amount), 0) as avg_amount,
    COUNT(DISTINCT donor_email) as unique_donors,
    COUNT(DISTINCT campaign_id) as campaigns_supported
    FROM donations WHERE 1=1";

$stats_params = [];

if ($status_filter != 'all') {
    $stats_sql .= " AND status = ?";
    $stats_params[] = $status_filter;
}

if ($payment_filter != 'all') {
    $stats_sql .= " AND payment_method = ?";
    $stats_params[] = $payment_filter;
}

if ($campaign_filter > 0) {
    $stats_sql .= " AND campaign_id = ?";
    $stats_params[] = $campaign_filter;
}

$stats_sql .= " AND DATE(created_at) BETWEEN ? AND ?";
$stats_params[] = $date_from;
$stats_params[] = $date_to;

if (!empty($search)) {
    $stats_sql .= " AND (donor_name LIKE ? OR donor_email LIKE ?)";
    $search_term = "%$search%";
    $stats_params[] = $search_term;
    $stats_params[] = $search_term;
}

$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($stats_params);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get all campaigns for filter dropdown
$campaigns = $pdo->query("SELECT id, title FROM campaigns ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get payment method summary
$payment_summary_sql = "SELECT 
    payment_method,
    COUNT(*) as count,
    COALESCE(SUM(amount), 0) as total
    FROM donations 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY payment_method";
$payment_summary_stmt = $pdo->prepare($payment_summary_sql);
$payment_summary_stmt->execute([$date_from, $date_to]);
$payment_summary = $payment_summary_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle success/error messages
$success_message = $_SESSION['success'] ?? '';
$error_message = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-hand-holding-heart me-2"></i>Donation Management</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Donations</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="admin_donations_chart.php" class="btn btn-primary rounded-pill me-2">
            <i class="fa-solid fa-chart-line me-2"></i>View Analytics
        </a>
        <button onclick="exportToExcel()" class="btn btn-success rounded-pill">
            <i class="fa-solid fa-file-excel me-2"></i>Export to Excel
        </button>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fa-solid fa-circle-check fs-5 me-2"></i>
            <span><?php echo $success_message; ?></span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-start">
            <i class="fa-solid fa-circle-exclamation fs-5 me-2 mt-1"></i>
            <span><?php echo $error_message; ?></span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white-50 mb-2">Total Donations</h6>
                    <h3 class="fw-bold mb-0"><?php echo number_format($stats['total_donations']); ?></h3>
                    <small class="text-white-50">In selected period</small>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-hand-holding-heart fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-success text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white-50 mb-2">Total Amount</h6>
                    <h3 class="fw-bold mb-0">$<?php echo number_format($stats['total_amount'], 2); ?></h3>
                    <small class="text-white-50">Avg: $<?php echo number_format($stats['avg_amount'], 2); ?></small>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-dollar-sign fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-info text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white-50 mb-2">Unique Donors</h6>
                    <h3 class="fw-bold mb-0"><?php echo number_format($stats['unique_donors']); ?></h3>
                    <small class="text-white-50">Individual donors</small>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-users fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-warning text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-white-50 mb-2">Campaigns Supported</h6>
                    <h3 class="fw-bold mb-0"><?php echo number_format($stats['campaigns_supported']); ?></h3>
                    <small class="text-white-50">Unique campaigns</small>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-rocket fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Method Summary -->
<div class="row g-4 mb-4">
    <?php foreach ($payment_summary as $payment): ?>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-3 text-center">
                <i class="fa-solid fa-<?php 
                    echo $payment['payment_method'] == 'bkash' ? 'credit-card' : 
                        ($payment['payment_method'] == 'nagad' ? 'credit-card' : 
                        ($payment['payment_method'] == 'islamic_bank' ? 'building-columns' : 
                        ($payment['payment_method'] == 'rocket' ? 'rocket' : 'building-columns'))); 
                ?> fa-2x text-primary mb-2"></i>
                <h6 class="fw-bold mb-1"><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></h6>
                <p class="small text-muted mb-0"><?php echo $payment['count']; ?> donations</p>
                <p class="fw-bold text-success mb-0">$<?php echo number_format($payment['total'], 2); ?></p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter Form -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="1">
            <div class="col-md-2">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="failed" <?php echo $status_filter == 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Payment Method</label>
                <select name="payment_method" class="form-select">
                    <option value="all" <?php echo $payment_filter == 'all' ? 'selected' : ''; ?>>All Methods</option>
                    <option value="bkash" <?php echo $payment_filter == 'bkash' ? 'selected' : ''; ?>>bKash</option>
                    <option value="nagad" <?php echo $payment_filter == 'nagad' ? 'selected' : ''; ?>>Nagad</option>
                    <option value="islamic_bank" <?php echo $payment_filter == 'islamic_bank' ? 'selected' : ''; ?>>Islamic Bank</option>
                    <option value="rocket" <?php echo $payment_filter == 'rocket' ? 'selected' : ''; ?>>Rocket</option>
                    <option value="bank_transfer" <?php echo $payment_filter == 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Campaign</label>
                <select name="campaign_id" class="form-select">
                    <option value="0">All Campaigns</option>
                    <?php foreach ($campaigns as $camp): ?>
                        <option value="<?php echo $camp['id']; ?>" <?php echo $campaign_filter == $camp['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(substr($camp['title'], 0, 30)) . '...'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, email, transaction..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-12 text-end">
                <a href="admin_donations.php" class="btn btn-secondary rounded-pill me-2">
                    <i class="fa-solid fa-undo me-2"></i>Reset
                </a>
                <button type="submit" class="btn btn-primary rounded-pill">
                    <i class="fa-solid fa-filter me-2"></i>Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Donations Table -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="card-title mb-0">
                <i class="fa-solid fa-list text-primary me-2"></i>
                Donation History
                <span class="badge bg-primary ms-2"><?php echo $total_records; ?> total records</span>
            </h5>
            <div>
                <span class="text-muted me-2">Show:</span>
                <select class="form-select d-inline-block w-auto" onchange="changePerPage(this.value)">
                    <option value="25" <?php echo $per_page == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                    <option value="200" <?php echo $per_page == 200 ? 'selected' : ''; ?>>200</option>
                </select>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="donationsTable">
                <thead class="bg-light">
                    <tr>
                        <th>ID</th>
                        <th>Date & Time</th>
                        <th>Donor Information</th>
                        <th>Campaign</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Transaction ID</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($donations) > 0): ?>
                        <?php foreach ($donations as $donation): ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold">#<?php echo $donation['id']; ?></span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold"><?php echo date('M d, Y', strtotime($donation['created_at'])); ?></span>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($donation['created_at'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="donor-avatar bg-light rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px;">
                                            <i class="fa-regular fa-user text-primary"></i>
                                        </div>
                                        <div>
                                            <strong>
                                                <?php if ($donation['is_anonymous']): ?>
                                                    <span class="text-muted"><i>Anonymous Donor</i></span>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($donation['donor_name']); ?>
                                                <?php endif; ?>
                                            </strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fa-regular fa-envelope me-1"></i>
                                                <?php echo htmlspecialchars($donation['donor_email']); ?>
                                            </small>
                                            <?php if (!empty($donation['phone_number'])): ?>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fa-regular fa-phone me-1"></i>
                                                    <?php echo htmlspecialchars($donation['phone_number']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <a href="../campaign.php?id=<?php echo $donation['campaign_id']; ?>" target="_blank" 
                                           class="fw-semibold text-decoration-none">
                                            <?php echo htmlspecialchars(substr($donation['campaign_title'], 0, 30)) . '...'; ?>
                                        </a>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fa-regular fa-user me-1"></i>
                                            by <?php echo htmlspecialchars($donation['creator_name']); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-success fs-5">$<?php echo number_format($donation['amount'], 2); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $method_icons = [
                                        'bkash' => 'fa-regular fa-credit-card',
                                        'nagad' => 'fa-regular fa-credit-card',
                                        'islamic_bank' => 'fa-regular fa-building-columns',
                                        'rocket' => 'fa-solid fa-rocket',
                                        'bank_transfer' => 'fa-regular fa-building-columns'
                                    ];
                                    $icon = $method_icons[$donation['payment_method']] ?? 'fa-regular fa-circle';
                                    ?>
                                    <span class="badge bg-light text-dark py-2 px-3">
                                        <i class="<?php echo $icon; ?> me-1"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $donation['payment_method'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="font-monospace"><?php echo $donation['transaction_id'] ?: 'N/A'; ?></small>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="donation_id" value="<?php echo $donation['id']; ?>">
                                        <select name="status" class="form-select form-select-sm status-select" 
                                                onchange="this.form.submit()" style="width: auto;">
                                            <option value="completed" <?php echo $donation['status'] == 'completed' ? 'selected' : ''; ?>>
                                                Completed
                                            </option>
                                            <option value="pending" <?php echo $donation['status'] == 'pending' ? 'selected' : ''; ?>>
                                                Pending
                                            </option>
                                            <option value="failed" <?php echo $donation['status'] == 'failed' ? 'selected' : ''; ?>>
                                                Failed
                                            </option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary rounded-circle" 
                                                onclick="viewDonation(<?php echo $donation['id']; ?>)"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewDonationModal"
                                                title="View Details">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info rounded-circle" 
                                                onclick="sendReceipt(<?php echo $donation['id']; ?>)"
                                                title="Send Receipt">
                                            <i class="fa-regular fa-envelope"></i>
                                        </button>
                                        <form method="POST" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this donation?');">
                                            <input type="hidden" name="donation_id" value="<?php echo $donation['id']; ?>">
                                            <button type="submit" name="delete_donation" 
                                                    class="btn btn-sm btn-outline-danger rounded-circle"
                                                    title="Delete">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="empty-state-icon mb-3">
                                    <i class="fa-solid fa-hand-holding-heart fa-3x text-muted"></i>
                                </div>
                                <h6 class="text-muted mb-2">No donations found</h6>
                                <p class="text-muted small">Try adjusting your filters or date range</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <!-- Previous button -->
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?<?php 
                        $params = $_GET;
                        $params['page'] = $page - 1;
                        echo http_build_query($params); 
                    ?>">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                </li>
                
                <!-- Page numbers -->
                <?php 
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                if ($start > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php 
                            $params = $_GET;
                            $params['page'] = 1;
                            echo http_build_query($params); 
                        ?>">1</a>
                    </li>
                    <?php if ($start > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php 
                            $params = $_GET;
                            $params['page'] = $i;
                            echo http_build_query($params); 
                        ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($end < $total_pages): ?>
                    <?php if ($end < $total_pages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php 
                            $params = $_GET;
                            $params['page'] = $total_pages;
                            echo http_build_query($params); 
                        ?>">
                            <?php echo $total_pages; ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Next button -->
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?<?php 
                        $params = $_GET;
                        $params['page'] = $page + 1;
                        echo http_build_query($params); 
                    ?>">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Showing results -->
        <div class="text-center text-muted small mt-2">
            Showing <?php echo min($offset + 1, $total_records); ?> to <?php echo min($offset + $per_page, $total_records); ?> of <?php echo $total_records; ?> donations
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Donation Modal -->
<div class="modal fade" id="viewDonationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Donation Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="donationDetails">
                Loading...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Donations Page Styles */
.stat-card {
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.2) !important;
}

.stat-icon {
    opacity: 0.3;
    transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.1) rotate(5deg);
    opacity: 0.5;
}

.bg-primary { background: linear-gradient(135deg, #141e30, #243b55) !important; }
.bg-success { background: linear-gradient(135deg, #28a745, #20c997) !important; }
.bg-info { background: linear-gradient(135deg, #17a2b8, #00c6ff) !important; }
.bg-warning { background: linear-gradient(135deg, #ffc107, #fd7e14) !important; }

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom-width: 2px;
}

.table td {
    vertical-align: middle;
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.status-select {
    padding: 0.25rem 1.5rem 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 20px;
}

/* Pagination */
.pagination .page-link {
    border: none;
    padding: 0.5rem 1rem;
    color: #141e30;
    border-radius: 8px;
    margin: 0 3px;
    transition: all 0.3s ease;
}

.pagination .page-link:hover {
    background: linear-gradient(135deg, #141e30, #243b55);
    color: white;
    transform: translateY(-2px);
}

.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #141e30, #243b55);
    color: white;
}

.pagination .page-item.disabled .page-link {
    color: #aaa;
    background: transparent;
    pointer-events: none;
}

/* Dark mode adjustments */
body.dark-mode .table {
    color: #ddd;
}

body.dark-mode .table th {
    color: #fff;
    border-bottom-color: #444;
    background: #2d2d2d;
}

body.dark-mode .table td {
    border-color: #333;
}

body.dark-mode .bg-light {
    background: #2d2d2d !important;
}

body.dark-mode .empty-state-icon {
    background: #2d2d2d;
}

body.dark-mode .text-muted {
    color: #aaa !important;
}

body.dark-mode .pagination .page-link {
    background: #2d2d2d;
    color: #ddd;
}

body.dark-mode .pagination .page-item.disabled .page-link {
    background: #1e1e1e;
    color: #666;
}

/* Responsive */
@media (max-width: 768px) {
    .stat-card {
        padding: 1rem;
    }
    
    .stat-card h3 {
        font-size: 1.5rem;
    }
    
    .table {
        font-size: 0.9rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
    }
}
</style>

<script>
// View donation details
function viewDonation(id) {
    document.getElementById('donationDetails').innerHTML = '<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">Loading...</p></div>';
    
    fetch('ajax/get_donation.php?id=' + id)
        .then(response => response.text())
        .then(data => {
            document.getElementById('donationDetails').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('donationDetails').innerHTML = '<p class="text-danger text-center py-4">Error loading donation details.</p>';
        });
}

// Send receipt
function sendReceipt(id) {
    if (confirm('Send donation receipt to donor email?')) {
        fetch('ajax/send_receipt.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({donation_id: id})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Receipt sent successfully!');
            } else {
                alert('Failed to send receipt: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error sending receipt. Please try again.');
        });
    }
}

// Change items per page
function changePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', 1); // Reset to first page
    window.location.href = url.toString();
}

// Export to Excel
function exportToExcel() {
    const table = document.getElementById('donationsTable');
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    // Get headers (excluding Actions column)
    const headers = [];
    table.querySelectorAll('thead th').forEach((th, index) => {
        if (index < 8) { // Exclude Actions column
            headers.push('"' + th.textContent.trim() + '"');
        }
    });
    csv.push(headers.join(','));
    
    // Get data rows
    rows.forEach(row => {
        const rowData = [];
        row.querySelectorAll('td').forEach((td, index) => {
            if (index < 8) { // Exclude Actions column
                let text = td.textContent.trim().replace(/"/g, '""'); // Escape quotes
                rowData.push('"' + text + '"');
            }
        });
        if (rowData.length > 0) {
            csv.push(rowData.join(','));
        }
    });
    
    // Download CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'donations_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php
require_once 'admin_footer.php';
ob_end_flush();
?>
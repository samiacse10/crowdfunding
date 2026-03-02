<?php
// Correct the path to config.php - go up two levels
require_once '../../config.php';

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo '<div class="alert alert-danger">Unauthorized access</div>';
    exit();
}

// Get donation ID from request
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    http_response_code(400);
    echo '<div class="alert alert-danger">Invalid donation ID</div>';
    exit();
}

try {
    // Fetch donation details with related information
    $stmt = $pdo->prepare("
        SELECT 
            d.*,
            c.id as campaign_id,
            c.title as campaign_title,
            c.description as campaign_description,
            c.target_amount as campaign_target,
            c.status as campaign_status,
            c.created_at as campaign_created,
            u.id as donor_user_id,
            u.username as donor_username,
            u.email as donor_user_email,
            u.created_at as donor_member_since,
            creator.id as creator_id,
            creator.username as creator_name,
            creator.email as creator_email,
            cat.name as category_name
        FROM donations d 
        LEFT JOIN campaigns c ON d.campaign_id = c.id 
        LEFT JOIN users u ON d.user_id = u.id
        LEFT JOIN users creator ON c.user_id = creator.id
        LEFT JOIN categories cat ON c.category_id = cat.id
        WHERE d.id = ?
    ");
    
    $stmt->execute([$id]);
    $donation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$donation) {
        echo '<div class="alert alert-warning">Donation not found</div>';
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Database error in get_donation.php: " . $e->getMessage());
    echo '<div class="alert alert-danger">Database error occurred: ' . $e->getMessage() . '</div>';
    exit();
}
?>

<!-- Donation Details Modal Content -->
<div class="container-fluid">
    <!-- Header with Status -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="modal-title">Donation Details #<?php echo str_pad($donation['id'], 5, '0', STR_PAD_LEFT); ?></h5>
        <?php
        $status_class = '';
        $status_text = '';
        
        switch($donation['status']) {
            case 'completed':
                $status_class = 'success';
                $status_text = 'Completed';
                break;
            case 'pending':
                $status_class = 'warning';
                $status_text = 'Pending';
                break;
            case 'failed':
                $status_class = 'danger';
                $status_text = 'Failed';
                break;
            default:
                $status_class = 'secondary';
                $status_text = $donation['status'];
        }
        ?>
        <span class="badge bg-<?php echo $status_class; ?> fs-6 p-2">
            <i class="fa-regular <?php echo $status_class == 'success' ? 'fa-circle-check' : ($status_class == 'warning' ? 'fa-clock' : 'fa-circle-xmark'); ?> me-1"></i>
            <?php echo $status_text; ?>
        </span>
    </div>
    
    <div class="row">
        <!-- Left Column - Donation Info -->
        <div class="col-md-6">
            <div class="card border-0 bg-light mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="fa-solid fa-circle-info text-primary me-2"></i>
                        Payment Information
                    </h6>
                    
                    <table class="table table-sm">
                        <tr>
                            <th style="width: 40%;">Amount:</th>
                            <td class="fw-bold text-success fs-5">$<?php echo number_format($donation['amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Payment Method:</th>
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
                        </tr>
                        <tr>
                            <th>Transaction ID:</th>
                            <td>
                                <span class="font-monospace bg-dark text-light p-1 rounded">
                                    <?php echo $donation['transaction_id'] ?: 'N/A'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php if (!empty($donation['phone_number'])): ?>
                        <tr>
                            <th>Phone Number:</th>
                            <td>
                                <i class="fa-regular fa-phone me-1"></i>
                                <?php echo htmlspecialchars($donation['phone_number']); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            
            <!-- Donor Message if exists -->
            <?php if (!empty($donation['message'])): ?>
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">
                        <i class="fa-regular fa-message text-primary me-2"></i>
                        Donor Message
                    </h6>
                    <div class="p-3 bg-white rounded-3">
                        <i class="fa-regular fa-quote-left text-muted me-1"></i>
                        <?php echo nl2br(htmlspecialchars($donation['message'])); ?>
                        <i class="fa-regular fa-quote-right text-muted ms-1"></i>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column - Donor & Campaign Info -->
        <div class="col-md-6">
            <!-- Donor Information -->
            <div class="card border-0 bg-light mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="fa-regular fa-user text-primary me-2"></i>
                        Donor Information
                    </h6>
                    
                    <table class="table table-sm">
                        <tr>
                            <th style="width: 40%;">Name:</th>
                            <td>
                                <?php if ($donation['is_anonymous']): ?>
                                    <span class="text-muted">
                                        <i class="fa-regular fa-eye-slash me-1"></i>
                                        Anonymous Donor
                                    </span>
                                <?php else: ?>
                                    <strong><?php echo htmlspecialchars($donation['donor_name']); ?></strong>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>
                                <?php if ($donation['is_anonymous']): ?>
                                    <span class="text-muted">
                                        <i class="fa-regular fa-eye-slash me-1"></i>
                                        Hidden
                                    </span>
                                <?php else: ?>
                                    <a href="mailto:<?php echo htmlspecialchars($donation['donor_email']); ?>">
                                        <i class="fa-regular fa-envelope me-1"></i>
                                        <?php echo htmlspecialchars($donation['donor_email']); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($donation['donor_user_id']): ?>
                        <tr>
                            <th>Username:</th>
                            <td>
                                <i class="fa-regular fa-user me-1"></i>
                                <?php echo htmlspecialchars($donation['donor_username']); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Donation Date:</th>
                            <td><?php echo date('F d, Y', strtotime($donation['created_at'])); ?> at <?php echo date('h:i A', strtotime($donation['created_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Campaign Information -->
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="fa-solid fa-rocket text-primary me-2"></i>
                        Campaign Information
                    </h6>
                    
                    <table class="table table-sm">
                        <tr>
                            <th style="width: 40%;">Campaign:</th>
                            <td>
                                <a href="../campaign.php?id=<?php echo $donation['campaign_id']; ?>" target="_blank">
                                    <strong><?php echo htmlspecialchars($donation['campaign_title']); ?></strong>
                                    <i class="fa-solid fa-up-right-from-square ms-1 small"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Category:</th>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo htmlspecialchars($donation['category_name'] ?? 'Uncategorized'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Campaign Target:</th>
                            <td>$<?php echo number_format($donation['campaign_target'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Campaign Status:</th>
                            <td>
                                <?php
                                $camp_status_class = '';
                                switch($donation['campaign_status']) {
                                    case 'approved':
                                        $camp_status_class = 'success';
                                        break;
                                    case 'pending':
                                        $camp_status_class = 'warning';
                                        break;
                                    case 'rejected':
                                        $camp_status_class = 'danger';
                                        break;
                                    default:
                                        $camp_status_class = 'secondary';
                                }
                                ?>
                                <span class="badge bg-<?php echo $camp_status_class; ?>">
                                    <?php echo ucfirst($donation['campaign_status']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Campaign Creator:</th>
                            <td>
                                <?php echo htmlspecialchars($donation['creator_name'] ?? 'Unknown'); ?>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($donation['creator_email'] ?? ''); ?></small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <hr class="my-3">
    <div class="d-flex justify-content-end gap-2">
        <?php if ($donation['status'] == 'pending'): ?>
        <form method="POST" action="admin_donations.php" class="d-inline">
            <input type="hidden" name="donation_id" value="<?php echo $donation['id']; ?>">
            <input type="hidden" name="status" value="completed">
            <button type="submit" name="update_status" class="btn btn-success">
                <i class="fa-regular fa-circle-check me-2"></i>Mark Completed
            </button>
        </form>
        <?php endif; ?>
        
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="fa-regular fa-file-pdf me-2"></i>Print Details
        </button>
    </div>
</div>

<style>
/* Modal specific styles */
.table-sm th {
    color: #6c757d;
    font-weight: 500;
    background-color: transparent;
    border-top: none;
    padding-left: 0;
}

.table-sm td {
    border-top: none;
    padding-right: 0;
}

.bg-light {
    background-color: #f8f9fa !important;
}

/* Dark mode support */
body.dark-mode .bg-light {
    background-color: #2d2d2d !important;
}

body.dark-mode .bg-white {
    background-color: #1e1e1e !important;
    color: #fff;
}

body.dark-mode .text-muted {
    color: #aaa !important;
}

/* Print styles */
@media print {
    .btn, form, .modal-footer {
        display: none !important;
    }
}
</style>
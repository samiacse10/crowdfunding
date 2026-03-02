<?php
require_once 'header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    $_SESSION['error'] = "Invalid campaign";
    redirect('index.php');
}

// Modified query to also fetch organizer information
$stmt = $pdo->prepare("SELECT campaigns.*, categories.name as category_name,
        users.username as organizer_username, users.email as organizer_email,
        (SELECT COUNT(*) FROM donations WHERE campaign_id = campaigns.id) as donor_count,
        (SELECT COALESCE(SUM(amount), 0) FROM donations WHERE campaign_id = campaigns.id) as raised_amount
        FROM campaigns 
        LEFT JOIN categories ON campaigns.category_id = categories.id 
        LEFT JOIN users ON campaigns.user_id = users.id
        WHERE campaigns.id = ?");
$stmt->execute([$id]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaign) {
    $_SESSION['error'] = "Campaign not found";
    redirect('index.php');
}

// Calculate progress
$progress = min(100, round(($campaign['raised_amount'] / $campaign['target_amount']) * 100, 1));

// Check if user can view this campaign
$canView = false;
$isOwner = false;
$user_type = '';
$user_status = '';

if (isLoggedIn()) {
    $user_stmt = $pdo->prepare("SELECT user_type, status FROM users WHERE id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user_data = $user_stmt->fetch();
    $user_type = $user_data['user_type'];
    $user_status = $user_data['status'];
    $isOwner = ($campaign['user_id'] == $_SESSION['user_id']);
}

if ($campaign['status'] == 'approved') {
    $canView = true;
} else {
    if (isLoggedIn() && $campaign['user_id'] == $_SESSION['user_id']) {
        $canView = true;
    }
    if (isAdminLoggedIn()) {
        $canView = true;
    }
}

// Check if user is an approved organizer
$is_organizer = ($user_type == 'organizer' || $user_type == 'both') && $user_status == 'active';

if (!$canView) {
    $_SESSION['error'] = "You are not authorized to view this campaign";
    redirect('index.php');
}
?>

<div class="container">
    <!-- Campaign Header with Edit Button -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="display-5 fw-bold"><?php echo htmlspecialchars($campaign['title']); ?></h1>
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <span class="badge bg-primary"><?php echo htmlspecialchars($campaign['category_name'] ?? 'Uncategorized'); ?></span>
                
                <!-- ORGANIZER NAME ADDED HERE -->
                <span class="text-muted">
                    <i class="fa-regular fa-user me-1"></i> 
                    Organized by: 
                    <span class="fw-semibold">
                        <?php echo htmlspecialchars($campaign['organizer_username'] ?? 'Unknown Organizer'); ?>
                    </span>
                </span>
                
                <span class="text-muted">
                    <i class="fa-regular fa-calendar me-1"></i> Created: <?php echo date('d M Y', strtotime($campaign['created_at'])); ?>
                </span>
                <span>
                    Status: 
                    <?php
                    $badge_class = '';
                    if ($campaign['status'] == 'approved') $badge_class = 'bg-success';
                    elseif ($campaign['status'] == 'pending') $badge_class = 'bg-warning text-dark';
                    elseif ($campaign['status'] == 'rejected') $badge_class = 'bg-danger';
                    ?>
                    <span class="badge <?php echo $badge_class; ?>"><?php echo $campaign['status']; ?></span>
                </span>
            </div>
        </div>
        
        <!-- Edit Button - Only visible to campaign owner who is an approved organizer -->
        <?php if ($isOwner && $is_organizer): ?>
            <a href="edit_campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-pen-to-square me-2"></i>
                Edit Campaign
            </a>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <!-- Left Column - Image -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <img src="<?php echo htmlspecialchars($campaign['image_path'] ?: 'assets/images/default-campaign.jpg'); ?>" 
                     class="img-fluid w-100" 
                     alt="<?php echo htmlspecialchars($campaign['title']); ?>"
                     style="max-height: 500px; object-fit: cover;">
            </div>
            
            <!-- Optional: Add organizer card below image on larger screens -->
            <div class="card border-0 shadow-sm rounded-4 mt-4 d-lg-none">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">
                        <i class="fa-regular fa-circle-user me-2 text-primary"></i>
                        About the Organizer
                    </h5>
                    <div class="d-flex align-items-center">
                        <div class="organizer-avatar me-3">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                <i class="fa-regular fa-user fa-2x text-primary"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($campaign['organizer_username'] ?? 'Unknown Organizer'); ?></h6>
                            <p class="text-muted small mb-0">
                                <i class="fa-regular fa-envelope me-1"></i>
                                <?php echo htmlspecialchars($campaign['organizer_email'] ?? 'Email not available'); ?>
                            </p>
                            <p class="text-muted small mb-0 mt-1">
                                <span class="badge bg-success bg-opacity-10 text-success">
                                    <i class="fa-regular fa-circle-check me-1"></i>Verified Organizer
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Details -->
        <div class="col-lg-6">
            <!-- Organizer Info Card - Visible on larger screens -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 d-none d-lg-block">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">
                        <i class="fa-regular fa-circle-user me-2 text-primary"></i>
                        About the Organizer
                    </h5>
                    <div class="d-flex align-items-center">
                        <div class="organizer-avatar me-3">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                <i class="fa-regular fa-user fa-2x text-primary"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($campaign['organizer_username'] ?? 'Unknown Organizer'); ?></h6>
                            <p class="text-muted small mb-0">
                                <i class="fa-regular fa-envelope me-1"></i>
                                <?php echo htmlspecialchars($campaign['organizer_email'] ?? 'Email not available'); ?>
                            </p>
                            <p class="text-muted small mb-0 mt-1">
                                <span class="badge bg-success bg-opacity-10 text-success">
                                    <i class="fa-regular fa-circle-check me-1"></i>Verified Organizer
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top">
                        <a href="mailto:<?php echo htmlspecialchars($campaign['organizer_email']); ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fa-regular fa-envelope me-2"></i>Contact Organizer
                        </a>
                    </div>
                </div>
            </div>

            <!-- Funding Progress Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">Funding Progress</h5>
                    
                    <!-- Raised Amount -->
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <span class="h3 fw-bold text-primary">$<?php echo number_format($campaign['raised_amount'], 2); ?></span>
                        <span class="text-muted">of $<?php echo number_format($campaign['target_amount'], 2); ?></span>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="progress mb-3" style="height: 12px;">
                        <div class="progress-bar bg-success" 
                             role="progressbar" 
                             style="width: <?php echo $progress; ?>%;" 
                             aria-valuenow="<?php echo $progress; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="d-flex justify-content-between text-center">
                        <div>
                            <div class="h5 mb-0"><?php echo $progress; ?>%</div>
                            <small class="text-muted">Funded</small>
                        </div>
                        <div>
                            <div class="h5 mb-0"><?php echo number_format($campaign['donor_count'] ?? 0); ?></div>
                            <small class="text-muted">Donors</small>
                        </div>
                        <div>
                            <div class="h5 mb-0"><?php echo date('d M', strtotime($campaign['created_at'] . ' +30 days')); ?></div>
                            <small class="text-muted">End Date</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">About This Campaign</h5>
                    <div class="campaign-description">
                        <?php echo nl2br(htmlspecialchars($campaign['description'])); ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <?php if ($campaign['status'] == 'approved'): ?>
                <div class="d-flex gap-3">
                    <a href="demo_donate.php?id=<?php echo $campaign['id']; ?>" class="btn btn-primary btn-lg flex-grow-1">
                        <i class="fa-solid fa-flask me-2"></i>Demo Donate
                    </a>
                    <a href="mailto:<?php echo htmlspecialchars($campaign['organizer_email']); ?>" class="btn btn-outline-primary btn-lg">
                        <i class="fa-regular fa-envelope"></i>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Share Buttons -->
            <div class="mt-4">
                <p class="text-muted mb-2">Share this campaign:</p>
                <div class="d-flex gap-2">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                       target="_blank" class="btn btn-outline-primary btn-sm rounded-circle" style="width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($campaign['title']); ?>" 
                       target="_blank" class="btn btn-outline-info btn-sm rounded-circle" style="width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                       target="_blank" class="btn btn-outline-secondary btn-sm rounded-circle" style="width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="mailto:?subject=<?php echo urlencode($campaign['title']); ?>&body=Check out this campaign: <?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                       class="btn btn-outline-danger btn-sm rounded-circle" style="width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-regular fa-envelope"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Donations Section - Different views for owner vs public -->
    <?php if ($campaign['donor_count'] > 0): ?>
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h4 class="mb-4">
                        <i class="fa-solid fa-hand-holding-heart me-2 text-primary"></i>
                        Recent Donors
                        <?php if ($isOwner && $is_organizer): ?>
                            <small class="text-muted fs-6 ms-2">(You see full donor details as the campaign owner)</small>
                        <?php endif; ?>
                    </h4>
                    
                    <?php
                    // Fetch recent donations - show more details to owner
                    if ($isOwner && $is_organizer) {
                        // Owner sees all details including payment method
                        $donationStmt = $pdo->prepare("SELECT d.*, u.username as donor_username 
                                                       FROM donations d 
                                                       LEFT JOIN users u ON d.user_id = u.id 
                                                       WHERE d.campaign_id = ? 
                                                       ORDER BY d.created_at DESC 
                                                       LIMIT 20");
                        $donationStmt->execute([$id]);
                    } else {
                        // Public only sees public info
                        $donationStmt = $pdo->prepare("SELECT donor_name, amount, message, is_anonymous, created_at 
                                                       FROM donations 
                                                       WHERE campaign_id = ? AND status = 'completed'
                                                       ORDER BY created_at DESC 
                                                       LIMIT 10");
                        $donationStmt->execute([$id]);
                    }
                    $donations = $donationStmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <?php if (count($donations) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Donor</th>
                                        <th>Amount</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                        <?php if ($isOwner && $is_organizer): ?>
                                            <th>Payment Method</th>
                                            <th>Status</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($donations as $donation): ?>
                                    <tr>
                                        <td>
                                            <?php if ($donation['is_anonymous']): ?>
                                                <span class="badge bg-secondary">
                                                    <i class="fa-regular fa-user-secret me-1"></i>Anonymous
                                                </span>
                                            <?php else: ?>
                                                <i class="fa-regular fa-user me-1"></i>
                                                <?php 
                                                if ($isOwner && $is_organizer && isset($donation['donor_username'])) {
                                                    echo htmlspecialchars($donation['donor_username']);
                                                } else {
                                                    echo htmlspecialchars($donation['donor_name'] ?? 'Anonymous');
                                                }
                                                ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-success">$<?php echo number_format($donation['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($donation['message'] ?: '-'); ?></td>
                                        <td><small><?php echo date('d M Y', strtotime($donation['created_at'])); ?></small></td>
                                        
                                        <?php if ($isOwner && $is_organizer): ?>
                                            <td>
                                                <span class="badge bg-info bg-opacity-10 text-info">
                                                    <?php echo ucfirst($donation['payment_method'] ?? 'Unknown'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($donation['status'] == 'completed'): ?>
                                                    <span class="badge bg-success">Completed</span>
                                                <?php elseif ($donation['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo $donation['status']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No donations yet. Be the first to support this campaign!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div><br>

<style>
/* Campaign Page Specific Styles */
.campaign-description {
    line-height: 1.8;
    color: #333;
    white-space: pre-line;
}

body.dark-mode .campaign-description {
    color: #ddd;
}

.progress-bar {
    background: linear-gradient(90deg, #00c6ff, #0072ff);
    border-radius: 6px;
}

.progress {
    background-color: #e9ecef;
    border-radius: 6px;
}

body.dark-mode .progress {
    background-color: #333;
}

/* Card hover effects */
.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.12) !important;
}

/* Share buttons */
.btn-outline-primary, .btn-outline-info, .btn-outline-secondary, .btn-outline-danger {
    border-width: 2px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-outline-primary:hover,
.btn-outline-info:hover,
.btn-outline-secondary:hover,
.btn-outline-danger:hover {
    transform: translateY(-2px);
}

/* Action buttons */
.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    border-radius: 8px;
}

/* Edit button specific */
.btn-primary {
    background: linear-gradient(135deg, #141e30, #243b55);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,198,255,0.4);
}

/* Table styling */
.table th {
    font-weight: 600;
    color: #495057;
    border-bottom-width: 2px;
}

body.dark-mode .table {
    color: #ddd;
}

body.dark-mode .table th {
    color: #fff;
    border-bottom-color: #444;
}

body.dark-mode .table td {
    border-color: #333;
}

/* Organizer avatar */
.organizer-avatar .rounded-circle {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .display-5 {
        font-size: 1.8rem;
    }
    
    .btn-lg {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .d-flex.flex-wrap.gap-3 {
        gap: 0.5rem !important;
    }
    
    .btn-lg.flex-grow-1 {
        width: 100%;
    }
    
    /* Stack header and edit button on mobile */
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .d-flex.justify-content-between .btn-lg {
        width: 100%;
    }
    
    /* Adjust organizer info for mobile */
    .gap-3 .btn-outline-primary {
        width: auto;
    }
}

/* Badge styling */
.badge {
    padding: 0.5rem 1rem;
    font-weight: 500;
    font-size: 0.85rem;
}

/* Container spacing */
.container {
    max-width: 1200px;
}

/* Ensure proper vertical rhythm */
.mb-4 {
    margin-bottom: 1.5rem !important;
}

.g-4 {
    --bs-gutter-y: 1.5rem;
}

/* Verified badge */
.bg-success.bg-opacity-10 {
    background: rgba(40, 167, 69, 0.1) !important;
}
</style>

<?php
require_once 'footer.php';
?>
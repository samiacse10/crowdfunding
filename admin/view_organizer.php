<?php
// Start output buffering
ob_start();

require_once '../config.php';
require_once 'admin_header.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Get organizer ID from URL
$organizer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($organizer_id === 0) {
    header('Location: admin_organizers.php');
    exit();
}

// Fetch complete organizer details with all fields
$query = "SELECT u.*, 
    COUNT(DISTINCT c.id) as campaign_count,
    COUNT(DISTINCT d.id) as donation_count,
    COALESCE(SUM(d.amount), 0) as total_donated
    FROM users u
    LEFT JOIN campaigns c ON u.id = c.user_id
    LEFT JOIN donations d ON u.id = d.user_id
    WHERE u.id = ? AND (u.user_type = 'organizer' OR u.user_type = 'both')
    GROUP BY u.id";

$stmt = $pdo->prepare($query);
$stmt->execute([$organizer_id]);
$organizer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$organizer) {
    $_SESSION['error'] = "Organizer not found!";
    header('Location: admin_organizers.php');
    exit();
}

// Handle approval/rejection
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->execute([$organizer_id]);
        $_SESSION['success'] = "Organizer approved successfully!";
        header('Location: view_organizer.php?id=' . $organizer_id);
        exit();
    } elseif ($_POST['action'] == 'reject') {
        $reason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';
        $stmt = $pdo->prepare("UPDATE users SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $organizer_id]);
        $_SESSION['success'] = "Organizer rejected.";
        header('Location: view_organizer.php?id=' . $organizer_id);
        exit();
    }
}

// Handle success/error messages
$success_message = $_SESSION['success'] ?? '';
$error_message = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Helper function to safely display array values
function safe_value($array, $key, $default = 'Not provided') {
    return isset($array[$key]) && !empty($array[$key]) ? htmlspecialchars($array[$key]) : $default;
}

// Helper function for NID type display
function get_nid_type_name($code) {
    $types = [
        'BD' => 'Bangladesh NID',
        'US' => 'US SSN',
        'UK' => 'UK National Insurance',
        'IN' => 'Indian Aadhaar',
        'PK' => 'Pakistan CNIC'
    ];
    return $types[$code] ?? 'NID';
}

// Helper function for country name
function get_country_name($code) {
    $countries = [
        'BD' => 'Bangladesh',
        'US' => 'United States',
        'UK' => 'United Kingdom',
        'IN' => 'India',
        'PK' => 'Pakistan'
    ];
    return $countries[$code] ?? 'Not specified';
}
?>

<!-- Display messages -->
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
        <h1><i class="fa-regular fa-id-card me-2"></i>Organizer Verification Details</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="admin_organizers.php">Organizers</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($organizer['username']); ?></li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="admin_organizers.php" class="btn btn-outline-secondary rounded-pill">
            <i class="fa-solid fa-arrow-left me-2"></i>Back to Organizers
        </a>
        <?php if ($organizer['status'] == 'pending'): ?>
            <button type="button" class="btn btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#approveModal">
                <i class="fa-regular fa-circle-check me-2"></i>Approve Organizer
            </button>
            <button type="button" class="btn btn-danger rounded-pill" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="fa-regular fa-circle-xmark me-2"></i>Reject Organizer
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Organizer Status Banner -->
<?php if ($organizer['status'] == 'pending'): ?>
    <div class="alert alert-warning mb-4">
        <i class="fa-solid fa-clock me-2"></i>
        <strong>Pending Verification:</strong> This organizer is waiting for approval. Please review their documents below.
    </div>
<?php elseif ($organizer['status'] == 'active'): ?>
    <div class="alert alert-success mb-4">
        <i class="fa-regular fa-circle-check me-2"></i>
        <strong>Approved:</strong> This organizer has been verified and approved.
    </div>
<?php elseif ($organizer['status'] == 'rejected'): ?>
    <div class="alert alert-danger mb-4">
        <i class="fa-regular fa-circle-xmark me-2"></i>
        <strong>Rejected:</strong> This organizer application was rejected.
        <?php if (!empty($organizer['rejection_reason'])): ?>
            <br><strong>Reason:</strong> <?php echo htmlspecialchars($organizer['rejection_reason']); ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Main Content Grid -->
<div class="row g-4">
    <!-- Left Column - Profile & Status -->
    <div class="col-md-4">
        <!-- Profile Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body text-center p-4">
                <div class="position-relative d-inline-block">
                    <?php if (!empty($organizer['profile_image']) && file_exists('../' . $organizer['profile_image'])): ?>
                        <img src="../<?php echo htmlspecialchars($organizer['profile_image']); ?>" 
                             alt="Profile" 
                             class="rounded-circle mb-3"
                             style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #2d6a4f;">
                    <?php else: ?>
                        <div class="avatar-circle-large bg-<?php 
                            echo $organizer['status'] == 'active' ? 'success' : 
                                ($organizer['status'] == 'pending' ? 'warning' : 'danger'); 
                        ?> text-white mx-auto mb-3 d-flex align-items-center justify-content-center" 
                        style="width: 120px; height: 120px; font-size: 3rem; border-radius: 50%;">
                            <?php echo strtoupper(substr($organizer['username'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($organizer['status'] == 'active'): ?>
                        <span class="position-absolute bottom-0 end-0 bg-success rounded-circle p-2 border border-2 border-white" style="width: 20px; height: 20px;"></span>
                    <?php endif; ?>
                </div>
                
                <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($organizer['full_name'] ?: $organizer['username']); ?></h3>
                <p class="text-muted mb-2">@<?php echo htmlspecialchars($organizer['username']); ?></p>
                
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-<?php 
                        echo $organizer['user_type'] == 'organizer' ? 'primary' : 'info'; 
                    ?> rounded-pill p-2">
                        <?php echo ucfirst($organizer['user_type']); ?>
                    </span>
                    <span class="badge bg-<?php 
                        echo $organizer['status'] == 'active' ? 'success' : 
                            ($organizer['status'] == 'pending' ? 'warning' : 'danger'); 
                    ?> rounded-pill p-2">
                        <?php echo ucfirst($organizer['status']); ?>
                    </span>
                </div>
                
                <div class="text-start">
                    <p class="mb-1"><i class="fa-regular fa-envelope text-green me-2"></i> <?php echo htmlspecialchars($organizer['email']); ?></p>
                    <?php if (!empty($organizer['phone'])): ?>
                        <p class="mb-1"><i class="fa-solid fa-phone text-green me-2"></i> <?php echo htmlspecialchars($organizer['phone']); ?></p>
                    <?php endif; ?>
                    <p class="mb-0"><i class="fa-regular fa-calendar text-green me-2"></i> Requested: <?php echo date('F j, Y', strtotime($organizer['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Statistics Card -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-chart-simple text-green me-2"></i>Statistics</h5>
                <div class="row text-center">
                    <div class="col-4">
                        <div class="p-2">
                            <h4 class="fw-bold text-primary mb-0"><?php echo $organizer['campaign_count']; ?></h4>
                            <small class="text-muted">Campaigns</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2">
                            <h4 class="fw-bold text-success mb-0"><?php echo $organizer['donation_count']; ?></h4>
                            <small class="text-muted">Donations</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2">
                            <h4 class="fw-bold text-info mb-0">$<?php echo number_format($organizer['total_donated'], 2); ?></h4>
                            <small class="text-muted">Donated</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column - Verification Details -->
    <div class="col-md-8">
        <!-- Personal Information Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="card-title mb-0">
                    <i class="fa-regular fa-id-card text-primary me-2"></i>
                    Personal Information
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 120px;">Full Name:</th>
                                <td><strong><?php echo safe_value($organizer, 'full_name'); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Username:</th>
                                <td><?php echo htmlspecialchars($organizer['username']); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($organizer['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td><?php echo safe_value($organizer, 'phone'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 120px;">Date of Birth:</th>
                                <td>
                                    <?php 
                                    if (!empty($organizer['dob'])) {
                                        echo date('F j, Y', strtotime($organizer['dob']));
                                    } else {
                                        echo 'Not provided';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Age:</th>
                                <td>
                                    <?php 
                                    if (!empty($organizer['dob'])) {
                                        $age = date_diff(date_create($organizer['dob']), date_create('today'))->y;
                                        echo $age . ' years';
                                    } else {
                                        echo 'Not provided';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Occupation:</th>
                                <td><?php echo safe_value($organizer, 'occupation'); ?></td>
                            </tr>
                            <tr>
                                <th>Experience:</th>
                                <td><?php echo !empty($organizer['experience']) ? ucfirst($organizer['experience']) : 'Not provided'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Identity Verification Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-passport text-primary me-2"></i>
                    Identity Verification
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 120px;">ID Type:</th>
                                <td>
                                    <?php 
                                    if (!empty($organizer['nid_type'])) {
                                        echo get_nid_type_name($organizer['nid_type']);
                                    } else {
                                        echo 'NID';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>ID Number:</th>
                                <td><strong><?php echo safe_value($organizer, 'nid_number'); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 120px;">Country:</th>
                                <td>
                                    <?php 
                                    if (!empty($organizer['country'])) {
                                        echo get_country_name($organizer['country']);
                                    } else {
                                        echo 'Not specified';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- ID Document Preview -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">ID Document (Front/Back)</label>
                        <?php if (!empty($organizer['nid_image']) && file_exists('../' . $organizer['nid_image'])): ?>
                            <div class="document-preview p-3 rounded-3 text-center" style="background: #f8f9fa;">
                                <img src="../<?php echo htmlspecialchars($organizer['nid_image']); ?>" 
                                     alt="NID Document" 
                                     class="img-fluid rounded-3 mb-2"
                                     style="max-height: 200px; cursor: pointer;"
                                     onclick="window.open('../<?php echo $organizer['nid_image']; ?>', '_blank')">
                                <div>
                                    <a href="../<?php echo $organizer['nid_image']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fa-regular fa-eye"></i> View Full Image
                                    </a>
                                    <a href="../<?php echo $organizer['nid_image']; ?>" download class="btn btn-sm btn-outline-green">
                                        <i class="fa-regular fa-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-4 rounded-3" style="background: #f8f9fa;">
                                <i class="fa-regular fa-image fa-3x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No ID document uploaded</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Profile Photo / Selfie</label>
                        <?php if (!empty($organizer['profile_image']) && file_exists('../' . $organizer['profile_image'])): ?>
                            <div class="document-preview p-3 rounded-3 text-center" style="background: #f8f9fa;">
                                <img src="../<?php echo htmlspecialchars($organizer['profile_image']); ?>" 
                                     alt="Profile Photo" 
                                     class="img-fluid rounded-3 mb-2"
                                     style="max-height: 200px; cursor: pointer;"
                                     onclick="window.open('../<?php echo $organizer['profile_image']; ?>', '_blank')">
                                <div>
                                    <a href="../<?php echo $organizer['profile_image']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fa-regular fa-eye"></i> View Full Image
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-4 rounded-3" style="background: #f8f9fa;">
                                <i class="fa-regular fa-image fa-3x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No profile photo uploaded</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Information Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-location-dot text-primary me-2"></i>
                    Address Information
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 120px;">Address:</th>
                                <td><?php echo safe_value($organizer, 'address'); ?></td>
                            </tr>
                            <tr>
                                <th>City:</th>
                                <td><?php echo safe_value($organizer, 'city'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 120px;">State:</th>
                                <td><?php echo safe_value($organizer, 'state'); ?></td>
                            </tr>
                            <tr>
                                <th>Postal Code:</th>
                                <td><?php echo safe_value($organizer, 'postal_code'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media & Additional Info Card -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="card-title mb-0">
                    <i class="fa-solid fa-share-nodes text-primary me-2"></i>
                    Social Media & Additional Info
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <?php if (!empty($organizer['facebook'])): ?>
                            <tr>
                                <th style="width: 100px;"><i class="fab fa-facebook text-primary"></i> Facebook:</th>
                                <td><a href="<?php echo htmlspecialchars($organizer['facebook']); ?>" target="_blank"><?php echo htmlspecialchars($organizer['facebook']); ?></a></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if (!empty($organizer['twitter'])): ?>
                            <tr>
                                <th><i class="fab fa-twitter text-info"></i> Twitter:</th>
                                <td><a href="<?php echo htmlspecialchars($organizer['twitter']); ?>" target="_blank"><?php echo htmlspecialchars($organizer['twitter']); ?></a></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if (!empty($organizer['linkedin'])): ?>
                            <tr>
                                <th><i class="fab fa-linkedin text-primary"></i> LinkedIn:</th>
                                <td><a href="<?php echo htmlspecialchars($organizer['linkedin']); ?>" target="_blank"><?php echo htmlspecialchars($organizer['linkedin']); ?></a></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <?php if (!empty($organizer['website'])): ?>
                            <tr>
                                <th style="width: 100px;"><i class="fa-solid fa-globe text-green"></i> Website:</th>
                                <td><a href="<?php echo htmlspecialchars($organizer['website']); ?>" target="_blank"><?php echo htmlspecialchars($organizer['website']); ?></a></td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr>
                                <th><i class="fa-solid fa-gift text-warning"></i> Referral:</th>
                                <td><?php echo !empty($organizer['referral_source']) ? ucfirst(str_replace('_', ' ', $organizer['referral_source'])) : 'Not specified'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fa-regular fa-circle-check me-2"></i>Approve Organizer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve <strong><?php echo htmlspecialchars($organizer['full_name'] ?: $organizer['username']); ?></strong> as an organizer?</p>
                <p class="text-success mb-0"><i class="fa-regular fa-circle-check me-1"></i> They will be able to create campaigns immediately.</p>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="action" value="approve">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Organizer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa-regular fa-circle-xmark me-2"></i>Reject Organizer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>Are you sure you want to reject <strong><?php echo htmlspecialchars($organizer['full_name'] ?: $organizer['username']); ?></strong>?</p>
                    
                    <div class="form-group mb-3">
                        <label for="rejection_reason" class="form-label fw-semibold">Reason for Rejection (Optional)</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" placeholder="Provide feedback to the organizer..."></textarea>
                    </div>
                    
                    <p class="text-danger mb-0"><i class="fa-regular fa-circle-exclamation me-1"></i> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="action" value="reject">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Organizer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.avatar-circle-large {
    font-weight: 600;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.document-preview {
    transition: all 0.3s ease;
    border: 1px solid #e0e0e0;
}

.document-preview:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.table-borderless th {
    font-weight: 600;
    color: #495057;
}

.table-borderless td {
    color: #212529;
}

.text-green {
    color: #2d6a4f;
}

.btn-outline-green {
    border: 1px solid #2d6a4f;
    color: #2d6a4f;
}

.btn-outline-green:hover {
    background: #2d6a4f;
    color: white;
}

body.dark-mode .table-borderless th {
    color: #aaa;
}

body.dark-mode .table-borderless td {
    color: #ddd;
}

body.dark-mode .document-preview {
    background: #2d2d2d !important;
    border-color: #444;
}
</style>

<?php
require_once 'admin_footer.php';
ob_end_flush();
?>
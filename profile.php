<?php
require_once 'header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = "Please login to view your profile";
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $bio = trim($_POST['bio']);
        $location = trim($_POST['location']);
        $website = trim($_POST['website']);
        
        // Check if username already exists (excluding current user)
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check_stmt->execute([$username, $user_id]);
        if ($check_stmt->fetch()) {
            $_SESSION['error'] = "Username already taken. Please choose another.";
            redirect('profile.php');
        }
        
        // Check if email already exists (excluding current user)
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->execute([$email, $user_id]);
        if ($check_stmt->fetch()) {
            $_SESSION['error'] = "Email already registered. Please use another.";
            redirect('profile.php');
        }
        
        // Handle profile picture upload
        $profile_pic = $user['profile_pic'] ?? 'assets/images/default-avatar.png';
        
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_pic']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $upload_dir = 'uploads/profiles/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                    // Delete old profile picture if not default
                    if ($profile_pic != 'assets/images/default-avatar.png' && file_exists($profile_pic)) {
                        unlink($profile_pic);
                    }
                    $profile_pic = $upload_path;
                }
            }
        }
        
        // Update user profile
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, bio = ?, location = ?, website = ?, profile_pic = ? WHERE id = ?");
        if ($stmt->execute([$username, $email, $bio, $location, $website, $profile_pic, $user_id])) {
            $_SESSION['username'] = $username;
            $_SESSION['profile_pic'] = $profile_pic;
            $_SESSION['success'] = "Profile updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update profile.";
        }
        redirect('profile.php');
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        
        if (password_verify($current_password, $user_data['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($stmt->execute([$hashed_password, $user_id])) {
                        $_SESSION['success'] = "Password changed successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to change password.";
                    }
                } else {
                    $_SESSION['error'] = "Password must be at least 6 characters long.";
                }
            } else {
                $_SESSION['error'] = "New passwords do not match.";
            }
        } else {
            $_SESSION['error'] = "Current password is incorrect.";
        }
        redirect('profile.php');
    }
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user's campaigns
$campaigns_stmt = $pdo->prepare("SELECT * FROM campaigns WHERE user_id = ? ORDER BY created_at DESC");
$campaigns_stmt->execute([$user_id]);
$campaigns = $campaigns_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's donations (with error handling if table doesn't exist)
$donations = [];
try {
    $donations_stmt = $pdo->prepare("SELECT d.*, c.title as campaign_title, c.id as campaign_id 
                                     FROM donations d 
                                     LEFT JOIN campaigns c ON d.campaign_id = c.id 
                                     WHERE d.user_id = ? 
                                     ORDER BY d.created_at DESC 
                                     LIMIT 10");
    $donations_stmt->execute([$user_id]);
    $donations = $donations_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Donations table doesn't exist, continue without donations
}

// Calculate stats
$total_campaigns = count($campaigns);
$total_donated = 0;
foreach ($donations as $donation) {
    $total_donated += $donation['amount'];
}
$active_campaigns = count(array_filter($campaigns, function($c) { return $c['status'] == 'approved'; }));
$pending_campaigns = count(array_filter($campaigns, function($c) { return $c['status'] == 'pending'; }));
$rejected_campaigns = count(array_filter($campaigns, function($c) { return $c['status'] == 'rejected'; }));
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="d-flex align-items-center mb-4">
        <div class="featured-icon me-3">
            <i class="fa-solid fa-user-circle fa-2x"></i>
        </div>
        <div>
            <h1 class="display-6 fw-bold mb-1">My Profile</h1>
            <p class="text-muted mb-0">Manage your account and view your activity</p>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-check-circle fs-5 me-2"></i>
                <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-exclamation-circle fs-5 me-2"></i>
                <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Left Column - Profile Info & Stats -->
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body text-center p-4">
                    <div class="position-relative d-inline-block mb-4">
                        <img src="<?php echo htmlspecialchars($user['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>" 
                             alt="Profile Picture" 
                             class="rounded-circle border border-3 border-primary shadow-sm"
                             style="width: 150px; height: 150px; object-fit: cover;">
                        <label for="profile_pic_input" class="btn btn-primary btn-sm rounded-circle position-absolute bottom-0 end-0 shadow-sm" 
                               style="width: 40px; height: 40px; cursor: pointer;">
                            <i class="fa-solid fa-camera"></i>
                        </label>
                    </div>
                    
                    <h3 class="h4 fw-bold mb-1"><?php echo htmlspecialchars($user['username']); ?></h3>
                    <p class="text-muted mb-3">
                        <i class="fa-regular fa-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?>
                    </p>
                    
                    <div class="d-flex justify-content-center gap-2 mb-3 flex-wrap">
                        <?php if (!empty($user['location'])): ?>
                            <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                                <i class="fa-solid fa-location-dot text-primary me-1"></i>
                                <?php echo htmlspecialchars($user['location']); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['website'])): ?>
                            <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank" 
                               class="badge bg-light text-dark rounded-pill px-3 py-2 text-decoration-none">
                                <i class="fa-solid fa-globe text-primary me-1"></i>
                                Website
                            </a>
                        <?php endif; ?>
                        
                        <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                            <i class="fa-regular fa-calendar text-primary me-1"></i>
                            Joined <?php echo date('M Y', strtotime($user['created_at'] ?? 'now')); ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($user['bio'])): ?>
                        <div class="text-start border-top pt-3 mt-2">
                            <p class="text-muted mb-0 small"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3">
                <div class="col-6">
                    <div class="stat-card bg-gradient-primary text-white p-3 rounded-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="fw-bold mb-1"><?php echo $total_campaigns; ?></h4>
                                <small class="opacity-75">Total Campaigns</small>
                            </div>
                            <i class="fa-solid fa-rocket fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card bg-gradient-success text-white p-3 rounded-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="fw-bold mb-1"><?php echo $active_campaigns; ?></h4>
                                <small class="opacity-75">Active</small>
                            </div>
                            <i class="fa-solid fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card bg-gradient-info text-white p-3 rounded-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="fw-bold mb-1">$<?php echo number_format($total_donated, 0); ?></h4>
                                <small class="opacity-75">Total Donated</small>
                            </div>
                            <i class="fa-solid fa-hand-holding-heart fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card bg-gradient-warning text-white p-3 rounded-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="fw-bold mb-1"><?php echo $pending_campaigns; ?></h4>
                                <small class="opacity-75">Pending</small>
                            </div>
                            <i class="fa-solid fa-clock fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Tabs -->
        <div class="col-lg-8">
            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs border-0 mb-4" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4 py-2 me-2" 
                            id="edit-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#edit" 
                            type="button" 
                            role="tab">
                        <i class="fa-solid fa-pen-to-square me-2"></i>Edit Profile
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 py-2 me-2" 
                            id="campaigns-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#campaigns" 
                            type="button" 
                            role="tab">
                        <i class="fa-solid fa-rocket me-2"></i>My Campaigns
                        <?php if ($pending_campaigns > 0): ?>
                            <span class="badge bg-danger ms-1"><?php echo $pending_campaigns; ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 py-2 me-2" 
                            id="donations-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#donations" 
                            type="button" 
                            role="tab">
                        <i class="fa-solid fa-hand-holding-heart me-2"></i>My Donations
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 py-2" 
                            id="password-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#password" 
                            type="button" 
                            role="tab">
                        <i class="fa-solid fa-lock me-2"></i>Change Password
                    </button>
                </li>
            </ul>

            <!-- Tabs Content -->
            <div class="tab-content">
                <!-- Edit Profile Tab -->
                <div class="tab-pane fade show active" id="edit" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">
                                <i class="fa-solid fa-circle-info text-primary me-2"></i>
                                Edit Profile Information
                            </h5>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <input type="file" name="profile_pic" id="profile_pic_input" class="d-none" accept="image/*">
                                
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="fa-regular fa-user text-primary me-1"></i>Username
                                        </label>
                                        <input type="text" name="username" class="form-control form-control-lg rounded-3" 
                                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="fa-regular fa-envelope text-primary me-1"></i>Email
                                        </label>
                                        <input type="email" name="email" class="form-control form-control-lg rounded-3" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="fa-solid fa-location-dot text-primary me-1"></i>Location
                                        </label>
                                        <input type="text" name="location" class="form-control form-control-lg rounded-3" 
                                               value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" 
                                               placeholder="City, Country">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="fa-solid fa-globe text-primary me-1"></i>Website
                                        </label>
                                        <input type="url" name="website" class="form-control form-control-lg rounded-3" 
                                               value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" 
                                               placeholder="https://example.com">
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">
                                            <i class="fa-regular fa-note-sticky text-primary me-1"></i>Bio
                                        </label>
                                        <textarea name="bio" class="form-control form-control-lg rounded-3" rows="4" 
                                                  placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" name="update_profile" class="btn btn-primary btn-lg rounded-pill px-5">
                                            <i class="fa-solid fa-save me-2"></i>Save Changes
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- My Campaigns Tab -->
                <div class="tab-pane fade" id="campaigns" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
                                <h5 class="card-title mb-0">
                                    <i class="fa-solid fa-rocket text-primary me-2"></i>
                                    My Campaigns
                                </h5>
                                <a href="create_campaign.php" class="btn btn-primary rounded-pill px-4">
                                    <i class="fa-solid fa-plus me-2"></i>New Campaign
                                </a>
                            </div>
                            
                            <?php if (count($campaigns) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Campaign</th>
                                                <th>Target</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($campaigns as $campaign): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="<?php echo htmlspecialchars($campaign['image_path'] ?: 'assets/images/default-campaign.jpg'); ?>" 
                                                                 class="rounded-3 me-3 shadow-sm" 
                                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                                            <div>
                                                                <a href="campaign.php?id=<?php echo $campaign['id']; ?>" class="fw-bold text-decoration-none text-dark">
                                                                    <?php echo htmlspecialchars($campaign['title']); ?>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="fw-bold text-primary">$<?php echo number_format($campaign['target_amount'], 2); ?></td>
                                                    <td>
                                                        <?php
                                                        $badge_class = '';
                                                        if ($campaign['status'] == 'approved') $badge_class = 'bg-success';
                                                        elseif ($campaign['status'] == 'pending') $badge_class = 'bg-warning text-dark';
                                                        elseif ($campaign['status'] == 'rejected') $badge_class = 'bg-danger';
                                                        ?>
                                                        <span class="badge <?php echo $badge_class; ?> rounded-pill px-3 py-2">
                                                            <?php echo $campaign['status']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <i class="fa-regular fa-calendar me-1"></i>
                                                            <?php echo date('M d, Y', strtotime($campaign['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex gap-2">
                                                            <a href="campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill" title="View">
                                                                <i class="fa-regular fa-eye"></i>
                                                            </a>
                                                            <a href="edit_campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-outline-secondary rounded-pill" title="Edit">
                                                                <i class="fa-regular fa-pen-to-square"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <div class="empty-state-icon mb-4">
                                        <i class="fa-solid fa-rocket fa-3x text-muted"></i>
                                    </div>
                                    <h6 class="text-muted mb-3">You haven't created any campaigns yet</h6>
                                    <a href="create_campaign.php" class="btn btn-primary rounded-pill px-4">
                                        <i class="fa-solid fa-plus me-2"></i>Start Your First Campaign
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- My Donations Tab -->
                <div class="tab-pane fade" id="donations" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">
                                <i class="fa-solid fa-hand-holding-heart text-primary me-2"></i>
                                My Donations
                            </h5>
                            
                            <?php if (count($donations) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Campaign</th>
                                                <th>Amount</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($donations as $donation): ?>
                                                <tr>
                                                    <td>
                                                        <a href="campaign.php?id=<?php echo $donation['campaign_id']; ?>" class="fw-bold text-decoration-none text-dark">
                                                            <?php echo htmlspecialchars($donation['campaign_title']); ?>
                                                        </a>
                                                    </td>
                                                    <td class="fw-bold text-success">$<?php echo number_format($donation['amount'], 2); ?></td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <i class="fa-regular fa-calendar me-1"></i>
                                                            <?php echo date('M d, Y', strtotime($donation['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success rounded-pill px-3 py-2">
                                                            <i class="fa-regular fa-circle-check me-1"></i>
                                                            <?php echo $donation['status']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <div class="empty-state-icon mb-4">
                                        <i class="fa-solid fa-hand-holding-heart fa-3x text-muted"></i>
                                    </div>
                                    <h6 class="text-muted mb-3">You haven't made any donations yet</h6>
                                    <a href="index.php" class="btn btn-primary rounded-pill px-4">
                                        <i class="fa-solid fa-magnifying-glass me-2"></i>Explore Campaigns
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Change Password Tab -->
                <div class="tab-pane fade" id="password" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">
                                <i class="fa-solid fa-lock text-primary me-2"></i>
                                Change Password
                            </h5>
                            
                            <form method="POST">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Current Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0 rounded-start-3">
                                                <i class="fa-solid fa-lock text-primary"></i>
                                            </span>
                                            <input type="password" name="current_password" class="form-control form-control-lg rounded-end-3" 
                                                   placeholder="Enter current password" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0 rounded-start-3">
                                                <i class="fa-solid fa-key text-primary"></i>
                                            </span>
                                            <input type="password" name="new_password" class="form-control form-control-lg rounded-end-3" 
                                                   placeholder="Min. 6 characters" required>
                                        </div>
                                        <small class="text-muted">Minimum 6 characters</small>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Confirm New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0 rounded-start-3">
                                                <i class="fa-solid fa-key text-primary"></i>
                                            </span>
                                            <input type="password" name="confirm_password" class="form-control form-control-lg rounded-end-3" 
                                                   placeholder="Re-enter new password" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" name="change_password" class="btn btn-primary btn-lg rounded-pill px-5">
                                            <i class="fa-solid fa-key me-2"></i>Update Password
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Profile Page Specific Styles */
:root {
    --primary-gradient: linear-gradient(135deg, #141e30, #243b55);
    --success-gradient: linear-gradient(135deg, #28a745, #20c997);
    --info-gradient: linear-gradient(135deg, #17a2b8, #00c6ff);
    --warning-gradient: linear-gradient(135deg, #ffc107, #fd7e14);
}

/* Tab Navigation */
.nav-tabs {
    border-bottom: none;
    gap: 0.5rem;
}

.nav-tabs .nav-link {
    border: 1px solid #dee2e6;
    background: white;
    color: #495057;
    font-weight: 500;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.nav-tabs .nav-link:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.nav-tabs .nav-link.active {
    background: var(--primary-gradient);
    color: white;
    border-color: transparent;
    box-shadow: 0 5px 15px rgba(0,198,255,0.3);
}

.nav-tabs .nav-link .badge {
    font-size: 0.7rem;
}

/* Form Controls */
.form-control-lg {
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
    padding: 0.75rem 1rem;
}

.form-control-lg:focus {
    border-color: #00c6ff;
    box-shadow: 0 0 0 3px rgba(0,198,255,0.1);
}

.input-group-text {
    padding: 0.75rem 1rem;
}

/* Table Styles */
.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    color: #495057;
    border-bottom-width: 2px;
    padding: 1rem;
    font-size: 0.9rem;
}

.table td {
    vertical-align: middle;
    padding: 1rem;
    border-color: #f0f0f0;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,198,255,0.02);
}

/* Stat Cards */
.stat-card {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2) !important;
}

.stat-card i {
    transition: all 0.3s ease;
}

.stat-card:hover i {
    transform: scale(1.1) rotate(5deg);
}

.bg-gradient-primary {
    background: var(--primary-gradient);
}

.bg-gradient-success {
    background: var(--success-gradient);
}

.bg-gradient-info {
    background: var(--info-gradient);
}

.bg-gradient-warning {
    background: var(--warning-gradient);
}

/* Featured Icon */
.featured-icon {
    width: 60px;
    height: 60px;
    background: var(--primary-gradient);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 5px 15px rgba(0,198,255,0.3);
}

/* Empty State */
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

/* Badge Styles */
.badge {
    font-weight: 500;
    font-size: 0.85rem;
    padding: 0.5rem 1rem;
}

.badge.bg-success {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
}

.badge.bg-warning {
    background: linear-gradient(135deg, #ffc107, #fd7e14) !important;
}

.badge.bg-danger {
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
}

/* Alert Styles */
.alert {
    padding: 1rem 1.25rem;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
}

/* Dark Mode Adjustments */
body.dark-mode .nav-tabs .nav-link {
    background: #1e1e1e;
    border-color: #333;
    color: #ddd;
}

body.dark-mode .nav-tabs .nav-link:hover {
    background: #2d2d2d;
}

body.dark-mode .nav-tabs .nav-link.active {
    background: var(--primary-gradient);
    color: white;
}

body.dark-mode .form-control-lg,
body.dark-mode .input-group-text {
    background: #2d2d2d;
    border-color: #444;
    color: #eee;
}

body.dark-mode .form-control-lg:focus {
    background: #2d2d2d;
    border-color: #00c6ff;
    color: #eee;
}

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

body.dark-mode .table-hover tbody tr:hover {
    background-color: #2d2d2d;
}

body.dark-mode .bg-light {
    background: #2d2d2d !important;
    color: #ddd !important;
}

body.dark-mode .empty-state-icon {
    background: #2d2d2d;
}

body.dark-mode .text-muted {
    color: #aaa !important;
}

body.dark-mode a.text-dark {
    color: #ddd !important;
}

body.dark-mode a.text-dark:hover {
    color: #00c6ff !important;
}

/* Responsive */
@media (max-width: 768px) {
    .nav-tabs {
        flex-wrap: nowrap;
        overflow-x: auto;
        padding-bottom: 0.5rem;
        scrollbar-width: thin;
    }
    
    .nav-tabs::-webkit-scrollbar {
        height: 3px;
    }
    
    .nav-tabs::-webkit-scrollbar-thumb {
        background: #00c6ff;
        border-radius: 10px;
    }
    
    .nav-tabs .nav-link {
        white-space: nowrap;
        font-size: 0.85rem;
        padding: 0.5rem 1rem;
    }
    
    .featured-icon {
        width: 50px;
        height: 50px;
    }
    
    .featured-icon i {
        font-size: 1.5rem;
    }
    
    .display-6 {
        font-size: 1.5rem;
    }
    
    .card-body {
        padding: 1.5rem !important;
    }
    
    .stat-card {
        padding: 1rem !important;
    }
    
    .stat-card h4 {
        font-size: 1.25rem;
    }
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.tab-pane {
    animation: fadeIn 0.5s ease;
}

/* Hover Effects */
.btn-primary {
    background: var(--primary-gradient);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,198,255,0.4);
}

.btn-outline-primary {
    border-color: #00c6ff;
    color: #00c6ff;
}

.btn-outline-primary:hover {
    background: var(--primary-gradient);
    border-color: transparent;
    transform: translateY(-2px);
}

.btn-outline-secondary:hover {
    transform: translateY(-2px);
}

/* Card Styles */
.card {
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
}
</style>

<script>
// Preview profile picture before upload
document.getElementById('profile_pic_input').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.querySelector('.rounded-circle').src = e.target.result;
        }
        reader.readAsDataURL(e.target.files[0]);
    }
});

// Confirm before logout
document.querySelectorAll('a[href="logout.php"]').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = this.href;
        }
    });
});
</script>

<?php
require_once 'footer.php';
?>
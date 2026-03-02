<?php
// Include database configuration first
require_once '../config.php';

// Now include the admin header
require_once 'admin_header.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];
$success_message = '';
$error_message = '';

// Fetch admin data from database
try {
    // First try to fetch from admins table
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If not found in admins table, try users table with admin role
    if (!$admin) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// If still no admin found, create default array to prevent errors
if (!$admin) {
    $admin = [
        'id' => $admin_id,
        'username' => $_SESSION['admin_username'] ?? 'Admin',
        'email' => '',
        'full_name' => '',
        'profile_pic' => 'assets/images/default-avatar.png',
        'password' => '',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);
        
        // Validation
        $errors = [];
        
        if (empty($username)) $errors[] = "Username is required";
        if (empty($email)) $errors[] = "Email is required";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        
        try {
            // Check which table to use
            $check_username = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
            $check_username->execute([$username, $admin_id]);
            if ($check_username->fetch()) {
                $errors[] = "Username already taken";
            }
            
            $check_email = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
            $check_email->execute([$email, $admin_id]);
            if ($check_email->fetch()) {
                $errors[] = "Email already registered";
            }
        } catch (PDOException $e) {
            // admins table might not exist, try users table
            $check_username = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ? AND role = 'admin'");
            $check_username->execute([$username, $admin_id]);
            if ($check_username->fetch()) {
                $errors[] = "Username already taken";
            }
            
            $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND role = 'admin'");
            $check_email->execute([$email, $admin_id]);
            if ($check_email->fetch()) {
                $errors[] = "Email already registered";
            }
        }
        
        if (empty($errors)) {
            // Handle profile picture upload
            $profile_pic = $admin['profile_pic'] ?? 'assets/images/default-avatar.png';
            
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_pic']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $upload_dir = '../uploads/admins/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                        // Delete old profile picture if not default
                        if ($profile_pic != 'assets/images/default-avatar.png' && file_exists('../' . $profile_pic)) {
                            unlink('../' . $profile_pic);
                        }
                        $profile_pic = 'uploads/admins/' . $new_filename;
                    }
                }
            }
            
            // Update admin profile
            try {
                // Try admins table first
                $update_sql = "UPDATE admins SET username = ?, email = ?, full_name = ?, profile_pic = ? WHERE id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                
                if ($update_stmt->execute([$username, $email, $full_name, $profile_pic, $admin_id])) {
                    $_SESSION['admin_username'] = $username;
                    $success_message = "Profile updated successfully!";
                    
                    // Refresh admin data
                    $admin['username'] = $username;
                    $admin['email'] = $email;
                    $admin['full_name'] = $full_name;
                    $admin['profile_pic'] = $profile_pic;
                } else {
                    // If admins table fails, try users table
                    $update_sql = "UPDATE users SET username = ?, email = ?, profile_pic = ? WHERE id = ? AND role = 'admin'";
                    $update_stmt = $pdo->prepare($update_sql);
                    
                    if ($update_stmt->execute([$username, $email, $profile_pic, $admin_id])) {
                        $_SESSION['admin_username'] = $username;
                        $success_message = "Profile updated successfully!";
                        
                        // Refresh admin data
                        $admin['username'] = $username;
                        $admin['email'] = $email;
                        $admin['profile_pic'] = $profile_pic;
                    } else {
                        $error_message = "Failed to update profile.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $errors = [];
        
        // Verify current password
        if (!password_verify($current_password, $admin['password'] ?? '')) {
            $errors[] = "Current password is incorrect";
        }
        
        if (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        if (empty($errors)) {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Try admins table first
                $update_pass = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                
                if ($update_pass->execute([$hashed_password, $admin_id])) {
                    $success_message = "Password changed successfully!";
                } else {
                    // Try users table
                    $update_pass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'admin'");
                    if ($update_pass->execute([$hashed_password, $admin_id])) {
                        $success_message = "Password changed successfully!";
                    } else {
                        $error_message = "Failed to change password.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fa-regular fa-user me-2"></i>Admin Profile</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Profile</li>
            </ol>
        </nav>
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

<div class="row">
    <!-- Left Column - Profile Info -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body text-center p-4">
                <div class="position-relative d-inline-block mb-4">
                    <img src="<?php echo '../' . ($admin['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>" 
                         alt="Profile Picture" 
                         class="rounded-circle border border-4 border-primary shadow"
                         style="width: 150px; height: 150px; object-fit: cover;">
                    <label for="profile_pic_input" class="btn btn-primary btn-sm rounded-circle position-absolute bottom-0 end-0 shadow"
                           style="width: 40px; height: 40px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-camera"></i>
                    </label>
                </div>
                
                <h3 class="h4 fw-bold mb-1"><?php echo htmlspecialchars($admin['full_name'] ?? $admin['username'] ?? 'Admin'); ?></h3>
                <p class="text-muted mb-3">
                    <i class="fa-regular fa-envelope me-1"></i><?php echo htmlspecialchars($admin['email'] ?? 'No email'); ?>
                </p>
                
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-primary rounded-pill px-3 py-2">
                        <i class="fa-regular fa-calendar me-1"></i>
                        Admin since <?php echo isset($admin['created_at']) ? date('M Y', strtotime($admin['created_at'])) : 'Recently'; ?>
                    </span>
                </div>
                
                <div class="text-start border-top pt-3">
                    <div class="row">
                        <div class="col-6 text-center">
                            <h6 class="fw-bold mb-0"><?php 
                                try {
                                    $total_campaigns = $pdo->query("SELECT COUNT(*) FROM campaigns")->fetchColumn();
                                    echo $total_campaigns ?: '0';
                                } catch (PDOException $e) {
                                    echo '0';
                                }
                            ?></h6>
                            <small class="text-muted">Campaigns</small>
                        </div>
                        <div class="col-6 text-center">
                            <h6 class="fw-bold mb-0"><?php 
                                try {
                                    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                                    echo $total_users ?: '0';
                                } catch (PDOException $e) {
                                    echo '0';
                                }
                            ?></h6>
                            <small class="text-muted">Users</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Activity Summary -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="card-title mb-3">
                    <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>
                    Recent Activity
                </h5>
                
                <div class="activity-list">
                    <?php
                    try {
                        // Fetch recent campaigns
                        $recent = $pdo->query("SELECT 'campaign' as type, title, created_at, status 
                                               FROM campaigns 
                                               ORDER BY created_at DESC 
                                               LIMIT 5")->fetchAll();
                        
                        if (count($recent) > 0) {
                            foreach ($recent as $activity): ?>
                                <div class="activity-item d-flex align-items-start mb-3 pb-2 border-bottom">
                                    <div class="activity-icon bg-light rounded-circle p-2 me-3">
                                        <i class="fa-solid fa-rocket text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($activity['title']); ?></p>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y', strtotime($activity['created_at'])); ?>
                                            <span class="badge bg-<?php 
                                                echo $activity['status'] == 'approved' ? 'success' : 
                                                    ($activity['status'] == 'pending' ? 'warning' : 'danger'); 
                                            ?> ms-2"><?php echo $activity['status']; ?></span>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; 
                        } else {
                            echo '<p class="text-muted text-center py-3">No recent activity</p>';
                        }
                    } catch (PDOException $e) {
                        echo '<p class="text-muted text-center py-3">Unable to load activity</p>';
                    } ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column - Edit Forms -->
    <div class="col-md-8">
        <!-- Profile Edit Form -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h5 class="card-title mb-4">
                    <i class="fa-regular fa-pen-to-square text-primary me-2"></i>
                    Edit Profile Information
                </h5>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="profile_pic" id="profile_pic_input" class="d-none" accept="image/*">
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fa-regular fa-user text-primary me-1"></i>
                                Username
                            </label>
                            <input type="text" name="username" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fa-regular fa-envelope text-primary me-1"></i>
                                Email Address
                            </label>
                            <input type="email" name="email" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">
                                <i class="fa-regular fa-id-card text-primary me-1"></i>
                                Full Name
                            </label>
                            <input type="text" name="full_name" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>" 
                                   placeholder="Enter your full name">
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" name="update_profile" class="btn btn-primary rounded-pill px-5">
                                <i class="fa-solid fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Change Password Form -->
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
                                       placeholder="Min. 8 characters" required>
                            </div>
                            <div class="form-text">Must be at least 8 characters long</div>
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
                            <button type="submit" name="change_password" class="btn btn-primary rounded-pill px-5">
                                <i class="fa-solid fa-key me-2"></i>Update Password
                            </button>
                        </div>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="alert alert-info rounded-3 border-0">
                    <div class="d-flex">
                        <i class="fa-solid fa-shield-hal fa-2x me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Security Tips</h6>
                            <p class="mb-0 small">Use a strong password that you don't use elsewhere. Enable two-factor authentication for additional security.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Picture Preview Script -->
<script>
document.getElementById('profile_pic_input')?.addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.querySelector('.rounded-circle').src = e.target.result;
        }
        reader.readAsDataURL(e.target.files[0]);
    }
});

// Password match validation
document.querySelector('input[name="confirm_password"]')?.addEventListener('input', function() {
    const newPass = document.querySelector('input[name="new_password"]').value;
    const confirmPass = this.value;
    
    if (confirmPass.length > 0) {
        if (newPass === confirmPass) {
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
        } else {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
        }
    }
});

// Enable tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<style>
/* Activity Item Styles */
.activity-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.activity-item {
    transition: all 0.3s ease;
}

.activity-item:hover {
    transform: translateX(5px);
    background: #f8f9fa;
    border-radius: 8px;
    padding-left: 10px;
}

/* Dark mode adjustments */
body.dark-mode .activity-item:hover {
    background: #2d2d2d;
}

body.dark-mode .border-bottom {
    border-bottom-color: #333 !important;
}

body.dark-mode .bg-light {
    background: #2d2d2d !important;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .card-body {
        padding: 1.5rem !important;
    }
}
</style>

<?php
require_once 'admin_footer.php';
?>
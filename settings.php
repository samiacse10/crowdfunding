<?php
require_once 'header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = "Please login to view settings";
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Handle notification settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_notifications'])) {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $campaign_updates = isset($_POST['campaign_updates']) ? 1 : 0;
        $new_donations = isset($_POST['new_donations']) ? 1 : 0;
        $newsletter = isset($_POST['newsletter']) ? 1 : 0;
        
        // Check if notification settings table exists
        try {
            $check = $pdo->query("SHOW TABLES LIKE 'user_settings'");
            if ($check->rowCount() == 0) {
                // Create table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS user_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    email_notifications TINYINT DEFAULT 1,
                    campaign_updates TINYINT DEFAULT 1,
                    new_donations TINYINT DEFAULT 1,
                    newsletter TINYINT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_user (user_id)
                )");
            }
            
            // Insert or update settings
            $stmt = $pdo->prepare("INSERT INTO user_settings (user_id, email_notifications, campaign_updates, new_donations, newsletter) 
                                   VALUES (?, ?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE 
                                   email_notifications = VALUES(email_notifications),
                                   campaign_updates = VALUES(campaign_updates),
                                   new_donations = VALUES(new_donations),
                                   newsletter = VALUES(newsletter)");
            
            if ($stmt->execute([$user_id, $email_notifications, $campaign_updates, $new_donations, $newsletter])) {
                $_SESSION['success'] = "Notification settings updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update notification settings.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
        redirect('settings.php');
    }
    
    // Handle privacy settings update
    if (isset($_POST['update_privacy'])) {
        $profile_visibility = $_POST['profile_visibility'];
        $show_donations = isset($_POST['show_donations']) ? 1 : 0;
        $show_campaigns = isset($_POST['show_campaigns']) ? 1 : 0;
        
        try {
            // Check if privacy settings table exists
            $check = $pdo->query("SHOW TABLES LIKE 'privacy_settings'");
            if ($check->rowCount() == 0) {
                // Create table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS privacy_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    profile_visibility ENUM('public', 'private', 'friends') DEFAULT 'public',
                    show_donations TINYINT DEFAULT 1,
                    show_campaigns TINYINT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_user (user_id)
                )");
            }
            
            // Insert or update privacy settings
            $stmt = $pdo->prepare("INSERT INTO privacy_settings (user_id, profile_visibility, show_donations, show_campaigns) 
                                   VALUES (?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE 
                                   profile_visibility = VALUES(profile_visibility),
                                   show_donations = VALUES(show_donations),
                                   show_campaigns = VALUES(show_campaigns)");
            
            if ($stmt->execute([$user_id, $profile_visibility, $show_donations, $show_campaigns])) {
                $_SESSION['success'] = "Privacy settings updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update privacy settings.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
        redirect('settings.php');
    }
    
    // Handle account deletion
    if (isset($_POST['delete_account'])) {
        $confirm_password = $_POST['confirm_password'];
        $confirm_text = $_POST['confirm_text'];
        
        // Verify password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        
        if (password_verify($confirm_password, $user_data['password'])) {
            if ($confirm_text === 'DELETE') {
                // Start transaction
                $pdo->beginTransaction();
                try {
                    // Delete user's campaigns
                    $pdo->prepare("DELETE FROM campaigns WHERE user_id = ?")->execute([$user_id]);
                    
                    // Delete user's donations (if table exists)
                    try {
                        $pdo->prepare("DELETE FROM donations WHERE user_id = ?")->execute([$user_id]);
                    } catch (PDOException $e) {
                        // Table doesn't exist, continue
                    }
                    
                    // Delete user settings
                    try {
                        $pdo->prepare("DELETE FROM user_settings WHERE user_id = ?")->execute([$user_id]);
                    } catch (PDOException $e) {
                        // Table doesn't exist, continue
                    }
                    
                    try {
                        $pdo->prepare("DELETE FROM privacy_settings WHERE user_id = ?")->execute([$user_id]);
                    } catch (PDOException $e) {
                        // Table doesn't exist, continue
                    }
                    
                    // Delete user
                    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
                    
                    $pdo->commit();
                    
                    // Destroy session
                    session_destroy();
                    
                    $_SESSION['success'] = "Your account has been permanently deleted.";
                    redirect('index.php');
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['error'] = "Failed to delete account: " . $e->getMessage();
                    redirect('settings.php');
                }
            } else {
                $_SESSION['error'] = "Please type DELETE to confirm account deletion.";
            }
        } else {
            $_SESSION['error'] = "Current password is incorrect.";
        }
        redirect('settings.php');
    }
}

// Fetch current settings
$notification_settings = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $notification_settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table doesn't exist, use defaults
}

$privacy_settings = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM privacy_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $privacy_settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table doesn't exist, use defaults
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Default values
$email_notifications = $notification_settings['email_notifications'] ?? 1;
$campaign_updates = $notification_settings['campaign_updates'] ?? 1;
$new_donations = $notification_settings['new_donations'] ?? 1;
$newsletter = $notification_settings['newsletter'] ?? 1;

$profile_visibility = $privacy_settings['profile_visibility'] ?? 'public';
$show_donations = $privacy_settings['show_donations'] ?? 1;
$show_campaigns = $privacy_settings['show_campaigns'] ?? 1;
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="d-flex align-items-center mb-4">
        <div class="featured-icon me-3">
            <i class="fa-solid fa-gear fa-2x"></i>
        </div>
        <div>
            <h1 class="display-6 fw-bold mb-1">Settings</h1>
            <p class="text-muted mb-0">Manage your account preferences and privacy</p>
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
        <!-- Left Column - Settings Navigation -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 100px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-3 p-2">
                        <img src="<?php echo htmlspecialchars($user['profile_pic'] ?? 'assets/images/default-avatar.png'); ?>" 
                             class="rounded-circle me-3" 
                             style="width: 50px; height: 50px; object-fit: cover;">
                        <div>
                            <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($user['username']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                        </div>
                    </div>
                    
                    <div class="nav flex-column nav-pills" role="tablist">
                        <button class="nav-link active text-start rounded-pill mb-2" 
                                id="notifications-tab" 
                                data-bs-toggle="pill" 
                                data-bs-target="#notifications" 
                                type="button" 
                                role="tab">
                            <i class="fa-solid fa-bell me-2"></i>Notifications
                        </button>
                        
                        <button class="nav-link text-start rounded-pill mb-2" 
                                id="privacy-tab" 
                                data-bs-toggle="pill" 
                                data-bs-target="#privacy" 
                                type="button" 
                                role="tab">
                            <i class="fa-solid fa-shield-hal met-2"></i>Privacy
                        </button>
                        
                        <button class="nav-link text-start rounded-pill mb-2" 
                                id="security-tab" 
                                data-bs-toggle="pill" 
                                data-bs-target="#security" 
                                type="button" 
                                role="tab">
                            <i class="fa-solid fa-lock me-2"></i>Security
                        </button>
                        
                        <button class="nav-link text-start rounded-pill mb-2" 
                                id="danger-tab" 
                                data-bs-toggle="pill" 
                                data-bs-target="#danger" 
                                type="button" 
                                role="tab">
                            <i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i>Danger Zone
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Settings Content -->
        <div class="col-lg-9">
            <div class="tab-content">
                <!-- Notifications Settings -->
                <div class="tab-pane fade show active" id="notifications" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">
                                <i class="fa-solid fa-bell text-primary me-2"></i>
                                Notification Preferences
                            </h5>
                            
                            <form method="POST">
                                <div class="settings-section mb-4">
                                    <h6 class="fw-semibold mb-3">Email Notifications</h6>
                                    
                                    <div class="settings-item d-flex justify-content-between align-items-center p-3 bg-light rounded-3 mb-2">
                                        <div>
                                            <label class="fw-medium mb-1">Email Notifications</label>
                                            <p class="text-muted small mb-0">Receive email notifications about your account</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="email_notifications" 
                                                   id="email_notifications" <?php echo $email_notifications ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                    
                                    <div class="settings-item d-flex justify-content-between align-items-center p-3 bg-light rounded-3 mb-2">
                                        <div>
                                            <label class="fw-medium mb-1">Campaign Updates</label>
                                            <p class="text-muted small mb-0">Get updates about your campaigns</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="campaign_updates" 
                                                   id="campaign_updates" <?php echo $campaign_updates ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                    
                                    <div class="settings-item d-flex justify-content-between align-items-center p-3 bg-light rounded-3 mb-2">
                                        <div>
                                            <label class="fw-medium mb-1">New Donations</label>
                                            <p class="text-muted small mb-0">Get notified when someone donates to your campaign</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="new_donations" 
                                                   id="new_donations" <?php echo $new_donations ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                    
                                    <div class="settings-item d-flex justify-content-between align-items-center p-3 bg-light rounded-3">
                                        <div>
                                            <label class="fw-medium mb-1">Newsletter</label>
                                            <p class="text-muted small mb-0">Receive our monthly newsletter with updates and tips</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="newsletter" 
                                                   id="newsletter" <?php echo $newsletter ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" name="update_notifications" class="btn btn-primary rounded-pill px-5">
                                        <i class="fa-solid fa-save me-2"></i>Save Notification Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Privacy Settings -->
                <div class="tab-pane fade" id="privacy" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">
                                <i class="fa-solid fa-shield-hal text-primary me-2"></i>
                                Privacy Settings
                            </h5>
                            
                            <form method="POST">
                                <div class="settings-section mb-4">
                                    <h6 class="fw-semibold mb-3">Profile Visibility</h6>
                                    
                                    <div class="mb-3">
                                        <select name="profile_visibility" class="form-select form-select-lg rounded-3">
                                            <option value="public" <?php echo $profile_visibility == 'public' ? 'selected' : ''; ?>>
                                                <i class="fa-solid fa-globe me-2"></i>Public - Everyone can see your profile
                                            </option>
                                            <option value="private" <?php echo $profile_visibility == 'private' ? 'selected' : ''; ?>>
                                                <i class="fa-solid fa-lock me-2"></i>Private - Only you can see your profile
                                            </option>
                                            <option value="friends" <?php echo $profile_visibility == 'friends' ? 'selected' : ''; ?>>
                                                <i class="fa-solid fa-users me-2"></i>Friends - Only logged in users can see your profile
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <h6 class="fw-semibold mb-3 mt-4">Content Visibility</h6>
                                    
                                    <div class="settings-item d-flex justify-content-between align-items-center p-3 bg-light rounded-3 mb-2">
                                        <div>
                                            <label class="fw-medium mb-1">Show Donations</label>
                                            <p class="text-muted small mb-0">Display your donation history on your profile</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="show_donations" 
                                                   id="show_donations" <?php echo $show_donations ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                    
                                    <div class="settings-item d-flex justify-content-between align-items-center p-3 bg-light rounded-3">
                                        <div>
                                            <label class="fw-medium mb-1">Show Campaigns</label>
                                            <p class="text-muted small mb-0">Display your campaigns on your profile</p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="show_campaigns" 
                                                   id="show_campaigns" <?php echo $show_campaigns ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" name="update_privacy" class="btn btn-primary rounded-pill px-5">
                                        <i class="fa-solid fa-save me-2"></i>Save Privacy Settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">
                                <i class="fa-solid fa-lock text-primary me-2"></i>
                                Security Settings
                            </h5>
                            
                            <div class="settings-section mb-4">
                                <h6 class="fw-semibold mb-3">Two-Factor Authentication</h6>
                                
                                <div class="settings-item d-flex justify-content-between align-items-center p-3 bg-light rounded-3 mb-4">
                                    <div>
                                        <label class="fw-medium mb-1">Two-Factor Authentication</label>
                                        <p class="text-muted small mb-0">Add an extra layer of security to your account</p>
                                    </div>
                                    <button class="btn btn-outline-primary rounded-pill px-4" disabled>
                                        <i class="fa-solid fa-clock me-2"></i>Coming Soon
                                    </button>
                                </div>
                                
                                <h6 class="fw-semibold mb-3">Login Sessions</h6>
                                
                                <div class="settings-item p-3 bg-light rounded-3 mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <span class="fw-medium">Current Session</span>
                                            <span class="badge bg-success ms-2">Active</span>
                                        </div>
                                        <small class="text-muted"><?php echo date('M d, Y H:i'); ?></small>
                                    </div>
                                    <p class="text-muted small mb-0">
                                        <i class="fa-solid fa-location-dot me-1"></i>
                                        <?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP'; ?>
                                    </p>
                                </div>
                                
                                <button class="btn btn-outline-danger rounded-pill mt-3" disabled>
                                    <i class="fa-solid fa-sign-out-alt me-2"></i>Logout All Devices (Coming Soon)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="tab-pane fade" id="danger" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 border-danger border-start border-4">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4 text-danger">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                Danger Zone
                            </h5>
                            
                            <div class="settings-section mb-4">
                                <div class="alert alert-warning rounded-3 mb-4">
                                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                                    <strong>Warning:</strong> These actions are irreversible. Please proceed with caution.
                                </div>
                                
                                <h6 class="fw-semibold mb-3">Delete Account</h6>
                                
                                <form method="POST" onsubmit="return confirm('Are you absolutely sure? This action cannot be undone.');">
                                    <div class="settings-item p-4 bg-light rounded-3">
                                        <p class="mb-3">Permanently delete your account and all associated data. This includes:</p>
                                        <ul class="text-muted small mb-4">
                                            <li>All your campaigns</li>
                                            <li>All your donations</li>
                                            <li>Profile information</li>
                                            <li>Settings and preferences</li>
                                        </ul>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Confirm Password</label>
                                            <input type="password" name="confirm_password" class="form-control form-control-lg rounded-3" 
                                                   placeholder="Enter your current password" required>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label fw-semibold">Type DELETE to confirm</label>
                                            <input type="text" name="confirm_text" class="form-control form-control-lg rounded-3" 
                                                   placeholder="DELETE" pattern="DELETE" required>
                                        </div>
                                        
                                        <button type="submit" name="delete_account" class="btn btn-danger rounded-pill px-5">
                                            <i class="fa-solid fa-trash-can me-2"></i>Permanently Delete Account
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create tables if they don't exist -->
<?php
// Check and create settings tables if needed
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email_notifications TINYINT DEFAULT 1,
        campaign_updates TINYINT DEFAULT 1,
        new_donations TINYINT DEFAULT 1,
        newsletter TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user (user_id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS privacy_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        profile_visibility ENUM('public', 'private', 'friends') DEFAULT 'public',
        show_donations TINYINT DEFAULT 1,
        show_campaigns TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user (user_id)
    )");
} catch (PDOException $e) {
    // Tables already exist or can't be created
}
?>

<style>
/* Settings Page Specific Styles */
:root {
    --primary-gradient: linear-gradient(135deg, #141e30, #243b55);
    --danger-gradient: linear-gradient(135deg, #dc3545, #c82333);
    --success-gradient: linear-gradient(135deg, #28a745, #20c997);
}

/* Settings Navigation */
.nav-pills .nav-link {
    color: #495057;
    transition: all 0.3s ease;
    padding: 0.75rem 1rem;
}

.nav-pills .nav-link:hover {
    background: #f8f9fa;
    transform: translateX(5px);
}

.nav-pills .nav-link.active {
    background: var(--primary-gradient);
    color: white;
    box-shadow: 0 5px 15px rgba(0,198,255,0.3);
}

.nav-pills .nav-link i {
    width: 20px;
    text-align: center;
}

/* Settings Items */
.settings-item {
    transition: all 0.3s ease;
}

.settings-item:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* Form Switches */
.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
    cursor: pointer;
}

.form-switch .form-check-input:checked {
    background-color: #00c6ff;
    border-color: #00c6ff;
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

/* Danger Zone */
.border-danger.border-start {
    border-width: 4px !important;
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

.alert-warning {
    background: linear-gradient(135deg, #fff3cd, #ffeeba);
    color: #856404;
}

/* Dark Mode Adjustments */
body.dark-mode .nav-pills .nav-link {
    color: #ddd;
}

body.dark-mode .nav-pills .nav-link:hover {
    background: #2d2d2d;
}

body.dark-mode .nav-pills .nav-link.active {
    background: var(--primary-gradient);
    color: white;
}

body.dark-mode .bg-light {
    background: #2d2d2d !important;
    color: #ddd !important;
}

body.dark-mode .text-muted {
    color: #aaa !important;
}

body.dark-mode .form-select,
body.dark-mode .form-control {
    background: #2d2d2d;
    border-color: #444;
    color: #eee;
}

body.dark-mode .form-select:focus,
body.dark-mode .form-control:focus {
    background: #2d2d2d;
    border-color: #00c6ff;
    color: #eee;
}

body.dark-mode .settings-item:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

/* Responsive */
@media (max-width: 768px) {
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
    
    .settings-item {
        flex-direction: column;
        text-align: center;
    }
    
    .settings-item .form-switch {
        margin-top: 1rem;
    }
    
    .sticky-top {
        position: relative;
        top: 0;
    }
}

/* Animation */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.tab-pane {
    animation: slideIn 0.5s ease;
}

/* Button Styles */
.btn-primary {
    background: var(--primary-gradient);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,198,255,0.4);
}

.btn-danger {
    background: var(--danger-gradient);
    border: none;
    transition: all 0.3s ease;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(220,53,69,0.4);
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
// Confirm before dangerous actions
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        if (this.querySelector('button[name="delete_account"]')) {
            if (!confirm('⚠️ WARNING: This will permanently delete your account and all associated data. This action cannot be undone. Are you absolutely sure?')) {
                e.preventDefault();
            }
        }
    });
});

// Preview profile picture before upload (if needed)
document.getElementById('profile_pic_input')?.addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.querySelector('.rounded-circle').src = e.target.result;
        }
        reader.readAsDataURL(e.target.files[0]);
    }
});

// Toggle all switches functionality
document.querySelectorAll('.form-switch input').forEach(switch_ => {
    switch_.addEventListener('change', function() {
        console.log(this.id + ' changed to: ' + this.checked);
    });
});
</script>

<?php
require_once 'footer.php';
?>
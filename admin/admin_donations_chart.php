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

// Handle different settings updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // General Settings
    if (isset($_POST['update_general'])) {
        $site_name = trim($_POST['site_name']);
        $site_description = trim($_POST['site_description']);
        $site_email = trim($_POST['site_email']);
        $site_phone = trim($_POST['site_phone']);
        $site_address = trim($_POST['site_address']);
        $site_currency = $_POST['site_currency'];
        $site_timezone = $_POST['site_timezone'];
        
        try {
            // Check if settings table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT,
                setting_type VARCHAR(50) DEFAULT 'general',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            // Update or insert settings
            $settings = [
                'site_name' => $site_name,
                'site_description' => $site_description,
                'site_email' => $site_email,
                'site_phone' => $site_phone,
                'site_address' => $site_address,
                'site_currency' => $site_currency,
                'site_timezone' => $site_timezone
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                       ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
            
            $success_message = "General settings updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
    
    // Payment Settings
    if (isset($_POST['update_payment'])) {
        $bkash_enabled = isset($_POST['bkash_enabled']) ? 1 : 0;
        $bkash_number = trim($_POST['bkash_number']);
        $bkash_merchant = trim($_POST['bkash_merchant']);
        
        $nagad_enabled = isset($_POST['nagad_enabled']) ? 1 : 0;
        $nagad_number = trim($_POST['nagad_number']);
        
        $rocket_enabled = isset($_POST['rocket_enabled']) ? 1 : 0;
        $rocket_number = trim($_POST['rocket_number']);
        
        $bank_enabled = isset($_POST['bank_enabled']) ? 1 : 0;
        $bank_name = trim($_POST['bank_name']);
        $bank_account_name = trim($_POST['bank_account_name']);
        $bank_account_number = trim($_POST['bank_account_number']);
        $bank_routing = trim($_POST['bank_routing']);
        $bank_branch = trim($_POST['bank_branch']);
        
        try {
            // Create payment settings table if not exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS payment_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                payment_method VARCHAR(50) NOT NULL UNIQUE,
                enabled TINYINT DEFAULT 1,
                account_number VARCHAR(100),
                merchant_number VARCHAR(100),
                account_name VARCHAR(100),
                bank_name VARCHAR(100),
                routing_number VARCHAR(50),
                branch_name VARCHAR(100),
                additional_info TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            // Update bKash settings
            $stmt = $pdo->prepare("INSERT INTO payment_settings (payment_method, enabled, account_number, merchant_number) 
                                   VALUES ('bkash', ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE enabled = ?, account_number = ?, merchant_number = ?");
            $stmt->execute([$bkash_enabled, $bkash_number, $bkash_merchant, $bkash_enabled, $bkash_number, $bkash_merchant]);
            
            // Update Nagad settings
            $stmt = $pdo->prepare("INSERT INTO payment_settings (payment_method, enabled, account_number) 
                                   VALUES ('nagad', ?, ?) 
                                   ON DUPLICATE KEY UPDATE enabled = ?, account_number = ?");
            $stmt->execute([$nagad_enabled, $nagad_number, $nagad_enabled, $nagad_number]);
            
            // Update Rocket settings
            $stmt = $pdo->prepare("INSERT INTO payment_settings (payment_method, enabled, account_number) 
                                   VALUES ('rocket', ?, ?) 
                                   ON DUPLICATE KEY UPDATE enabled = ?, account_number = ?");
            $stmt->execute([$rocket_enabled, $rocket_number, $rocket_enabled, $rocket_number]);
            
            // Update Bank settings
            $stmt = $pdo->prepare("INSERT INTO payment_settings (payment_method, enabled, bank_name, account_name, account_number, routing_number, branch_name) 
                                   VALUES ('bank_transfer', ?, ?, ?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE enabled = ?, bank_name = ?, account_name = ?, account_number = ?, routing_number = ?, branch_name = ?");
            $stmt->execute([$bank_enabled, $bank_name, $bank_account_name, $bank_account_number, $bank_routing, $bank_branch, 
                           $bank_enabled, $bank_name, $bank_account_name, $bank_account_number, $bank_routing, $bank_branch]);
            
            $success_message = "Payment settings updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
    
    // Email Settings
    if (isset($_POST['update_email'])) {
        $smtp_host = trim($_POST['smtp_host']);
        $smtp_port = intval($_POST['smtp_port']);
        $smtp_username = trim($_POST['smtp_username']);
        $smtp_password = trim($_POST['smtp_password']);
        $smtp_encryption = $_POST['smtp_encryption'];
        $from_email = trim($_POST['from_email']);
        $from_name = trim($_POST['from_name']);
        
        try {
            // Create email settings table if not exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS email_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            $email_settings = [
                'smtp_host' => $smtp_host,
                'smtp_port' => $smtp_port,
                'smtp_username' => $smtp_username,
                'smtp_password' => $smtp_password,
                'smtp_encryption' => $smtp_encryption,
                'from_email' => $from_email,
                'from_name' => $from_name
            ];
            
            foreach ($email_settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO email_settings (setting_key, setting_value) VALUES (?, ?) 
                                       ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
            
            $success_message = "Email settings updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
    
    // Security Settings
    if (isset($_POST['update_security'])) {
        $two_factor_auth = isset($_POST['two_factor_auth']) ? 1 : 0;
        $recaptcha_enabled = isset($_POST['recaptcha_enabled']) ? 1 : 0;
        $recaptcha_site_key = trim($_POST['recaptcha_site_key']);
        $recaptcha_secret_key = trim($_POST['recaptcha_secret_key']);
        $session_timeout = intval($_POST['session_timeout']);
        $max_login_attempts = intval($_POST['max_login_attempts']);
        
        try {
            // Create security settings table if not exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS security_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            $security_settings = [
                'two_factor_auth' => $two_factor_auth,
                'recaptcha_enabled' => $recaptcha_enabled,
                'recaptcha_site_key' => $recaptcha_site_key,
                'recaptcha_secret_key' => $recaptcha_secret_key,
                'session_timeout' => $session_timeout,
                'max_login_attempts' => $max_login_attempts
            ];
            
            foreach ($security_settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO security_settings (setting_key, setting_value) VALUES (?, ?) 
                                       ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
            
            $success_message = "Security settings updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
    
    // Maintenance Settings
    if (isset($_POST['update_maintenance'])) {
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $maintenance_message = trim($_POST['maintenance_message']);
        $allow_admin_access = isset($_POST['allow_admin_access']) ? 1 : 0;
        
        try {
            // Create maintenance settings table if not exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS maintenance_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            $maintenance_settings = [
                'maintenance_mode' => $maintenance_mode,
                'maintenance_message' => $maintenance_message,
                'allow_admin_access' => $allow_admin_access
            ];
            
            foreach ($maintenance_settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO maintenance_settings (setting_key, setting_value) VALUES (?, ?) 
                                       ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
            
            $success_message = "Maintenance settings updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch current settings
$general_settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $general_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Table might not exist, use defaults
}

// Fetch payment settings
$payment_settings = [];
try {
    $stmt = $pdo->query("SELECT * FROM payment_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $payment_settings[$row['payment_method']] = $row;
    }
} catch (PDOException $e) {
    // Table might not exist
}

// Fetch email settings
$email_settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM email_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $email_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Table might not exist
}

// Fetch security settings
$security_settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM security_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $security_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Table might not exist
}

// Fetch maintenance settings
$maintenance_settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM maintenance_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $maintenance_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Table might not exist
}

// Default values
$site_name = $general_settings['site_name'] ?? 'Crowdfunding Platform';
$site_description = $general_settings['site_description'] ?? 'A platform for crowdfunding campaigns';
$site_email = $general_settings['site_email'] ?? 'info@crowdfund.com';
$site_phone = $general_settings['site_phone'] ?? '+1 (555) 123-4567';
$site_address = $general_settings['site_address'] ?? '123 Business Ave, New York, NY 10001';
$site_currency = $general_settings['site_currency'] ?? 'USD';
$site_timezone = $general_settings['site_timezone'] ?? 'America/New_York';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fa-solid fa-gear me-2"></i>Admin Settings</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Settings</li>
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

<!-- Settings Tabs -->
<ul class="nav nav-tabs border-0 mb-4" id="settingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active rounded-pill px-4 py-2 me-2" 
                id="general-tab" 
                data-bs-toggle="tab" 
                data-bs-target="#general" 
                type="button" 
                role="tab">
            <i class="fa-solid fa-globe me-2"></i>General
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4 py-2 me-2" 
                id="payment-tab" 
                data-bs-toggle="tab" 
                data-bs-target="#payment" 
                type="button" 
                role="tab">
            <i class="fa-solid fa-credit-card me-2"></i>Payment
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4 py-2 me-2" 
                id="email-tab" 
                data-bs-toggle="tab" 
                data-bs-target="#email" 
                type="button" 
                role="tab">
            <i class="fa-solid fa-envelope me-2"></i>Email
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4 py-2 me-2" 
                id="security-tab" 
                data-bs-toggle="tab" 
                data-bs-target="#security" 
                type="button" 
                role="tab">
            <i class="fa-solid fa-shield me-2"></i>Security
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4 py-2" 
                id="maintenance-tab" 
                data-bs-toggle="tab" 
                data-bs-target="#maintenance" 
                type="button" 
                role="tab">
            <i class="fa-solid fa-wrench me-2"></i>Maintenance
        </button>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content">
    <!-- General Settings Tab -->
    <div class="tab-pane fade show active" id="general" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="card-title mb-4">
                    <i class="fa-solid fa-globe text-primary me-2"></i>
                    General Settings
                </h5>
                
                <form method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Site Name</label>
                            <input type="text" name="site_name" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($site_name); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Site Email</label>
                            <input type="email" name="site_email" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($site_email); ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-semibold">Site Description</label>
                            <textarea name="site_description" class="form-control form-control-lg rounded-3" 
                                      rows="3"><?php echo htmlspecialchars($site_description); ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="site_phone" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($site_phone); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Currency</label>
                            <select name="site_currency" class="form-select form-select-lg rounded-3">
                                <option value="USD" <?php echo $site_currency == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                <option value="BDT" <?php echo $site_currency == 'BDT' ? 'selected' : ''; ?>>BDT (৳)</option>
                                <option value="EUR" <?php echo $site_currency == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                <option value="GBP" <?php echo $site_currency == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Timezone</label>
                            <select name="site_timezone" class="form-select form-select-lg rounded-3">
                                <option value="America/New_York" <?php echo $site_timezone == 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                <option value="America/Chicago" <?php echo $site_timezone == 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                <option value="America/Denver" <?php echo $site_timezone == 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                                <option value="America/Los_Angeles" <?php echo $site_timezone == 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                <option value="Asia/Dhaka" <?php echo $site_timezone == 'Asia/Dhaka' ? 'selected' : ''; ?>>Bangladesh Time</option>
                                <option value="UTC" <?php echo $site_timezone == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Address</label>
                            <input type="text" name="site_address" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($site_address); ?>">
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" name="update_general" class="btn btn-primary rounded-pill px-5">
                                <i class="fa-solid fa-save me-2"></i>Save General Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Payment Settings Tab -->
    <div class="tab-pane fade" id="payment" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="card-title mb-4">
                    <i class="fa-solid fa-credit-card text-primary me-2"></i>
                    Payment Gateway Settings
                </h5>
                
                <form method="POST">
                    <!-- bKash Settings -->
                    <div class="payment-section mb-4 p-3 bg-light rounded-4">
                        <h6 class="fw-semibold mb-3">
                            <i class="fa-solid fa-credit-card text-primary me-2"></i>
                            bKash
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="bkash_enabled" 
                                           id="bkash_enabled" <?php echo ($payment_settings['bkash']['enabled'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="bkash_enabled">Enable bKash</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">bKash Number</label>
                                <input type="text" name="bkash_number" class="form-control" 
                                       value="<?php echo htmlspecialchars($payment_settings['bkash']['account_number'] ?? '019XXXXXXXX'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Merchant Number</label>
                                <input type="text" name="bkash_merchant" class="form-control" 
                                       value="<?php echo htmlspecialchars($payment_settings['bkash']['merchant_number'] ?? '018XXXXXXXX'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Nagad Settings -->
                    <div class="payment-section mb-4 p-3 bg-light rounded-4">
                        <h6 class="fw-semibold mb-3">
                            <i class="fa-solid fa-credit-card text-primary me-2"></i>
                            Nagad
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="nagad_enabled" 
                                           id="nagad_enabled" <?php echo ($payment_settings['nagad']['enabled'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="nagad_enabled">Enable Nagad</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nagad Number</label>
                                <input type="text" name="nagad_number" class="form-control" 
                                       value="<?php echo htmlspecialchars($payment_settings['nagad']['account_number'] ?? '017XXXXXXXX'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Rocket Settings -->
                    <div class="payment-section mb-4 p-3 bg-light rounded-4">
                        <h6 class="fw-semibold mb-3">
                            <i class="fa-solid fa-rocket text-primary me-2"></i>
                            Rocket
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="rocket_enabled" 
                                           id="rocket_enabled" <?php echo ($payment_settings['rocket']['enabled'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="rocket_enabled">Enable Rocket</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Rocket Number</label>
                                <input type="text" name="rocket_number" class="form-control" 
                                       value="<?php echo htmlspecialchars($payment_settings['rocket']['account_number'] ?? '016XXXXXXXX'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bank Transfer Settings -->
                    <div class="payment-section mb-4 p-3 bg-light rounded-4">
                        <h6 class="fw-semibold mb-3">
                            <i class="fa-solid fa-building-columns text-primary me-2"></i>
                            Bank Transfer
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="bank_enabled" 
                                           id="bank_enabled" <?php echo ($payment_settings['bank_transfer']['enabled'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="bank_enabled">Enable Bank Transfer</label>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <input type="text" name="bank_name" class="form-control mb-2" 
                                       placeholder="Bank Name" value="<?php echo htmlspecialchars($payment_settings['bank_transfer']['bank_name'] ?? 'Islami Bank Bangladesh Ltd.'); ?>">
                                <input type="text" name="bank_account_name" class="form-control mb-2" 
                                       placeholder="Account Name" value="<?php echo htmlspecialchars($payment_settings['bank_transfer']['account_name'] ?? 'Crowdfunding Platform'); ?>">
                                <input type="text" name="bank_account_number" class="form-control mb-2" 
                                       placeholder="Account Number" value="<?php echo htmlspecialchars($payment_settings['bank_transfer']['account_number'] ?? '12345678901'); ?>">
                                <div class="row">
                                    <div class="col">
                                        <input type="text" name="bank_routing" class="form-control" 
                                               placeholder="Routing Number" value="<?php echo htmlspecialchars($payment_settings['bank_transfer']['routing_number'] ?? '123456789'); ?>">
                                    </div>
                                    <div class="col">
                                        <input type="text" name="bank_branch" class="form-control" 
                                               placeholder="Branch Name" value="<?php echo htmlspecialchars($payment_settings['bank_transfer']['branch_name'] ?? 'Motijheel, Dhaka'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" name="update_payment" class="btn btn-primary rounded-pill px-5">
                            <i class="fa-solid fa-save me-2"></i>Save Payment Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Email Settings Tab -->
    <div class="tab-pane fade" id="email" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="card-title mb-4">
                    <i class="fa-solid fa-envelope text-primary me-2"></i>
                    Email Settings
                </h5>
                
                <form method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($email_settings['smtp_host'] ?? 'smtp.gmail.com'); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMTP Port</label>
                            <input type="number" name="smtp_port" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($email_settings['smtp_port'] ?? '587'); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMTP Username</label>
                            <input type="text" name="smtp_username" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($email_settings['smtp_username'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMTP Password</label>
                            <input type="password" name="smtp_password" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($email_settings['smtp_password'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SMTP Encryption</label>
                            <select name="smtp_encryption" class="form-select form-select-lg rounded-3">
                                <option value="tls" <?php echo ($email_settings['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo ($email_settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value="none" <?php echo ($email_settings['smtp_encryption'] ?? '') == 'none' ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">From Email</label>
                            <input type="email" name="from_email" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($email_settings['from_email'] ?? $site_email); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">From Name</label>
                            <input type="text" name="from_name" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($email_settings['from_name'] ?? $site_name); ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" name="update_email" class="btn btn-primary rounded-pill px-5">
                                <i class="fa-solid fa-save me-2"></i>Save Email Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Security Settings Tab -->
    <div class="tab-pane fade" id="security" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="card-title mb-4">
                    <i class="fa-solid fa-shield text-primary me-2"></i>
                    Security Settings
                </h5>
                
                <form method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="two_factor_auth" 
                                       id="two_factor_auth" <?php echo ($security_settings['two_factor_auth'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="two_factor_auth">Enable Two-Factor Authentication</label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="recaptcha_enabled" 
                                       id="recaptcha_enabled" <?php echo ($security_settings['recaptcha_enabled'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="recaptcha_enabled">Enable reCAPTCHA</label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Session Timeout (minutes)</label>
                            <input type="number" name="session_timeout" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($security_settings['session_timeout'] ?? '30'); ?>" min="5" max="480">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Max Login Attempts</label>
                            <input type="number" name="max_login_attempts" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($security_settings['max_login_attempts'] ?? '5'); ?>" min="1" max="20">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">reCAPTCHA Site Key</label>
                            <input type="text" name="recaptcha_site_key" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($security_settings['recaptcha_site_key'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">reCAPTCHA Secret Key</label>
                            <input type="text" name="recaptcha_secret_key" class="form-control form-control-lg rounded-3" 
                                   value="<?php echo htmlspecialchars($security_settings['recaptcha_secret_key'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" name="update_security" class="btn btn-primary rounded-pill px-5">
                                <i class="fa-solid fa-save me-2"></i>Save Security Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Maintenance Settings Tab -->
    <div class="tab-pane fade" id="maintenance" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="card-title mb-4">
                    <i class="fa-solid fa-wrench text-primary me-2"></i>
                    Maintenance Settings
                </h5>
                
                <form method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="maintenance_mode" 
                                       id="maintenance_mode" <?php echo ($maintenance_settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintenance_mode">Enable Maintenance Mode</label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="allow_admin_access" 
                                       id="allow_admin_access" <?php echo ($maintenance_settings['allow_admin_access'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="allow_admin_access">Allow Admin Access</label>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-semibold">Maintenance Message</label>
                            <textarea name="maintenance_message" class="form-control form-control-lg rounded-3" 
                                      rows="4"><?php echo htmlspecialchars($maintenance_settings['maintenance_message'] ?? 'We are currently undergoing scheduled maintenance. Please check back soon.'); ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" name="update_maintenance" class="btn btn-primary rounded-pill px-5">
                                <i class="fa-solid fa-save me-2"></i>Save Maintenance Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Settings Page Styles */
.nav-tabs {
    border-bottom: none;
    gap: 0.5rem;
    flex-wrap: wrap;
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
    background: linear-gradient(135deg, #141e30, #243b55);
    color: white;
    border-color: transparent;
    box-shadow: 0 5px 15px rgba(0,198,255,0.3);
}

.payment-section {
    border-left: 4px solid #00c6ff;
    transition: all 0.3s ease;
}

.payment-section:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
    cursor: pointer;
}

.form-switch .form-check-input:checked {
    background-color: #00c6ff;
    border-color: #00c6ff;
}

/* Dark mode adjustments */
body.dark-mode .nav-tabs .nav-link {
    background: #1e1e1e;
    border-color: #333;
    color: #ddd;
}

body.dark-mode .nav-tabs .nav-link:hover {
    background: #2d2d2d;
}

body.dark-mode .nav-tabs .nav-link.active {
    background: linear-gradient(135deg, #2c3e50, #3498db);
    color: white;
}

body.dark-mode .bg-light {
    background: #2d2d2d !important;
    color: #ddd;
}

body.dark-mode .form-control,
body.dark-mode .form-select {
    background: #2d2d2d;
    border-color: #444;
    color: #eee;
}

body.dark-mode .form-control:focus,
body.dark-mode .form-select:focus {
    background: #2d2d2d;
    border-color: #00c6ff;
    color: #eee;
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
    }
    
    .payment-section .row > div {
        margin-bottom: 1rem;
    }
}
</style>

<?php
require_once 'admin_footer.php';
?>
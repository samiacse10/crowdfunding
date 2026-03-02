<?php
require_once 'header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = "Please login to make a donation";
    redirect('login.php?redirect=donate&id=' . $_GET['id']);
}

$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$campaign_id) {
    $_SESSION['error'] = "Invalid campaign";
    redirect('index.php');
}

// Fetch campaign details
$stmt = $pdo->prepare("SELECT campaigns.*, categories.name as category_name,
                       users.username as creator_name
                       FROM campaigns 
                       LEFT JOIN categories ON campaigns.category_id = categories.id 
                       LEFT JOIN users ON campaigns.user_id = users.id
                       WHERE campaigns.id = ? AND campaigns.status = 'approved'");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaign) {
    $_SESSION['error'] = "Campaign not found or not approved";
    redirect('index.php');
}

// Check if user is donating to their own campaign
$is_own_campaign = ($campaign['user_id'] == $_SESSION['user_id']);

// Fetch raised amount
$raised_amount = 0;
try {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE campaign_id = ? AND status = 'completed'");
    $stmt->execute([$campaign_id]);
    $raised_amount = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Donations table might not exist
}

$progress = min(100, round(($raised_amount / $campaign['target_amount']) * 100, 1));
$remaining = $campaign['target_amount'] - $raised_amount;

// Handle donation submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = floatval($_POST['amount']);
    $custom_amount = floatval($_POST['custom_amount'] ?? 0);
    $payment_method = $_POST['payment_method'];
    $donor_name = trim($_POST['donor_name'] ?? $_SESSION['username']);
    $donor_email = trim($_POST['donor_email'] ?? $_SESSION['email']);
    $message = trim($_POST['message'] ?? '');
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    
    // Determine final amount
    if ($amount == 'custom' && $custom_amount > 0) {
        $final_amount = $custom_amount;
    } else {
        $final_amount = $amount;
    }
    
    $errors = [];
    
    // Validate amount
    if ($final_amount <= 0) {
        $errors[] = "Please enter a valid amount";
    }
    if ($final_amount < 1) {
        $errors[] = "Minimum donation amount is $1";
    }
    if ($final_amount > 10000) {
        $errors[] = "Maximum donation amount is $10,000";
    }
    
    // Validate payment method
    $valid_methods = ['bkash', 'nagad', 'islamic_bank', 'rocket', 'bank_transfer'];
    if (!in_array($payment_method, $valid_methods)) {
        $errors[] = "Please select a valid payment method";
    }
    
    // Validate payment details based on method
    switch ($payment_method) {
        case 'bkash':
        case 'nagad':
        case 'rocket':
            $phone_number = $_POST['phone_number'] ?? '';
            $transaction_id = $_POST['transaction_id'] ?? '';
            
            if (empty($phone_number)) {
                $errors[] = "Phone number is required";
            } elseif (!preg_match('/^01[3-9]\d{8}$/', $phone_number)) {
                $errors[] = "Please enter a valid Bangladeshi phone number";
            }
            
            if (empty($transaction_id)) {
                $errors[] = "Transaction ID is required";
            }
            break;
            
        case 'islamic_bank':
            $card_number = $_POST['card_number'] ?? '';
            $card_holder = $_POST['card_holder'] ?? '';
            $expiry_month = $_POST['expiry_month'] ?? '';
            $expiry_year = $_POST['expiry_year'] ?? '';
            $cvv = $_POST['cvv'] ?? '';
            
            if (empty($card_number)) {
                $errors[] = "Card number is required";
            } elseif (!preg_match('/^\d{16}$/', str_replace(' ', '', $card_number))) {
                $errors[] = "Please enter a valid 16-digit card number";
            }
            
            if (empty($card_holder)) {
                $errors[] = "Card holder name is required";
            }
            
            if (empty($expiry_month) || empty($expiry_year)) {
                $errors[] = "Expiry date is required";
            }
            
            if (empty($cvv) || !preg_match('/^\d{3}$/', $cvv)) {
                $errors[] = "Please enter a valid 3-digit CVV";
            }
            break;
            
        case 'bank_transfer':
            // Bank transfer doesn't need immediate validation
            break;
    }
    
    if (empty($errors)) {
        // Process donation
        try {
            // Check if donations table exists, create if not
            $pdo->exec("CREATE TABLE IF NOT EXISTS donations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                campaign_id INT NOT NULL,
                user_id INT,
                donor_name VARCHAR(100),
                donor_email VARCHAR(100),
                amount DECIMAL(10,2) NOT NULL,
                message TEXT,
                is_anonymous TINYINT DEFAULT 0,
                payment_method VARCHAR(50),
                transaction_id VARCHAR(100),
                phone_number VARCHAR(20),
                status VARCHAR(20) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )");
            
            // Insert donation record
            $stmt = $pdo->prepare("INSERT INTO donations 
                (campaign_id, user_id, donor_name, donor_email, amount, message, is_anonymous, payment_method, transaction_id, phone_number, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $status = ($payment_method == 'bank_transfer') ? 'pending' : 'completed';
            $transaction_id = $transaction_id ?? 'TXN' . time() . rand(100, 999);
            
            if ($stmt->execute([
                $campaign_id,
                $_SESSION['user_id'],
                $donor_name,
                $donor_email,
                $final_amount,
                $message,
                $is_anonymous,
                $payment_method,
                $transaction_id,
                $phone_number ?? null,
                $status
            ])) {
                // Update session notification
                $_SESSION['success'] = "Thank you for your donation!";
                if ($status == 'pending') {
                    $_SESSION['success'] .= " Your donation is pending confirmation.";
                }
                
                // Send email notification (implement later)
                // sendDonationConfirmation($donor_email, $campaign, $final_amount);
                
                redirect('campaign.php?id=' . $campaign_id);
            } else {
                $errors[] = "Failed to process donation. Please try again.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch recent donations for this campaign
$recent_donations = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM donations WHERE campaign_id = ? AND status = 'completed' ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$campaign_id]);
    $recent_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Donations table might not exist
}
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="d-flex align-items-center mb-4">
        <div class="featured-icon me-3">
            <i class="fa-solid fa-hand-holding-heart fa-2x"></i>
        </div>
        <div>
            <h1 class="display-6 fw-bold mb-1">Make a Donation</h1>
            <p class="text-muted mb-0">Support <?php echo htmlspecialchars($campaign['title']); ?></p>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-start">
                <i class="fa-solid fa-circle-exclamation fs-5 me-2 mt-1"></i>
                <div>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Main Donation Form -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">
                        <i class="fa-solid fa-circle-info text-primary me-2"></i>
                        Donation Details
                    </h5>
                    
                    <form method="POST" action="" id="donationForm">
                        <!-- Campaign Summary -->
                        <div class="campaign-summary bg-light rounded-4 p-3 mb-4">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo htmlspecialchars($campaign['image_path'] ?? 'assets/images/default-campaign.jpg'); ?>" 
                                     class="rounded-3 me-3" 
                                     style="width: 80px; height: 80px; object-fit: cover;">
                                <div>
                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($campaign['title']); ?></h6>
                                    <p class="small text-muted mb-1">by <?php echo htmlspecialchars($campaign['creator_name'] ?? 'Anonymous'); ?></p>
                                    <div class="d-flex align-items-center gap-3 small">
                                        <span><i class="fa-regular fa-circle-check text-success me-1"></i><?php echo $progress; ?>% Funded</span>
                                        <span><i class="fa-regular fa-clock me-1"></i>$<?php echo number_format($remaining, 0); ?> remaining</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Donation Amount -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fa-solid fa-coins text-primary me-1"></i>
                                Select Amount <span class="text-danger">*</span>
                            </label>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="amount" id="amount10" value="10" autocomplete="off" checked>
                                    <label class="btn btn-outline-primary w-100 py-3 rounded-3" for="amount10">$10</label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="amount" id="amount25" value="25" autocomplete="off">
                                    <label class="btn btn-outline-primary w-100 py-3 rounded-3" for="amount25">$25</label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="amount" id="amount50" value="50" autocomplete="off">
                                    <label class="btn btn-outline-primary w-100 py-3 rounded-3" for="amount50">$50</label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="amount" id="amount100" value="100" autocomplete="off">
                                    <label class="btn btn-outline-primary w-100 py-3 rounded-3" for="amount100">$100</label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="amount" id="amount500" value="500" autocomplete="off">
                                    <label class="btn btn-outline-primary w-100 py-3 rounded-3" for="amount500">$500</label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="amount" id="amountCustom" value="custom" autocomplete="off">
                                    <label class="btn btn-outline-primary w-100 py-3 rounded-3" for="amountCustom">Custom</label>
                                </div>
                            </div>
                            
                            <div class="custom-amount-input mt-3 d-none">
                                <label class="form-label">Enter Custom Amount ($)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0 rounded-start-3">$</span>
                                    <input type="number" 
                                           step="0.01" 
                                           min="1" 
                                           max="10000"
                                           class="form-control form-control-lg rounded-end-3" 
                                           id="custom_amount" 
                                           name="custom_amount" 
                                           placeholder="Enter amount">
                                </div>
                                <small class="text-muted">Minimum $1, Maximum $10,000</small>
                            </div>
                        </div>
                        
                        <!-- Payment Methods -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="fa-solid fa-credit-card text-primary me-1"></i>
                                Payment Method <span class="text-danger">*</span>
                            </label>
                            
                            <!-- bKash -->
                            <div class="payment-method-card border rounded-3 p-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <input type="radio" class="form-check-input me-3" name="payment_method" id="bkash" value="bkash" required>
                                    <label for="bkash" class="d-flex align-items-center flex-grow-1 cursor-pointer">
                                        <img src="assets/images/bkash-logo.png" alt="bKash" style="height: 30px;" onerror="this.src='https://via.placeholder.com/80x30?text=bKash'">
                                        <span class="ms-3 fw-medium">bKash</span>
                                    </label>
                                    <span class="badge bg-success">Popular</span>
                                </div>
                                
                                <div class="payment-details bkash-details mt-3 d-none">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">bKash Phone Number</label>
                                            <input type="tel" class="form-control" name="phone_number" placeholder="01XXXXXXXXX">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Transaction ID</label>
                                            <input type="text" class="form-control" name="transaction_id" placeholder="Enter transaction ID">
                                        </div>
                                        <div class="col-12">
                                            <small class="text-muted">
                                                <i class="fa-regular fa-circle-info me-1"></i>
                                                Send payment to bKash Merchant: 019XXXXXXXX
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Nagad -->
                            <div class="payment-method-card border rounded-3 p-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <input type="radio" class="form-check-input me-3" name="payment_method" id="nagad" value="nagad">
                                    <label for="nagad" class="d-flex align-items-center flex-grow-1 cursor-pointer">
                                        <img src="assets/images/nagad-logo.png" alt="Nagad" style="height: 30px;" onerror="this.src='https://via.placeholder.com/80x30?text=Nagad'">
                                        <span class="ms-3 fw-medium">Nagad</span>
                                    </label>
                                </div>
                                
                                <div class="payment-details nagad-details mt-3 d-none">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nagad Phone Number</label>
                                            <input type="tel" class="form-control" name="nagad_phone" placeholder="01XXXXXXXXX">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Transaction ID</label>
                                            <input type="text" class="form-control" name="nagad_transaction" placeholder="Enter transaction ID">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Islamic Bank Card -->
                            <div class="payment-method-card border rounded-3 p-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <input type="radio" class="form-check-input me-3" name="payment_method" id="islamic_bank" value="islamic_bank">
                                    <label for="islamic_bank" class="d-flex align-items-center flex-grow-1 cursor-pointer">
                                        <i class="fa-regular fa-credit-card fa-2x text-primary me-2"></i>
                                        <span class="fw-medium">Islamic Bank Card</span>
                                    </label>
                                    <span class="badge bg-info">Visa/Master</span>
                                </div>
                                
                                <div class="payment-details islamic-bank-details mt-3 d-none">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Card Number</label>
                                            <input type="text" class="form-control" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Card Holder Name</label>
                                            <input type="text" class="form-control" name="card_holder" placeholder="As per card">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Expiry Month</label>
                                            <select class="form-select" name="expiry_month">
                                                <option value="">MM</option>
                                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                                    <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>">
                                                        <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Expiry Year</label>
                                            <select class="form-select" name="expiry_year">
                                                <option value="">YYYY</option>
                                                <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">CVV</label>
                                            <input type="text" class="form-control" name="cvv" placeholder="123" maxlength="3">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Rocket -->
                            <div class="payment-method-card border rounded-3 p-3 mb-2">
                                <div class="d-flex align-items-center">
                                    <input type="radio" class="form-check-input me-3" name="payment_method" id="rocket" value="rocket">
                                    <label for="rocket" class="d-flex align-items-center flex-grow-1 cursor-pointer">
                                        <img src="assets/images/rocket-logo.png" alt="Rocket" style="height: 30px;" onerror="this.src='https://via.placeholder.com/80x30?text=Rocket'">
                                        <span class="ms-3 fw-medium">Rocket</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Bank Transfer -->
                            <div class="payment-method-card border rounded-3 p-3">
                                <div class="d-flex align-items-center">
                                    <input type="radio" class="form-check-input me-3" name="payment_method" id="bank_transfer" value="bank_transfer">
                                    <label for="bank_transfer" class="d-flex align-items-center flex-grow-1 cursor-pointer">
                                        <i class="fa-solid fa-building-columns fa-2x text-primary me-2"></i>
                                        <span class="fw-medium">Bank Transfer</span>
                                    </label>
                                </div>
                                
                                <div class="payment-details bank-details mt-3 d-none">
                                    <div class="alert alert-info">
                                        <strong>Bank Details:</strong><br>
                                        Bank: Islami Bank Bangladesh Ltd.<br>
                                        Account Name: Crowdfunding Platform<br>
                                        Account Number: 12345678901<br>
                                        Routing Number: 123456789<br>
                                        Branch: Motijheel, Dhaka
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Donor Information -->
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-3">Donor Information</h6>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Your Name</label>
                                    <input type="text" class="form-control" name="donor_name" 
                                           value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="donor_email" 
                                           value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Message (Optional)</label>
                                    <textarea class="form-control" name="message" rows="2" 
                                              placeholder="Leave a message of support..."></textarea>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_anonymous" id="anonymous">
                                        <label class="form-check-label" for="anonymous">
                                            Donate anonymously (your name won't be displayed publicly)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill py-3">
                                <i class="fa-solid fa-hand-holding-heart me-2"></i>
                                Complete Donation
                            </button>
                        </div>
                        
                        <p class="text-center text-muted small mt-3 mb-0">
                            <i class="fa-regular fa-lock me-1"></i>
                            Secure payment. Your information is encrypted.
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Donation Summary -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 sticky-top" style="top: 100px;">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">
                        <i class="fa-regular fa-receipt me-2"></i>
                        Donation Summary
                    </h5>
                    
                    <div class="summary-item d-flex justify-content-between mb-2">
                        <span class="text-muted">Campaign:</span>
                        <span class="fw-medium"><?php echo htmlspecialchars(substr($campaign['title'], 0, 30)) . '...'; ?></span>
                    </div>
                    
                    <div class="summary-item d-flex justify-content-between mb-2">
                        <span class="text-muted">Donation Amount:</span>
                        <span class="fw-bold text-primary" id="summaryAmount">$10.00</span>
                    </div>
                    
                    <div class="summary-item d-flex justify-content-between mb-3">
                        <span class="text-muted">Payment Method:</span>
                        <span class="fw-medium" id="summaryMethod">bKash</span>
                    </div>
                    
                    <hr>
                    
                    <div class="summary-total d-flex justify-content-between mb-3">
                        <span class="fw-bold">Total:</span>
                        <span class="fw-bold text-primary fs-5" id="summaryTotal">$10.00</span>
                    </div>
                    
                    <!-- Recent Donations -->
                    <?php if (!empty($recent_donations)): ?>
                        <hr>
                        <h6 class="fw-semibold mb-3">Recent Donors</h6>
                        <div class="recent-donors">
                            <?php foreach ($recent_donations as $donation): ?>
                                <div class="donor-item d-flex align-items-center mb-2">
                                    <div class="donor-avatar bg-light rounded-circle me-2" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fa-regular fa-user text-muted"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <small class="fw-medium">
                                            <?php echo $donation['is_anonymous'] ? 'Anonymous' : htmlspecialchars($donation['donor_name']); ?>
                                        </small>
                                        <small class="text-success d-block">$<?php echo number_format($donation['amount'], 2); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Donation Page Styles */
:root {
    --primary-gradient: linear-gradient(135deg, #141e30, #243b55);
}

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

/* Payment Method Cards */
.payment-method-card {
    background: white;
    transition: all 0.3s ease;
    cursor: pointer;
}

.payment-method-card:hover {
    border-color: #00c6ff !important;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.payment-method-card.selected {
    border-color: #00c6ff !important;
    background: #f0f9ff;
}

.payment-method-card .cursor-pointer {
    cursor: pointer;
}

/* Amount Selection */
.btn-check:checked + .btn-outline-primary {
    background: var(--primary-gradient);
    border-color: transparent;
    color: white;
}

.btn-outline-primary {
    border-color: #dee2e6;
    color: #495057;
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    background: #e9ecef;
    border-color: #dee2e6;
    color: #495057;
    transform: translateY(-2px);
}

/* Dark Mode Adjustments */
body.dark-mode .payment-method-card {
    background: #1e1e1e;
    border-color: #333;
}

body.dark-mode .payment-method-card.selected {
    background: #2d2d2d;
    border-color: #00c6ff !important;
}

body.dark-mode .btn-outline-primary {
    background: #2d2d2d;
    border-color: #444;
    color: #ddd;
}

body.dark-mode .btn-outline-primary:hover {
    background: #3d3d3d;
}

body.dark-mode .bg-light {
    background: #2d2d2d !important;
}

body.dark-mode .text-muted {
    color: #aaa !important;
}

body.dark-mode .border {
    border-color: #444 !important;
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
    
    .btn-outline-primary {
        padding: 0.5rem !important;
    }
}

/* Animations */
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

.payment-details {
    animation: fadeIn 0.5s ease;
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

/* Card Styles */
.card {
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
}
</style>

<script>
// Amount selection
document.querySelectorAll('input[name="amount"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.value === 'custom') {
            document.querySelector('.custom-amount-input').classList.remove('d-none');
            updateSummary();
        } else {
            document.querySelector('.custom-amount-input').classList.add('d-none');
            document.getElementById('summaryAmount').textContent = '$' + parseFloat(this.value).toFixed(2);
            document.getElementById('summaryTotal').textContent = '$' + parseFloat(this.value).toFixed(2);
        }
    });
});

// Custom amount input
document.getElementById('custom_amount')?.addEventListener('input', function() {
    if (this.value > 0) {
        document.getElementById('summaryAmount').textContent = '$' + parseFloat(this.value).toFixed(2);
        document.getElementById('summaryTotal').textContent = '$' + parseFloat(this.value).toFixed(2);
    }
});

// Payment method selection and details
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // Hide all payment details
        document.querySelectorAll('.payment-details').forEach(detail => {
            detail.classList.add('d-none');
        });
        
        // Remove selected class from all cards
        document.querySelectorAll('.payment-method-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Add selected class to parent card
        this.closest('.payment-method-card').classList.add('selected');
        
        // Show relevant details
        if (this.value === 'bkash') {
            document.querySelector('.bkash-details').classList.remove('d-none');
            document.getElementById('summaryMethod').textContent = 'bKash';
        } else if (this.value === 'nagad') {
            document.querySelector('.nagad-details').classList.remove('d-none');
            document.getElementById('summaryMethod').textContent = 'Nagad';
        } else if (this.value === 'islamic_bank') {
            document.querySelector('.islamic-bank-details').classList.remove('d-none');
            document.getElementById('summaryMethod').textContent = 'Islamic Bank Card';
        } else if (this.value === 'rocket') {
            document.getElementById('summaryMethod').textContent = 'Rocket';
        } else if (this.value === 'bank_transfer') {
            document.querySelector('.bank-details').classList.remove('d-none');
            document.getElementById('summaryMethod').textContent = 'Bank Transfer';
        }
    });
});

// Format card number
document.querySelector('input[name="card_number"]')?.addEventListener('input', function(e) {
    let value = this.value.replace(/\s/g, '');
    if (value.length > 0) {
        value = value.match(new RegExp('.{1,4}', 'g')).join(' ');
        this.value = value;
    }
});

// Form validation
document.getElementById('donationForm')?.addEventListener('submit', function(e) {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!paymentMethod) {
        e.preventDefault();
        alert('Please select a payment method');
    }
});

// Update summary on load
window.addEventListener('DOMContentLoaded', function() {
    document.getElementById('summaryAmount').textContent = '$10.00';
    document.getElementById('summaryTotal').textContent = '$10.00';
    document.getElementById('summaryMethod').textContent = 'bKash';
});
</script>

<?php
require_once 'footer.php';
?>
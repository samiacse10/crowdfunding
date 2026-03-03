<?php
require_once 'header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = "Please login to make a  donation";
    redirect('login.php');
}

$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$campaign_id) {
    redirect('index.php');
}

// Fetch campaign details
$stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND status = 'approved'");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaign) {
    $_SESSION['error'] = "Campaign not found";
    redirect('index.php');
}

// Handle demo donation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = floatval($_POST['amount']);
    $payment_method = $_POST['payment_method'];
    $message = trim($_POST['message'] ?? '');
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    
    if ($amount <= 0) {
        $error = "Please enter a valid amount";
    } else {
        // Generate fake transaction ID
        $transaction_id = 'DEMO_' . strtoupper(uniqid());
        
        // Insert donation record
        try {
            // Create donations table if not exists
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
                status VARCHAR(20) DEFAULT 'completed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )");
            
            $stmt = $pdo->prepare("INSERT INTO donations 
                (campaign_id, user_id, donor_name, donor_email, amount, message, is_anonymous, payment_method, transaction_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')");
            
            $stmt->execute([
                $campaign_id,
                $_SESSION['user_id'],
                $_SESSION['username'],
                $_SESSION['email'] ?? 'demo@example.com',
                $amount,
                $message,
                $is_anonymous,
                $payment_method,
                $transaction_id
            ]);
            
            $_SESSION['success'] = "Demo donation successful! (No real money was transferred)";
            redirect('campaign.php?id=' . $campaign_id);
            
        } catch (PDOException $e) {
            $error = "Demo donation failed: " . $e->getMessage();
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Demo Notice -->
            <div class="alert alert-info text-center mb-4 rounded-4 border-0 shadow-sm">
                <i class="fa-solid fa-flask fa-2x mb-3"></i>
                <!-- <h5>🎓 Demo Mode - No Real Money</h5>
                <p class="mb-0">This is a student project demonstration. No actual payment will be processed.</p> -->
            </div>
            
            <!-- Campaign Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo htmlspecialchars($campaign['image_path'] ?? 'assets/images/default-campaign.jpg'); ?>" 
                             class="rounded-3 me-3" 
                             style="width: 80px; height: 80px; object-fit: cover;">
                        <div>
                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($campaign['title']); ?></h5>
                            <p class="text-muted small mb-0">Demo Donation</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Demo Donation Form -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">
                        <i class="fa-solid fa-hand-holding-heart text-primary me-2"></i>
                        Make a Demo Donation
                    </h5>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Amount ($)</label>
                            <div class="row g-2">
                                <div class="col-3">
                                    <button type="button" class="btn btn-outline-primary w-100" onclick="setAmount(10)">$10</button>
                                </div>
                                <div class="col-3">
                                    <button type="button" class="btn btn-outline-primary w-100" onclick="setAmount(25)">$25</button>
                                </div>
                                <div class="col-3">
                                    <button type="button" class="btn btn-outline-primary w-100" onclick="setAmount(50)">$50</button>
                                </div>
                                <div class="col-3">
                                    <button type="button" class="btn btn-outline-primary w-100" onclick="setAmount(100)">$100</button>
                                </div>
                            </div>
                            <input type="number" name="amount" id="amount" class="form-control form-control-lg mt-3" 
                                   placeholder="Enter amount" min="1" step="0.01" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Payment Method </label>
                            <select name="payment_method" class="form-select form-select-lg" required>
                                <option value="bkash">📱 bKash </option>
                                <option value="nagad">📱 Nagad </option>
                                <option value="rocket">🚀 Rocket </option>
                                <option value="bank">🏦 Bank Transfer </option>
                                <option value="card">💳 Credit Card </option>
                            </select>
                            <small class="text-muted">All payment methods are simulated for demonstration</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Message (Optional)</label>
                            <textarea name="message" class="form-control" rows="3" 
                                      placeholder="Leave a message of support..."></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_anonymous" id="anonymous">
                                <label class="form-check-label" for="anonymous">
                                    Donate anonymously
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">
                            <i class="fa-solid fa-flask me-2"></i>
                            Complete  Donation
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setAmount(amount) {
    document.getElementById('amount').value = amount;
}
</script>

<style>
.btn-outline-primary {
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,198,255,0.3);
}
</style>

<?php require_once 'footer.php'; ?>
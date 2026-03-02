<?php
require_once 'header.php';

$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
$donation_id = isset($_GET['donation_id']) ? intval($_GET['donation_id']) : 0;

if (!$campaign_id || !$donation_id) {
    redirect('index.php');
}

// Fetch donation details
$stmt = $pdo->prepare("SELECT d.*, c.title as campaign_title 
                       FROM donations d 
                       JOIN campaigns c ON d.campaign_id = c.id 
                       WHERE d.id = ? AND d.user_id = ?");
$stmt->execute([$donation_id, $_SESSION['user_id']]);
$donation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donation) {
    redirect('index.php');
}
?>

<div class="container py-5">
    <div class="text-center mb-4">
        <div class="success-icon mb-4">
            <i class="fa-solid fa-circle-check fa-5x text-success"></i>
        </div>
        <h1 class="display-5 fw-bold mb-3">Thank You for Your Donation!</h1>
        <p class="lead text-muted">Your support means the world to us and the campaign creator.</p>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">Donation Details</h5>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Campaign:</div>
                        <div class="col-sm-8 fw-bold"><?php echo htmlspecialchars($donation['campaign_title']); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Amount:</div>
                        <div class="col-sm-8 fw-bold text-success">$<?php echo number_format($donation['amount'], 2); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Payment Method:</div>
                        <div class="col-sm-8"><?php echo ucfirst(str_replace('_', ' ', $donation['payment_method'])); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Transaction ID:</div>
                        <div class="col-sm-8"><?php echo htmlspecialchars($donation['transaction_id']); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Date:</div>
                        <div class="col-sm-8"><?php echo date('F d, Y h:i A', strtotime($donation['created_at'])); ?></div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="campaign.php?id=<?php echo $campaign_id; ?>" class="btn btn-outline-primary rounded-pill px-4">
                            <i class="fa-regular fa-eye me-2"></i>View Campaign
                        </a>
                        <a href="dashboard.php" class="btn btn-primary rounded-pill px-4">
                            <i class="fa-solid fa-gauge me-2"></i>Go to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
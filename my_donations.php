<?php
require_once 'header.php';

if (!isLoggedIn()) {
    $_SESSION['error'] = "Please login first";
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count of user's donations
$count_sql = "SELECT COUNT(*) as total FROM donations WHERE user_id = ?";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute([$user_id]);
$total_donations = $count_stmt->fetch()['total'];
$total_pages = ceil($total_donations / $limit);

// Get user's donations with pagination - FIXED SQL SYNTAX
$donation_sql = "SELECT d.*, c.title as campaign_title, c.user_id as campaign_owner_id,
                 u.username as campaign_owner, c.image_path as campaign_image
                 FROM donations d 
                 JOIN campaigns c ON d.campaign_id = c.id 
                 LEFT JOIN users u ON c.user_id = u.id
                 WHERE d.user_id = ? 
                 ORDER BY d.created_at DESC 
                 LIMIT $offset, $limit"; // FIXED: Using variables directly instead of placeholders for LIMIT

$donation_stmt = $pdo->prepare($donation_sql);
$donation_stmt->execute([$user_id]); // Only pass user_id as parameter
$my_donations = $donation_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total amount donated
$total_amount_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE user_id = ? AND status = 'completed'";
$total_amount_stmt = $pdo->prepare($total_amount_sql);
$total_amount_stmt->execute([$user_id]);
$total_amount = $total_amount_stmt->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Donations - Crowdfunding Platform</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #141e30, #243b55);
        }

        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Page Header */
        .page-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 2rem 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }

        /* Stats Card */
        .stats-card {
            background: white;
            border-radius: 1.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #141e30;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 1.5rem;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom-width: 2px;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .campaign-image-small {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            object-fit: cover;
        }

        .donor-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-gradient);
            color: white;
            font-size: 0.9rem;
        }

        /* Status Badges */
        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .badge-status.completed {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .badge-status.pending {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: #000;
        }

        .badge-status.failed {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        /* Pagination */
        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }

        .page-link {
            border: none;
            padding: 0.75rem 1rem;
            margin: 0 0.25rem;
            border-radius: 10px;
            color: #141e30;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid #dee2e6;
        }

        .page-link:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-2px);
        }

        .page-item.active .page-link {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
        }

        .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background: #f8f9fa;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
        }

        .empty-state-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
            font-size: 3rem;
        }

        /* Back Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            background: white;
            color: #141e30;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .back-btn:hover {
            background: #f8f9fa;
            transform: translateX(-5px);
        }

        /* Dark Mode */
        body.dark-mode {
            background: #121212;
        }

        body.dark-mode .stats-card,
        body.dark-mode .table-container {
            background: #1e1e1e;
            border-color: #333;
        }

        body.dark-mode .table {
            color: #eee;
        }

        body.dark-mode .table th {
            background: #2d2d2d;
            color: #ddd;
        }

        body.dark-mode .table td {
            color: #ddd;
            border-color: #444;
        }

        body.dark-mode .text-muted {
            color: #aaa !important;
        }

        body.dark-mode .empty-state-icon {
            background: #2d2d2d;
        }

        body.dark-mode .back-btn {
            background: #1e1e1e;
            color: #eee;
            border-color: #333;
        }

        body.dark-mode .back-btn:hover {
            background: #2d2d2d;
        }

        body.dark-mode .stat-value {
            color: #00c6ff;
        }

        body.dark-mode .page-link {
            background: #1e1e1e;
            color: #eee;
            border-color: #333;
        }

        body.dark-mode .page-link:hover {
            background: var(--primary-gradient);
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                padding: 1.5rem 0;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .table-container {
                padding: 1rem;
            }

            .table th {
                font-size: 0.75rem;
                padding: 0.75rem;
            }

            .table td {
                padding: 0.75rem;
            }

            .campaign-image-small {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fa-solid fa-hand-holding-heart fa-3x opacity-75"></i>
                </div>
                <div>
                    <h1 class="display-5 fw-bold">My Donations</h1>
                    <p class="lead mb-0">Track all the campaigns you've supported</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Back to Dashboard Button -->
        <a href="dashboard.php" class="back-btn">
            <i class="fa-solid fa-arrow-left me-2"></i>
            Back to Dashboard
        </a>

        <!-- Stats Overview -->
        <div class="stats-card">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-value">$<?php echo number_format($total_amount, 2); ?></div>
                        <div class="stat-label">Total Donated</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $total_donations; ?></div>
                        <div class="stat-label">Total Donations</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-value">$<?php echo $total_donations > 0 ? number_format($total_amount / $total_donations, 2) : '0.00'; ?></div>
                        <div class="stat-label">Average Donation</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Donations Table -->
        <div class="table-container">
            <?php if (count($my_donations) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Campaign</th>
                                <th>Organizer</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_donations as $donation): ?>
                            <tr>
                                <td>
                                    <div><?php echo date('M d, Y', strtotime($donation['created_at'])); ?></div>
                                    <small class="text-muted"><?php echo date('h:i A', strtotime($donation['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($donation['campaign_image'] ?? 'assets/images/default-campaign.jpg'); ?>" 
                                             class="campaign-image-small me-2" 
                                             alt="">
                                        <div>
                                            <a href="campaign.php?id=<?php echo $donation['campaign_id']; ?>" class="text-decoration-none fw-semibold text-dark">
                                                <?php echo htmlspecialchars(substr($donation['campaign_title'], 0, 30)) . (strlen($donation['campaign_title']) > 30 ? '...' : ''); ?>
                                            </a>
                                            <?php if ($donation['is_anonymous']): ?>
                                                <br><small class="text-muted"><i class="fa-regular fa-user-secret me-1"></i>Anonymous Donation</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="donor-avatar me-2" style="width: 30px; height: 30px; background: linear-gradient(135deg, #28a745, #20c997);">
                                            <i class="fa-regular fa-user"></i>
                                        </div>
                                        <?php echo htmlspecialchars($donation['campaign_owner'] ?? 'Unknown'); ?>
                                    </div>
                                </td>
                                <td class="fw-bold text-success fs-5">$<?php echo number_format($donation['amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill">
                                        <i class="fa-regular fa-credit-card me-1"></i>
                                        <?php echo ucfirst($donation['payment_method'] ?? 'Unknown'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($donation['status'] == 'completed'): ?>
                                        <span class="badge-status completed">
                                            <i class="fa-regular fa-circle-check me-1"></i>Completed
                                        </span>
                                    <?php elseif ($donation['status'] == 'pending'): ?>
                                        <span class="badge-status pending">
                                            <i class="fa-regular fa-clock me-1"></i>Pending
                                        </span>
                                    <?php elseif ($donation['status'] == 'failed'): ?>
                                        <span class="badge-status failed">
                                            <i class="fa-regular fa-circle-xmark me-1"></i>Failed
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo ucfirst($donation['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="campaign.php?id=<?php echo $donation['campaign_id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="fa-regular fa-eye me-1"></i>View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Donations pagination" class="mt-4">
                        <ul class="pagination">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>" tabindex="-1">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fa-solid fa-hand-holding-heart"></i>
                    </div>
                    <h4 class="fw-bold mb-3">No Donations Yet</h4>
                    <p class="text-muted mb-4">You haven't made any donations. Explore campaigns and support a cause you believe in!</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="index.php" class="btn btn-primary btn-lg rounded-pill px-5 py-3">
                            <i class="fa-solid fa-magnifying-glass me-2"></i>
                            Explore Campaigns
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-lg rounded-pill px-5 py-3">
                            <i class="fa-solid fa-gauge me-2"></i>
                            Go to Dashboard
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Dark Mode Script -->
    <script>
        if (localStorage.getItem('dark-mode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>

<?php require_once 'footer.php'; ?>
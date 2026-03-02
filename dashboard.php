<?php
require_once 'header.php';

if (!isLoggedIn()) {
    $_SESSION['error'] = "Please login first";
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get user data to check role
$stmt = $pdo->prepare("SELECT user_type, status, username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Check if user is an organizer or both (active)
$is_organizer = ($user['user_type'] == 'organizer' || $user['user_type'] == 'both') && $user['status'] == 'active';

// Get user's campaigns (only for organizers)
$campaigns = [];
if ($is_organizer) {
    $stmt = $pdo->prepare("SELECT campaigns.*, categories.name as category_name FROM campaigns LEFT JOIN categories ON campaigns.category_id = categories.id WHERE campaigns.user_id = ? ORDER BY campaigns.created_at DESC");
    $stmt->execute([$user_id]);
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get donations made by this user (for all users - donors see this)
$donation_sql = "SELECT d.*, c.title as campaign_title, c.user_id as campaign_owner_id,
                 (SELECT username FROM users WHERE id = c.user_id) as campaign_owner
                 FROM donations d 
                 JOIN campaigns c ON d.campaign_id = c.id 
                 WHERE d.user_id = ? 
                 ORDER BY d.created_at DESC 
                 LIMIT 10";
$donation_stmt = $pdo->prepare($donation_sql);
$donation_stmt->execute([$user_id]);
$my_donations = $donation_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get donations FOR user's campaigns (only for organizers)
$my_campaign_donations = [];
if ($is_organizer) {
    $campaign_donation_sql = "SELECT d.*, c.title as campaign_title, u.username as donor_username
                              FROM donations d 
                              JOIN campaigns c ON d.campaign_id = c.id 
                              LEFT JOIN users u ON d.user_id = u.id
                              WHERE c.user_id = ? 
                              ORDER BY d.created_at DESC 
                              LIMIT 10";
    $campaign_donation_stmt = $pdo->prepare($campaign_donation_sql);
    $campaign_donation_stmt->execute([$user_id]);
    $my_campaign_donations = $campaign_donation_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get total stats
$total_donated = 0;
$donation_count = 0;
foreach ($my_donations as $don) {
    $total_donated += $don['amount'];
    $donation_count++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Crowdfunding Platform</title>
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

        /* Welcome Card */
        .welcome-card {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .welcome-card h2 {
            color: #141e30;
            font-weight: 700;
        }

        /* Stat Cards */
        .stat-card {
            border-radius: 1.5rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .stat-card.primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .stat-card.success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .stat-card.info {
            background: linear-gradient(135deg, #17a2b8, #00c6ff);
        }

        .stat-card.warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        /* Section Cards */
        .section-card {
            background: white;
            border-radius: 1.5rem;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .section-header {
            padding: 1.5rem 1.5rem 0 1.5rem;
        }

        .section-title {
            font-weight: 700;
            color: #141e30;
            margin-bottom: 0.5rem;
        }

        .section-title i {
            color: #00c6ff;
            margin-right: 0.5rem;
        }

        /* Table Styles */
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

        /* Campaign Card */
        .campaign-card {
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 1rem;
            transition: all 0.3s ease;
            height: 100%;
        }

        .campaign-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .campaign-image {
            height: 150px;
            object-fit: cover;
            border-radius: 1rem 1rem 0 0;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
        }

        .empty-state-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 1.5rem;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
            font-size: 2.5rem;
        }

        /* Badges */
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

        /* Dark Mode */
        body.dark-mode {
            background: #121212;
        }

        body.dark-mode .welcome-card,
        body.dark-mode .section-card {
            background: #1e1e1e;
            border-color: #333;
        }

        body.dark-mode .welcome-card h2,
        body.dark-mode .section-title {
            color: #eee;
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

        body.dark-mode .campaign-card {
            background: #1e1e1e;
            border-color: #333;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                padding: 1.5rem 0;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .welcome-card {
                padding: 1.5rem;
            }

            .welcome-card h2 {
                font-size: 1.5rem;
            }

            .stat-card {
                padding: 1.2rem;
            }

            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
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
                    <i class="fa-solid fa-gauge-high fa-3x opacity-75"></i>
                </div>
                <div>
                    <h1 class="display-5 fw-bold">Dashboard</h1>
                    <p class="lead mb-0">Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-2">Hello, <?php echo htmlspecialchars($user['username']); ?>! 👋</h2>
                    <p class="text-muted mb-0">
                        <?php if ($user['user_type'] == 'donor'): ?>
                            Thank you for supporting our community. Browse campaigns and make a difference!
                        <?php elseif ($user['user_type'] == 'organizer'): ?>
                            Ready to bring your ideas to life? Manage your campaigns and track donations.
                        <?php else: ?>
                            You can both create campaigns and support others. Make the most of your experience!
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <?php if ($is_organizer): ?>
                        <a href="create_campaign.php" class="btn btn-primary btn-lg rounded-pill px-4">
                            <i class="fa-solid fa-plus-circle me-2"></i>Start Campaign
                        </a>
                    <?php else: ?>
                        <a href="index.php" class="btn btn-primary btn-lg rounded-pill px-4">
                            <i class="fa-solid fa-magnifying-glass me-2"></i>Explore Campaigns
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <!-- Total Donated (shown to everyone) -->
            <div class="col-md-<?php echo $is_organizer ? '4' : '6'; ?>">
                <div class="stat-card primary">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-white-50 mb-1">Total Donated</p>
                            <h3 class="text-white fw-bold mb-0">$<?php echo number_format($total_donated, 2); ?></h3>
                            <small class="text-white-50">Across <?php echo $donation_count; ?> donations</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Campaigns Count (only for organizers) -->
            <?php if ($is_organizer): ?>
            <div class="col-md-4">
                <div class="stat-card success">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-white-50 mb-1">Your Campaigns</p>
                            <h3 class="text-white fw-bold mb-0"><?php echo count($campaigns); ?></h3>
                            <small class="text-white-50">Active campaigns</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-rocket"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Donations Made (shown to everyone) -->
            <div class="col-md-<?php echo $is_organizer ? '4' : '6'; ?>">
                <div class="stat-card info">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-white-50 mb-1">Donations Made</p>
                            <h3 class="text-white fw-bold mb-0"><?php echo $donation_count; ?></h3>
                            <small class="text-white-50">Total transactions</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Donations Section (shown to everyone) -->
        <div class="section-card">
            <div class="section-header">
                <h4 class="section-title">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                    My Donations
                </h4>
                <p class="text-muted">Campaigns you have supported</p>
            </div>
            
            <div class="p-4">
                <?php if (count($my_donations) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Campaign</th>
                                    <th>Organizer</th>
                                    <th>Amount</th>
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
                                        <a href="campaign.php?id=<?php echo $donation['campaign_id']; ?>" class="text-decoration-none fw-semibold">
                                            <?php echo htmlspecialchars(substr($donation['campaign_title'], 0, 40)); ?>
                                            <?php if (strlen($donation['campaign_title']) > 40) echo '...'; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="donor-avatar me-2" style="width: 30px; height: 30px;">
                                                <i class="fa-regular fa-user"></i>
                                            </div>
                                            <?php echo htmlspecialchars($donation['campaign_owner'] ?? 'Unknown'); ?>
                                        </div>
                                    </td>
                                    <td class="fw-bold text-success fs-5">$<?php echo number_format($donation['amount'], 2); ?></td>
                                    <td>
                                        <?php if ($donation['status'] == 'completed'): ?>
                                            <span class="badge-status completed">
                                                <i class="fa-regular fa-circle-check me-1"></i>Completed
                                            </span>
                                        <?php elseif ($donation['status'] == 'pending'): ?>
                                            <span class="badge-status pending">
                                                <i class="fa-regular fa-clock me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="campaign.php?id=<?php echo $donation['campaign_id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                            View Campaign
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (count($my_donations) >= 10): ?>
                        <div class="text-center mt-4">
                            <a href="my_donations.php" class="btn btn-outline-primary rounded-pill px-5">
                                View All Donations <i class="fa-solid fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                        </div>
                        <h5 class="fw-bold mb-2">No Donations Yet</h5>
                        <p class="text-muted mb-4">You haven't made any donations. Explore campaigns and support a cause!</p>
                        <a href="index.php" class="btn btn-primary rounded-pill px-5 py-2">
                            Explore Campaigns
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Donations to Your Campaigns Section (ONLY FOR ORGANIZERS) -->
        <?php if ($is_organizer && count($my_campaign_donations) > 0): ?>
            <div class="section-card">
                <div class="section-header">
                    <h4 class="section-title">
                        <i class="fa-solid fa-bell"></i>
                        Recent Donations to Your Campaigns
                    </h4>
                    <p class="text-muted">Latest supporters of your campaigns</p>
                </div>
                
                <div class="p-4">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Campaign</th>
                                    <th>Donor</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_campaign_donations as $donation): ?>
                                <tr>
                                    <td>
                                        <div><?php echo date('M d, Y', strtotime($donation['created_at'])); ?></div>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($donation['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <a href="campaign.php?id=<?php echo $donation['campaign_id']; ?>" class="text-decoration-none fw-semibold">
                                            <?php echo htmlspecialchars(substr($donation['campaign_title'], 0, 40)); ?>
                                            <?php if (strlen($donation['campaign_title']) > 40) echo '...'; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($donation['is_anonymous']): ?>
                                            <span class="badge bg-secondary">
                                                <i class="fa-regular fa-user-secret me-1"></i>Anonymous
                                            </span>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center">
                                                <div class="donor-avatar me-2" style="width: 30px; height: 30px;">
                                                    <i class="fa-regular fa-user"></i>
                                                </div>
                                                <?php echo htmlspecialchars($donation['donor_username'] ?? $donation['donor_name'] ?? 'Guest'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-success fs-5">$<?php echo number_format($donation['amount'], 2); ?></td>
                                    <td>
                                        <?php if ($donation['status'] == 'completed'): ?>
                                            <span class="badge-status completed">
                                                <i class="fa-regular fa-circle-check me-1"></i>Completed
                                            </span>
                                        <?php elseif ($donation['status'] == 'pending'): ?>
                                            <span class="badge-status pending">
                                                <i class="fa-regular fa-clock me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="campaign.php?id=<?php echo $donation['campaign_id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                            View Campaign
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (count($my_campaign_donations) >= 10): ?>
                        <div class="text-center mt-4">
                            <a href="my_campaign_donations.php" class="btn btn-outline-primary rounded-pill px-5">
                                View All Donations <i class="fa-solid fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- My Campaigns Preview (only for organizers) -->
        <?php if ($is_organizer && count($campaigns) > 0): ?>
            <div class="section-card">
                <div class="section-header">
                    <h4 class="section-title">
                        <i class="fa-solid fa-rocket"></i>
                        Your Campaigns
                    </h4>
                    <p class="text-muted">Quick overview of your active campaigns</p>
                </div>
                
                <div class="p-4">
                    <div class="row g-4">
                        <?php 
                        $display_campaigns = array_slice($campaigns, 0, 3);
                        foreach ($display_campaigns as $campaign): 
                        ?>
                            <div class="col-md-4">
                                <div class="campaign-card">
                                    <img src="<?php echo htmlspecialchars($campaign['image_path'] ?? 'assets/images/default-campaign.jpg'); ?>" 
                                         class="campaign-image w-100" 
                                         alt="<?php echo htmlspecialchars($campaign['title']); ?>">
                                    <div class="p-3">
                                        <h6 class="fw-bold mb-2"><?php echo htmlspecialchars(substr($campaign['title'], 0, 30)); ?></h6>
                                        <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($campaign['category_name'] ?? 'Uncategorized'); ?></span>
                                        <p class="small text-muted mb-2"><?php echo htmlspecialchars(substr($campaign['description'], 0, 60)); ?>...</p>
                                        <a href="campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-outline-primary w-100 rounded-pill">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($campaigns) > 3): ?>
                        <div class="text-center mt-4">
                            <a href="my_campaigns.php" class="btn btn-outline-primary rounded-pill px-5">
                                View All Campaigns (<?php echo count($campaigns); ?>)
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
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
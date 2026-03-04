<?php
require_once 'header.php';

// Get selected category (if any)
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Fetch all categories for filter bar
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Check if donations table exists
$donations_table_exists = false;
try {
    $result = $pdo->query("SHOW TABLES LIKE 'donations'");
    $donations_table_exists = $result->rowCount() > 0;
} catch (PDOException $e) {
    // Table doesn't exist, continue without donations data
}

// Get total stats from ALL approved campaigns
if ($donations_table_exists) {
    $stats_sql = "SELECT 
                COUNT(DISTINCT campaigns.id) as total_campaigns,
                COALESCE(SUM(donations.amount), 0) as total_raised,
                COUNT(DISTINCT donations.id) as total_donors
                FROM campaigns 
                LEFT JOIN donations ON campaigns.id = donations.campaign_id 
                WHERE campaigns.status='approved'";
} else {
    $stats_sql = "SELECT 
                COUNT(*) as total_campaigns,
                0 as total_raised,
                0 as total_donors
                FROM campaigns 
                WHERE campaigns.status='approved'";
}

if ($selected_category > 0) {
    $stats_sql .= " AND campaigns.category_id = $selected_category";
}

$stats_stmt = $pdo->query($stats_sql);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$total_campaigns = $stats['total_campaigns'];
$total_raised = $stats['total_raised'];
$total_donors = $stats['total_donors'];

// Get total count for "View All" button
$count_sql = "SELECT COUNT(*) as total FROM campaigns WHERE status='approved'";
if ($selected_category > 0) {
    $count_sql .= " AND category_id = $selected_category";
}
$count_result = $pdo->query($count_sql)->fetch(PDO::FETCH_ASSOC);
$total_available = $count_result['total'];

// Build query for campaigns - FIXED to prevent duplicates
if ($donations_table_exists) {
    $sql = "SELECT DISTINCT campaigns.*, categories.name as category_name,
            (SELECT COUNT(*) FROM donations WHERE campaign_id = campaigns.id) as donor_count,
            (SELECT COALESCE(SUM(amount), 0) FROM donations WHERE campaign_id = campaigns.id) as raised_amount
            FROM campaigns 
            LEFT JOIN categories ON campaigns.category_id = categories.id 
            WHERE campaigns.status='approved'";
} else {
    $sql = "SELECT DISTINCT campaigns.*, categories.name as category_name,
            0 as donor_count,
            0 as raised_amount
            FROM campaigns 
            LEFT JOIN categories ON campaigns.category_id = categories.id 
            WHERE campaigns.status='approved'";
}

$params = [];
if ($selected_category > 0) {
    $sql .= " AND campaigns.category_id = ?";
    $params[] = $selected_category;
}

// Add GROUP BY to ensure unique campaigns
$sql .= " GROUP BY campaigns.id";
$sql .= " ORDER BY campaigns.created_at DESC LIMIT 3";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate progress for each campaign
foreach ($campaigns as &$campaign) {
    $campaign['progress'] = min(100, round(($campaign['raised_amount'] / $campaign['target_amount']) * 100, 1));
}

// Final filter to ensure absolutely no duplicates
$unique_campaigns = [];
$seen_ids = [];
foreach ($campaigns as $campaign) {
    if (!in_array($campaign['id'], $seen_ids)) {
        $unique_campaigns[] = $campaign;
        $seen_ids[] = $campaign['id'];
    }
}
$campaigns = $unique_campaigns;
?>

<!-- Hero Section with Professional Design -->
<div class="hero-section text-white text-center py-5 mb-5">
    <div class="container py-5">
        <h1 class="display-4 fw-bold mb-4 animate__animated animate__fadeInDown">Small Donation! Big Impact!</h1>
        <p class="lead mb-5 animate__animated animate__fadeInUp">Even a small donation can create a big impact in someone's life.</p>
        <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 animate__animated animate__fadeIn">
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-light btn-lg px-5 py-3 rounded-pill shadow-sm">
                    <i class="fa-solid fa-rocket me-2"></i>Start a Campaign
                </a>
                <a href="#campaigns" class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>Explore Campaigns
                </a>
            <?php else: ?>
                <?php 
                // Check if user can create campaigns (organizer or both with active status)
                $can_create = false;
                if (isset($_SESSION['user_id'])) {
                    $user_stmt = $pdo->prepare("SELECT user_type, status FROM users WHERE id = ?");
                    $user_stmt->execute([$_SESSION['user_id']]);
                    $user = $user_stmt->fetch();
                    if ($user) {
                        $can_create = ($user['user_type'] == 'organizer' || $user['user_type'] == 'both') && $user['status'] == 'active';
                    }
                }
                ?>
                <?php if ($can_create): ?>
                    <a href="create_campaign.php" class="btn btn-light btn-lg px-5 py-3 rounded-pill shadow-sm">
                        <i class="fa-solid fa-plus-circle me-2"></i>Start a Campaign
                    </a>
                <?php endif; ?>
                <a href="#campaigns" class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>Explore Campaigns
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Features Grid -->
    <div class="container">
        <div class="features-grid">
            <div class="feature-item">
                <i class="fa-solid fa-rocket text-green"></i>
                <h4>Easy to Start</h4>
                <p>Create your campaign in minutes with our simple step-by-step process.</p>
            </div>
            <div class="feature-item">
                <i class="fa-solid fa-lock text-green"></i>
                <h4>Secure Donations</h4>
                <p>Multiple payment methods including bKash, Nagad, and cards. Your money is safe.</p>
            </div>
            <div class="feature-item">
                <i class="fa-solid fa-users text-green"></i>
                <h4>Community Support</h4>
                <p>Join a community of passionate backers ready to support your next idea.</p>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container" id="campaigns">
    <!-- Header with Title and Filter -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5">
        <div class="d-flex align-items-center mb-3 mb-md-0">
            <div class="featured-icon me-3">
                <i class="fa-solid fa-fire-flame-curved fa-2x"></i>
            </div>
            <div>
                <h1 class="display-6 fw-bold mb-1">Featured Campaigns</h1>
                <p class="text-muted mb-0">Discover amazing projects waiting for your support</p>
            </div>
        </div>
        
        <!-- Category filter -->
        <div class="category-filter-wrapper">
            <!-- Mobile dropdown -->
            <div class="d-md-none w-100">
                <select class="form-select form-select-lg rounded-pill" onchange="window.location.href=this.value">
                    <option value="index.php" <?php echo $selected_category == 0 ? 'selected' : ''; ?>>All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="index.php?category=<?php echo $cat['id']; ?>" <?php echo $selected_category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Desktop pills -->
            <div class="d-none d-md-block">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link rounded-pill <?php echo $selected_category == 0 ? 'active' : ''; ?>" href="index.php">
                            All
                        </a>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                        <li class="nav-item">
                            <a class="nav-link rounded-pill <?php echo $selected_category == $cat['id'] ? 'active' : ''; ?>" href="index.php?category=<?php echo $cat['id']; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Campaign Statistics -->
    <?php if ($total_campaigns > 0): ?>
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card bg-primary text-white p-4 rounded-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-value display-5 fw-bold mb-1"><?php echo $total_campaigns; ?></div>
                            <p class="mb-0 opacity-75">Active Campaigns</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-rocket fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card bg-success text-white p-4 rounded-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-value display-5 fw-bold mb-1">৳<?php echo number_format($total_raised, 0); ?></div>
                            <p class="mb-0 opacity-75">Total Raised</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-hand-holding-heart fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card bg-info text-white p-4 rounded-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="stat-value display-5 fw-bold mb-1"><?php echo number_format($total_donors); ?></div>
                            <p class="mb-0 opacity-75">Total Donors</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-users fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Campaign Grid -->
    <div class="row g-4">
        <?php if (count($campaigns) > 0): ?>
            <?php foreach ($campaigns as $campaign): ?>
                <div class="col-xl-4 col-lg-4 col-md-6">
                    <div class="card h-100 campaign-card border-0 shadow-sm rounded-4">
                        <!-- Campaign Image with Category Badge -->
                        <div class="position-relative overflow-hidden rounded-top-4">
                            <img src="<?php echo htmlspecialchars($campaign['image_path'] ?: 'assets/images/default-campaign.jpg'); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($campaign['title']); ?>"
                                 style="height: 220px; object-fit: cover;">
                            <span class="position-absolute top-0 end-0 badge bg-primary m-3 px-3 py-2 rounded-pill shadow-sm">
                                <i class="fa-solid fa-tag me-1"></i>
                                <?php echo htmlspecialchars($campaign['category_name'] ?? 'Uncategorized'); ?>
                            </span>
                            <?php if($campaign['progress'] >= 100): ?>
                                <span class="position-absolute top-0 start-0 badge bg-success m-3 px-3 py-2 rounded-pill shadow-sm">
                                    <i class="fa-solid fa-check-circle me-1"></i>Funded
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body d-flex flex-column p-4">
                            <!-- Title -->
                            <h5 class="card-title fw-bold mb-3">
                                <a href="campaign.php?id=<?php echo $campaign['id']; ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($campaign['title']); ?>
                                </a>
                            </h5>
                            
                            <!-- Description -->
                            <p class="card-text text-muted small mb-4">
                                <?php echo nl2br(htmlspecialchars(substr($campaign['description'], 0, 120))); ?>...
                            </p>
                            
                            <!-- Progress Bar -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-bold text-primary">৳<?php echo number_format($campaign['raised_amount'], 0); ?></span>
                                    <span class="text-muted small">of ৳<?php echo number_format($campaign['target_amount'], 0); ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success rounded-pill" 
                                         role="progressbar" 
                                         style="width: <?php echo $campaign['progress']; ?>%;">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-2 small">
                                    <span class="text-success fw-medium"><?php echo $campaign['progress']; ?>% Funded</span>
                                    <span class="text-muted">
                                        <i class="fa-regular fa-user me-1"></i> <?php echo $campaign['donor_count']; ?> donors
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Creator & Action -->
                            <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top">
                                <small class="text-muted">
                                    <i class="fa-regular fa-calendar me-1"></i>
                                    <?php echo date('M d, Y', strtotime($campaign['created_at'])); ?>
                                </small>
                                <a href="campaign.php?id=<?php echo $campaign['id']; ?>" 
                                   class="btn btn-primary btn-sm rounded-pill px-4">
                                    View Details <i class="fa-solid fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- View All Button -->
            <?php if ($total_available > 3): ?>
            <div class="col-12 text-center mt-5">
                <a href="all_campaigns.php<?php echo $selected_category > 0 ? '?category='.$selected_category : ''; ?>" 
                   class="btn btn-primary btn-lg rounded-pill px-5 py-3 shadow-sm">
                    <i class="fa-solid fa-eye me-2"></i>
                    View All Campaigns (<?php echo $total_available; ?>)
                </a>
                <p class="text-muted small mt-3">
                    <i class="fa-regular fa-circle-check text-success me-1"></i>
                    Showing <?php echo count($campaigns); ?> of <?php echo $total_available; ?> campaigns
                </p>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Empty State -->
            <div class="col-12">
                <div class="text-center py-5">
                    <div class="empty-state-icon mb-4">
                        <i class="fa-solid fa-rocket fa-4x text-muted"></i>
                    </div>
                    <h3 class="h4 fw-bold mb-3">No Campaigns Found</h3>
                    <p class="text-muted mb-4">There are no campaigns available in this category at the moment.</p>
                    <?php if ($selected_category > 0): ?>
                        <a href="index.php" class="btn btn-primary btn-lg rounded-pill px-5">
                            <i class="fa-solid fa-arrow-left me-2"></i>View All Campaigns
                        </a>
                    <?php else: ?>
                        <?php if (!isLoggedIn()): ?>
                            <a href="login.php" class="btn btn-primary btn-lg rounded-pill px-5">
                                <i class="fa-solid fa-right-to-bracket me-2"></i>Login to Start a Campaign
                            </a>
                        <?php else: ?>
                            <a href="create_campaign.php" class="btn btn-primary btn-lg rounded-pill px-5">
                                <i class="fa-solid fa-plus-circle me-2"></i>Start Your First Campaign
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- SQL to create donations table (if needed) -->
<?php if (!$donations_table_exists): ?>
<div class="container mt-5">
    <div class="alert alert-info border-0 rounded-4 shadow-sm">
        <div class="d-flex align-items-start">
            <i class="fa-solid fa-circle-info fa-2x me-3 mt-1"></i>
            <div>
                <h5 class="alert-heading fw-bold mb-2">Database Setup Required</h5>
                <p class="mb-2">The donations table doesn't exist yet. Please run this SQL to create it:</p>
                <pre class="bg-dark text-light p-3 rounded-3 mb-0"><code>CREATE TABLE IF NOT EXISTS donations (
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
);</code></pre>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Professional CSS -->
<style>
/* Hero Section */
.hero-section {
    background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                url('uploads/images/donation-bg.png');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    margin-top: -1.5rem;
    position: relative;
    padding: 4rem 0 !important;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(20,30,48,0.4), rgba(36,59,85,0.4));
    pointer-events: none;
}

.hero-section .container {
    position: relative;
    z-index: 2;
}

.hero-section .btn {
    min-width: 220px;
    font-weight: 600;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
    border-width: 2px;
}

.hero-section .btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.3);
}

/* Features Grid */
.features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin: 4rem 0 1rem;
    position: relative;
    z-index: 2;
}

.feature-item {
    text-align: center;
    padding: 2.5rem 2rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.feature-item:hover {
    transform: translateY(-8px);
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
}

.feature-item i {
    font-size: 3rem;
    margin-bottom: 1.5rem;
    color: #a7e0b5;
}

.feature-item h4 {
    color: white;
    font-weight: 600;
    margin-bottom: 1rem;
    font-size: 1.25rem;
}

.feature-item p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 0;
}

/* Campaign Cards */
.campaign-card {
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
    background: white;
    height: 100%;
}

.campaign-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
}

.campaign-card:hover .card-img-top {
    transform: scale(1.08);
}

.card-img-top {
    transition: transform 0.6s ease;
}

/* Progress Bar */
.progress {
    background-color: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    background: linear-gradient(90deg, #2ecc71, #27ae60);
    border-radius: 10px;
    transition: width 1s ease;
}

/* Category Filter */
.nav-pills .nav-link {
    color: #495057;
    border-radius: 30px;
    padding: 0.6rem 1.5rem;
    margin: 0 3px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.nav-pills .nav-link:hover {
    background-color: #e9ecef;
    transform: translateY(-2px);
    border-color: #dee2e6;
}

.nav-pills .nav-link.active {
    background: linear-gradient(135deg, #141e30, #243b55);
    color: white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    border-color: transparent;
}

/* Stat Cards */
.stat-card {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    border-radius: 20px !important;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.2) !important;
}

.stat-value {
    line-height: 1.2;
}

.stat-icon {
    opacity: 0.2;
    transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.2) rotate(5deg);
    opacity: 0.3;
}

/* Featured Icon */
.featured-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #141e30, #243b55);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

/* Empty State */
.empty-state-icon {
    width: 120px;
    height: 120px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    transition: all 0.3s ease;
}

.empty-state-icon:hover {
    transform: scale(1.05);
    background: #e9ecef;
}

/* Badge Styles */
.badge {
    font-weight: 500;
    font-size: 0.85rem;
    backdrop-filter: blur(5px);
    letter-spacing: 0.3px;
}

.badge.bg-primary {
    background: linear-gradient(135deg, #141e30, #243b55) !important;
}

/* Button Styles */
.btn-primary {
    background: linear-gradient(135deg, #141e30, #243b55);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    background: linear-gradient(135deg, #1a2538, #2a3f5a);
}

.btn-outline-light {
    border-width: 2px;
    font-weight: 600;
}

.btn-outline-light:hover {
    background: white;
    color: #141e30 !important;
}

/* Dark Mode */
body.dark-mode .campaign-card {
    background: #1e1e1e;
    border: 1px solid #333 !important;
}

body.dark-mode .card-title a {
    color: #fff !important;
}

body.dark-mode .text-muted {
    color: #aaa !important;
}

body.dark-mode .nav-pills .nav-link {
    color: #ddd;
    border-color: #444;
}

body.dark-mode .nav-pills .nav-link:hover {
    background-color: #333;
    border-color: #555;
}

body.dark-mode .nav-pills .nav-link.active {
    background: linear-gradient(135deg, #2c3e50, #3498db);
    border-color: transparent;
}

body.dark-mode .progress {
    background-color: #333;
}

body.dark-mode .border-top {
    border-top-color: #333 !important;
}

body.dark-mode .empty-state-icon {
    background: #2d2d2d;
}

body.dark-mode .empty-state-icon i {
    color: #666 !important;
}

body.dark-mode .bg-light {
    background-color: #2d2d2d !important;
}

/* Responsive */
@media (max-width: 768px) {
    .hero-section {
        padding: 2rem 0 !important;
    }
    
    .hero-section h1 {
        font-size: 2rem;
    }
    
    .hero-section .btn {
        min-width: 100%;
        padding: 0.75rem 1rem !important;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
        margin: 2rem 0;
    }
    
    .feature-item {
        padding: 1.5rem;
    }
    
    .campaign-card:hover {
        transform: none;
    }
    
    .stat-card {
        text-align: center;
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
}

/* Container */
.container {
    max-width: 1280px;
}

/* Animations */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate__animated {
    animation-duration: 1s;
    animation-fill-mode: both;
}

.animate__fadeInDown {
    animation-name: fadeInDown;
}

.animate__fadeInUp {
    animation-name: fadeInUp;
}

/* Spacing */
.gap-3 {
    gap: 1rem !important;
}

.mb-5 {
    margin-bottom: 4rem !important;
}

/* Rounded corners */
.rounded-4 {
    border-radius: 20px !important;
}

.rounded-pill {
    border-radius: 50px !important;
}
</style>

<?php
require_once 'footer.php';
?>
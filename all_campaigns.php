<?php
require_once 'header.php';

// Get selected category (if any)
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 9; // Campaigns per page
$offset = ($page - 1) * $limit;

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

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM campaigns WHERE status='approved'";
$count_params = [];
if ($selected_category > 0) {
    $count_sql .= " AND category_id = ?";
    $count_params[] = $selected_category;
}
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$total_campaigns = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_campaigns / $limit);

// Build query with conditional subqueries and pagination
if ($donations_table_exists) {
    $sql = "SELECT campaigns.*, categories.name as category_name,
            (SELECT COUNT(*) FROM donations WHERE campaign_id = campaigns.id) as donor_count,
            (SELECT COALESCE(SUM(amount), 0) FROM donations WHERE campaign_id = campaigns.id) as raised_amount
            FROM campaigns 
            LEFT JOIN categories ON campaigns.category_id = categories.id 
            WHERE campaigns.status='approved'";
} else {
    $sql = "SELECT campaigns.*, categories.name as category_name,
            0 as donor_count,
            0 as raised_amount
            FROM campaigns 
            LEFT JOIN categories ON campaigns.category_id = categories.id 
            WHERE campaigns.status='approved'";
}

// Build query with conditional subqueries and pagination
if ($donations_table_exists) {
    $sql = "SELECT campaigns.*, categories.name as category_name,
            (SELECT COUNT(*) FROM donations WHERE campaign_id = campaigns.id) as donor_count,
            (SELECT COALESCE(SUM(amount), 0) FROM donations WHERE campaign_id = campaigns.id) as raised_amount
            FROM campaigns 
            LEFT JOIN categories ON campaigns.category_id = categories.id 
            WHERE campaigns.status='approved'";
} else {
    $sql = "SELECT campaigns.*, categories.name as category_name,
            0 as donor_count,
            0 as raised_amount
            FROM campaigns 
            LEFT JOIN categories ON campaigns.category_id = categories.id 
            WHERE campaigns.status='approved'";
}

// Add category filter if selected
if ($selected_category > 0) {
    $sql .= " AND campaigns.category_id = :category_id";
}

$sql .= " ORDER BY campaigns.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

// Bind parameters with explicit types
if ($selected_category > 0) {
    $stmt->bindValue(':category_id', $selected_category, PDO::PARAM_INT);
}
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Calculate progress for each campaign
foreach ($campaigns as &$campaign) {
    $campaign['progress'] = min(100, round(($campaign['raised_amount'] / $campaign['target_amount']) * 100, 1));
}
?>

<!-- Page Header -->
<div class="bg-light py-4 mb-5">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div>
                <h1 class="display-5 fw-bold mb-2">All Campaigns</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                        <li class="breadcrumb-item active">All Campaigns</li>
                    </ol>
                </nav>
            </div>
            <div class="mt-3 mt-md-0">
                <span class="badge bg-primary p-3 rounded-pill">
                    <i class="fa-solid fa-campground me-2"></i>
                    <?php echo $total_campaigns; ?> Campaigns Available
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container">
    <!-- Filter Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5">
        <div class="d-flex align-items-center mb-3 mb-md-0">
            <div class="featured-icon me-3">
                <i class="fa-solid fa-filter fa-2x text-primary"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1">Filter by Category</h5>
                <p class="text-muted mb-0">Find campaigns that match your interests</p>
            </div>
        </div>
        
        <!-- Category filter -->
        <div class="category-filter-wrapper">
            <!-- Mobile dropdown -->
            <div class="d-md-none w-100">
                <select class="form-select form-select-lg" onchange="window.location.href=this.value">
                    <option value="all_campaigns.php" <?php echo $selected_category == 0 ? 'selected' : ''; ?>>All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="all_campaigns.php?category=<?php echo $cat['id']; ?>" <?php echo $selected_category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Desktop pills -->
            <div class="d-none d-md-block">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $selected_category == 0 ? 'active' : ''; ?>" href="all_campaigns.php">
                            All
                        </a>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $selected_category == $cat['id'] ? 'active' : ''; ?>" href="all_campaigns.php?category=<?php echo $cat['id']; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Campaign Grid -->
    <?php if (count($campaigns) > 0): ?>
        <div class="row g-4">
            <?php foreach ($campaigns as $campaign): ?>
                <div class="col-xl-4 col-lg-4 col-md-6">
                    <div class="card h-100 campaign-card border-0 shadow-sm">
                        <!-- Campaign Image with Category Badge -->
                        <div class="position-relative overflow-hidden">
                            <img src="<?php echo htmlspecialchars($campaign['image_path'] ?: 'assets/images/default-campaign.jpg'); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($campaign['title']); ?>"
                                 style="height: 220px; object-fit: cover; transition: transform 0.5s ease;">
                            <span class="position-absolute top-0 end-0 badge bg-primary m-3 px-3 py-2 rounded-pill">
                                <i class="fa-solid fa-tag me-1"></i>
                                <?php echo htmlspecialchars($campaign['category_name'] ?? 'Uncategorized'); ?>
                            </span>
                            <?php if($campaign['progress'] >= 100): ?>
                                <span class="position-absolute top-0 start-0 badge bg-success m-3 px-3 py-2 rounded-pill">
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
                                    <span class="fw-bold text-primary">$<?php echo number_format($campaign['raised_amount'], 0); ?></span>
                                    <span class="text-muted">of $<?php echo number_format($campaign['target_amount'], 0); ?></span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success" 
                                         role="progressbar" 
                                         style="width: <?php echo $campaign['progress']; ?>%;" 
                                         aria-valuenow="<?php echo $campaign['progress']; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-2 small">
                                    <span class="text-success fw-bold"><?php echo $campaign['progress']; ?>% Funded</span>
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
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Campaign pagination" class="mt-5">
            <ul class="pagination justify-content-center">
                <!-- Previous button -->
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $selected_category > 0 ? '&category='.$selected_category : ''; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                
                <!-- Page numbers -->
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $selected_category > 0 ? '&category='.$selected_category : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <!-- Next button -->
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $selected_category > 0 ? '&category='.$selected_category : ''; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

        <!-- Back to Home link -->
        <div class="text-center mt-4">
            <a href="index.php" class="text-decoration-none">
                <i class="fa-solid fa-arrow-left me-2"></i>
                Back to Home
            </a>
        </div>

    <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-5">
            <div class="empty-state-icon mb-4">
                <i class="fa-solid fa-rocket fa-4x text-muted"></i>
            </div>
            <h3 class="h4 text-muted mb-3">No Campaigns Found</h3>
            <p class="text-muted mb-4">There are no campaigns available in this category at the moment.</p>
            <a href="all_campaigns.php" class="btn btn-primary btn-lg rounded-pill px-5">
                <i class="fa-solid fa-arrow-left me-2"></i>View All Campaigns
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- SQL to create donations table (if needed) -->
<?php if (!$donations_table_exists): ?>
<div class="container mt-5">
    <div class="alert alert-info border-0 rounded-4 shadow-sm">
        <div class="d-flex align-items-start">
            <i class="fa-solid fa-circle-info fa-2x me-3 mt-1"></i>
            <div>
                <h5 class="alert-heading mb-2">Database Setup Required</h5>
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

<!-- Additional CSS -->
<style>
/* Campaign Card Styles */
.campaign-card {
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
    background: white;
}

.campaign-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.12) !important;
}

.campaign-card:hover .card-img-top {
    transform: scale(1.05);
}

.card-img-top {
    transition: transform 0.5s ease;
}

.progress {
    background-color: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    background: linear-gradient(90deg, #00c6ff, #0072ff);
    border-radius: 10px;
    transition: width 1s ease;
}

.nav-pills .nav-link {
    color: #495057;
    border-radius: 30px;
    padding: 0.6rem 1.5rem;
    margin: 0 3px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.nav-pills .nav-link:hover {
    background-color: #e9ecef;
    transform: translateY(-2px);
}

.nav-pills .nav-link.active {
    background: linear-gradient(45deg, #141e30, #243b55);
    color: white;
    box-shadow: 0 5px 15px rgba(0,198,255,0.3);
}

.featured-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #141e30, #243b55);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.empty-state-icon {
    width: 120px;
    height: 120px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

/* Pagination styles */
.pagination {
    gap: 5px;
}

.page-link {
    border: none;
    padding: 0.75rem 1rem;
    color: #141e30;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.page-link:hover {
    background: linear-gradient(135deg, #141e30, #243b55);
    color: white;
    transform: translateY(-2px);
}

.page-item.active .page-link {
    background: linear-gradient(135deg, #141e30, #243b55);
    color: white;
}

.page-item.disabled .page-link {
    background: #f8f9fa;
    color: #aaa;
}

/* Breadcrumb */
.breadcrumb-item a {
    color: #6c757d;
}

.breadcrumb-item.active {
    color: #00c6ff;
}

/* Dark mode adjustments */
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
}

body.dark-mode .nav-pills .nav-link:hover {
    background-color: #333;
}

body.dark-mode .nav-pills .nav-link.active {
    background: linear-gradient(45deg, #2c3e50, #3498db);
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

body.dark-mode .page-link {
    background: #2d2d2d;
    color: #ddd;
    border: 1px solid #444;
}

body.dark-mode .page-item.disabled .page-link {
    background: #1e1e1e;
    color: #666;
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
    
    .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<?php
require_once 'footer.php';
?>
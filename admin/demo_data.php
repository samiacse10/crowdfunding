<?php
require_once '../config.php';
require_once 'admin_header.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$message = '';

// Generate demo data
if (isset($_POST['generate_demo'])) {
    try {
        // Create tables if not exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS campaigns (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                category_id INT,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                target_amount DECIMAL(10,2) NOT NULL,
                image_path VARCHAR(255),
                status ENUM('pending','approved','rejected') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS donations (
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
            )
        ");
        
        // Insert sample categories
        $categories = ['Technology', 'Art', 'Music', 'Film', 'Education', 'Health', 'Community', 'Environment', 'Business', 'Sports'];
        foreach ($categories as $cat) {
            $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (?)")->execute([$cat]);
        }
        
        // Get existing users
        $users = $pdo->query("SELECT id FROM users LIMIT 5")->fetchAll();
        if (empty($users)) {
            // Create demo users
            for ($i = 1; $i <= 5; $i++) {
                $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)")->execute([
                    "demo_user_$i",
                    "demo$i@example.com",
                    password_hash("demo123", PASSWORD_DEFAULT)
                ]);
            }
            $users = $pdo->query("SELECT id FROM users LIMIT 5")->fetchAll();
        }
        
        // Generate sample campaigns
        $campaign_titles = [
            'Help Build a Community Library',
            'Support Local Art Gallery',
            'Medical Treatment for Child',
            'Clean Water Project',
            'Startup Tech Innovation',
            'Animal Shelter Renovation',
            'School Computer Lab',
            'Environmental Conservation',
            'Music Album Production',
            'Documentary Film Project'
        ];
        
        for ($i = 0; $i < 20; $i++) {
            $user = $users[array_rand($users)];
            $category_id = rand(1, 10);
            $title = $campaign_titles[array_rand($campaign_titles)] . ' ' . ($i + 1);
            $target = rand(1000, 50000);
            $status = ['pending', 'approved', 'approved', 'approved', 'rejected'][rand(0, 4)];
            
            $pdo->prepare("INSERT INTO campaigns (user_id, category_id, title, description, target_amount, image_path, status, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))")
                ->execute([
                    $user['id'],
                    $category_id,
                    $title,
                    "This is a sample campaign description for $title. This demonstrates how campaigns look on the platform.",
                    $target,
                    'uploads/default-campaign.jpg',
                    $status,
                    rand(1, 60)
                ]);
        }
        
        // Get all campaigns
        $campaigns = $pdo->query("SELECT id FROM campaigns")->fetchAll();
        
        // Generate sample donations
        $payment_methods = ['bkash', 'nagad', 'rocket', 'bank_transfer', 'card'];
        $donor_names = ['John Doe', 'Jane Smith', 'Mike Johnson', 'Sarah Wilson', 'David Brown', 'Emma Davis', 'Chris Lee', 'Lisa Anderson'];
        
        for ($i = 0; $i < 100; $i++) {
            $campaign = $campaigns[array_rand($campaigns)];
            $user = $users[array_rand($users)];
            $amount = rand(10, 500);
            $method = $payment_methods[array_rand($payment_methods)];
            $days_ago = rand(1, 60);
            
            $pdo->prepare("INSERT INTO donations 
                (campaign_id, user_id, donor_name, donor_email, amount, payment_method, transaction_id, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', DATE_SUB(NOW(), INTERVAL ? DAY))")
                ->execute([
                    $campaign['id'],
                    $user['id'],
                    $donor_names[array_rand($donor_names)],
                    $user['email'] ?? 'demo@example.com',
                    $amount,
                    $method,
                    'TXN' . uniqid(),
                    $days_ago
                ]);
        }
        
        $message = '<div class="alert alert-success">✅ Demo data generated successfully! Created 20 campaigns and 100 donations.</div>';
        
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// Clear demo data
if (isset($_POST['clear_demo'])) {
    try {
        $pdo->exec("DELETE FROM donations");
        $pdo->exec("DELETE FROM campaigns WHERE id > 0");
        $pdo->exec("DELETE FROM categories WHERE id > 0");
        $message = '<div class="alert alert-warning">🧹 Demo data cleared!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="container-fluid py-4">
    <div class="page-header">
        <h1><i class="fa-solid fa-flask me-2"></i>Demo Data Generator</h1>
        <p class="text-muted">Generate sample data for your project demonstration</p>
    </div>
    
    <?php echo $message; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center p-5">
                    <i class="fa-solid fa-magic fa-4x text-primary mb-4"></i>
                    <h3 class="fw-bold mb-3">Generate Demo Data</h3>
                    <p class="text-muted mb-4">
                        This will create sample categories, campaigns, and donations<br>
                        <small>✓ 10 Categories</small><br>
                        <small>✓ 20 Campaigns</small><br>
                        <small>✓ 100 Donations</small>
                    </p>
                    <form method="POST">
                        <button type="submit" name="generate_demo" class="btn btn-primary btn-lg rounded-pill px-5">
                            <i class="fa-solid fa-play me-2"></i>Generate Demo Data
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center p-5">
                    <i class="fa-solid fa-trash-can fa-4x text-danger mb-4"></i>
                    <h3 class="fw-bold mb-3">Clear Demo Data</h3>
                    <p class="text-muted mb-4">
                        Remove all demo data from the database<br>
                        <small>⚠️ This action cannot be undone</small>
                    </p>
                    <form method="POST" onsubmit="return confirm('Are you sure? This will delete all demo data!');">
                        <button type="submit" name="clear_demo" class="btn btn-outline-danger btn-lg rounded-pill px-5">
                            <i class="fa-regular fa-trash-can me-2"></i>Clear All Data
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">
                        <i class="fa-solid fa-circle-info text-primary me-2"></i>
                        How to Present to Your Teacher
                    </h5>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="step-circle bg-primary text-white rounded-circle mx-auto mb-3">1</div>
                                <h6>Generate Demo Data</h6>
                                <small class="text-muted">Click the button to create sample data</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="step-circle bg-primary text-white rounded-circle mx-auto mb-3">2</div>
                                <h6>View Dashboard</h6>
                                <small class="text-muted">See statistics and charts with real numbers</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="step-circle bg-primary text-white rounded-circle mx-auto mb-3">3</div>
                                <h6>Demo Donations</h6>
                                <small class="text-muted">Use the demo donation page to simulate payments</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="step-circle bg-primary text-white rounded-circle mx-auto mb-3">4</div>
                                <h6>Show Reports</h6>
                                <small class="text-muted">Export data to show your teacher</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="alert alert-info">
                        <i class="fa-regular fa-lightbulb me-2"></i>
                        <strong>Pro Tip:</strong> Before presenting to your teacher, generate the demo data and show:
                        <ul class="mt-2 mb-0">
                            <li>Admin Dashboard with statistics</li>
                            <li>Donation charts and analytics</li>
                            <li>Campaign management page</li>
                            <li>User profile and donation history</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.step-circle {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.page-header {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

body.dark-mode .page-header {
    background: #2d2d2d;
}
</style>

<?php require_once 'admin_footer.php'; ?>
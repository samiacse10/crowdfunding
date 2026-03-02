<?php
require_once 'config.php';
if(session_status() == PHP_SESSION_NONE) session_start();
// Menu links configuration
function getUserRole() {
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        return $user ? $user['role'] : 'donor';
    }
    return null;
}

function canCreateCampaign() {
    $role = getUserRole();
    return $role === 'campaign_creator' || $role === 'admin';
}
$menuLinks = [
    'guest' => [
        ['label' => 'Home', 'url' => 'index.php'],
        ['label' => 'Login', 'url' => 'login.php'],
        ['label' => 'Register', 'url' => 'register.php'],
        ['label' => 'Admin Login', 'url' => 'admin/admin_login.php']
    ],
    'user' => [
        ['label' => 'Home', 'url' => 'index.php'],
        ['label' => 'Start a Campaign', 'url' => 'create_campaign.php'],
        ['label' => 'Dashboard', 'url' => 'dashboard.php'],
        ['label' => 'Profile', 'url' => 'profile.php'],
        ['label' => 'Settings', 'url' => 'settings.php'],
        ['label' => 'Logout', 'url' => 'logout.php']
    ],
    'admin' => [
        ['label' => 'Admin Dashboard', 'url' => 'admin/admin_dashboard.php'],
        ['label' => 'Admin Logout', 'url' => 'admin/admin_logout.php']
    ]
];

// Determine current menu based on user role
if (isLoggedIn()) {
    $currentMenu = $menuLinks['user'];
} elseif (isAdminLoggedIn()) {
    $currentMenu = $menuLinks['admin'];
} else {
    $currentMenu = $menuLinks['guest'];
}

// Helper function to determine active menu item
function isActive($url) {
    return basename($_SERVER['PHP_SELF']) === $url ? 'active' : '';
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crowdfunding Platform</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* Body light/dark mode */
        body {
            background: #f8f9fa;
            color: #212926;
            transition: background-color 0.3s, color 0.3s;
        }

        body.dark-mode {
            background: #121212;
            color: #eee;
        }

        /* Dark mode link styles */
        body.dark-mode a { 
            color: #6fcf97; 
        }
        
        body.dark-mode a:hover { 
            color: #fff; 
        }
        
        body.dark-mode .container, 
        body.dark-mode main { 
            color: #eee; 
        }

        /* Navbar styles - Dark Green Theme */
        .navbar {
            background: linear-gradient(135deg, #1a472a 0%, #2d6a4f 50%, #40916c 100%);
            padding: 0.5rem 1rem;
            border-bottom: 3px solid #74c69d;
        }
        
        .navbar .navbar-brand {
            color: #fff !important;
            font-size: 1.35rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            letter-spacing: 0.5px;
        }
        
        .navbar .navbar-brand i {
            color: #a7e0b5;
            margin-right: 8px;
            font-size: 1.5rem;
        }
        
        .navbar .nav-link {
            color: rgba(255,255,255,0.95) !important;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem !important;
            font-weight: 500;
            border-radius: 25px;
            margin: 0 2px;
        }
        
        .navbar .nav-link:hover {
            color: #fff !important;
            background: rgba(167, 224, 181, 0.25);
            transform: translateY(-2px);
        }
        
        .navbar .nav-link.active {
            font-weight: 600;
            background: rgba(167, 224, 181, 0.3);
            border-bottom: 2px solid #a7e0b5;
            border-radius: 25px 25px 0 0;
        }
        
        .navbar .badge {
            font-size: 0.7rem;
            margin-left: 5px;
            background: #ff6b6b !important;
            color: white;
        }

        /* Navbar buttons */
        .navbar .btn-outline-light {
            border: 2px solid #a7e0b5;
            color: white;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 30px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            background: rgba(167, 224, 181, 0.15);
        }
        
        .navbar .btn-outline-light:hover {
            background: #a7e0b5;
            border-color: #a7e0b5;
            color: #1a472a;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(167, 224, 181, 0.4);
        }
        
        .navbar .btn-outline-light i {
            font-size: 1rem;
        }

        /* Profile sidebar styles */
        #profile-sidebar {
            position: fixed;
            top: 80px;
            right: 20px;
            width: 280px;
            background: #1e3a2f;
            color: #fff;
            padding: 20px;
            display: none;
            flex-direction: column;
            gap: 8px;
            z-index: 999;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            border-radius: 16px;
            border: 1px solid #40916c;
        }
        
        #profile-sidebar h5 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #40916c;
            color: #a7e0b5;
            font-weight: 600;
        }
        
        #profile-sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.05);
        }
        
        #profile-sidebar a i {
            width: 20px;
            text-align: center;
            color: #a7e0b5;
        }
        
        #profile-sidebar a:hover {
            background: #40916c;
            color: #fff;
            transform: translateX(-5px);
        }
        
        #profile-sidebar a:hover i {
            color: #fff;
        }

        /* Dark mode navbar adjustments */
        body.dark-mode .navbar {
            background: linear-gradient(135deg, #0f2a1a 0%, #1e4a33 50%, #2d6a4f 100%);
            border-bottom: 3px solid #52b788;
        }
        
        body.dark-mode #profile-sidebar {
            background: #132b1f;
            border-color: #2d6a4f;
        }
        
        body.dark-mode #profile-sidebar a:hover {
            background: #2d6a4f;
        }

        /* Responsive adjustments */
        @media(max-width: 991.98px) {
            .navbar .btn-outline-light {
                margin: 5px 0;
            }
            
            .navbar .d-flex {
                margin-top: 10px;
                justify-content: flex-end;
            }
            
            .navbar .navbar-nav {
                background: rgba(0,0,0,0.2);
                padding: 10px;
                border-radius: 15px;
                margin-top: 10px;
            }
        }
        
        @media(max-width: 768px) {
            #profile-sidebar {
                width: calc(100% - 40px);
                right: 20px;
                max-height: 80vh;
                overflow-y: auto;
            }
            
            .navbar .btn-outline-light {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            
            .navbar .navbar-brand {
                font-size: 1.1rem;
            }
            
            .navbar .navbar-brand i {
                font-size: 1.2rem;
            }
        }

        /* Add subtle animation to brand */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .navbar .navbar-brand:hover i {
            animation: pulse 0.5s ease;
        }
    </style>
    
    <script>
        // Toggle dark mode
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('dark-mode', document.body.classList.contains('dark-mode'));
            
            // Update icon
            const icon = document.querySelector('#darkModeBtn i');
            if (document.body.classList.contains('dark-mode')) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }

        // Toggle profile sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('profile-sidebar');
            if (sidebar.style.display === 'flex') {
                sidebar.style.display = 'none';
            } else {
                sidebar.style.display = 'flex';
            }
        }

        // Load dark mode preference
        window.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('dark-mode') === 'true') {
                document.body.classList.add('dark-mode');
                // Update icon
                const icon = document.querySelector('#darkModeBtn i');
                if (icon) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                }
            }
        });

        // Close sidebar when clicking outside
        window.addEventListener('click', function(e) {
            const sidebar = document.getElementById('profile-sidebar');
            const button = document.querySelector('[onclick="toggleSidebar()"]');
            
            if (sidebar && sidebar.style.display === 'flex') {
                if (!sidebar.contains(e.target) && !button.contains(e.target)) {
                    sidebar.style.display = 'none';
                }
            }
        });
    </script>
</head>
<body>
    <!-- Navigation Bar with Dark Green Theme -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow-lg">
        <div class="container">
            <!-- Brand with enhanced styling -->
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fa-solid fa-seedling"></i> Crowdfund
            </a>
            
            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <?php foreach($currentMenu as $link): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive($link['url']); ?>" href="<?php echo $link['url']; ?>">
                                <?php 
                                // Display logout with username if logged in
                                if($link['label'] === 'Logout' && isLoggedIn()) {
                                    echo "Logout (" . htmlspecialchars($_SESSION['username']) . ")";
                                } else {
                                    echo $link['label'];
                                }
                                
                                // Display notification badge on dashboard
                                if($link['label'] === 'Dashboard' && isset($_SESSION['notifications']) && $_SESSION['notifications'] > 0) {
                                    echo "<span class='badge bg-danger'>" . $_SESSION['notifications'] . "</span>";
                                }
                                ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <!-- Right Side Buttons -->
                <div class="d-flex ms-lg-3 mt-2 mt-lg-0">
                    <?php if(isLoggedIn()): ?>
                        <button class="btn btn-outline-light me-2" onclick="toggleSidebar()" title="Profile Menu">
                            <i class="fa-solid fa-user-cog"></i>
                        </button>
                    <?php endif; ?>
                    
                    <button class="btn btn-outline-light" onclick="toggleDarkMode()" id="darkModeBtn" title="Toggle Dark Mode">
                        <i class="fa-solid fa-moon"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Profile Sidebar (only shown when logged in) -->
    <?php if(isLoggedIn()): ?>
        <div id="profile-sidebar">
            <h5><i class="fa-solid fa-seedling me-2"></i>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></h5>
            <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
            <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    <?php endif; ?>
    
    <!-- Spacer for fixed navbar -->
    <div style="margin-top: 76px;"></div>
    
    <!-- Main content starts here -->
    <main class="container my-5">
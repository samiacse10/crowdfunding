<?php
if (session_status() == PHP_SESSION_NONE) session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) && basename($_SERVER['PHP_SELF']) != 'admin_login.php') {
    header('Location: admin_login.php');
    exit();
}

// Get admin info if logged in
$admin_name = '';
if (isset($_SESSION['admin_id'])) {
    // You can fetch admin name from database if needed
    $admin_name = $_SESSION['admin_username'] ?? 'Admin';
}

// Get current page name for active menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Crowdfunding Platform</title>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f4f6f9;
        }

        /* Admin Navigation */
        .admin-navbar {
            background: linear-gradient(135deg, #141e30, #243b55);
            padding: 0.8rem 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .admin-brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: white !important;
            letter-spacing: -0.5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-brand i {
            color: #00c6ff;
            font-size: 1.8rem;
        }

        .admin-nav-link {
            color: rgba(255,255,255,0.8) !important;
            font-weight: 500;
            padding: 0.6rem 1.2rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-nav-link:hover {
            color: white !important;
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        .admin-nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white !important;
            font-weight: 600;
            position: relative;
        }

        .admin-nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 3px;
            background: #00c6ff;
            border-radius: 3px;
        }

        .admin-nav-link i {
            font-size: 1.1rem;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-profile .dropdown-toggle {
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .admin-profile .dropdown-toggle:hover {
            background: rgba(255,255,255,0.2);
        }

        .admin-profile .dropdown-toggle::after {
            display: none;
        }

        .admin-profile .dropdown-menu {
            border: none;
            box-shadow: 0 5px 30px rgba(0,0,0,0.15);
            border-radius: 12px;
            padding: 0.5rem;
            min-width: 200px;
        }

        .admin-profile .dropdown-item {
            padding: 0.7rem 1rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .admin-profile .dropdown-item:hover {
            background: linear-gradient(135deg, #141e30, #243b55);
            color: white;
            transform: translateX(5px);
        }

        .admin-profile .dropdown-item i {
            width: 20px;
            text-align: center;
        }

        .admin-profile .dropdown-divider {
            margin: 0.5rem 0;
        }

        /* Main Content Area */
        .admin-main {
            padding: 20px;
            min-height: calc(100vh - 70px);
        }

        /* Page Header */
        .page-header {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #141e30;
            margin: 0;
        }

        .page-header .breadcrumb {
            margin: 0;
            background: transparent;
        }

        .page-header .breadcrumb-item a {
            color: #6c757d;
            text-decoration: none;
        }

        .page-header .breadcrumb-item.active {
            color: #00c6ff;
        }

        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #141e30, #243b55);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .stat-card .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: #141e30;
            margin: 0.5rem 0 0.2rem;
        }

        .stat-card .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }

        /* Tables */
        .admin-table {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .admin-table thead {
            background: #f8f9fa;
        }

        .admin-table th {
            color: #141e30;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 1rem;
        }

        .admin-table td {
            padding: 1rem;
            vertical-align: middle;
        }

        /* Buttons */
        .btn-admin {
            background: linear-gradient(135deg, #141e30, #243b55);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,198,255,0.3);
            color: white;
        }

        .btn-admin-outline {
            background: transparent;
            border: 2px solid #141e30;
            color: #141e30;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-admin-outline:hover {
            background: linear-gradient(135deg, #141e30, #243b55);
            border-color: transparent;
            color: white;
            transform: translateY(-2px);
        }

        /* Badges */
        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-approved {
            background: #d4edda;
            color: #155724;
        }

        .badge-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        /* Sidebar for mobile */
        @media (max-width: 768px) {
            .admin-navbar .container-fluid {
                flex-wrap: wrap;
            }
            
            .admin-profile {
                margin-top: 1rem;
                width: 100%;
                justify-content: flex-end;
            }
            
            .navbar-nav {
                padding: 1rem 0;
            }
            
            .admin-nav-link {
                padding: 0.8rem !important;
            }
            
            .admin-nav-link.active::after {
                display: none;
            }
        }

        /* Dark mode support */
        body.dark-mode {
            background: #1a1a1a;
        }

        body.dark-mode .page-header,
        body.dark-mode .stat-card,
        body.dark-mode .admin-table {
            background: #2d2d2d;
            color: #fff;
        }

        body.dark-mode .page-header h1 {
            color: #fff;
        }

        body.dark-mode .stat-card .stat-value {
            color: #fff;
        }

        body.dark-mode .admin-table thead {
            background: #3d3d3d;
        }

        body.dark-mode .admin-table th {
            color: #fff;
        }

        body.dark-mode .admin-table td {
            color: #ddd;
            border-color: #444;
        }

        /* Animation for active link */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .admin-main {
            animation: slideIn 0.5s ease;
        }
    </style>
</head>
<body>
    <!-- Admin Navigation -->
    <nav class="admin-navbar">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center w-100">
                <!-- Brand -->
                <a href="admin_dashboard.php" class="admin-brand">
                    <i class="fa-solid fa-crown"></i>
                    <span>AdminPanel</span>
                </a>

                <!-- Navigation Links -->
                <div class="collapse navbar-collapse" id="adminNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item">
                            <a class="admin-nav-link <?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>" 
                               href="admin_dashboard.php">
                                <i class="fa-solid fa-chart-pie"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="admin-nav-link <?php echo $current_page == 'admin_campaigns.php' ? 'active' : ''; ?>" 
                               href="admin_campaigns.php">
                                <i class="fa-solid fa-rocket"></i>
                                Campaigns
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="admin-nav-link <?php echo $current_page == 'admin_donations.php' ? 'active' : ''; ?>" 
                               href="admin_donations.php">
                                <i class="fa-solid fa-hand-holding-heart"></i>
                                Donations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="admin-nav-link <?php echo $current_page == 'admin_donations_chart.php' ? 'active' : ''; ?>" 
                               href="admin_donations_chart.php">
                                <i class="fa-solid fa-chart-line"></i>
                                Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="admin-nav-link <?php echo $current_page == 'admin_users.php' ? 'active' : ''; ?>" 
                               href="admin_users.php">
                                <i class="fa-solid fa-users"></i>
                                Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="admin-nav-link <?php echo $current_page == 'admin_categories.php' ? 'active' : ''; ?>" 
                               href="admin_categories.php">
                                <i class="fa-solid fa-tags"></i>
                                Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="admin-nav-link <?php echo $current_page == 'admin_settings.php' ? 'active' : ''; ?>" 
                               href="admin_settings.php">
                                <i class="fa-solid fa-gear"></i>
                                Settings
                            </a>
                        </li>
                    </ul>
                </div>
                <li class="nav-item">
    <a class="admin-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'demo_data.php' ? 'active' : ''; ?>" 
       href="demo_data.php">
        <i class="fa-solid fa-flask"></i>
        Demo Data
    </a>
</li>

                <!-- Admin Profile -->
                <?php if (isset($_SESSION['admin_id'])): ?>
                <div class="admin-profile">
                    <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fa-regular fa-circle-user"></i>
                            <span><?php echo htmlspecialchars($admin_name); ?></span>
                            <i class="fa-solid fa-chevron-down ms-1" style="font-size: 0.8rem;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="admin_profile.php">
                                    <i class="fa-regular fa-user"></i>
                                    Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="admin_settings.php">
                                    <i class="fa-regular fa-gear"></i>
                                    Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="admin_logout.php">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Mobile menu toggle -->
                    <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                        <i class="fa-solid fa-bars text-white"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="admin-main">
        <!-- Page Header will be included in each page -->
        <!-- Content will go here -->
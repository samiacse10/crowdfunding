<?php
// No need to start session here as config.php handles it
require_once '../config.php';

// Check if admin is logged in using admin session
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['error'] = 'Access denied. Admin privileges required.';
    header('Location: admin_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Organizer Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --admin-primary: #141e30;
            --admin-secondary: #243b55;
            --admin-gradient: linear-gradient(135deg, #141e30, #243b55);
        }

        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Admin Navbar */
        .admin-navbar {
            background: var(--admin-gradient);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-navbar .brand {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            text-decoration: none;
        }

        .admin-navbar .brand i {
            margin-right: 10px;
            color: #00c6ff;
        }

        .admin-navbar .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-navbar .user-menu span {
            color: white;
            font-weight: 500;
        }

        .admin-navbar .user-menu a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }

        .admin-navbar .user-menu a:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Cards */
        .stat-card {
            border-radius: 15px;
            padding: 1.5rem;
            color: white;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .stat-card.pending { background: linear-gradient(135deg, #f39c12, #f1c40f); }
        .stat-card.approved { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .stat-card.suspended { background: linear-gradient(135deg, #e74c3c, #c0392b); }

        .featured-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
        }

        .avatar {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }

        .empty-state-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 50%;
        }

        /* Dark mode */
        body.dark-mode {
            background: #121212;
        }

        body.dark-mode .card {
            background: #1e1e1e;
            border-color: #333;
        }

        body.dark-mode .table {
            color: #ddd;
        }

        body.dark-mode .table th {
            background: #2d2d2d;
            color: #ddd;
        }

        body.dark-mode .table td {
            border-color: #444;
        }

        body.dark-mode .bg-light {
            background-color: #2d2d2d !important;
        }

        body.dark-mode .empty-state-icon {
            background: #2d2d2d;
        }

        body.dark-mode .text-muted {
            color: #aaa !important;
        }
    </style>
</head>
<body>
    <!-- Admin Navbar -->
    <nav class="admin-navbar">
        <a href="admin_dashboard.php" class="brand">
            <i class="fa-solid fa-shield-halved"></i>
            Admin Panel
        </a>
        <div class="user-menu">
            <span>
                <i class="fa-regular fa-user me-2"></i>
                <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
            </span>
            <a href="admin_logout.php">
                <i class="fa-solid fa-right-from-bracket me-2"></i>
                Logout
            </a>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold mb-0">Organizer Management</h1>
                <p class="text-muted mb-0">Review and manage organizer requests from users</p>
            </div>
            <a href="admin_dashboard.php" class="btn btn-outline-primary">
                <i class="fa-solid fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

        <?php
        // Handle organizer approval/rejection
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            $user_id = intval($_POST['user_id']);
            $action = $_POST['action'];
            
            try {
                if ($action === 'approve') {
                    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ? AND user_type IN ('organizer', 'both')");
                    $stmt->execute([$user_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fa-regular fa-circle-check me-2"></i>Organizer approved successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                              </div>';
                    }
                } elseif ($action === 'reject') {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type IN ('organizer', 'both') AND status = 'pending'");
                    $stmt->execute([$user_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fa-regular fa-circle-xmark me-2"></i>Organizer request rejected and user removed.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                              </div>';
                    }
                }
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        }

        // Handle suspend action
        if (isset($_GET['suspend'])) {
            $user_id = intval($_GET['suspend']);
            try {
                $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ? AND user_type IN ('organizer', 'both') AND status = 'active'");
                $stmt->execute([$user_id]);
                
                if ($stmt->rowCount() > 0) {
                    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fa-regular fa-pause-circle me-2"></i>Organizer suspended successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>';
                }
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        }

        // Get pending organizers
        $pending = $pdo->query("SELECT id, username, email, user_type, created_at,
            (SELECT COUNT(*) FROM campaigns WHERE user_id = users.id) as total_campaigns
            FROM users WHERE user_type IN ('organizer', 'both') AND status = 'pending' 
            ORDER BY created_at DESC")->fetchAll();

        // Get approved organizers
        $approved = $pdo->query("SELECT id, username, email, user_type, created_at,
            (SELECT COUNT(*) FROM campaigns WHERE user_id = users.id) as total_campaigns
            FROM users WHERE user_type IN ('organizer', 'both') AND status = 'active' 
            ORDER BY created_at DESC")->fetchAll();

        // Get suspended organizers
        $suspended = $pdo->query("SELECT id, username, email, user_type, created_at,
            (SELECT COUNT(*) FROM campaigns WHERE user_id = users.id) as total_campaigns
            FROM users WHERE user_type IN ('organizer', 'both') AND status = 'suspended' 
            ORDER BY created_at DESC")->fetchAll();
        ?>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card pending">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-1">Pending</h6>
                            <h2 class="mb-0"><?php echo count($pending); ?></h2>
                        </div>
                        <div class="featured-icon">
                            <i class="fa-regular fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card approved">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-1">Approved</h6>
                            <h2 class="mb-0"><?php echo count($approved); ?></h2>
                        </div>
                        <div class="featured-icon">
                            <i class="fa-regular fa-circle-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card suspended">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-1">Suspended</h6>
                            <h2 class="mb-0"><?php echo count($suspended); ?></h2>
                        </div>
                        <div class="featured-icon">
                            <i class="fa-regular fa-pause-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Organizers -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0">
                    <i class="fa-regular fa-clock text-warning me-2"></i>
                    Pending Organizer Requests (<?php echo count($pending); ?>)
                </h5>
            </div>
            <div class="card-body p-4">
                <?php if (count($pending) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Requested</th>
                                    <th>Campaigns</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending as $user): ?>
                                <tr>
                                    <td>
                                        <i class="fa-regular fa-user me-2 text-primary"></i>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['user_type'] == 'organizer' ? 'primary' : 'info'; ?>">
                                            <?php echo $user['user_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $user['total_campaigns']; ?></span></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this organizer?')">
                                                <i class="fa-regular fa-circle-check me-1"></i>Approve
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this request? This will delete the user.')">
                                                <i class="fa-regular fa-circle-xmark me-1"></i>Reject
                                            </button>
                                        </form>
                                        <a href="view_organizer.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-regular fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No pending organizer requests.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Approved Organizers -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0">
                    <i class="fa-regular fa-circle-check text-success me-2"></i>
                    Approved Organizers (<?php echo count($approved); ?>)
                </h5>
            </div>
            <div class="card-body p-4">
                <?php if (count($approved) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Since</th>
                                    <th>Campaigns</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($approved as $user): ?>
                                <tr>
                                    <td>
                                        <i class="fa-regular fa-circle-check text-success me-2"></i>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="badge bg-success"><?php echo $user['user_type']; ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $user['total_campaigns']; ?></span></td>
                                    <td>
                                        <a href="?suspend=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-warning"
                                           onclick="return confirm('Suspend this organizer?')">
                                            <i class="fa-regular fa-pause-circle me-1"></i>Suspend
                                        </a>
                                        <a href="view_organizer.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-regular fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No approved organizers yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Suspended Organizers (if any) -->
        <?php if (count($suspended) > 0): ?>
        <div class="card border-0 shadow-sm rounded-4 mt-4">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0">
                    <i class="fa-regular fa-pause-circle text-danger me-2"></i>
                    Suspended Organizers (<?php echo count($suspended); ?>)
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Suspended Since</th>
                                <th>Campaigns</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suspended as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo $user['user_type']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td><span class="badge bg-secondary"><?php echo $user['total_campaigns']; ?></span></td>
                                <td>
                                    <a href="view_organizer.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fa-regular fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Dark mode
    if (localStorage.getItem('dark-mode') === 'true') {
        document.body.classList.add('dark-mode');
    }
    </script>
</body>
</html>
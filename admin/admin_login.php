<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $errors = [];

    if (empty($username)) $errors[] = "Username is required";
    if (empty($password)) $errors[] = "Password is required";

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['success'] = "Admin login successful!";
            redirect('admin_dashboard.php');
        } else {
            $errors[] = "Invalid username/email or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Crowdfunding Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --admin-primary: #1a472a;
            --admin-secondary: #2d6a4f;
            --admin-accent: #74c69d;
            --admin-gradient: linear-gradient(135deg, #1a472a 0%, #2d6a4f 50%, #40916c 100%);
            --admin-dark: #0f2a1a;
            --admin-light: #a7e0b5;
        }

        body.admin-auth-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body.admin-auth-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0 0 L100 100 M100 0 L0 100" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></svg>');
            opacity: 0.1;
            pointer-events: none;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .auth-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.4);
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(30deg);
            transition: all 0.5s ease;
            pointer-events: none;
        }

        .auth-card:hover::before {
            transform: rotate(30deg) scale(1.1);
        }

        .auth-header {
            margin-bottom: 2rem;
            position: relative;
        }

        .auth-header i {
            background: var(--admin-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }

        .auth-header h2 {
            color: var(--admin-dark);
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: #6c757d;
            font-size: 1rem;
        }

        .form-floating > .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem 0.75rem;
            height: auto;
            transition: all 0.3s ease;
        }

        .form-floating > .form-control:focus {
            border-color: var(--admin-secondary);
            box-shadow: 0 0 0 4px rgba(45, 106, 79, 0.15);
            outline: none;
        }

        .form-floating > label {
            padding: 1rem 0.75rem;
            color: #6c757d;
        }

        .form-floating > .form-control:focus ~ label {
            color: var(--admin-primary);
            font-weight: 500;
        }

        .btn-primary {
            background: var(--admin-gradient);
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(26, 71, 42, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border: none;
            border-radius: 12px;
            color: #721c24;
            padding: 1rem;
            margin-bottom: 1.5rem;
            animation: slideDown 0.5s ease;
        }

        .alert-danger ul {
            margin: 0;
            padding-left: 1.5rem;
        }

        .alert-danger li {
            list-style-type: disc;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-footer a {
            color: var(--admin-secondary);
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .auth-footer a:hover {
            color: var(--admin-primary);
            transform: translateX(-5px);
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body.admin-auth-bg {
                background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            }

            .auth-card {
                background: rgba(30, 30, 30, 0.95);
            }

            .auth-header h2 {
                color: #fff;
            }

            .auth-header p {
                color: #aaa;
            }

            .form-floating > .form-control {
                background: #2d2d2d;
                border-color: #444;
                color: #fff;
            }

            .form-floating > .form-control:focus {
                border-color: var(--admin-light);
            }

            .form-floating > label {
                color: #aaa;
            }

            .auth-footer a {
                color: var(--admin-light);
            }

            .auth-footer a:hover {
                color: #fff;
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .auth-card {
                padding: 2rem;
                margin: 1rem;
            }

            .auth-header h2 {
                font-size: 1.75rem;
            }
        }

        /* Loading animation for button */
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-primary:disabled::before {
            display: none;
        }
    </style>
</head>
<body class="admin-auth-bg">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="auth-card admin-auth-card">
                    <div class="auth-header text-center">
                        <i class="fas fa-shield-alt fa-3x mb-3"></i>
                        <h2>Admin Login</h2>
                        <p class="text-muted">Access your admin dashboard</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="" class="auth-form" id="loginForm">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username or Email" required>
                            <label for="username"><i class="fas fa-user me-2"></i>Username or Email</label>
                        </div>

                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="loginBtn">
                                <i class="fas fa-lock me-2"></i>Login
                            </button>
                        </div>

                        <div class="auth-footer text-center mt-4">
                            <a href="../index.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i>Back to Home
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating shapes for decoration -->
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <style>
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .shape {
            position: absolute;
            background: var(--admin-gradient);
            opacity: 0.1;
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }

        .shape-1 {
            width: 200px;
            height: 200px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 300px;
            height: 300px;
            bottom: 10%;
            right: 10%;
            animation-delay: -5s;
        }

        .shape-3 {
            width: 150px;
            height: 150px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-30px) rotate(10deg);
            }
        }

        @media (max-width: 768px) {
            .shape {
                opacity: 0.05;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation and animation
        document.getElementById('loginForm')?.addEventListener('submit', function(e) {
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');
            
            if (!username.value || !password.value) {
                e.preventDefault();
                return false;
            }
            
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Logging in...';
            
            return true;
        });

        // Auto-focus username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // Password visibility toggle (optional)
        document.addEventListener('keydown', function(e) {
            // Press Ctrl+Shift+E to toggle password visibility (for testing)
            if (e.ctrlKey && e.shiftKey && e.key === 'E') {
                e.preventDefault();
                const passwordField = document.getElementById('password');
                passwordField.type = passwordField.type === 'password' ? 'text' : 'password';
            }
        });
    </script>
</body>
</html>
<?php
require_once 'header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    $errors = [];

    if (empty($username)) $errors[] = "Username or email is required";
    if (empty($password)) $errors[] = "Password is required";

    if (empty($errors)) {
        // Fetch user with status and user_type
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            
            // Check user status
            if ($user['status'] == 'pending') {
                // User is pending approval
                $errors[] = "Your account is pending approval. You'll be able to login once an administrator approves your account.";
            } 
            elseif ($user['status'] == 'suspended') {
                // User is suspended
                $errors[] = "Your account has been suspended. Please contact support for assistance.";
            }
            elseif ($user['status'] == 'active') {
                // Active user - proceed with login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'] ?? 'donor';
                $_SESSION['profile_pic'] = $user['profile_pic'] ?? 'assets/images/default-avatar.png';
                
                // Set remember me cookie (30 days)
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), '/');
                    // Store token in database (you'd need a remember_me table for this)
                }
                
                $_SESSION['success'] = "Welcome back, " . htmlspecialchars($user['username']) . "!";
                
                // Redirect based on user type (optional)
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                
                // If user is organizer but not approved, show warning (though status check above already handles this)
                if ($user['user_type'] == 'organizer' || $user['user_type'] == 'both') {
                    $_SESSION['info'] = "You can browse and donate to campaigns. Your organizer features are active.";
                }
                
                redirect($redirect);
            } else {
                // Unknown status
                $errors[] = "Account status unknown. Please contact support.";
            }
        } else {
            $errors[] = "Invalid username/email or password";
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <!-- Login Card -->
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <!-- Card Header with decorative element -->
                <div class="card-header bg-primary text-white text-center py-4 border-0" style="background: linear-gradient(135deg, #1a472a 0%, #2d6a4f 50%, #40916c 100%); border-bottom: 3px solid #74c69d;">
                    <div class="featured-icon mx-auto mb-3">
                        <i class="fa-solid fa-seedling fa-2x"></i>
                    </div>
                    <h2 class="h3 fw-bold mb-1">Welcome Back</h2>
                    <p class="text-white-50 mb-0">Login to your account</p>
                </div>
                
                <!-- Card Body -->
                <div class="card-body p-4 p-lg-5">
                    <!-- Alert Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0" role="alert">
                            <div class="d-flex align-items-start">
                                <i class="fa-solid fa-circle-exclamation fs-5 me-2 mt-1"></i>
                                <div>
                                    <strong>Login failed:</strong>
                                    <ul class="mb-0 mt-2 ps-3">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show rounded-3 border-0" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-circle-check fs-5 me-2"></i>
                                <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['info'])): ?>
                        <div class="alert alert-info alert-dismissible fade show rounded-3 border-0" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-circle-info fs-5 me-2"></i>
                                <span><?php echo $_SESSION['info']; unset($_SESSION['info']); ?></span>
                                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Status Information for Pending Users -->
                    <?php if (isset($_POST['username']) && !empty($errors)): ?>
                        <?php foreach ($errors as $error): ?>
                            <?php if (strpos($error, 'pending approval') !== false): ?>
                                <div class="alert alert-warning border-0 rounded-3 mb-4">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i class="fa-solid fa-clock fa-2x text-warning"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h5 class="alert-heading fw-bold">Account Pending Approval</h5>
                                            <p class="mb-0">Your organizer account is awaiting admin approval. You'll receive an email once approved. Thank you for your patience!</p>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif (strpos($error, 'suspended') !== false): ?>
                                <div class="alert alert-danger border-0 rounded-3 mb-4">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i class="fa-solid fa-ban fa-2x"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h5 class="alert-heading fw-bold">Account Suspended</h5>
                                            <p class="mb-0">Your account has been suspended. Please contact our support team for assistance.</p>
                                            <hr>
                                            <a href="#" class="text-danger fw-bold" data-bs-toggle="modal" data-bs-target="#supportModal">
                                                <i class="fa-regular fa-envelope me-1"></i>Contact Support
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form method="post" action="" class="needs-validation" novalidate>
                        <!-- Username/Email Field -->
                        <div class="form-group mb-4">
                            <label for="username" class="form-label fw-semibold">
                                <i class="fa-regular fa-user text-green me-1"></i>
                                Username or Email
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 rounded-start-3">
                                    <i class="fa-regular fa-user text-green"></i>
                                </span>
                                <input type="text" 
                                       class="form-control form-control-lg rounded-end-3" 
                                       id="username" 
                                       name="username" 
                                       placeholder="Enter your username or email"
                                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" 
                                       required>
                            </div>
                            <div class="form-text">
                                <i class="fa-regular fa-circle-question me-1 text-green"></i>
                                Use your username or email address
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="form-group mb-4">
                            <label for="password" class="form-label fw-semibold">
                                <i class="fa-solid fa-lock text-green me-1"></i>
                                Password
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 rounded-start-3">
                                    <i class="fa-solid fa-lock text-green"></i>
                                </span>
                                <input type="password" 
                                       class="form-control form-control-lg rounded-0 border-end-0" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Enter your password"
                                       required>
                                <button class="btn btn-outline-green rounded-end-3" type="button" id="togglePassword">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="remember">
                                    <i class="fa-regular fa-circle-check me-1 text-green"></i>
                                    Remember me
                                </label>
                            </div>
                            <a href="#" class="text-decoration-none green-link" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                                <i class="fa-regular fa-circle-question me-1"></i>
                                Forgot password?
                            </a>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-green btn-lg rounded-pill py-3" id="loginBtn">
                                <i class="fa-solid fa-right-to-bracket me-2"></i>
                                Login to Account
                            </button>
                        </div>

                        <!-- Register Link -->
                        <div class="text-center mt-4">
                            <p class="text-muted mb-0">
                                Don't have an account? 
                                <a href="register.php" class="text-green fw-semibold text-decoration-none">
                                    Create account <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
                
                <!-- Card Footer with Status Info -->
                <div class="card-footer bg-light border-0 p-4">
                    <div class="text-center mb-3">
                        <span class="text-muted small">Account Status Guide</span>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <span class="badge bg-green p-2">
                            <i class="fa-regular fa-circle-check me-1"></i>Active
                        </span>
                        <span class="badge bg-warning text-dark p-2">
                            <i class="fa-regular fa-clock me-1"></i>Pending
                        </span>
                        <span class="badge bg-danger p-2">
                            <i class="fa-solid fa-ban me-1"></i>Suspended
                        </span>
                    </div>
                    <p class="small text-muted text-center mt-3 mb-0">
                        <i class="fa-regular fa-shield me-1 text-green"></i>
                        Your information is secure and encrypted
                    </p>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="row g-3 mt-4">
                <div class="col-4">
                    <div class="text-center">
                        <div class="feature-icon-small mx-auto mb-2" style="background: rgba(167, 224, 181, 0.2);">
                            <i class="fa-solid fa-shield text-green"></i>
                        </div>
                        <small class="fw-semibold">Secure Login</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="text-center">
                        <div class="feature-icon-small mx-auto mb-2" style="background: rgba(167, 224, 181, 0.2);">
                            <i class="fa-solid fa-lock text-green"></i>
                        </div>
                        <small class="fw-semibold">Encrypted</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="text-center">
                        <div class="feature-icon-small mx-auto mb-2" style="background: rgba(167, 224, 181, 0.2);">
                            <i class="fa-solid fa-clock text-green"></i>
                        </div>
                        <small class="fw-semibold">24/7 Access</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1a472a, #2d6a4f); color: white;">
                <h5 class="modal-title" id="forgotPasswordModalLabel"><i class="fa-solid fa-key me-2"></i>Reset Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Enter your email address and we'll send you a link to reset your password.</p>
                <div class="form-group mb-3">
                    <label for="resetEmail" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="resetEmail" placeholder="name@example.com">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-green" onclick="alert('Password reset feature coming soon!')">
                    Send Reset Link
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Support Contact Modal (for suspended users) -->
<div class="modal fade" id="supportModal" tabindex="-1" aria-labelledby="supportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1a472a, #2d6a4f); color: white;">
                <h5 class="modal-title" id="supportModalLabel"><i class="fa-solid fa-headset me-2"></i>Contact Support</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">If your account has been suspended and you believe this is an error, please contact our support team:</p>
                
                <div class="bg-light p-3 rounded-3 mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fa-regular fa-envelope text-green me-2"></i>
                        <strong>Email:</strong>
                    </div>
                    <a href="mailto:support@crowdfunding.com" class="text-green">support@crowdfunding.com</a>
                </div>
                
                <div class="bg-light p-3 rounded-3">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fa-regular fa-clock text-green me-2"></i>
                        <strong>Response Time:</strong>
                    </div>
                    <p class="mb-0">We typically respond within 24-48 hours</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="mailto:support@crowdfunding.com" class="btn btn-green">
                    <i class="fa-regular fa-envelope me-2"></i>Send Email
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom Styles for Login Page - Dark Green Theme */

.text-green {
    color: #2d6a4f !important;
}

.bg-green {
    background: #2d6a4f !important;
}

.btn-green {
    background: linear-gradient(135deg, #1a472a, #2d6a4f, #40916c);
    border: none;
    color: white;
    transition: all 0.3s ease;
}

.btn-green:hover {
    background: linear-gradient(135deg, #2d6a4f, #40916c, #74c69d);
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(46, 125, 50, 0.4);
}

.btn-outline-green {
    border: 2px solid #a7e0b5;
    background: transparent;
    color: #2d6a4f;
    transition: all 0.3s ease;
}

.btn-outline-green:hover {
    background: #a7e0b5;
    color: #1a472a;
    transform: translateY(-2px);
}

.green-link {
    color: #2d6a4f;
    transition: all 0.3s ease;
}

.green-link:hover {
    color: #74c69d;
    transform: translateX(3px);
}

.featured-icon {
    width: 70px;
    height: 70px;
    background: rgba(167, 224, 181, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    backdrop-filter: blur(5px);
    border: 2px solid rgba(167, 224, 181, 0.3);
}

.form-control-lg {
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
    font-size: 1rem;
    padding: 0.75rem 1rem;
}

.form-control-lg:focus {
    border-color: #74c69d;
    box-shadow: 0 0 0 3px rgba(116, 198, 157, 0.2);
}

.input-group-text {
    padding: 0.75rem 1rem;
    background: #f8f9fa;
}

.feature-icon-small {
    width: 40px;
    height: 40px;
    background: rgba(167, 224, 181, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    transition: all 0.3s ease;
}

.feature-icon-small:hover {
    transform: scale(1.1);
    background: rgba(167, 224, 181, 0.3);
}

.card {
    transition: all 0.3s ease;
    border: none;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 30px 60px rgba(26, 71, 42, 0.15) !important;
}

.card-header {
    border-bottom: 3px solid #74c69d !important;
}

/* Alert Styles */
.alert-danger {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    border: none;
    color: #721c24;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    border: none;
    color: #155724;
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd, #ffe69c);
    border: none;
    color: #856404;
}

.alert-info {
    background: linear-gradient(135deg, #d1e7dd, #b8dfd0);
    border: none;
    color: #0f5132;
}

/* Status badges */
.badge {
    padding: 0.5rem 1rem;
    font-weight: 500;
    border-radius: 50px;
}

.bg-green {
    background: linear-gradient(135deg, #1a472a, #2d6a4f) !important;
    color: white;
}

/* Checkbox styling */
.form-check-input:checked {
    background-color: #2d6a4f;
    border-color: #2d6a4f;
}

/* Dark Mode Adjustments */
body.dark-mode .card {
    background: #1e1e1e;
}

body.dark-mode .card-header {
    background: linear-gradient(135deg, #0f2a1a, #1e4a33, #2d6a4f) !important;
    border-bottom: 3px solid #52b788 !important;
}

body.dark-mode .card-footer {
    background: #2d2d2d !important;
    border-color: #333 !important;
}

body.dark-mode .form-control-lg,
body.dark-mode .input-group-text {
    background: #2d2d2d;
    border-color: #444;
    color: #eee;
}

body.dark-mode .form-control-lg:focus {
    background: #2d2d2d;
    border-color: #74c69d;
    color: #eee;
}

body.dark-mode .text-muted {
    color: #aaa !important;
}

body.dark-mode .bg-light {
    background: #2d2d2d !important;
}

body.dark-mode .btn-outline-green {
    border-color: #a7e0b5;
    color: #a7e0b5;
}

body.dark-mode .btn-outline-green:hover {
    background: #a7e0b5;
    color: #1a472a;
}

body.dark-mode .text-green {
    color: #a7e0b5 !important;
}

body.dark-mode .green-link {
    color: #a7e0b5;
}

body.dark-mode .green-link:hover {
    color: #74c69d;
}

body.dark-mode .feature-icon-small {
    background: rgba(167, 224, 181, 0.1);
}

/* Responsive */
@media (max-width: 576px) {
    .card-body {
        padding: 1.5rem !important;
    }
    
    .featured-icon {
        width: 60px;
        height: 60px;
    }
    
    .featured-icon i {
        font-size: 1.5rem;
    }
    
    .btn-lg {
        padding: 0.75rem 1rem !important;
        font-size: 1rem;
    }
    
    .d-flex.flex-wrap {
        flex-direction: column;
        gap: 0.5rem;
    }
}

/* Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.6s ease;
}

/* Password toggle button */
#togglePassword {
    border-color: #e0e0e0;
    background: transparent;
    transition: all 0.3s ease;
}

#togglePassword:hover {
    background: rgba(167, 224, 181, 0.1);
    border-color: #74c69d;
}

body.dark-mode #togglePassword {
    border-color: #444;
    color: #ddd;
}

body.dark-mode #togglePassword:hover {
    background: #3d3d3d;
    border-color: #74c69d;
}

/* Modal styles */
.modal-content {
    border: none;
    border-radius: 16px;
    overflow: hidden;
}

.modal-header {
    border-bottom: 2px solid #74c69d;
}

.modal-footer {
    border-top: 1px solid rgba(167, 224, 181, 0.3);
}

.btn-close-white {
    filter: brightness(0) invert(1);
}
</style>

<script>
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Form validation
(function() {
    'use strict';
    
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();

// Remember me checkbox animation
document.getElementById('remember')?.addEventListener('change', function() {
    const label = this.nextElementSibling;
    if (this.checked) {
        label.classList.add('text-green');
    } else {
        label.classList.remove('text-green');
    }
});

// Auto-focus username field
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('username').focus();
});

// Prevent multiple form submissions
document.getElementById('loginBtn')?.addEventListener('click', function() {
    if (this.form.checkValidity()) {
        this.disabled = true;
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Logging in...';
        this.form.submit();
    }
});
</script>

<?php require_once 'footer.php'; ?>
<?php
require_once 'header.php';

// Password strength requirements
$password_requirements = [
    'min_length' => 8,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_number' => true,
    'require_special' => false
];

// Countries list for phone code
$countries = [
    'BD' => ['name' => 'Bangladesh', 'code' => '+880', 'pattern' => '[0-9]{10}'],
    'US' => ['name' => 'United States', 'code' => '+1', 'pattern' => '[0-9]{10}'],
    'UK' => ['name' => 'United Kingdom', 'code' => '+44', 'pattern' => '[0-9]{10}'],
    'IN' => ['name' => 'India', 'code' => '+91', 'pattern' => '[0-9]{10}'],
    'PK' => ['name' => 'Pakistan', 'code' => '+92', 'pattern' => '[0-9]{10}'],
    'NP' => ['name' => 'Nepal', 'code' => '+977', 'pattern' => '[0-9]{10}'],
    'LK' => ['name' => 'Sri Lanka', 'code' => '+94', 'pattern' => '[0-9]{9}'],
    'MY' => ['name' => 'Malaysia', 'code' => '+60', 'pattern' => '[0-9]{9,10}'],
    'SG' => ['name' => 'Singapore', 'code' => '+65', 'pattern' => '[0-9]{8}'],
    'AE' => ['name' => 'UAE', 'code' => '+971', 'pattern' => '[0-9]{9}']
];

// NID formats by country
$nid_formats = [
    'BD' => ['name' => 'Bangladesh NID', 'pattern' => '[0-9]{10,17}', 'example' => '1234567890'],
    'US' => ['name' => 'SSN', 'pattern' => '[0-9]{3}-[0-9]{2}-[0-9]{4}', 'example' => '123-45-6789'],
    'UK' => ['name' => 'National Insurance', 'pattern' => '[A-Z]{2}[0-9]{6}[A-Z]', 'example' => 'AB123456C'],
    'IN' => ['name' => 'Aadhaar', 'pattern' => '[0-9]{12}', 'example' => '123456789012'],
    'PK' => ['name' => 'CNIC', 'pattern' => '[0-9]{5}-[0-9]{7}-[0-9]', 'example' => '12345-6789012-3']
];

// Experience levels
$experience_levels = [
    'beginner' => 'Beginner (0-1 years)',
    'intermediate' => 'Intermediate (2-5 years)',
    'expert' => 'Expert (5+ years)'
];

// Referral sources
$referral_sources = [
    '' => 'Select an option',
    'social_media' => 'Social Media',
    'friend' => 'Friend/Colleague',
    'search_engine' => 'Search Engine',
    'advertisement' => 'Advertisement',
    'email' => 'Email Newsletter',
    'other' => 'Other'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = isset($_POST['role']) ? $_POST['role'] : 'donor';
    $terms = isset($_POST['terms']) ? true : false;
    
    // Organizer verification fields
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $country = isset($_POST['country']) ? $_POST['country'] : 'BD';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $full_phone = isset($_POST['full_phone']) ? $_POST['full_phone'] : '';
    $nid_type = isset($_POST['nid_type']) ? $_POST['nid_type'] : 'BD';
    $nid_number = isset($_POST['nid_number']) ? trim($_POST['nid_number']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    $state = isset($_POST['state']) ? trim($_POST['state']) : '';
    $postal_code = isset($_POST['postal_code']) ? trim($_POST['postal_code']) : '';
    $dob = isset($_POST['dob']) ? $_POST['dob'] : '';
    $occupation = isset($_POST['occupation']) ? trim($_POST['occupation']) : '';
    $experience = isset($_POST['experience']) ? $_POST['experience'] : '';
    $facebook = isset($_POST['facebook']) ? trim($_POST['facebook']) : '';
    $twitter = isset($_POST['twitter']) ? trim($_POST['twitter']) : '';
    $linkedin = isset($_POST['linkedin']) ? trim($_POST['linkedin']) : '';
    $website = isset($_POST['website']) ? trim($_POST['website']) : '';
    $referral_source = isset($_POST['referral_source']) ? $_POST['referral_source'] : '';

    $errors = [];

    // Username validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long";
    } elseif (strlen($username) > 20) {
        $errors[] = "Username cannot exceed 20 characters";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores";
    }

    // Email validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required";
    } else {
        $password_errors = [];
        if (strlen($password) < $password_requirements['min_length']) {
            $password_errors[] = "at least {$password_requirements['min_length']} characters";
        }
        if ($password_requirements['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $password_errors[] = "one uppercase letter";
        }
        if ($password_requirements['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $password_errors[] = "one lowercase letter";
        }
        if ($password_requirements['require_number'] && !preg_match('/[0-9]/', $password)) {
            $password_errors[] = "one number";
        }
        
        if (!empty($password_errors)) {
            $errors[] = "Password must contain: " . implode(', ', $password_errors);
        }
    }

    // Confirm password
    if ($password != $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Role validation
    $allowed_roles = ['donor', 'organizer', 'both'];
    if (!in_array($role, $allowed_roles)) {
        $errors[] = "Invalid role selected";
    }

    // Terms agreement
    if (!$terms) {
        $errors[] = "You must agree to the Terms & Conditions";
    }

    // Additional validation for organizer/both roles
    if ($role == 'organizer' || $role == 'both') {
        if (empty($full_name)) {
            $errors[] = "Full name is required for organizer verification";
        }
        
        if (empty($full_phone)) {
            $errors[] = "Phone number is required for organizer verification";
        } elseif (!preg_match('/^[0-9+\-\s]{10,15}$/', $full_phone)) {
            $errors[] = "Please enter a valid phone number";
        }
        
        if (empty($nid_number)) {
            $errors[] = "ID number is required for organizer verification";
        }
        
        if (empty($address)) {
            $errors[] = "Address is required for organizer verification";
        }
        
        if (empty($city)) {
            $errors[] = "City is required";
        }
        
        if (empty($postal_code)) {
            $errors[] = "Postal code is required";
        }
        
        if (empty($dob)) {
            $errors[] = "Date of birth is required for organizer verification";
        } else {
            $age = date_diff(date_create($dob), date_create('today'))->y;
            if ($age < 18) {
                $errors[] = "You must be at least 18 years old to become an organizer";
            }
        }

        // Handle NID image upload
        if (isset($_FILES['nid_image']) && $_FILES['nid_image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['nid_image']['type'], $allowed_types)) {
                $errors[] = "ID image must be JPG, PNG, or GIF format";
            }
            if ($_FILES['nid_image']['size'] > $max_size) {
                $errors[] = "ID image size must be less than 5MB";
            }
        } else {
            $errors[] = "Please upload a clear photo of your ID";
        }

        // Handle profile/selfie image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                $errors[] = "Profile photo must be JPG or PNG format";
            }
            if ($_FILES['profile_image']['size'] > $max_size) {
                $errors[] = "Profile photo size must be less than 5MB";
            }
        } else {
            $errors[] = "Please upload a clear profile photo/selfie";
        }
    }

    if (empty($errors)) {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $errors[] = "Username or email already taken";
            } else {
                // Determine user type and status based on role
                $user_type = $role;
                
                if ($role == 'organizer' || $role == 'both') {
                    $status = 'pending';
                } else {
                    $status = 'active';
                }
                
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                
                // Upload images if organizer/both
                $nid_image_path = null;
                $profile_image_path = null;
                
                if ($role == 'organizer' || $role == 'both') {
                    // Create upload directory if not exists
                    $upload_dir = '../uploads/verification/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Upload NID image
                    $nid_ext = pathinfo($_FILES['nid_image']['name'], PATHINFO_EXTENSION);
                    $nid_filename = 'nid_' . time() . '_' . uniqid() . '.' . $nid_ext;
                    $nid_image_path = 'uploads/verification/' . $nid_filename;
                    move_uploaded_file($_FILES['nid_image']['tmp_name'], '../' . $nid_image_path);
                    
                    // Upload profile image
                    $profile_ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                    $profile_filename = 'profile_' . time() . '_' . uniqid() . '.' . $profile_ext;
                    $profile_image_path = 'uploads/verification/' . $profile_filename;
                    move_uploaded_file($_FILES['profile_image']['tmp_name'], '../' . $profile_image_path);
                }
                
                // Insert user with all organizer fields
                $stmt = $pdo->prepare("INSERT INTO users (
                    username, email, password, user_type, status, 
                    full_name, country, phone, nid_type, nid_number, nid_image, profile_image, 
                    address, city, state, postal_code, dob, occupation, experience,
                    facebook, twitter, linkedin, website, referral_source, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                
                if ($stmt->execute([
                    $username, $email, $hashed, $user_type, $status,
                    $full_name, $country, $full_phone, $nid_type, $nid_number, $nid_image_path, $profile_image_path,
                    $address, $city, $state, $postal_code, $dob, $occupation, $experience,
                    $facebook, $twitter, $linkedin, $website, $referral_source
                ])) {
                    $user_id = $pdo->lastInsertId();
                    
                    if ($status == 'pending') {
                        $_SESSION['success'] = "Registration successful! Your organizer account has been submitted for verification. We'll review your documents and notify you via email within 2-3 business days.";
                    } else {
                        $_SESSION['success'] = "Registration successful! Please login to continue.";
                    }
                    
                    redirect('login.php');
                } else {
                    $errors[] = "Registration failed, please try again";
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '42S22') {
                $errors[] = "Database setup required. Please run the following SQL:<br>
                <small class='text-muted'>
                ALTER TABLE users ADD COLUMN user_type ENUM('donor', 'organizer', 'both') DEFAULT 'donor';<br>
                ALTER TABLE users ADD COLUMN status ENUM('active', 'pending', 'suspended') DEFAULT 'active';<br>
                ALTER TABLE users ADD COLUMN full_name VARCHAR(100) NULL;<br>
                ALTER TABLE users ADD COLUMN country VARCHAR(10) NULL;<br>
                ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL;<br>
                ALTER TABLE users ADD COLUMN nid_type VARCHAR(10) NULL;<br>
                ALTER TABLE users ADD COLUMN nid_number VARCHAR(50) NULL;<br>
                ALTER TABLE users ADD COLUMN nid_image VARCHAR(255) NULL;<br>
                ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL;<br>
                ALTER TABLE users ADD COLUMN address TEXT NULL;<br>
                ALTER TABLE users ADD COLUMN city VARCHAR(100) NULL;<br>
                ALTER TABLE users ADD COLUMN state VARCHAR(100) NULL;<br>
                ALTER TABLE users ADD COLUMN postal_code VARCHAR(20) NULL;<br>
                ALTER TABLE users ADD COLUMN dob DATE NULL;<br>
                ALTER TABLE users ADD COLUMN occupation VARCHAR(100) NULL;<br>
                ALTER TABLE users ADD COLUMN experience VARCHAR(50) NULL;<br>
                ALTER TABLE users ADD COLUMN facebook VARCHAR(255) NULL;<br>
                ALTER TABLE users ADD COLUMN twitter VARCHAR(255) NULL;<br>
                ALTER TABLE users ADD COLUMN linkedin VARCHAR(255) NULL;<br>
                ALTER TABLE users ADD COLUMN website VARCHAR(255) NULL;<br>
                ALTER TABLE users ADD COLUMN referral_source VARCHAR(50) NULL;</small>";
            } else {
                $errors[] = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-11 col-lg-10 col-xl-9">
            <!-- Register Card -->
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <!-- Card Header -->
                <div class="card-header text-white text-center py-4 border-0" style="background: linear-gradient(135deg, #1a472a 0%, #2d6a4f 50%, #40916c 100%); border-bottom: 3px solid #74c69d;">
                    <div class="featured-icon mx-auto mb-3">
                        <i class="fa-solid fa-seedling fa-2x"></i>
                    </div>
                    <h2 class="h3 fw-bold mb-1">Create Account</h2>
                    <p class="text-white-50 mb-0">Join our crowdfunding community today</p>
                </div>
                
                <!-- Card Body -->
                <div class="card-body p-4 p-lg-5">
                    <!-- Progress Bar -->
                    <div class="registration-progress mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-green fw-semibold">Registration Progress</span>
                            <span class="text-green fw-semibold" id="progressPercentage">0%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-green" id="progressBar" role="progressbar" style="width: 0%;"></div>
                        </div>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0" role="alert">
                            <div class="d-flex align-items-start">
                                <i class="fa-solid fa-circle-exclamation fs-5 me-2 mt-1"></i>
                                <div>
                                    <strong>Please fix the following errors:</strong>
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

                    <!-- Registration Form -->
                    <form method="post" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <!-- Role Selection Tabs -->
                        <div class="role-tabs mb-4">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="role-tab <?php echo (!isset($role) || $role == 'donor') ? 'active' : ''; ?>" onclick="selectRole('donor')">
                                        <input type="radio" name="role" id="role_donor" value="donor" class="d-none" <?php echo (!isset($role) || $role == 'donor') ? 'checked' : ''; ?>>
                                        <div class="tab-content text-center p-3">
                                            <i class="fa-solid fa-hand-holding-heart fa-2x mb-2"></i>
                                            <h6 class="fw-bold mb-1">Donor Only</h6>
                                            <small class="text-muted">Support campaigns</small>
                                            <div class="mt-2">
                                                <span class="badge bg-green">Instant Access</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="role-tab <?php echo (isset($role) && $role == 'organizer') ? 'active' : ''; ?>" onclick="selectRole('organizer')">
                                        <input type="radio" name="role" id="role_organizer" value="organizer" class="d-none" <?php echo (isset($role) && $role == 'organizer') ? 'checked' : ''; ?>>
                                        <div class="tab-content text-center p-3">
                                            <i class="fa-solid fa-rocket fa-2x mb-2"></i>
                                            <h6 class="fw-bold mb-1">Organizer Only</h6>
                                            <small class="text-muted">Create campaigns</small>
                                            <div class="mt-2">
                                                <span class="badge bg-warning text-dark">Needs Verification</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="role-tab <?php echo (isset($role) && $role == 'both') ? 'active' : ''; ?>" onclick="selectRole('both')">
                                        <input type="radio" name="role" id="role_both" value="both" class="d-none" <?php echo (isset($role) && $role == 'both') ? 'checked' : ''; ?>>
                                        <div class="tab-content text-center p-3">
                                            <i class="fa-solid fa-users fa-2x mb-2"></i>
                                            <h6 class="fw-bold mb-1">Both</h6>
                                            <small class="text-muted">Support & Create</small>
                                            <div class="mt-2">
                                                <span class="badge bg-green mb-1">Donor: Instant</span>
                                                <span class="badge bg-warning text-dark">Organizer: Verification</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Information Section -->
                        <div class="form-section mb-4">
                            <h5 class="text-green fw-bold mb-3">
                                <i class="fa-solid fa-user me-2"></i>
                                Basic Information
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="username" class="form-label fw-semibold">
                                            <i class="fa-regular fa-user text-green me-1"></i>
                                            Username <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control form-control-lg" 
                                               id="username" 
                                               name="username" 
                                               placeholder="Choose a unique username"
                                               value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" 
                                               pattern="[a-zA-Z0-9_]{3,20}"
                                               required>
                                        <div class="form-text">
                                            <i class="fa-regular fa-circle-check text-green me-1"></i>
                                            3-20 characters, letters, numbers, and underscore only
                                        </div>
                                        <div class="username-availability small mt-1"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="email" class="form-label fw-semibold">
                                            <i class="fa-regular fa-envelope text-green me-1"></i>
                                            Email Address <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" 
                                               class="form-control form-control-lg" 
                                               id="email" 
                                               name="email" 
                                               placeholder="name@example.com"
                                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" 
                                               required>
                                        <div class="form-text">
                                            <i class="fa-regular fa-shield text-green me-1"></i>
                                            We'll never share your email
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Organizer Verification Section -->
                        <div id="organizerFields" style="display: <?php echo ($role == 'organizer' || $role == 'both') ? 'block' : 'none'; ?>;">
                            <div class="form-section mb-4 p-4 rounded-3" style="background: rgba(167, 224, 181, 0.1); border: 2px solid #a7e0b5;">
                                <h5 class="text-green fw-bold mb-3">
                                    <i class="fa-solid fa-shield me-2"></i>
                                    Organizer Verification
                                </h5>
                                <p class="small text-muted mb-4">
                                    <i class="fa-solid fa-info-circle me-1"></i>
                                    Please provide accurate information. Your documents will be reviewed within 2-3 business days.
                                </p>

                                <!-- Personal Information -->
                                <h6 class="text-green mb-3">Personal Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="full_name" class="form-label fw-semibold">
                                                <i class="fa-regular fa-id-card text-green me-1"></i>
                                                Full Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="full_name" 
                                                   name="full_name" 
                                                   placeholder="As shown on your ID"
                                                   value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="dob" class="form-label fw-semibold">
                                                <i class="fa-solid fa-cake-candles text-green me-1"></i>
                                                Date of Birth <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="dob" 
                                                   name="dob" 
                                                   value="<?php echo isset($dob) ? $dob : ''; ?>"
                                                   max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                                            <div class="form-text">You must be at least 18 years old</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact Information -->
                                <h6 class="text-green mb-3 mt-4">Contact Information</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="country" class="form-label fw-semibold">
                                                <i class="fa-solid fa-globe text-green me-1"></i>
                                                Country
                                            </label>
                                            <select class="form-select" id="country" name="country">
                                                <?php foreach ($countries as $code => $country_data): ?>
                                                    <option value="<?php echo $code; ?>" 
                                                            data-code="<?php echo $country_data['code']; ?>"
                                                            <?php echo (isset($country) && $country == $code) ? 'selected' : ''; ?>>
                                                        <?php echo $country_data['name']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group mb-3">
                                            <label for="phone" class="form-label fw-semibold">
                                                <i class="fa-solid fa-phone text-green me-1"></i>
                                                Phone Number <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light" id="countryCode">+880</span>
                                                <input type="tel" 
                                                       class="form-control" 
                                                       id="phone" 
                                                       name="phone" 
                                                       placeholder="Enter phone number"
                                                       value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                                                <input type="hidden" name="full_phone" id="full_phone">
                                            </div>
                                            <div class="form-text" id="phoneFormat">Format: 10 digits after country code</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Identity Verification -->
                                <h6 class="text-green mb-3 mt-4">Identity Verification</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="nid_type" class="form-label fw-semibold">
                                                <i class="fa-solid fa-passport text-green me-1"></i>
                                                ID Type
                                            </label>
                                            <select class="form-select" id="nid_type" name="nid_type">
                                                <?php foreach ($nid_formats as $code => $format): ?>
                                                    <option value="<?php echo $code; ?>" 
                                                            data-pattern="<?php echo $format['pattern']; ?>"
                                                            data-example="<?php echo $format['example']; ?>"
                                                            <?php echo (isset($nid_type) && $nid_type == $code) ? 'selected' : ''; ?>>
                                                        <?php echo $format['name']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group mb-3">
                                            <label for="nid_number" class="form-label fw-semibold">
                                                <i class="fa-solid fa-qrcode text-green me-1"></i>
                                                ID Number <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="nid_number" 
                                                   name="nid_number" 
                                                   placeholder="Enter your ID number"
                                                   value="<?php echo isset($nid_number) ? htmlspecialchars($nid_number) : ''; ?>">
                                            <div class="form-text" id="nidFormat">Example: 1234567890</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Document Upload -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="nid_image" class="form-label fw-semibold">
                                                <i class="fa-solid fa-image text-green me-1"></i>
                                                Upload ID (Front & Back) <span class="text-danger">*</span>
                                            </label>
                                            <div class="upload-area p-3 text-center rounded-3 position-relative" style="border: 2px dashed #a7e0b5;">
                                                <i class="fa-solid fa-cloud-upload-alt fa-2x text-green mb-2"></i>
                                                <p class="mb-1">Click or drag to upload</p>
                                                <small class="text-muted">JPG, PNG, GIF up to 5MB</small>
                                                <input type="file" 
                                                       class="position-absolute top-0 start-0 w-100 h-100 opacity-0" 
                                                       id="nid_image" 
                                                       name="nid_image" 
                                                       accept="image/jpeg,image/png,image/gif"
                                                       style="cursor: pointer;">
                                            </div>
                                            <div class="file-info small mt-1" id="nid_image_info"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="profile_image" class="form-label fw-semibold">
                                                <i class="fa-solid fa-camera text-green me-1"></i>
                                                Profile Photo / Selfie <span class="text-danger">*</span>
                                            </label>
                                            <div class="upload-area p-3 text-center rounded-3 position-relative" style="border: 2px dashed #a7e0b5;">
                                                <i class="fa-solid fa-camera fa-2x text-green mb-2"></i>
                                                <p class="mb-1">Upload your photo</p>
                                                <small class="text-muted">JPG, PNG up to 5MB</small>
                                                <input type="file" 
                                                       class="position-absolute top-0 start-0 w-100 h-100 opacity-0" 
                                                       id="profile_image" 
                                                       name="profile_image" 
                                                       accept="image/jpeg,image/png"
                                                       style="cursor: pointer;">
                                            </div>
                                            <div class="file-info small mt-1" id="profile_image_info"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Address Information -->
                                <h6 class="text-green mb-3 mt-4">Address Information</h6>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group mb-3">
                                            <label for="address" class="form-label fw-semibold">
                                                <i class="fa-solid fa-location-dot text-green me-1"></i>
                                                Street Address <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="address" 
                                                   name="address" 
                                                   placeholder="House, Road, Area"
                                                   value="<?php echo isset($address) ? htmlspecialchars($address) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group mb-3">
                                            <label for="city" class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="city" name="city" value="<?php echo isset($city) ? htmlspecialchars($city) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="state" class="form-label fw-semibold">State/Province</label>
                                            <input type="text" class="form-control" id="state" name="state" value="<?php echo isset($state) ? htmlspecialchars($state) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-3">
                                            <label for="postal_code" class="form-label fw-semibold">Postal Code <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo isset($postal_code) ? htmlspecialchars($postal_code) : ''; ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Professional Information -->
                                <h6 class="text-green mb-3 mt-4">Professional Information</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="occupation" class="form-label fw-semibold">
                                                <i class="fa-solid fa-briefcase text-green me-1"></i>
                                                Occupation
                                            </label>
                                            <input type="text" class="form-control" id="occupation" name="occupation" 
                                                   placeholder="e.g., Student, Business, Freelancer"
                                                   value="<?php echo isset($occupation) ? htmlspecialchars($occupation) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="experience" class="form-label fw-semibold">
                                                <i class="fa-solid fa-chart-line text-green me-1"></i>
                                                Experience Level
                                            </label>
                                            <select class="form-select" id="experience" name="experience">
                                                <option value="">Select experience level</option>
                                                <?php foreach ($experience_levels as $value => $label): ?>
                                                    <option value="<?php echo $value; ?>" <?php echo (isset($experience) && $experience == $value) ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Social Media (Optional) -->
                                <h6 class="text-green mb-3 mt-4">Social Media (Optional)</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="facebook" class="form-label">
                                                <i class="fab fa-facebook text-primary me-1"></i>
                                                Facebook
                                            </label>
                                            <input type="url" class="form-control" id="facebook" name="facebook" 
                                                   placeholder="https://facebook.com/username"
                                                   value="<?php echo isset($facebook) ? htmlspecialchars($facebook) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="twitter" class="form-label">
                                                <i class="fab fa-twitter text-info me-1"></i>
                                                Twitter
                                            </label>
                                            <input type="url" class="form-control" id="twitter" name="twitter" 
                                                   placeholder="https://twitter.com/username"
                                                   value="<?php echo isset($twitter) ? htmlspecialchars($twitter) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="linkedin" class="form-label">
                                                <i class="fab fa-linkedin text-primary me-1"></i>
                                                LinkedIn
                                            </label>
                                            <input type="url" class="form-control" id="linkedin" name="linkedin" 
                                                   placeholder="https://linkedin.com/in/username"
                                                   value="<?php echo isset($linkedin) ? htmlspecialchars($linkedin) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="website" class="form-label">
                                                <i class="fa-solid fa-globe text-green me-1"></i>
                                                Personal Website
                                            </label>
                                            <input type="url" class="form-control" id="website" name="website" 
                                                   placeholder="https://yourwebsite.com"
                                                   value="<?php echo isset($website) ? htmlspecialchars($website) : ''; ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Referral -->
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="form-group mb-3">
                                            <label for="referral_source" class="form-label">
                                                <i class="fa-solid fa-gift text-green me-1"></i>
                                                How did you hear about us?
                                            </label>
                                            <select class="form-select" id="referral_source" name="referral_source">
                                                <?php foreach ($referral_sources as $value => $label): ?>
                                                    <option value="<?php echo $value; ?>" <?php echo (isset($referral_source) && $referral_source == $value) ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-3">
                                    <i class="fa-solid fa-clock me-2"></i>
                                    <strong>Verification Time:</strong> 2-3 business days. You'll receive an email once approved.
                                </div>
                            </div>
                        </div>

                        <!-- Password Section -->
                        <div class="form-section mb-4">
                            <h5 class="text-green fw-bold mb-3">
                                <i class="fa-solid fa-lock me-2"></i>
                                Security
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="password" class="form-label fw-semibold">
                                            <i class="fa-solid fa-lock text-green me-1"></i>
                                            Password <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="fa-solid fa-lock text-green"></i>
                                            </span>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="password" 
                                                   name="password" 
                                                   placeholder="Create a strong password"
                                                   required>
                                            <button class="btn btn-outline-green" type="button" id="togglePassword">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="confirm_password" class="form-label fw-semibold">
                                            <i class="fa-solid fa-lock text-green me-1"></i>
                                            Confirm Password <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="fa-solid fa-lock text-green"></i>
                                            </span>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="confirm_password" 
                                                   name="confirm_password" 
                                                   placeholder="Re-enter your password"
                                                   required>
                                            <button class="btn btn-outline-green" type="button" id="toggleConfirmPassword">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Password Strength Meter -->
                            <div class="password-strength mt-2">
                                <div class="progress mb-2" style="height: 5px;">
                                    <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%;"></div>
                                </div>
                                <div class="d-flex flex-wrap gap-3 small" id="passwordRequirements">
                                    <span class="req-item" data-requirement="length">
                                        <i class="fa-regular fa-circle me-1"></i> 8+ characters
                                    </span>
                                    <span class="req-item" data-requirement="uppercase">
                                        <i class="fa-regular fa-circle me-1"></i> Uppercase
                                    </span>
                                    <span class="req-item" data-requirement="lowercase">
                                        <i class="fa-regular fa-circle me-1"></i> Lowercase
                                    </span>
                                    <span class="req-item" data-requirement="number">
                                        <i class="fa-regular fa-circle me-1"></i> Number
                                    </span>
                                </div>
                            </div>
                            <div class="password-match-feedback mt-2 small"></div>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="form-group mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" class="text-green" data-bs-toggle="modal" data-bs-target="#termsModal">Terms & Conditions</a> and 
                                    <a href="#" class="text-green" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                                    <span class="text-danger">*</span>
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-green btn-lg rounded-pill py-3" id="registerBtn">
                                <i class="fa-solid fa-user-plus me-2"></i>
                                Create Account
                            </button>
                        </div>

                        <!-- Login Link -->
                        <div class="text-center mt-4">
                            <p class="text-muted mb-0">
                                Already have an account? 
                                <a href="login.php" class="text-green fw-semibold text-decoration-none">
                                    Login here <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Keep your existing modals (Terms & Privacy) -->

<style>
/* Enhanced Styles */
.role-tab {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    border-radius: 12px;
    background: #f8f9fa;
    height: 100%;
}

.role-tab:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(46, 125, 50, 0.15);
}

.role-tab.active {
    border-color: #2d6a4f;
    background: linear-gradient(135deg, rgba(167, 224, 181, 0.1), rgba(116, 198, 157, 0.1));
}

.upload-area {
    cursor: pointer;
    transition: all 0.3s ease;
}

.upload-area:hover {
    background: rgba(167, 224, 181, 0.1);
    border-color: #2d6a4f !important;
}

.form-section {
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.registration-progress {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 50px;
}

/* Dark mode styles */
body.dark-mode .role-tab {
    background: #2d2d2d;
}

body.dark-mode .role-tab.active {
    background: linear-gradient(135deg, rgba(167, 224, 181, 0.15), rgba(116, 198, 157, 0.15));
}

body.dark-mode .registration-progress {
    background: #2d2d2d;
}

body.dark-mode .upload-area {
    background: #1e1e1e;
}

.opacity-0 {
    opacity: 0;
}
</style>

<script>
// Role selection function with animation
function selectRole(role) {
    document.getElementById('role_' + role).checked = true;
    
    document.querySelectorAll('.role-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.currentTarget.classList.add('active');
    
    const organizerFields = document.getElementById('organizerFields');
    if (role === 'organizer' || role === 'both') {
        organizerFields.style.display = 'block';
        organizerFields.style.animation = 'slideDown 0.3s ease';
        setRequired(true);
    } else {
        organizerFields.style.display = 'none';
        setRequired(false);
    }
    updateProgress();
}

// Set required fields
function setRequired(required) {
    const fields = ['full_name', 'phone', 'dob', 'nid_number', 'nid_image', 'profile_image', 'address', 'city', 'postal_code'];
    fields.forEach(field => {
        const element = document.getElementById(field);
        if (element) element.required = required;
    });
}

// Phone number handling
document.getElementById('country')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const code = selected.dataset.code;
    document.getElementById('countryCode').textContent = code;
});

document.getElementById('phone')?.addEventListener('input', function() {
    const countryCode = document.getElementById('countryCode').textContent;
    const phone = this.value;
    document.getElementById('full_phone').value = countryCode + phone;
});

// NID format handling
document.getElementById('nid_type')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const example = selected.dataset.example;
    document.getElementById('nidFormat').textContent = 'Example: ' + example;
});

// Username availability check (simulated)
let usernameTimeout;
document.getElementById('username')?.addEventListener('input', function() {
    clearTimeout(usernameTimeout);
    const username = this.value;
    const availabilityDiv = document.querySelector('.username-availability');
    
    if (username.length >= 3) {
        availabilityDiv.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking availability...';
        usernameTimeout = setTimeout(() => {
            // Simulate availability check - replace with actual AJAX call
            availabilityDiv.innerHTML = '<span class="text-success"><i class="fa-regular fa-circle-check"></i> Username available</span>';
        }, 500);
    } else {
        availabilityDiv.innerHTML = '';
    }
});

// File upload preview
document.getElementById('nid_image')?.addEventListener('change', function(e) {
    const fileInfo = document.getElementById('nid_image_info');
    if (this.files.length > 0) {
        fileInfo.innerHTML = '<i class="fa-regular fa-circle-check text-success"></i> ' + this.files[0].name;
    } else {
        fileInfo.innerHTML = '';
    }
});

document.getElementById('profile_image')?.addEventListener('change', function(e) {
    const fileInfo = document.getElementById('profile_image_info');
    if (this.files.length > 0) {
        fileInfo.innerHTML = '<i class="fa-regular fa-circle-check text-success"></i> ' + this.files[0].name;
    } else {
        fileInfo.innerHTML = '';
    }
});

// Progress calculator
function updateProgress() {
    const totalFields = document.querySelectorAll('input, select, textarea').length;
    const filledFields = Array.from(document.querySelectorAll('input, select, textarea')).filter(el => el.value && el.value !== '').length;
    const percentage = Math.round((filledFields / totalFields) * 100);
    
    const progressBar = document.getElementById('progressBar');
    const progressPercentage = document.getElementById('progressPercentage');
    
    if (progressBar && progressPercentage) {
        progressBar.style.width = percentage + '%';
        progressPercentage.textContent = percentage + '%';
    }
}

// Update progress on input
document.querySelectorAll('input, select, textarea').forEach(el => {
    el.addEventListener('input', updateProgress);
    el.addEventListener('change', updateProgress);
});

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    updateProgress();
    
    // Initialize phone
    const countrySelect = document.getElementById('country');
    if (countrySelect) {
        const selected = countrySelect.options[countrySelect.selectedIndex];
        if (selected && selected.dataset.code) {
            document.getElementById('countryCode').textContent = selected.dataset.code;
        }
    }
    
    // Initialize NID format
    const nidType = document.getElementById('nid_type');
    if (nidType) {
        const selected = nidType.options[nidType.selectedIndex];
        if (selected && selected.dataset.example) {
            document.getElementById('nidFormat').textContent = 'Example: ' + selected.dataset.example;
        }
    }
});

// Toggle password visibility
document.getElementById('togglePassword')?.addEventListener('click', function() {
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

document.getElementById('toggleConfirmPassword')?.addEventListener('click', function() {
    const password = document.getElementById('confirm_password');
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

// Password strength checker
document.getElementById('password')?.addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password)
    };
    
    let strength = 0;
    const total = Object.keys(requirements).length;
    for (let req in requirements) {
        if (requirements[req]) strength++;
    }
    const percentage = (strength / total) * 100;
    
    strengthBar.style.width = percentage + '%';
    
    if (percentage < 50) {
        strengthBar.style.background = 'linear-gradient(90deg, #dc3545, #ffc107)';
    } else if (percentage < 75) {
        strengthBar.style.background = 'linear-gradient(90deg, #ffc107, #28a745)';
    } else {
        strengthBar.style.background = 'linear-gradient(90deg, #28a745, #20c997)';
    }
    
    document.querySelectorAll('.req-item').forEach(item => {
        const req = item.dataset.requirement;
        const icon = item.querySelector('i');
        
        if (requirements[req]) {
            item.classList.add('met');
            icon.classList.remove('fa-circle');
            icon.classList.add('fa-circle-check');
        } else {
            item.classList.remove('met');
            icon.classList.remove('fa-circle-check');
            icon.classList.add('fa-circle');
        }
    });
    
    checkPasswordMatch();
});

// Password match checker
document.getElementById('confirm_password')?.addEventListener('input', checkPasswordMatch);
document.getElementById('password')?.addEventListener('input', checkPasswordMatch);

function checkPasswordMatch() {
    const password = document.getElementById('password')?.value || '';
    const confirm = document.getElementById('confirm_password')?.value || '';
    const feedback = document.querySelector('.password-match-feedback');
    const registerBtn = document.getElementById('registerBtn');
    
    if (confirm.length > 0) {
        if (password === confirm) {
            if (feedback) {
                feedback.innerHTML = '<i class="fa-regular fa-circle-check me-1"></i> Passwords match';
                feedback.className = 'password-match-feedback mt-2 small match-success';
            }
            if (registerBtn) registerBtn.disabled = false;
        } else {
            if (feedback) {
                feedback.innerHTML = '<i class="fa-regular fa-circle-exclamation me-1"></i> Passwords do not match';
                feedback.className = 'password-match-feedback mt-2 small match-error';
            }
            if (registerBtn) registerBtn.disabled = true;
        }
    } else {
        if (feedback) feedback.innerHTML = '';
        if (registerBtn) registerBtn.disabled = false;
    }
}

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
</script>

<?php require_once 'footer.php'; ?>
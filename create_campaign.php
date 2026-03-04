<?php
require_once 'header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = "Please login to create a campaign";
    redirect('login.php');
}

// Check if user has permission to create campaigns (organizer or both with active status)
$stmt = $pdo->prepare("SELECT user_type, status FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_data = $stmt->fetch();

// Check if user can create campaigns (organizer or both) AND status is active
$can_create_campaign = ($user_data['user_type'] == 'organizer' || $user_data['user_type'] == 'both') && $user_data['status'] == 'active';

if (!$can_create_campaign) {
    if ($user_data['status'] == 'pending') {
        $_SESSION['error'] = "Your organizer account is pending approval. You cannot create campaigns yet.";
    } else {
        $_SESSION['error'] = "Only organizers can create campaigns. Please register as an organizer.";
    }
    redirect('dashboard.php');
}

// Fetch all categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get user's info for personalized message
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!isLoggedIn()) {
    $_SESSION['error'] = "Please login to create a campaign";
    redirect('login.php');
}

// Fetch all categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get user's info for personalized message
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $target_amount = floatval($_POST['target_amount']);
    $category_id = intval($_POST['category_id']);
    $user_id = $_SESSION['user_id'];
    
    $errors = [];

    // Validate category
    if ($category_id <= 0) {
        $errors[] = "Please select a category";
    } else {
        // Verify category exists
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        if (!$stmt->fetch()) {
            $errors[] = "Invalid category selected";
        }
    }

    // Image upload code
    $target_dir = "uploads/campaigns/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $image_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["image"]["name"]));
    $target_file = $target_dir . $image_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // Validation
    if (empty($title)) $errors[] = "Title is required";
    if (strlen($title) < 5) $errors[] = "Title must be at least 5 characters long";
    if (strlen($title) > 100) $errors[] = "Title must be less than 100 characters";
    
    if (empty($description)) $errors[] = "Description is required";
    if (strlen($description) < 50) $errors[] = "Description must be at least 50 characters long";
    
    if ($target_amount <= 0) $errors[] = "Target amount must be greater than zero";
    if ($target_amount > 1000000) $errors[] = "Target amount cannot exceed ৳10,00,000";
    
    if (empty($_FILES["image"]["name"])) {
        $errors[] = "Campaign image is required";
    } elseif (!in_array($imageFileType, $allowed_types)) {
        $errors[] = "Only JPG, JPEG, PNG, GIF, WEBP files are allowed";
    } elseif ($_FILES["image"]["size"] > 5 * 1024 * 1024) {
        $errors[] = "Image size must be less than 5MB";
    }

    if (empty($errors)) {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $stmt = $pdo->prepare("INSERT INTO campaigns (user_id, category_id, title, description, target_amount, image_path, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
            if ($stmt->execute([$user_id, $category_id, $title, $description, $target_amount, $target_file])) {
                $_SESSION['success'] = "Campaign created successfully! It is pending admin approval.";
                redirect('dashboard.php');
            } else {
                $errors[] = "Database error, please try again";
                if (file_exists($target_file)) {
                    unlink($target_file);
                }
            }
        } else {
            $errors[] = "Sorry, there was an error uploading your file. Please try again.";
        }
    }
}
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="d-flex align-items-center mb-4">
        <div class="featured-icon me-3">
            <i class="fa-solid fa-rocket fa-2x"></i>
        </div>
        <div>
            <h1 class="display-6 fw-bold mb-1">Start a New Campaign</h1>
            <p class="text-muted mb-0">Bring your ideas to life with <?php echo htmlspecialchars($user['username']); ?></p>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-start">
                <i class="fa-solid fa-circle-exclamation fs-5 me-2 mt-1"></i>
                <div>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Progress Steps -->
    <div class="progress-steps mb-5">
        <div class="row g-2">
            <div class="col-4">
                <div class="step-item active">
                    <div class="step-number">1</div>
                    <div class="step-text">Campaign Details</div>
                </div>
            </div>
            <div class="col-4">
                <div class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-text">Review & Submit</div>
                </div>
            </div>
            <div class="col-4">
                <div class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-text">Admin Approval</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Form Column -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="card-title mb-4">
                        <i class="fa-solid fa-circle-info text-primary me-2"></i>
                        Campaign Information
                    </h5>
                    
                    <form method="post" action="" enctype="multipart/form-data" id="campaignForm">
                        <!-- Title -->
                        <div class="mb-4">
                            <label for="title" class="form-label fw-semibold">
                                <i class="fa-regular fa-heading text-primary me-1"></i>
                                Campaign Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg rounded-3" 
                                   id="title" 
                                   name="title" 
                                   value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" 
                                   placeholder="e.g., Help Build a Community Library"
                                   required>
                            <div class="form-text">
                                <i class="fa-regular fa-lightbulb me-1"></i>
                                Choose a clear, catchy title (5-100 characters)
                            </div>
                        </div>

                        <!-- Category -->
                        <div class="mb-4">
                            <label for="category_id" class="form-label fw-semibold">
                                <i class="fa-solid fa-tag text-primary me-1"></i>
                                Category <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-lg rounded-3" id="category_id" name="category_id" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo (isset($category_id) && $category_id == $cat['id']) ? 'selected' : ''; ?>>
                                        <i class="fa-solid fa-folder"></i>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                <i class="fa-regular fa-circle-question me-1"></i>
                                Choose the most relevant category for your campaign
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-semibold">
                                <i class="fa-regular fa-note-sticky text-primary me-1"></i>
                                Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control form-control-lg rounded-3" 
                                      id="description" 
                                      name="description" 
                                      rows="6" 
                                      placeholder="Tell your story... Why should people support your campaign?"
                                      required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                            <div class="form-text">
                                <i class="fa-regular fa-clock me-1"></i>
                                Minimum 50 characters. Be detailed and compelling!
                                <span id="charCount" class="float-end">0/50 min</span>
                            </div>
                        </div>

                        <!-- Target Amount -->
                        <div class="mb-4">
                            <label for="target_amount" class="form-label fw-semibold">
                                <i class="fa-solid fa-coins text-primary me-1"></i>
                                Target Amount (৳) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 rounded-start-3">৳</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="1" 
                                       max="1000000"
                                       class="form-control form-control-lg rounded-end-3" 
                                       id="target_amount" 
                                       name="target_amount" 
                                       value="<?php echo isset($target_amount) ? htmlspecialchars($target_amount) : ''; ?>" 
                                       placeholder="1000.00"
                                       required>
                            </div>
                            <div class="form-text">
                                <i class="fa-regular fa-circle-check me-1"></i>
                                Enter amount between ৳1 and ৳10,00,000
                            </div>
                        </div>

                        <!-- Image Upload -->
                        <div class="mb-4">
                            <label for="image" class="form-label fw-semibold">
                                <i class="fa-regular fa-image text-primary me-1"></i>
                                Campaign Image <span class="text-danger">*</span>
                            </label>
                            
                            <div class="upload-area border border-2 border-dashed rounded-4 p-4 text-center" id="dropArea">
                                <input type="file" class="d-none" id="image" name="image" accept="image/*" required>
                                
                                <div class="upload-placeholder">
                                    <i class="fa-solid fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                    <h6 class="fw-semibold">Drag & Drop or Click to Upload</h6>
                                    <p class="text-muted small mb-2">Supported: JPG, JPEG, PNG, GIF, WEBP (Max 5MB)</p>
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-4" id="browseBtn">
                                        <i class="fa-regular fa-folder-open me-2"></i>Browse Files
                                    </button>
                                </div>
                                
                                <div class="upload-preview d-none">
                                    <img src="#" alt="Preview" class="img-fluid rounded-3 mb-3" style="max-height: 200px;">
                                    <p class="file-name small text-muted mb-2"></p>
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill" id="removeImage">
                                        <i class="fa-regular fa-trash-can me-2"></i>Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Column -->
        <div class="col-lg-4">
            <!-- Tips Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">
                        <i class="fa-solid fa-lightbulb text-warning me-2"></i>
                        Campaign Tips
                    </h5>
                    
                    <div class="tips-list">
                        <div class="tip-item d-flex mb-3">
                            <div class="tip-icon me-3">
                                <i class="fa-solid fa-check-circle text-success"></i>
                            </div>
                            <div class="tip-text small">
                                <strong>Clear Title</strong>
                                <p class="text-muted mb-0">Make your title attention-grabbing and descriptive</p>
                            </div>
                        </div>
                        
                        <div class="tip-item d-flex mb-3">
                            <div class="tip-icon me-3">
                                <i class="fa-solid fa-check-circle text-success"></i>
                            </div>
                            <div class="tip-text small">
                                <strong>Compelling Story</strong>
                                <p class="text-muted mb-0">Share your passion and explain why this matters</p>
                            </div>
                        </div>
                        
                        <div class="tip-item d-flex mb-3">
                            <div class="tip-icon me-3">
                                <i class="fa-solid fa-check-circle text-success"></i>
                            </div>
                            <div class="tip-text small">
                                <strong>Realistic Goal</strong>
                                <p class="text-muted mb-0">Set a achievable target amount for your campaign</p>
                            </div>
                        </div>
                        
                        <div class="tip-item d-flex">
                            <div class="tip-icon me-3">
                                <i class="fa-solid fa-check-circle text-success"></i>
                            </div>
                            <div class="tip-text small">
                                <strong>Quality Image</strong>
                                <p class="text-muted mb-0">Use high-quality images that represent your project</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Card -->
            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 100px;">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3">
                        <i class="fa-regular fa-eye text-primary me-2"></i>
                        Campaign Preview
                    </h5>
                    
                    <div class="preview-content">
                        <div class="preview-image bg-light rounded-3 d-flex align-items-center justify-content-center mb-3" style="height: 150px;">
                            <i class="fa-regular fa-image fa-2x text-muted"></i>
                        </div>
                        
                        <h6 class="preview-title fw-bold mb-2">Your Campaign Title</h6>
                        
                        <div class="preview-category small text-muted mb-2">
                            <i class="fa-solid fa-tag me-1"></i>
                            <span>Category will appear here</span>
                        </div>
                        
                        <div class="preview-description small text-muted mb-3">
                            Your campaign description will be previewed here...
                        </div>
                        
                        <div class="preview-target fw-bold text-primary">
                            Target: ৳0.00
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    
                    <button type="submit" form="campaignForm" class="btn btn-primary w-100 rounded-pill py-3">
                        <i class="fa-solid fa-paper-plane me-2"></i>
                        Submit Campaign
                    </button>
                    
                    <p class="text-muted small text-center mt-3 mb-0">
                        <i class="fa-regular fa-clock me-1"></i>
                        Campaign will be reviewed by admin
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Create Campaign Page Styles */
:root {
    --primary-gradient: linear-gradient(135deg, #141e30, #243b55);
}

/* Featured Icon */
.featured-icon {
    width: 60px;
    height: 60px;
    background: var(--primary-gradient);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 5px 15px rgba(0,198,255,0.3);
}

/* Progress Steps */
.progress-steps .step-item {
    text-align: center;
    position: relative;
}

.progress-steps .step-item.active .step-number {
    background: var(--primary-gradient);
    color: white;
    border-color: transparent;
}

.progress-steps .step-number {
    width: 40px;
    height: 40px;
    background: white;
    border: 2px solid #dee2e6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.progress-steps .step-text {
    font-size: 0.9rem;
    color: #6c757d;
}

/* Upload Area */
.upload-area {
    background: #f8f9fa;
    cursor: pointer;
    transition: all 0.3s ease;
}

.upload-area:hover {
    background: #e9ecef;
    border-color: #00c6ff !important;
}

.upload-area.dragover {
    background: #e3f2fd;
    border-color: #00c6ff !important;
}

.border-dashed {
    border-style: dashed !important;
}

/* Form Controls */
.form-control-lg, .form-select-lg {
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
    padding: 0.75rem 1rem;
}

.form-control-lg:focus, .form-select-lg:focus {
    border-color: #00c6ff;
    box-shadow: 0 0 0 3px rgba(0,198,255,0.1);
}

.input-group-text {
    padding: 0.75rem 1rem;
}

/* Tips List */
.tip-icon {
    min-width: 20px;
}

/* Preview Card */
.preview-content {
    min-height: 250px;
}

/* Dark Mode Adjustments */
body.dark-mode .upload-area {
    background: #2d2d2d;
}

body.dark-mode .upload-area:hover {
    background: #3d3d3d;
}

body.dark-mode .progress-steps .step-number {
    background: #1e1e1e;
    border-color: #444;
    color: #ddd;
}

body.dark-mode .progress-steps .step-item.active .step-number {
    background: var(--primary-gradient);
    color: white;
}

body.dark-mode .progress-steps .step-text {
    color: #aaa;
}

body.dark-mode .form-control-lg,
body.dark-mode .form-select-lg,
body.dark-mode .input-group-text {
    background: #2d2d2d;
    border-color: #444;
    color: #eee;
}

body.dark-mode .form-control-lg:focus,
body.dark-mode .form-select-lg:focus {
    background: #2d2d2d;
    border-color: #00c6ff;
    color: #eee;
}

body.dark-mode .bg-light {
    background: #2d2d2d !important;
}

body.dark-mode .text-muted {
    color: #aaa !important;
}

body.dark-mode .border {
    border-color: #444 !important;
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
    
    .display-6 {
        font-size: 1.5rem;
    }
    
    .progress-steps .step-number {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
    }
    
    .progress-steps .step-text {
        font-size: 0.8rem;
    }
    
    .card-body {
        padding: 1.5rem !important;
    }
}

/* Character Counter */
#charCount {
    font-size: 0.85rem;
    color: #6c757d;
}

#charCount.text-success {
    color: #28a745 !important;
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.tab-pane {
    animation: fadeIn 0.5s ease;
}

/* Button Styles */
.btn-primary {
    background: var(--primary-gradient);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,198,255,0.4);
}

/* Card Styles */
.card {
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
}
</style>

<script>
// Real-time character counter for description
document.getElementById('description')?.addEventListener('input', function() {
    const charCount = this.value.length;
    const counter = document.getElementById('charCount');
    counter.textContent = charCount + '/50 min';
    
    if (charCount >= 50) {
        counter.classList.add('text-success');
        counter.classList.remove('text-muted');
    } else {
        counter.classList.remove('text-success');
        counter.classList.add('text-muted');
    }
});

// Image upload preview
document.getElementById('image')?.addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        const fileName = this.files[0].name;
        
        reader.onload = function(e) {
            document.querySelector('.upload-placeholder').classList.add('d-none');
            document.querySelector('.upload-preview').classList.remove('d-none');
            document.querySelector('.upload-preview img').src = e.target.result;
            document.querySelector('.upload-preview .file-name').textContent = fileName;
        }
        
        reader.readAsDataURL(this.files[0]);
    }
});

// Remove image button
document.getElementById('removeImage')?.addEventListener('click', function() {
    document.getElementById('image').value = '';
    document.querySelector('.upload-placeholder').classList.remove('d-none');
    document.querySelector('.upload-preview').classList.add('d-none');
});

// Browse button
document.getElementById('browseBtn')?.addEventListener('click', function() {
    document.getElementById('image').click();
});

// Drag and drop
const dropArea = document.getElementById('dropArea');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    dropArea.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, unhighlight, false);
});

function highlight() {
    dropArea.classList.add('dragover');
}

function unhighlight() {
    dropArea.classList.remove('dragover');
}

dropArea.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0) {
        document.getElementById('image').files = files;
        
        // Trigger change event
        const event = new Event('change', { bubbles: true });
        document.getElementById('image').dispatchEvent(event);
    }
}

// Live preview update
document.getElementById('title')?.addEventListener('input', function() {
    if (this.value) {
        document.querySelector('.preview-title').textContent = this.value;
    } else {
        document.querySelector('.preview-title').textContent = 'Your Campaign Title';
    }
});

document.getElementById('category_id')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    if (selected.value) {
        document.querySelector('.preview-category span').textContent = selected.text;
    } else {
        document.querySelector('.preview-category span').textContent = 'Category will appear here';
    }
});

document.getElementById('description')?.addEventListener('input', function() {
    if (this.value) {
        let preview = this.value.substring(0, 100);
        if (this.value.length > 100) preview += '...';
        document.querySelector('.preview-description').textContent = preview;
    } else {
        document.querySelector('.preview-description').textContent = 'Your campaign description will be previewed here...';
    }
});

document.getElementById('target_amount')?.addEventListener('input', function() {
    if (this.value) {
        document.querySelector('.preview-target').innerHTML = `Target: ৳${parseFloat(this.value).toFixed(2)}`;
    } else {
        document.querySelector('.preview-target').innerHTML = 'Target: ৳0.00';
    }
});

// Form validation before submit
document.getElementById('campaignForm')?.addEventListener('submit', function(e) {
    const description = document.getElementById('description').value;
    
    if (description.length < 50) {
        e.preventDefault();
        alert('Description must be at least 50 characters long. Currently: ' + description.length + ' characters');
    }
});
</script>

<?php
require_once 'footer.php';
?>
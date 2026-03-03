<?php
require_once 'header.php';

if (!isLoggedIn()) {
    $_SESSION['error'] = "Please login first";
    redirect('login.php');
}

$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$campaign_id) {
    $_SESSION['error'] = "Invalid campaign ID";
    redirect('my_campaigns.php');
}

// Get user data
$user_stmt = $pdo->prepare("SELECT user_type, status FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$user_data = $user_stmt->fetch();

// Check if user is an approved organizer
$is_organizer = ($user_data['user_type'] == 'organizer' || $user_data['user_type'] == 'both') && $user_data['status'] == 'active';

if (!$is_organizer) {
    $_SESSION['error'] = "Only approved organizers can edit campaigns";
    redirect('dashboard.php');
}

// Get campaign details
$stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ?");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    $_SESSION['error'] = "Campaign not found";
    redirect('my_campaigns.php');
}

// Check if user owns this campaign
if ($campaign['user_id'] != $_SESSION['user_id']) {
    $_SESSION['error'] = "You can only edit your own campaigns";
    redirect('my_campaigns.php');
}

// Fetch all categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $target_amount = floatval($_POST['target_amount']);
    $category_id = intval($_POST['category_id']);
    
    $errors = [];

    // Validation
    if (empty($title)) {
        $errors[] = "Title is required";
    } elseif (strlen($title) < 5) {
        $errors[] = "Title must be at least 5 characters long";
    } elseif (strlen($title) > 100) {
        $errors[] = "Title must be less than 100 characters";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    } elseif (strlen($description) < 50) {
        $errors[] = "Description must be at least 50 characters long";
    }
    
    if ($target_amount <= 0) {
        $errors[] = "Target amount must be greater than zero";
    } elseif ($target_amount > 1000000) {
        $errors[] = "Target amount cannot exceed $1,000,000";
    }
    
    if ($category_id <= 0) {
        $errors[] = "Please select a category";
    } else {
        // Verify category exists
        $cat_stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
        $cat_stmt->execute([$category_id]);
        if (!$cat_stmt->fetch()) {
            $errors[] = "Invalid category selected";
        }
    }

    // Handle image upload if new image is provided
    $image_path = $campaign['image_path'];
    
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/campaigns/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $image_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["image"]["name"]));
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($imageFileType, $allowed_types)) {
            $errors[] = "Only JPG, JPEG, PNG, GIF, WEBP files are allowed";
        } elseif ($_FILES["image"]["size"] > 5 * 1024 * 1024) {
            $errors[] = "Image size must be less than 5MB";
        } else {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Delete old image if exists and not default
                if (file_exists($campaign['image_path']) && 
                    $campaign['image_path'] != 'assets/images/default-campaign.jpg' &&
                    strpos($campaign['image_path'], 'default-campaign') === false) {
                    unlink($campaign['image_path']);
                }
                $image_path = $target_file;
            } else {
                $errors[] = "Failed to upload image. Please try again.";
            }
        }
    }

    if (empty($errors)) {
        // FIXED: Removed updated_at column since it doesn't exist
        $update_sql = "UPDATE campaigns SET 
                       title = ?, 
                       description = ?, 
                       target_amount = ?, 
                       category_id = ?, 
                       image_path = ? 
                       WHERE id = ? AND user_id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        
        if ($update_stmt->execute([$title, $description, $target_amount, $category_id, $image_path, $campaign_id, $_SESSION['user_id']])) {
            $_SESSION['success'] = "Campaign updated successfully!";
            redirect("campaign.php?id=$campaign_id");
        } else {
            $errors[] = "Failed to update campaign. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Campaign - <?php echo htmlspecialchars($campaign['title']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #141e30, #243b55);
        }

        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 2rem 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .form-container {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .current-image {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 1rem;
            margin-bottom: 1rem;
            border: 3px solid #00c6ff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .image-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 1rem;
            margin-top: 1rem;
        }

        /* Form Controls */
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 1rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #00c6ff;
            box-shadow: 0 0 0 3px rgba(0,198,255,0.1);
        }

        .form-text {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        /* Buttons */
        .btn-save {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,198,255,0.3);
            color: white;
        }

        .btn-cancel {
            background: white;
            color: #141e30;
            border: 2px solid #dee2e6;
            padding: 0.75rem 2rem;
            border-radius: 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
            transform: translateY(-2px);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            background: white;
            color: #141e30;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid #dee2e6;
            margin-bottom: 1.5rem;
        }

        .back-btn:hover {
            background: #f8f9fa;
            transform: translateX(-5px);
        }

        /* Alert Messages */
        .alert {
            border-radius: 1rem;
            border: none;
            padding: 1rem 1.5rem;
        }

        .alert-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .alert-info {
            background: linear-gradient(135deg, #17a2b8, #00c6ff);
            color: white;
        }

        /* Dark Mode */
        body.dark-mode {
            background: #121212;
        }

        body.dark-mode .form-container {
            background: #1e1e1e;
            border: 1px solid #333;
        }

        body.dark-mode .form-label {
            color: #ddd;
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background: #2d2d2d;
            border-color: #444;
            color: #eee;
        }

        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
            background: #2d2d2d;
            border-color: #00c6ff;
            color: #eee;
        }

        body.dark-mode .form-text {
            color: #aaa;
        }

        body.dark-mode .image-info {
            background: #2d2d2d;
        }

        body.dark-mode .back-btn {
            background: #1e1e1e;
            color: #eee;
            border-color: #333;
        }

        body.dark-mode .back-btn:hover {
            background: #2d2d2d;
        }

        body.dark-mode .btn-cancel {
            background: #1e1e1e;
            color: #eee;
            border-color: #444;
        }

        body.dark-mode .btn-cancel:hover {
            background: #2d2d2d;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                padding: 1.5rem 0;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            .current-image {
                width: 150px;
                height: 112px;
            }

            .btn-save, .btn-cancel {
                width: 100%;
                margin: 0.5rem 0;
            }

            .d-flex.gap-3 {
                flex-direction: column;
                gap: 0.5rem !important;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <h1 class="display-5 fw-bold">Edit Campaign</h1>
            <p class="lead mb-0">Update your campaign information</p>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Back Button -->
        <a href="campaign.php?id=<?php echo $campaign_id; ?>" class="back-btn">
            <i class="fa-solid fa-arrow-left me-2"></i>
            Back to Campaign
        </a>

        <!-- Alert Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="post" action="" enctype="multipart/form-data">
                <!-- Title -->
                <div class="mb-4">
                    <label for="title" class="form-label">
                        <i class="fa-regular fa-heading text-primary me-1"></i>
                        Campaign Title <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg" 
                           id="title" 
                           name="title" 
                           value="<?php echo htmlspecialchars($_POST['title'] ?? $campaign['title']); ?>" 
                           placeholder="Enter your campaign title"
                           required>
                    <div class="form-text">5-100 characters. Make it catchy and descriptive!</div>
                </div>

                <!-- Category -->
                <div class="mb-4">
                    <label for="category_id" class="form-label">
                        <i class="fa-solid fa-tag text-primary me-1"></i>
                        Category <span class="text-danger">*</span>
                    </label>
                    <select class="form-select form-select-lg" id="category_id" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                <?php echo (($_POST['category_id'] ?? $campaign['category_id']) == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label for="description" class="form-label">
                        <i class="fa-regular fa-note-sticky text-primary me-1"></i>
                        Description <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control form-control-lg" 
                              id="description" 
                              name="description" 
                              rows="8" 
                              placeholder="Tell your story... Why should people support your campaign?"
                              required><?php echo htmlspecialchars($_POST['description'] ?? $campaign['description']); ?></textarea>
                    <div class="form-text">
                        Minimum 50 characters. Be detailed and compelling!
                        <span id="charCount" class="float-end">
                            <?php echo strlen($_POST['description'] ?? $campaign['description']); ?>/50 min
                        </span>
                    </div>
                </div>

                <!-- Target Amount -->
                <div class="mb-4">
                    <label for="target_amount" class="form-label">
                        <i class="fa-solid fa-coins text-primary me-1"></i>
                        Target Amount ($) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0 rounded-start-3">$</span>
                        <input type="number" 
                               step="0.01" 
                               min="1" 
                               max="1000000"
                               class="form-control form-control-lg" 
                               id="target_amount" 
                               name="target_amount" 
                               value="<?php echo htmlspecialchars($_POST['target_amount'] ?? $campaign['target_amount']); ?>" 
                               required>
                    </div>
                    <div class="form-text">Enter amount between $1 and $1,000,000</div>
                </div>

                <!-- Current Image -->
                <div class="mb-4">
                    <label class="form-label">
                        <i class="fa-regular fa-image text-primary me-1"></i>
                        Current Image
                    </label>
                    <div>
                        <img src="<?php echo htmlspecialchars($campaign['image_path']); ?>" 
                             class="current-image" 
                             alt="Current campaign image"
                             onerror="this.src='assets/images/default-campaign.jpg'">
                        <div class="image-info">
                            <p class="mb-1"><strong>Current file:</strong> <?php echo basename($campaign['image_path']); ?></p>
                            <p class="mb-0 text-muted small">You can upload a new image below if you want to change it.</p>
                        </div>
                    </div>
                </div>

                <!-- New Image Upload -->
                <div class="mb-4">
                    <label for="image" class="form-label">
                        <i class="fa-regular fa-cloud-arrow-up text-primary me-1"></i>
                        Update Image (Optional)
                    </label>
                    <input type="file" 
                           class="form-control form-control-lg" 
                           id="image" 
                           name="image" 
                           accept="image/*">
                    <div class="form-text">
                        <i class="fa-regular fa-circle-info me-1"></i>
                        Leave empty to keep current image. Max 5MB. Allowed: JPG, JPEG, PNG, GIF, WEBP
                    </div>
                    <div id="imagePreview" class="mt-3 d-none">
                        <p class="mb-2">New image preview:</p>
                        <img src="#" alt="Preview" class="img-fluid rounded-3" style="max-height: 200px;">
                    </div>
                </div>

                <!-- Status Notice -->
                <div class="alert alert-info mb-4">
                    <i class="fa-regular fa-clock me-2"></i>
                    <strong>Note:</strong> After editing, your campaign will remain in 
                    <strong><?php echo ucfirst($campaign['status']); ?></strong> status.
                    <?php if ($campaign['status'] == 'pending'): ?>
                        It will still need admin approval.
                    <?php endif; ?>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-save flex-grow-1">
                        <i class="fa-solid fa-save me-2"></i>
                        Save Changes
                    </button>
                    <a href="campaign.php?id=<?php echo $campaign_id; ?>" class="btn btn-cancel">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Help Section -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">
                    <i class="fa-solid fa-lightbulb text-warning me-2"></i>
                    Editing Tips
                </h5>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fa-solid fa-check-circle text-success fa-xl"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Clear Title</h6>
                                <p class="text-muted small mb-0">Make sure your title clearly explains what your campaign is about</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fa-solid fa-check-circle text-success fa-xl"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Detailed Description</h6>
                                <p class="text-muted small mb-0">Update your story, add new updates, and keep supporters engaged</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fa-solid fa-check-circle text-success fa-xl"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Fresh Image</h6>
                                <p class="text-muted small mb-0">A new image can attract more attention to your campaign</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Character counter for description
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

        // Image preview
        document.getElementById('image')?.addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const previewImg = preview.querySelector('img');
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.classList.remove('d-none');
                }
                
                reader.readAsDataURL(this.files[0]);
            } else {
                preview.classList.add('d-none');
                previewImg.src = '#';
            }
        });

        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const description = document.getElementById('description').value;
            
            if (description.length < 50) {
                e.preventDefault();
                alert('Description must be at least 50 characters long. Currently: ' + description.length + ' characters');
            }
        });

        // Dark mode
        if (localStorage.getItem('dark-mode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php require_once 'footer.php'; ?>
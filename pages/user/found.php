<?php
session_start();
require_once '../../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /Lost-Found/pages/login.php');
    exit();
}

// Get user data
$user_name = 'User';
try {
    $pdo = new PDO("mysql:host=localhost;port=8889;dbname=lost_found_db", 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $user_name = $user['full_name'];
    }
} catch (PDOException $e) {
    // Log error but don't break the page
    error_log("Database error: " . $e->getMessage());
}

$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDbConnection();
        
        // Validate and sanitize input
        $itemName = filter_input(INPUT_POST, 'itemName', FILTER_SANITIZE_STRING);
        $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $dateFound = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
        $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
        $userId = $_SESSION['user_id'];
        
        // Handle file upload
        $imagePath = null;
        if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/items/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['itemImage']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('item_') . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['itemImage']['tmp_name'], $targetPath)) {
                $imagePath = 'uploads/items/' . $fileName;
            }
        }
        
        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO found_items (user_id, item_name, category, description, found_date, location, image_path, status, created_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, 'available', NOW())");
        $stmt->execute([$userId, $itemName, $category, $description, $dateFound, $location, $imagePath]);
        
        $success = 'Thank you for reporting the found item! We\'ll help reunite it with its owner.';
        
        // Clear form
        $_POST = array();
        
    } catch (PDOException $e) {
        $error = 'An error occurred while processing your request. Please try again.';
        error_log('Database Error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Found Item - Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Lost-Found/assets/style.css">
    <style>
        .form-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .form-title {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .btn-form-submit {
            background-color: #4a6cf7;
            color: white;
            font-weight: 600;
            padding: 0.5rem 2rem;
            border-radius: 8px;
            border: none;
        }
        .btn-form-submit:hover {
            background-color: #3a5bd9;
            color: white;
        }
        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .dashboard-btn {
            color: #4a6cf7;
            text-decoration: none;
            font-weight: 500;
        }
        .app-header {
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .logo-icon {
            color: #ffffffff;
            font-size: 1.8rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="app-header">
        <div class="container py-3">
            <div class="d-flex justify-content-between align-items-center">
                <a href="userdash.php" class="logo">
                    <i class="bi bi-search-heart logo-icon"></i>
                    Lost&Found
                </a>
                <div class="d-flex align-items-center gap-3">
                    <a href="userdash.php" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-link text-decoration-none p-0 border-0 bg-transparent dropdown-toggle d-flex align-items-center" 
                                type="button" 
                                id="userDropdown" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false">
                            <div class="profile-icon me-2">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <span class="d-none d-md-inline"><?php echo htmlspecialchars($user_name); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li class="user-email" title="<?php echo htmlspecialchars($user_email); ?>">
                                <?php echo htmlspecialchars($user_email); ?>
                            </li>
                            <li><hr class="dropdown-divider m-0"></li>
                            <li><a class="dropdown-item" href="userprofile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="claim.php"><i class="bi bi-clipboard-check me-2"></i>My Claims</a></li>
                            <li><hr class="dropdown-divider m-0"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <div class="mt-2">
                            <a href="userdash.php" class="btn btn-sm btn-outline-primary me-2">Back to Dashboard</a>
                            <a href="found.php" class="btn btn-sm btn-primary">Report Another Item</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center mb-5">
                        <h2 class="form-title">Report Found Item</h2>
                        <p class="text-muted">Help reunite someone with their lost belongings by reporting what you found</p>
                    </div>

                    <div class="form-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Item Details</h4>
                            <a href="userdash.php" class="text-dark fs-5 text-decoration-none" aria-label="Close Form">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        </div>

                        <form id="foundForm" action="found.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label for="itemName" class="form-label fw-semibold">Item Name</label>
                                <input type="text" class="form-control" id="itemName" name="itemName" 
                                       value="<?php echo isset($_POST['itemName']) ? htmlspecialchars($_POST['itemName']) : ''; ?>" 
                                       placeholder="e.g., iPhone 13, Black Wallet, Car Keys" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="category" class="form-label fw-semibold">Category</label>
                                <select class="form-control" id="category" name="category" required>
                                    <option value="">Select a category</option>
                                    <option value="Phone" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Phone') ? 'selected' : ''; ?>>Phone</option>
                                    <option value="Electronics" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Electronics') ? 'selected' : ''; ?>>Electronics</option>
                                    <option value="Wallet" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Wallet') ? 'selected' : ''; ?>>Wallet</option>
                                    <option value="Keys" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Keys') ? 'selected' : ''; ?>>Keys</option>
                                    <option value="Bag" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Bag') ? 'selected' : ''; ?>>Bag</option>
                                    <option value="Clothing" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Clothing') ? 'selected' : ''; ?>>Clothing</option>
                                    <option value="Book" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Book') ? 'selected' : ''; ?>>Book</option>
                                    <option value="Jewelry" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Jewelry') ? 'selected' : ''; ?>>Jewelry</option>
                                    <option value="Other" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="description" class="form-label fw-semibold">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" 
                                          placeholder="Describe the item in detail including color, brand, size, unique features, contents, etc." 
                                          required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                <div class="form-text">Be as detailed as possible to help identify the item's owner.</div>
                            </div>

                            <div class="mb-4">
                                <label for="itemImage" class="form-label fw-semibold">Upload Image (Optional)</label>
                                <input class="form-control" type="file" id="itemImage" name="itemImage" accept="image/*">
                                <div class="form-text">Upload a clear photo of the item (Max 5MB, JPG, PNG, or GIF)</div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label for="date" class="form-label fw-semibold">Date Found</label>
                                    <input type="date" class="form-control" id="date" name="date" 
                                           value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>" 
                                           max="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="location" class="form-label fw-semibold">Location Found</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" 
                                           placeholder="Where did you find it?" required>
                                </div>
                            </div>

                            <div class="text-end">
                                <a href="userdash.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-form-submit">Submit Report</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Set max date to today for date input
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('date');
            if (dateInput && !dateInput.value) {
                dateInput.valueAsDate = new Date();
            }
            
            // Image preview
            const imageInput = document.getElementById('itemImage');
            const imagePreview = document.getElementById('imagePreview');
            
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        if (!imagePreview) {
                            const previewContainer = document.createElement('div');
                            previewContainer.id = 'imagePreview';
                            previewContainer.className = 'mt-2';
                            imageInput.parentNode.insertBefore(previewContainer, imageInput.nextSibling);
                        }
                        
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            const img = document.createElement('img');
                            img.src = event.target.result;
                            img.className = 'img-thumbnail';
                            img.style.maxHeight = '200px';
                            
                            const previewContainer = document.getElementById('imagePreview');
                            previewContainer.innerHTML = '';
                            previewContainer.appendChild(img);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>
</body>
</html>

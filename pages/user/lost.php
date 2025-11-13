<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /Lost-Found/pages/login.php');
    exit();
}

// Initialize variables
$error = '';
$success = '';
$user_name = 'User';

// Get user data
try {
    $stmt = $pdo->prepare("CALL GetUserById(?)");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $user_name = $user['username'];
    }
    $stmt->closeCursor();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify user exists
        $stmt = $pdo->prepare("SELECT user_id FROM User WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userExists = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        if (!$userExists) {
            throw new Exception("User not found. Please log in again.");
        }
        
        // Validate required fields
        $required = ['itemName', 'category', 'description', 'date', 'location'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }
        
        // Sanitize input
        $itemName = sanitize_input($_POST['itemName']);
        $category = sanitize_input($_POST['category']);
        $description = sanitize_input($_POST['description']);
        $dateLost = $_POST['date']; // Already validated as date
        $location = sanitize_input($_POST['location']);
        $userId = $_SESSION['user_id'];
        
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Use stored procedure to insert lost item
            $stmt = $pdo->prepare("CALL ReportLostItem(?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $userId,
                $itemName,
                $description,
                $category,
                $location,
                $dateLost
            ]);
            $stmt->closeCursor();
            
            // Commit the transaction
            $pdo->commit();
            
            $success = 'Your lost item has been reported successfully!';
            
            // Clear form
            $_POST = [];
            
        } catch (PDOException $e) {
            // Rollback the transaction on error
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log('Error in lost.php: ' . $error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Lost Item - Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

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
                            <a href="lost.php" class="btn btn-sm btn-primary">Report Another Item</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center mb-5">
                        <h2 class="form-title">Report Lost Item</h2>
                        <p class="text-muted">Provide details about your lost item to help us reunite you with your belongings</p>
                    </div>

                    <div class="form-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Item Details</h4>
                            <a href="userdash.php" class="text-dark fs-5 text-decoration-none" aria-label="Close Form">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        </div>

                        <form id="lostForm" action="lost.php" method="POST">
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
                                          placeholder="Describe your item in detail including color, brand, size, unique features, contents, etc." 
                                          required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                <div class="form-text">Be as detailed as possible to help identify your item.</div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label for="date" class="form-label fw-semibold">Date Lost</label>
                                    <input type="date" class="form-control" id="date" name="date" 
                                           value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>" 
                                           max="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="location" class="form-label fw-semibold">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" 
                                           placeholder="Where did you lose it?" required>
                                </div>
                            </div>

                            <div class="text-end">
                                <a href="userdash.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                <br><br>
                                <button type="submit" class="btn btn-form-submit">Submit Report</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS (includes Popper) - required for dropdowns and other interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

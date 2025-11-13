<?php
// Include database connection and functions
require_once('../../config/db.php');
require_once('../../includes/functions.php');

// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Initialize variables
$user = [];
$error = '';
$success = '';

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $full_name = sanitize_input($_POST['full_name']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = sanitize_phone($_POST['phone']);
        
        // Call stored procedure to update user profile
        $stmt = $pdo->prepare("CALL UpdateUserProfile(?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $email, $full_name, $phone]);
        $stmt->closeCursor();
        
        $success = 'Profile updated successfully!';
    } catch (PDOException $e) {
        $error = 'Error updating profile: ' . $e->getMessage();
        error_log("Profile update error: " . $e->getMessage());
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    try {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            throw new Exception("New passwords do not match");
        }
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM User WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $stmt->closeCursor();
        
        if (!password_verify($current_password, $user['password'])) {
            throw new Exception("Current password is incorrect");
        }
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE User SET password = ? WHERE user_id = ?");
        $stmt->execute([$hashed_password, $_SESSION['user_id']]);
        $stmt->closeCursor();
        
        $success = 'Password updated successfully!';
    } catch (Exception $e) {
        $error = 'Error changing password: ' . $e->getMessage();
    }
}

// Get user data
try {
    $stmt = $pdo->prepare("CALL GetUserById(?)");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    
    if (!$user) {
        throw new Exception("User not found");
    }
    
    // Get user stats using stored procedures
    $stmt = $pdo->query("CALL GetUserLostItemsCount(" . $_SESSION['user_id'] . ")");
    $lostItems = $stmt->fetch()['total'];
    $stmt->closeCursor();
    
    $stmt = $pdo->query("CALL GetUserFoundItemsCount(" . $_SESSION['user_id'] . ")");
    $foundItems = $stmt->fetch()['total'];
    $stmt->closeCursor();
    
    $stmt = $pdo->query("CALL GetUserClaimsCount(" . $_SESSION['user_id'] . ")");
    $claimsCount = $stmt->fetch()['total'];
    $stmt->closeCursor();
    
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
    error_log("Profile error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container my-5">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php else: ?>
            <div class="profile-header text-center">
                <div class="profile-avatar">
                    <i class="bi bi-person"></i>
                </div>
                <h2 class="mb-2"><?php echo htmlspecialchars($user['username']); ?></h2>
                <p class="text-muted mb-4">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                
                <!-- Stats Cards -->
                <div class="profile-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($lostItems ?? 0); ?></div>
                        <div class="stat-label">Your Lost Items</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($foundItems ?? 0); ?></div>
                        <div class="stat-label">Your Found Items</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($claimsCount ?? 0); ?></div>
                        <div class="stat-label">Your Claims</div>
                    </div>
                </div>
                
                <div class="profile-info mt-5 text-start">
                    <h4 class="mb-4">Account Information</h4>
                    <div class="info-item">
                        <div class="info-label">Username:</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email:</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <?php if (!empty($user['phone'])): ?>
                    <div class="info-item">
                        <div class="info-label">Phone:</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['phone']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <div class="info-label">Member Since:</div>
                        <div class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                    </div>
                </div>

            <!-- Additional sections can be added here -->
            <div class="mt-4 text-end">
                <a href="changeuserpassword.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-key"></i> Change Password
                </a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Lost&Found. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

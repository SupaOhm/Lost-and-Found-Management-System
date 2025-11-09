<?php
// Include database connection
require_once('../../config/db.php');

// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get user data
$user = [];
$error = '';
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("User not found");
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
    error_log("Profile error: " . $e->getMessage());
}

// Get user stats
try {
    // Get user's lost items count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM lost_items WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $lostItems = $stmt->fetch()['count'];
    
    // Get user's found items count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM found_items WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $foundItems = $stmt->fetch()['count'];
    
    // Get user's claims count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM claims WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $claimsCount = $stmt->fetch()['count'];
    
} catch (PDOException $e) {
    error_log("Stats error: " . $e->getMessage());
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
    <link rel="stylesheet" href="/Lost-Found/assets/style.css">
    <style>
        .profile-header {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 3rem 2rem;
            margin: 2rem 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
            color: #6c757d;
        }
        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
        }
        .stat-card {
            text-align: center;
            padding: 1.5rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            flex: 1;
            max-width: 200px;
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #4361ee;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .profile-info {
            max-width: 600px;
            margin: 0 auto;
        }
        .info-item {
            display: flex;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 120px;
        }
        .info-value {
            color: #212529;
            flex: 1;
        }
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            color: #6c757d;
        }
        .profile-details {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .detail-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
            min-width: 150px;
        }
        .detail-value {
            color: #212529;
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
                    <a href="userdash.php" class="btn btn-outline-light btn-sm me-2">
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
                            <span id="usernameDisplay" class="d-none d-md-inline"><?php echo htmlspecialchars($user['full_name']); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li class="user-email" title="<?php echo htmlspecialchars($user['email']); ?>">
                                <?php echo htmlspecialchars($user['email']); ?>
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
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php else: ?>
            <div class="profile-header text-center">
                <div class="profile-avatar">
                    <i class="bi bi-person"></i>
                </div>
                <h2 class="mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                <p class="text-muted mb-4">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                
                <!-- Stats Cards -->
                <div class="profile-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($lostItems ?? 0); ?></div>
                        <div class="stat-label">Lost Items</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($foundItems ?? 0); ?></div>
                        <div class="stat-label">Found Items</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($claimsCount ?? 0); ?></div>
                        <div class="stat-label">Claims</div>
                    </div>
                </div>
                
                <div class="profile-info mt-5 text-start">
                    <h4 class="mb-4">Account Information</h4>
                    <div class="info-item">
                        <div class="info-label">Full Name:</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
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

            <div class="profile-details">
                <h4 class="mb-4">Account Information</h4>
                
                <div class="detail-item">
                    <div class="detail-label">
                        <i class="bi bi-person me-2"></i>Full Name
                    </div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">
                        <i class="bi bi-envelope me-2"></i>Email Address
                    </div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">
                        <i class="bi bi-telephone me-2"></i>Phone Number
                    </div>
                    <div class="detail-value">
                        <?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : '<span class="text-muted">Not provided</span>'; ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">
                        <i class="bi bi-calendar3 me-2"></i>Member Since
                    </div>
                    <div class="detail-value">
                        <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                    </div>
                </div>
            </div>

            <!-- Additional sections can be added here -->
            <div class="mt-4 text-end">
                <a href="changepassword.php" class="btn btn-outline-primary me-2">
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

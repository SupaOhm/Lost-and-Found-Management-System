<?php
// Include database connection
require_once('../../config/userconfig.php');

// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get user data
$user_name = 'User';
try {
    $stmt = $pdo->prepare("CALL GetUserById(?)");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $user_name = $user['username'];
    }
    $stmt->closeCursor(); // Close the cursor after fetching the result
} catch (PDOException $e) {
    // Log error but don't break the page
    error_log("Database error: " . $e->getMessage());
}

// Get stats for dashboard
$stats = [
    'lost_items' => 0,
    'found_items' => 0,
    'my_claims' => 0
];

try {
    // Get lost items count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM LostItem");
    $result = $stmt->fetch();
    $stats['lost_items'] = $result['count'];
    $stmt->closeCursor();
    
    // Get found items count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM FoundItem");
    $result = $stmt->fetch();
    $stats['found_items'] = $result['count'];
    $stmt->closeCursor();
    
    // Get total claims count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM ClaimRequest");
    $result = $stmt->fetch();
    $stats['my_claims'] = $result['count'];
    $stmt->closeCursor();
    
} catch (PDOException $e) {
    // Log error but don't break the page
    error_log("Dashboard stats error: " . $e->getMessage());
}

// Check for potential matches
$match_count = 0;
$potential_matches = [];
try {
    // Get match count
    $stmt = $pdo->prepare("CALL GetUserMatchCount(?)");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    if ($result) {
        $match_count = $result['match_count'];
    }
    $stmt->closeCursor();
    
    // Get potential matches if any exist
    if ($match_count > 0) {
        $stmt = $pdo->prepare("CALL FindPotentialMatches(?)");
        $stmt->execute([$_SESSION['user_id']]);
        $potential_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
    }
} catch (PDOException $e) {
    // Log error but don't break the page
    error_log("Match detection error: " . $e->getMessage());
}

// Check for recent claim status updates (approved/rejected in last 7 days)
$claim_notifications = [];
$claim_notification_count = 0;
try {
    $stmt = $pdo->prepare("
        SELECT c.claim_id, c.status, c.approved_date, f.item_name, f.category
        FROM ClaimRequest c
        JOIN FoundItem f ON c.found_id = f.found_id
        WHERE c.user_id = ? 
        AND c.status IN ('approved', 'rejected')
        AND c.approved_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY c.approved_date DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $claim_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $claim_notification_count = count($claim_notifications);
} catch (PDOException $e) {
    error_log("Claim notification error: " . $e->getMessage());
}

// Calculate total notification count
$total_notification_count = $match_count + $claim_notification_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost&Found - Reuniting People with Their Belongings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/style.css">
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
                    <?php if ($total_notification_count > 0): ?>
                    <!-- Notification Bell -->
                    <div class="dropdown">
                        <button class="notification-bell-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell"></i>
                            <span class="notification-badge"><?php echo $total_notification_count; ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                            <li class="dropdown-header">
                                <i class="bi bi-bell me-2"></i>
                                <strong>Notifications</strong>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            
                            <?php if ($match_count > 0): ?>
                            <li>
                                <a class="dropdown-item notification-item" href="match_results.php">
                                    <div class="d-flex align-items-start gap-2">
                                        <div class="notification-icon-small match-type">
                                            <i class="bi bi-stars"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="notification-item-title">Potential Matches Found</div>
                                            <div class="notification-item-text"><?php echo $match_count; ?> item<?php echo $match_count > 1 ? 's' : ''; ?> may match your reports</div>
                                        </div>
                                    </div>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php foreach ($claim_notifications as $claim_notif): ?>
                            <li>
                                <a class="dropdown-item notification-item" href="claim.php">
                                    <div class="d-flex align-items-start gap-2">
                                        <div class="notification-icon-small <?php echo $claim_notif['status'] === 'approved' ? 'claim-type' : 'alert-type'; ?>">
                                            <i class="bi bi-<?php echo $claim_notif['status'] === 'approved' ? 'check-circle' : 'x-circle'; ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="notification-item-title">Claim <?php echo ucfirst($claim_notif['status']); ?></div>
                                            <div class="notification-item-text">Your claim for "<?php echo htmlspecialchars($claim_notif['item_name']); ?>" was <?php echo $claim_notif['status']; ?></div>
                                        </div>
                                    </div>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <div class="dropdown">
                        <button class="btn btn-link text-decoration-none p-0 border-0 bg-transparent dropdown-toggle d-flex align-items-center" 
                                type="button" 
                                id="userDropdown" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false">
                            <div class="profile-icon me-2">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <span id="usernameDisplay" class="d-none d-md-inline"><?php echo htmlspecialchars($user_name); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="userprofile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="claim.php"><i class="bi bi-clipboard-check me-2"></i>My Claims & Reports</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>


    <!-- Features Section -->
    <section class="feature-section">
        <div class="container">
            <br><br>
            <h2 class="section-title">How Can We Help?</h2>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <a href="lost.php" class="feature-card">
                        <div class="feature-icon lost">
                            <i class="bi bi-info-circle"></i>
                        </div>
                        <h3>Report Lost Item</h3>
                        <p>Lost something? Post details to help others find it.</p>
                    </a>
                </div>
                <div class="col-lg-4 col-md-6">
                    <a href="found.php" class="feature-card">
                        <div class="feature-icon found">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <h3>Report Found Item</h3>
                        <p>Found something? List it so the owner can claim.</p>
                    </a>
                </div>
                <div class="col-lg-4 col-md-6">
                    <a href="search.php" class="feature-card">
                        <div class="feature-icon search">
                            <i class="bi bi-search"></i>
                        </div>
                        <h3>Search Database</h3>
                        <p>Find lost or found items by category or location.</p>
                    </a>
                </div>
            </div>
        </div>
    </section>

     <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Track Your Reports</h2>
            <p class="cta-text">You can see your reports and claims here.</p>
            <a href="claim.php" class="btn btn-cta">View Your Claims & Reports</a>
            <style>
                .btn-cta:hover {
                    color: #fff !important;
                }
            </style>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="py-4">
        <div class="container">
            <div class="row g-4">
                <div class="col-12 col-md-4 d-flex align-items-center justify-content-end">
                    <div class="stat-card text-center d-flex flex-column align-items-center justify-content-center h-100">
                        <div class="stat-number"><?php echo number_format($stats['lost_items']); ?></div>
                        <div class="stat-label">Total Lost Reported</div>
                    </div>
                </div>
                <div class="col-12 col-md-4 d-flex align-items-center justify-content-center">
                    <div class="stat-card text-center d-flex flex-column align-items-center justify-content-center h-100">
                        <div class="stat-number"><?php echo number_format($stats['found_items']); ?></div>
                        <div class="stat-label">Total Found Reported</div>
                    </div>
                </div>
                <div class="col-12 col-md-4 d-flex align-items-center justify-content-start">
                    <div class="stat-card text-center d-flex flex-column align-items-center justify-content-center h-100">
                        <div class="stat-number"><?php echo number_format($stats['my_claims']); ?></div>
                        <div class="stat-label">Total Claims</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
   

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <a href="userdash.php" class="footer-logo">Lost&Found</a>
                    <p>Helping reunite people with their lost belongings through community collaboration.</p>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-4 mb-md-0">
                    <div class="footer-links">
                        <h5>Quick Links</h5>
                        <ul>
                            <li><a href="userdash.php">Home</a></li>
                            <li><a href="lost.php">Report Lost</a></li>
                            <li><a href="found.php">Report Found</a></li>
                            <li><a href="search.php">Search</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-4 mb-md-0">
                    <div class="footer-links">
                        <h5>Account</h5>
                        <ul>
                            <li><a href="claim.php">My Claims & Reports</a></li>
                            <li><a href="userprofile.php">Profile</a></li>
                            <li><a href="#">Settings</a></li>
                            <li><a href="#">Help</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4">
                    <div class="footer-links">
                        <h5>Contact Us</h5>
                        <ul>
                            <li><i class="bi bi-envelope me-2"></i> help@lostfound.com</li>
                            <li><i class="bi bi-telephone me-2"></i> +1 (555) 123-4567</li>
                            <li><i class="bi bi-geo-alt me-2"></i> 123 Community St, City</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Lost&Found. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activate tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Set username in the header
        document.addEventListener('DOMContentLoaded', function() {
            const usernameDisplay = document.getElementById('usernameDisplay');
            if (usernameDisplay) {
                const name = '<?php echo addslashes($user_name); ?>';
                if (name) {
                    usernameDisplay.textContent = name;
                }
            }
        });
    </script>
</body>
</html>

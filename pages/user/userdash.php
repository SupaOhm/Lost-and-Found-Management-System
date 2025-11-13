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
    
    // Get user's claims count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM ClaimRequest WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $stats['my_claims'] = $result['count'];
    $stmt->closeCursor();
    
} catch (PDOException $e) {
    // Log error but don't break the page
    error_log("Dashboard stats error: " . $e->getMessage());
}
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
                        <p>Can't find something valuable? Report it here with details and photos to increase chances of
                            recovery.</p>
                    </a>
                </div>
                <div class="col-lg-4 col-md-6">
                    <a href="found.php" class="feature-card">
                        <div class="feature-icon found">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <h3>Report Found Item</h3>
                        <p>Found something that doesn't belong to you? Help reunite it with its owner by reporting it
                            here.</p>
                    </a>
                </div>
                <div class="col-lg-4 col-md-6">
                    <a href="search.php" class="feature-card">
                        <div class="feature-icon search">
                            <i class="bi bi-search"></i>
                        </div>
                        <h3>Search Database</h3>
                        <p>Browse through lost and found items in our database. Filter by category, location, or date.</p>
                    </a>
                </div>
            </div>
        </div>
    </section>

     <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Track Your Reports?</h2>
            <p class="cta-text">You can track your reports and claims here.</p>
            <a href="claim.php" class="btn btn-cta">View Your Claims & Reports</a>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="py-4">
        <div class="container">
            <div class="row g-4">
                <div class="col-12 col-md-4 mx-auto">
                    <div class="stat-card justify-content-center">
                        <div class="stat-number"><?php echo number_format($stats['lost_items']); ?></div>
                        <div class="stat-label">Lost Items Reported</div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mx-auto">
                    <div class="stat-card justify-content-center">
                        <div class="stat-number"><?php echo number_format($stats['found_items']); ?></div>
                        <div class="stat-label">Found Items Reported</div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mx-auto">
                    <div class="stat-card justify-content-center">
                        <div class="stat-number"><?php echo number_format($stats['my_claims']); ?></div>
                        <div class="stat-label">Claims</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Hero Section 
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">Lost Something Important?</h1>
                    <p class="hero-subtitle">Our community helps reunite people with their lost belongings. Report lost
                        items, found items, or search our database.</p>
                    <div class="hero-actions">
                        <a href="lost.php" class="btn btn-hero btn-hero-primary">Report Lost Item</a>
                        <a href="found.php" class="btn btn-hero btn-hero-secondary">Report Found Item</a>
                    </div>
                </div>
            </div>
        </div>
    </section> -->


   

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
        
        // Auto-hide alerts after 5 seconds
        window.setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
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

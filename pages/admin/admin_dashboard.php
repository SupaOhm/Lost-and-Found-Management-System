<?php
session_start();

// Redirect if not logged in as admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../admin_login.php');
    exit();
}

require_once('../../config/adminconfig.php');

// Get admin stats
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM user");
    $user_count = $stmt->fetch()['total'];
    
    // Total lost items
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM lostitem");
    $lost_count = $stmt->fetch()['total'];
    
    // Total found items
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM founditem");
    $found_count = $stmt->fetch()['total'];
    
    // Pending claims
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM claimrequest WHERE status = 'pending'");
    $pending_count = $stmt->fetch()['total'];
    
    // Total staff (from new staff table)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff");
    $staff_count = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    $user_count = $lost_count = $found_count = $pending_count = $staff_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/admin-style.css">
</head>
<body>
    <?php include('includes/header.php'); ?>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-sidebar-header">
                <h4>
                    <i class="bi bi-speedometer2"></i>
                </h4>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link active" href="admin_dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link" href="admin_claim.php">
                    <i class="bi bi-clipboard-check"></i> Manage Claims
                </a>
                <a class="nav-link" href="admin_report.php">
                    <i class="bi bi-file-earmark-text"></i> Manage Reports
                </a>
                <a class="nav-link" href="admin_users.php">
                    <i class="bi bi-people"></i> Manage Users
                </a>
                <a class="nav-link" href="admin_staff.php">
                    <i class="bi bi-person-gear"></i> Manage Staff
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="admin-main-content">
            
            <div class="admin-content">
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $user_count; ?></h4>
                                        <p class="mb-0">Total Users</p>
                                    </div>
                                    <i class="bi bi-people display-6 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $lost_count; ?></h4>
                                        <p class="mb-0">Lost Items</p>
                                    </div>
                                    <i class="bi bi-bag-x display-6 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $found_count; ?></h4>
                                        <p class="mb-0">Found Items</p>
                                    </div>
                                    <i class="bi bi-bag-check display-6 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $pending_count; ?></h4>
                                        <p class="mb-0">Pending Claims</p>
                                    </div>
                                    <i class="bi bi-clock display-6 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="row equal-height">
                    <div class="col-md-3 mb-3">
                        <a href="admin_claim.php" class="quick-link-card text-primary text-decoration-none">
                            <div class="card-body">
                                <i class="bi bi-clipboard-check"></i>
                                <h5>Manage Claims</h5>
                                <p class="text-muted">Review and process claim requests</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="admin_report.php" class="quick-link-card text-info text-decoration-none">
                            <div class="card-body">
                                <i class="bi bi-file-earmark-text"></i>
                                <h5>Manage Reports</h5>
                                <p class="text-muted">View all lost and found items</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="admin_users.php" class="quick-link-card text-success text-decoration-none">
                            <div class="card-body">
                                <i class="bi bi-people"></i>
                                <h5>Manage Users</h5>
                                <p class="text-muted">Oversee user accounts</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="admin_staff.php" class="quick-link-card text-secondary text-decoration-none">
                            <div class="card-body">
                                <i class="bi bi-person-gear"></i>
                                <h5>Manage Staff</h5>
                                <p class="text-muted">Manage staff accounts and permissions</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
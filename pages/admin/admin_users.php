<?php
require_once '../../config/adminconfig.php';

// Handle delete user
if (isset($_GET['delete_user'])) {
    $user_id = (int)$_GET['delete_user'];
    
    try {
        // Delete the user - trigger will handle cascade deletion
        $stmt = $pdo->prepare("DELETE FROM User WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $message = "User deleted successfully!";
        
        // Redirect to avoid resubmission
        header("Location: admin_users.php?success=" . urlencode($message));
        exit();
    } catch (PDOException $e) {
        $error = "Error deleting user: " . $e->getMessage();
    }
}

// Get all users
$users = $pdo->query("SELECT * FROM User ORDER BY created_at DESC")->fetchAll();

// Display success message from redirect
if (isset($_GET['success'])) {
    $message = $_GET['success'];
}

// Get user details if viewing
$user_details = null;
if (isset($_GET['view_user'])) {
    require_once '../../includes/functions.php';
    $user_id = (int)$_GET['view_user'];
    $stmt = $pdo->prepare("SELECT * FROM User WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_details = $stmt->fetch();
    if ($user_details && isset($user_details['phone'])) {
        $user_details['phone'] = decrypt_phone($user_details['phone']);
    }
    // Get user's lost items count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM LostItem WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $lost_count = $stmt->fetchColumn();
    // Get user's found items count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM FoundItem WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $found_count = $stmt->fetchColumn();
    // Get user's claims count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ClaimRequest WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $claims_count = $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Lost&Found</title>
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
                    <i class="bi bi bi-people"></i>
                </h4>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="admin_dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link" href="admin_claim.php">
                    <i class="bi bi-clipboard-check"></i> Manage Claims
                </a>
                <a class="nav-link" href="admin_report.php">
                    <i class="bi bi-file-earmark-text"></i> Manage Reports
                </a>
                <a class="nav-link active" href="admin_users.php">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage Users</h2>
                    <span class="badge bg-primary"><?php echo count($users); ?> Total Users</span>
                </div>

                <?php if (isset($message)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- User Details View -->
                <?php if ($user_details): ?>
                    <div class="admin-card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">User Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>User ID:</strong> #<?php echo $user_details['user_id']; ?></p>
                                    <p><strong>Username:</strong> <?php echo $user_details['username']; ?></p>
                                    <p><strong>Email:</strong> <?php echo $user_details['email']; ?></p>
                                    <p><strong>Phone:</strong> <?php echo $user_details['phone'] ?: 'Not provided'; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Joined Date:</strong> <?php echo date('M j, Y', strtotime($user_details['created_at'])); ?></p>
                                    <p><strong>Lost Items Posted:</strong> <span class="badge bg-danger"><?php echo $lost_count; ?></span></p>
                                    <p><strong>Found Items Posted:</strong> <span class="badge bg-success"><?php echo $found_count; ?></span></p>
                                    <p><strong>Claims Made:</strong> <span class="badge bg-warning"><?php echo $claims_count; ?></span></p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="admin_users.php" class="btn btn-secondary">Back to Users List</a>
                                <a href="?delete_user=<?php echo $user_details['user_id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this user? This will also delete all their items and claims.')">
                                    <i class="bi bi-trash"></i> Delete User Account
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Users List -->
                <div class="admin-card">
                    <div class="card-header">
                        <h5 class="mb-0">All Users</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($users) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>User ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Joined Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>#<?php echo $user['user_id']; ?></td>
                                            <td><?php echo $user['username']; ?></td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td>
                                                <?php
                                                    require_once '../../includes/functions.php';
                                                    $phone = '';
                                                    if (!empty($user['phone'])) {
                                                        try {
                                                            $phone = decrypt_phone($user['phone']);
                                                        } catch (Exception $e) {
                                                            $phone = 'N/A';
                                                        }
                                                    }
                                                    echo $phone ?: 'N/A';
                                                ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="?view_user=<?php echo $user['user_id']; ?>" 
                                                           class="btn btn-info"
                                                           data-bs-toggle="tooltip" 
                                                           title="View User Details">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="?delete_user=<?php echo $user['user_id']; ?>" 
                                                           class="btn btn-danger"
                                                           data-bs-toggle="tooltip" 
                                                           title="Delete User"
                                                           onclick="return confirm('Are you sure you want to delete this user?')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <h4>No Users Found</h4>
                                <p>No users have registered in the system yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
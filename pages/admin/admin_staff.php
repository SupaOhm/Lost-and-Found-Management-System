<?php
require_once '../../config/db.php';

// Handle delete staff
if (isset($_GET['delete_staff'])) {
    $staff_id = $_GET['delete_staff'];
    
    try {
        $pdo->query("DELETE FROM staff WHERE staff_id = $staff_id");
        $success = "Staff account deleted successfully!";
    } catch (Exception $e) {
        $error = "Error deleting staff: " . $e->getMessage();
    }
}

// Handle add staff
if (isset($_POST['add_staff'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    
    try {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT staff_id FROM staff WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            $error = "Username already exists!";
        } else {
            // Hash the password before storing
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO staff (username, password, email, full_name, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashedPassword, $email, $full_name, $phone]);
            $success = "Staff account created successfully!";
        }
    } catch (Exception $e) {
        $error = "Error creating staff: " . $e->getMessage();
    }
}

// Get all staff
try {
    $staff = $pdo->query("SELECT * FROM staff ORDER BY created_at DESC")->fetchAll();
    
    // Get counts
    $total_staff = count($staff);
    
} catch (PDOException $e) {
    $staff = [];
    $total_staff = 0;
    $error = "Error loading staff data: " . $e->getMessage();
}

// Get staff details if viewing
$staff_details = null;
if (isset($_GET['view_staff'])) {
    $staff_id = $_GET['view_staff'];
    $staff_details = $pdo->query("SELECT * FROM staff WHERE staff_id = $staff_id")->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - Admin Lost&Found</title>
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
                    <i class="bi bi-person-gear"></i>
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
                <a class="nav-link" href="admin_users.php">
                    <i class="bi bi-people"></i> Manage Users
                </a>
                <a class="nav-link active" href="admin_staff.php">
                    <i class="bi bi-person-gear"></i> Manage Staff
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="admin-main-content">
            <div class="admin-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage Staff Accounts</h2>
                    <div>
                        <span class="badge bg-primary me-2">Total: <?php echo $total_staff; ?></span>
                    </div>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Staff Details View -->
                <?php if ($staff_details): ?>
                    <div class="admin-card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">Staff Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Staff ID:</strong> #<?php echo $staff_details['staff_id']; ?></p>
                                    <p><strong>Username:</strong> <?php echo $staff_details['username']; ?></p>
                                    <p><strong>Full Name:</strong> <?php echo $staff_details['full_name'] ?: 'Not set'; ?></p>
                                    <p><strong>Email:</strong> <?php echo $staff_details['email']; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Phone:</strong> <?php echo $staff_details['phone'] ?: 'Not set'; ?></p>
                                    <p><strong>Account Created:</strong> <?php echo date('M j, Y', strtotime($staff_details['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="admin_staff.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Back to Staff List
                                </a>
                                <a href="?delete_staff=<?php echo $staff_details['staff_id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this staff account?')">
                                    <i class="bi bi-trash"></i> Delete Staff Account
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Add New Staff Form -->
                <div class="admin-card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-person-plus"></i> Add New Staff Member</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Username *</label>
                                        <input type="text" name="username" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Password *</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="full_name" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="add_staff" class="btn btn-success">
                                <i class="bi bi-person-plus"></i> Create Staff Account
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Staff List -->
                <div class="admin-card">
                    <div class="card-header">
                        <h5 class="mb-0">All Staff Accounts</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($staff) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Staff ID</th>
                                            <th>Username</th>
                                            <th>Full Name</th>
                                            <th>Email</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($staff as $staff_member): ?>
                                        <tr>
                                            <td>#<?php echo $staff_member['staff_id']; ?></td>
                                            <td><?php echo $staff_member['username']; ?></td>
                                            <td><?php echo $staff_member['full_name'] ?: 'N/A'; ?></td>
                                            <td><?php echo $staff_member['email']; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($staff_member['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="?view_staff=<?php echo $staff_member['staff_id']; ?>" 
                                                       class="btn btn-info"
                                                       data-bs-toggle="tooltip" 
                                                       title="View Staff Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="?delete_staff=<?php echo $staff_member['staff_id']; ?>" 
                                                       class="btn btn-danger"
                                                       data-bs-toggle="tooltip" 
                                                       title="Delete Staff"
                                                       onclick="return confirm('Are you sure you want to delete this staff account?')">
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
                                <i class="bi bi-person-gear"></i>
                                <h4>No Staff Accounts Found</h4>
                                <p>Use the form above to add new staff members.</p>
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
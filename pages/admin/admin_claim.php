<?php
session_start();
require_once '../../config/db.php';

// get admin id from session if available
$adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;

// Handle claim approval (use stored procedure ApproveClaim)
if (isset($_POST['approve_claim'])) {
    $claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : 0;
    
    try {
        $stmt = $pdo->prepare("CALL ApproveClaim(?, ?)");
        $stmt->execute([$claim_id, $adminId]);
        // Close cursor to allow next calls
        $stmt->closeCursor();
        $success = "Claim approved successfully!";
    } catch (Exception $e) {
        $error = "Error approving claim: " . $e->getMessage();
    }
}

// Handle claim rejection (use stored procedure RejectClaim)
if (isset($_POST['reject_claim'])) {
    $claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : 0;
    
    try {
        $stmt = $pdo->prepare("CALL RejectClaim(?, ?)");
        $stmt->execute([$claim_id, $adminId]);
        $stmt->closeCursor();
        $success = "Claim rejected successfully!";
    } catch (Exception $e) {
        $error = "Error rejecting claim: " . $e->getMessage();
    }
}

// Get pending claims
try {
    // Use stored procedure to fetch pending claims
    $stmt = $pdo->query("CALL ViewPendingClaims()");
    $pending_claims = $stmt->fetchAll();
    // Close cursor to free connection for further calls
    $stmt->closeCursor();

    // If the procedure returned no rows, fall back to a LEFT JOIN select with claim description
    if (empty($pending_claims)) {
        $fallbackSql = "SELECT c.claim_id, u.username AS requester, c.description AS claim_description, l.item_name AS lost_item, f.item_name AS found_item, c.status, c.claim_date
                        FROM ClaimRequest c
                        LEFT JOIN User u ON c.user_id = u.user_id
                        LEFT JOIN LostItem l ON c.lost_id = l.lost_id
                        LEFT JOIN FoundItem f ON c.found_id = f.found_id
                        WHERE c.status = 'pending'
                        ORDER BY c.claim_date DESC";
        $stmt = $pdo->query($fallbackSql);
        $pending_claims = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $pending_claims = [];
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Claims - Admin Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/admin-style.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-sidebar-header">
                <h4>
                    <i class="bi bi-person-workspace"></i> Admin Panel
                </h4>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="admin_dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link active" href="admin_claim.php">
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
            <div class="admin-header">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Manage Claims</li>
                    </ol>
                </nav>
            </div>
            <div class="admin-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage Claims</h2>
                    <span class="badge bg-warning text-dark"><?php echo count($pending_claims); ?> Pending Claims</span>
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

                <div class="admin-card">
                    <div class="card-header">
                        <h5 class="mb-0">Pending Claims</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($pending_claims) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Claim ID</th>
                                            <th>Claimant</th>
                                            <th>Claim Details</th>
                                            <th>Date Claimed</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_claims as $claim): ?>
                                            <tr>
                                                <td>#<?php echo $claim['claim_id']; ?></td>
                                                <td><?php echo htmlspecialchars($claim['requester']); ?></td>
                                                <td>
                                                    <?php 
                                                    if (!empty($claim['claim_description'])) {
                                                        echo '<span class="text-muted">' . htmlspecialchars($claim['claim_description']) . '</span>';
                                                    } else {
                                                        echo '<span class="text-muted">No details provided.</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($claim['claim_date'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="claim_id" value="<?php echo $claim['claim_id']; ?>">
                                                            <button type="submit" 
                                                                    name="approve_claim" 
                                                                    class="btn btn-success"
                                                                    title="Approve Claim">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="claim_id" value="<?php echo $claim['claim_id']; ?>">
                                                            <button type="submit" 
                                                                    name="reject_claim" 
                                                                    class="btn btn-danger"
                                                                    title="Reject Claim">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-clipboard-check"></i>
                                <h4 class="text-muted mt-3">No Pending Claims</h4>
                                <p class="text-muted">All claims have been processed.</p>
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
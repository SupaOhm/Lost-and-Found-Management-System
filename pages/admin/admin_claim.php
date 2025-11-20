<?php
session_start();
require_once '../../config/adminconfig.php';

// get admin id from session if available
$adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;

// Handle claim approval (use stored procedure ApproveClaim)
if (isset($_POST['approve_claim'])) {
    $claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : 0;
    
    try {
        // Approve the claim
        $stmt = $pdo->prepare("CALL ApproveClaim(?, ?, ?)");
        $stmt->execute([$claim_id, $adminId, 'admin']);
        $stmt->closeCursor();

        // Get found_id for this claim
        $foundIdStmt = $pdo->prepare("SELECT found_id FROM ClaimRequest WHERE claim_id = ?");
        $foundIdStmt->execute([$claim_id]);
        $foundId = $foundIdStmt->fetchColumn();
        $foundIdStmt->closeCursor();

        // Reject all other pending claims for the same found item
        if ($foundId) {
            $rejectStmt = $pdo->prepare("UPDATE ClaimRequest SET status = 'rejected', admin_approver_id = ?, approved_date = NOW() WHERE found_id = ? AND claim_id != ? AND status = 'pending'");
            $rejectStmt->execute([$adminId, $foundId, $claim_id]);
            $rejectStmt->closeCursor();
        }

        $success = "Claim approved successfully!";
    } catch (Exception $e) {
        $error = "Error approving claim: " . $e->getMessage();
    }
}

// Handle claim rejection (use stored procedure RejectClaim)
if (isset($_POST['reject_claim'])) {
    $claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : 0;
    
    try {
        $stmt = $pdo->prepare("CALL RejectClaim(?, ?, ?)");
        $stmt->execute([$claim_id, $adminId, 'admin']);
        $stmt->closeCursor();
        $success = "Claim rejected successfully!";
    } catch (Exception $e) {
        $error = "Error rejecting claim: " . $e->getMessage();
    }
}

// Get pending claims
try {
    // Use stored procedure to fetch pending claims
    $stmt = $pdo->query("CALL ViewPendingClaimsWithFoundDetails()");
    $pending_claims = $stmt->fetchAll();
    // Close cursor to free connection for further calls
    $stmt->closeCursor();
} catch (PDOException $e) {
    $pending_claims = [];
    $error = "Database error: " . $e->getMessage();
}

// Get processed claims (approved and rejected)
try {
    $stmt = $pdo->query("CALL ViewProcessedClaimsWithDetails()");
    $processed_claims = $stmt->fetchAll();
    $stmt->closeCursor();
} catch (PDOException $e) {
    $processed_claims = [];
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
    <?php include('includes/header.php'); ?>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-sidebar-header">
                <h4>
                    <i class="bi bi-clipboard-check"></i>
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
            <div class="admin-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage Claims</h2>
                    <div>
                        <span class="badge bg-warning text-dark me-2"><?php echo count($pending_claims); ?> Pending</span>
                        <span class="badge bg-secondary"><?php echo count($processed_claims); ?> Processed</span>
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
                                            <th>Found Item</th>
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
                                                    <div class="small">
                                                        <strong><?php echo htmlspecialchars($claim['item_name']); ?></strong><br>
                                                        <span class="text-muted">Category:</span> <?php echo htmlspecialchars($claim['category']); ?><br>
                                                        <span class="text-muted">Location:</span> <?php echo htmlspecialchars($claim['location']); ?><br>
                                                        <span class="text-muted">Date:</span> <?php echo date('M j, Y', strtotime($claim['found_date'])); ?><br>
                                                        <span class="text-muted">Description:</span> <?php echo htmlspecialchars($claim['description']); ?>
                                                    </div>
                                                </td>
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

                <!-- Processed Claims Section -->
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Processed Claims History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($processed_claims) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Claim ID</th>
                                            <th>Claimant</th>
                                            <th>Claim Details</th>
                                            <th>Found Item</th>
                                            <th>Status</th>
                                            <th>Processed By</th>
                                            <th>Date Processed</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($processed_claims as $claim): 
                                            $status_badge = ($claim['status'] == 'approved') ? 'bg-success' : 'bg-danger';
                                            $status_icon = ($claim['status'] == 'approved') ? 'check-circle-fill' : 'x-circle-fill';
                                        ?>
                                            <tr>
                                                <td>#<?php echo $claim['claim_id']; ?></td>
                                                <td><?php echo htmlspecialchars($claim['requester']); ?></td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php 
                                                        if (!empty($claim['claim_description'])) {
                                                            $desc = htmlspecialchars($claim['claim_description']);
                                                            echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                                                        } else {
                                                            echo 'No details provided.';
                                                        }
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="small">
                                                        <strong><?php echo htmlspecialchars($claim['item_name']); ?></strong><br>
                                                        <span class="text-muted"><?php echo htmlspecialchars($claim['category']); ?> • <?php echo htmlspecialchars($claim['location']); ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $status_badge; ?>">
                                                        <i class="bi bi-<?php echo $status_icon; ?>"></i>
                                                        <?php echo ucfirst($claim['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="small">
                                                        <strong><?php echo htmlspecialchars($claim['approver_name']); ?></strong><br>
                                                        <span class="badge badge-sm <?php echo isset($claim['approver_type']) && $claim['approver_type'] == 'admin' ? 'bg-primary' : 'bg-info'; ?>">
                                                            <?php echo isset($claim['approver_type']) ? ucfirst($claim['approver_type']) : 'N/A'; ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($claim['approved_date']) {
                                                        echo date('M j, Y', strtotime($claim['approved_date'])) . '<br>';
                                                        echo '<small class="text-muted">' . date('g:i A', strtotime($claim['approved_date'])) . '</small>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-clock-history"></i>
                                <h4 class="text-muted mt-3">No Processed Claims</h4>
                                <p class="text-muted">No claims have been approved or rejected yet.</p>
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
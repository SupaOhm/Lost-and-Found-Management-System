<?php
session_start();
require_once '../../config/staffconfig.php';


// Handle delete actions
if (isset($_GET['delete'])) {
    $type = $_GET['type'];
    $id = $_GET['id'];
    
    try {
        if ($type === 'lost') {
            // Delete the lost item (no claims are associated with lost items)
            $stmt = $pdo->prepare("DELETE FROM LostItem WHERE lost_id = ?");
            $stmt->execute([$id]);
            $success = "Lost item deleted successfully!";
            
        } elseif ($type === 'found') {
            // First delete any claims associated with this found item
            $stmt = $pdo->prepare("DELETE FROM ClaimRequest WHERE found_id = ?");
            $stmt->execute([$id]);
            
            // Then delete the found item
            $stmt = $pdo->prepare("DELETE FROM FoundItem WHERE found_id = ?");
            $stmt->execute([$id]);
            $success = "Found item deleted successfully!";
        }
        
        // Redirect to avoid resubmission
        header("Location: staff_report.php?success=" . urlencode($success));
        exit();
        
    } catch (Exception $e) {
        $error = "Error deleting item: " . $e->getMessage();
    }
}

// Handle view report - show details in a section
$viewing_report = null;
if (isset($_GET['view_report'])) {
    $view_id_parts = explode('_', $_GET['view_report']);
    if (count($view_id_parts) === 2) {
        $view_type = $view_id_parts[0];
        $view_item_id = (int)$view_id_parts[1];
        
        if ($view_type === 'lost') {
            $stmt = $pdo->prepare("SELECT * FROM LostItem WHERE lost_id = ?");
            $stmt->execute([$view_item_id]);
            $viewing_report = $stmt->fetch();
            if ($viewing_report) {
                $viewing_report['type'] = 'lost';
            }
        } elseif ($view_type === 'found') {
            $stmt = $pdo->prepare("SELECT * FROM FoundItem WHERE found_id = ?");
            $stmt->execute([$view_item_id]);
            $viewing_report = $stmt->fetch();
            if ($viewing_report) {
                $viewing_report['type'] = 'found';
            }
        }
    }
}

// Display success message from redirect
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Get filter values
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Get all reports (lost and found items) with filters
try {
    $reports = [];
    
    // Build WHERE clause for filters
    $where_conditions = [];
    
    if ($filter_status) {
        if ($filter_status === 'pending') {
            $where_conditions[] = "(status = 'pending' OR status = 'available')";
        } elseif ($filter_status === 'claimed') {
            $where_conditions[] = "(status = 'claimed' OR status = 'returned')";
        }
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get lost items (if filter allows)
    if ($filter_type === '' || $filter_type === 'lost') {
        $sql = "SELECT 'lost' as type, lost_id as id, item_name, description, category, location, 
                   lost_date as item_date, status, created_at, user_id
            FROM LostItem 
            $where_clause
            ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        $lost_items = $stmt->fetchAll();
        $reports = array_merge($reports, $lost_items);
    }
    
    // Get found items (if filter allows)
    if ($filter_type === '' || $filter_type === 'found') {
        $sql = "SELECT 'found' as type, found_id as id, item_name, description, category, location, 
                   found_date as item_date, status, created_at, user_id
            FROM FoundItem 
            $where_clause
            ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        $found_items = $stmt->fetchAll();
        $reports = array_merge($reports, $found_items);
    }
    
    // Sort by creation date
    usort($reports, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
} catch (PDOException $e) {
    $reports = [];
}

// Function to get username by ID
function get_username_by_id($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT username FROM User WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn() ?: 'Unknown User';
    } catch (PDOException $e) {
        return 'Error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reports - Staff Lost&Found</title>
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
                    <i class="bi bi-file-earmark-text"></i>
                </h4>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="staff_dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link" href="staff_claim.php">
                    <i class="bi bi-clipboard-check"></i> Manage Claims
                </a>
                <a class="nav-link active" href="staff_report.php">
                    <i class="bi bi-file-earmark-text"></i> Manage Reports
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="admin-main-content">
            <div class="admin-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage Reports</h2>
                    <span class="badge bg-primary"><?php echo count($reports); ?> Total Reports</span>
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

                <!-- Active Filters Display -->
                <?php if ($filter_type || $filter_status): ?>
                    <div class="alert alert-info alert-dismissible fade show">
                        <strong>Active Filters:</strong>
                        <?php if ($filter_type): ?>
                            <span class="badge bg-primary"><?php echo ucfirst($filter_type); ?> Items</span>
                        <?php endif; ?>
                        <?php if ($filter_status): ?>
                            <span class="badge bg-primary"><?php echo ucfirst($filter_status); ?></span>
                        <?php endif; ?>
                        <a href="staff_report.php" class="btn btn-sm btn-outline-secondary ms-2">Clear Filters</a>
                    </div>
                <?php endif; ?>

                <!-- View Report Details Section -->
                <?php if ($viewing_report): 
                    $type_text = ($viewing_report['type'] == 'lost') ? 'LOST' : 'FOUND';
                    $status_badge = ($viewing_report['status'] == 'pending' || $viewing_report['status'] == 'available') ? 'bg-warning' : 'bg-info';
                    $status_text = ($viewing_report['type'] == 'lost') ? 
                        ($viewing_report['status'] == 'pending' ? 'Pending' : 'Claimed') : 
                        ($viewing_report['status'] == 'available' ? 'Available' : 'Returned');
                ?>
                    <div class="admin-card mb-4">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Item Details</h5>
                            <a href="staff_report.php" class="btn btn-light btn-sm">Close</a>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Item ID:</strong> #<?php echo $viewing_report['type'] == 'lost' ? $viewing_report['lost_id'] : $viewing_report['found_id']; ?></p>
                                    <p><strong>Type:</strong> <span class="badge <?php echo $viewing_report['type'] == 'lost' ? 'bg-danger' : 'bg-success'; ?>"><?php echo $type_text; ?></span></p>
                                    <p><strong>Item Name:</strong> <?php echo htmlspecialchars($viewing_report['item_name']); ?></p>
                                    <p><strong>Category:</strong> <span class="badge bg-secondary"><?php echo htmlspecialchars($viewing_report['category']); ?></span></p>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($viewing_report['location']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Status:</strong> <span class="badge <?php echo $status_badge; ?>"><?php echo $status_text; ?></span></p>
                                    <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($viewing_report['type'] == 'lost' ? $viewing_report['lost_date'] : $viewing_report['found_date'])); ?></p>
                                    <p><strong>Reported By:</strong> <?php echo htmlspecialchars(get_username_by_id($pdo, $viewing_report['user_id'])); ?></p>
                                    <p><strong>Reported On:</strong> <?php echo date('F j, Y g:i A', strtotime($viewing_report['created_at'])); ?></p>
                                </div>
                            </div>
                            <?php if (!empty($viewing_report['description'])): ?>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <strong>Description:</strong>
                                        <p class="mt-2 p-3 bg-light rounded"><?php echo htmlspecialchars($viewing_report['description']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="admin-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">All Item Reports</h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($reports) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Report ID</th>
                                            <th>Type</th>
                                            <th>Item Name</th>
                                            <th>Description</th>
                                            <th>Category</th>
                                            <th>Location</th>
                                            <th>Reported By</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reports as $report): 
                                            $badge_color = ($report['type'] == 'lost') ? 'bg-danger' : 'bg-success';
                                            $type_text = ($report['type'] == 'lost') ? 'LOST' : 'FOUND';
                                            $status_badge = ($report['status'] == 'pending' || $report['status'] == 'available') ? 'bg-warning' : 'bg-info';
                                            $status_text = ($report['type'] == 'lost') ? 
                                                ($report['status'] == 'pending' ? 'Pending' : 'Claimed') : 
                                                ($report['status'] == 'available' ? 'Available' : 'Returned');
                                        ?>
                                            <tr>
                                                <td>#<?php echo $report['id']; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $badge_color; ?>">
                                                        <?php echo $type_text; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($report['item_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php 
                                                        $description = htmlspecialchars($report['description']);
                                                        echo strlen($description) > 50 ? substr($description, 0, 50) . '...' : $description;
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($report['category']); ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($report['location']); ?></td>
                                                <td><?php echo htmlspecialchars(get_username_by_id($pdo, $report['user_id'])); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($report['item_date'])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $status_badge; ?>">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                                <td class="table-actions">
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <!-- View Button - Direct link to view page -->
                                                        <a href="?view_report=<?php echo $report['type'] . '_' . $report['id']; ?>" 
                                                           class="btn btn-info"
                                                           title="View Item Details">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        
                                                        <!-- Delete Button - Direct confirmation link -->
                                                        <a href="?delete=true&type=<?php echo $report['type']; ?>&id=<?php echo $report['id']; ?>" 
                                                           class="btn btn-danger"
                                                           title="Delete Item"
                                                           onclick="return confirm('Are you sure you want to delete this <?php echo $report['type']; ?> item? This action cannot be undone.')">
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
                                <i class="bi bi-file-earmark-text"></i>
                                <h4>No Reports Found</h4>
                                <p><?php echo ($filter_type || $filter_status) ? 'No reports match your filter criteria.' : 'No lost or found items have been reported yet.'; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Reports</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="GET" id="filterForm">
                        <div class="mb-3">
                            <label class="form-label">Item Type</label>
                            <select name="type" class="form-select">
                                <option value="" <?php echo $filter_type === '' ? 'selected' : ''; ?>>All Types</option>
                                <option value="lost" <?php echo $filter_type === 'lost' ? 'selected' : ''; ?>>Lost Items</option>
                                <option value="found" <?php echo $filter_type === 'found' ? 'selected' : ''; ?>>Found Items</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="" <?php echo $filter_status === '' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending/Available</option>
                                <option value="claimed" <?php echo $filter_status === 'claimed' ? 'selected' : ''; ?>>Claimed/Returned</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="applyFilters()">Apply Filters</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function applyFilters() {
            document.getElementById('filterForm').submit();
        }
    </script>
</body>
</html>
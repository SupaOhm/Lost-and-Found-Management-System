<?php
session_start();
require_once '../../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /Lost-Found/pages/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'] ?? 'User';

// Initialize variables
$claims = [];
$reportedItems = [];
$error = '';

// Pagination settings
$claimsPerPage = 5;
$reportsPerPage = 5;
$claimsPage = isset($_GET['claims_page']) ? (int)$_GET['claims_page'] : 1;
$reportsPage = isset($_GET['reports_page']) ? (int)$_GET['reports_page'] : 1;

try {
    // Database connection is already available from db.php
    $pdo = $pdo;
    
    // Get user's claims with pagination
    $claimsOffset = ($claimsPage - 1) * $claimsPerPage;
    $claimsStmt = $pdo->prepare("
        SELECT c.*, 
               CASE 
                   WHEN c.item_type = 'lost' THEN l.item_name 
                   ELSE f.item_name 
               END as item_name,
               CASE 
                   WHEN c.item_type = 'lost' THEN l.image_path 
                   ELSE f.image_path 
               END as item_image,
               u.full_name as owner_name,
               u.email as owner_email
        FROM claims c
        LEFT JOIN lost_items l ON c.item_type = 'lost' AND c.item_id = l.item_id
        LEFT JOIN found_items f ON c.item_type = 'found' AND c.item_id = f.item_id
        LEFT JOIN users u ON u.user_id = CASE 
                                          WHEN c.item_type = 'lost' THEN l.user_id 
                                          ELSE f.user_id 
                                        END
        WHERE c.claimant_id = ?
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $claimsStmt->execute([$userId, $claimsPerPage, $claimsOffset]);
    $claims = $claimsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total claims count for pagination
    $totalClaimsStmt = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE claimant_id = ?");
    $totalClaimsStmt->execute([$userId]);
    $totalClaims = $totalClaimsStmt->fetchColumn();
    $totalClaimsPages = ceil($totalClaims / $claimsPerPage);
    
    // Get user's reported items with pagination
    $reportsOffset = ($reportsPage - 1) * $reportsPerPage;
    
    // Get lost items reported by user
    $lostItemsStmt = $pdo->prepare("
        SELECT 'lost' as item_type, item_id, item_name, description, image_path, status, created_at
        FROM lost_items 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $lostItemsStmt->bindValue(1, $userId, PDO::PARAM_INT);
    $lostItemsStmt->bindValue(2, $reportsPerPage, PDO::PARAM_INT);
    $lostItemsStmt->bindValue(3, $reportsOffset, PDO::PARAM_INT);
    $lostItemsStmt->execute();
    $lostItems = $lostItemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get found items reported by user
    $foundItemsStmt = $pdo->prepare("
        SELECT 'found' as item_type, item_id, item_name, description, image_path, status, created_at
        FROM found_items 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $foundItemsStmt->bindValue(1, $userId, PDO::PARAM_INT);
    $foundItemsStmt->bindValue(2, $reportsPerPage, PDO::PARAM_INT);
    $foundItemsStmt->bindValue(3, $reportsOffset, PDO::PARAM_INT);
    $foundItemsStmt->execute();
    $foundItems = $foundItemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine lost and found items
    $reportedItems = array_merge($lostItems, $foundItems);
    
    // Get total reported items count for pagination
    $totalLostStmt = $pdo->prepare("SELECT COUNT(*) FROM lost_items WHERE user_id = ?");
    $totalFoundStmt = $pdo->prepare("SELECT COUNT(*) FROM found_items WHERE user_id = ?");
    $totalLostStmt->execute([$userId]);
    $totalFoundStmt->execute([$userId]);
    $totalReportedItems = $totalLostStmt->fetchColumn() + $totalFoundStmt->fetchColumn();
    $totalReportsPages = ceil($totalReportedItems / $reportsPerPage);
    
} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    $error = 'An error occurred while loading your claims and reports. Please try again later.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Claims & Reports - Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Lost-Found/assets/style.css">
    <style>
        .list-section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1.25rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f2f5;
        }
        .scrollable-box {
            max-height: 500px;
            overflow-y: auto;
            margin-bottom: 1rem;
            padding-right: 0.5rem;
        }
        .scrollable-box::-webkit-scrollbar {
            width: 6px;
        }
        .scrollable-box::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .scrollable-box::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        .scrollable-box::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        .claim-card, .report-card {
            background: white;
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .claim-card:hover, .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 1rem;
        }
        .item-details {
            flex: 1;
        }
        .item-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        .item-meta {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            text-transform: capitalize;
            display: inline-block;
            margin-top: 0.5rem;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-available {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .pagination-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }
        .page-info {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 2.5rem;
            color: #dee2e6;
            margin-bottom: 1rem;
            display: block;
        }
    </style>
</head>
<body>
    <header class="app-header shadow-sm">
        <div class="container py-3">
            <div class="d-flex justify-content-between align-items-center">
                <a href="userdash.php" class="logo">
                    <i class="bi bi-search-heart logo-icon"></i>
                    Lost&Found
                </a>
                <div class="d-flex align-items-center gap-3">
                    <a href="userdash.php" class="dashboard-btn d-flex align-items-center gap-2">
                        <i class="bi bi-house-door-fill"></i> Dashboard
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
                            <span id="usernameDisplay" class="d-none d-md-inline"><?php echo htmlspecialchars($userName); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="userprofile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item active" href="claim.php"><i class="bi bi-clipboard-check me-2"></i>My Claims</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container py-5">
        <a href="userdash.php" class="btn btn-link text-dark text-decoration-none mb-4">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="row g-4">
            <!-- My Claim Requests -->
            <div class="col-lg-6">
                <h3 class="list-section-title">My Claim Requests</h3>
                <div class="bg-light p-3 p-md-4 rounded shadow-sm h-100">
                    <?php if (empty($claims)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>You haven't made any claims yet.</p>
                            <a href="search.php" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i> Browse Items
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="scrollable-box" id="claimRequests">
                            <?php foreach ($claims as $claim): ?>
                                <div class="claim-card d-flex">
                                    <?php if (!empty($claim['item_image'])): ?>
                                        <img src="/Lost-Found/<?php echo htmlspecialchars($claim['item_image']); ?>" 
                                             class="item-image" 
                                             alt="<?php echo htmlspecialchars($claim['item_name']); ?>">
                                    <?php else: ?>
                                        <div class="item-image bg-light d-flex align-items-center justify-content-center">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="item-details">
                                        <h5 class="item-title"><?php echo htmlspecialchars($claim['item_name']); ?></h5>
                                        <div class="item-meta">
                                            <div><i class="bi bi-tag-fill me-1"></i> <?php echo ucfirst($claim['item_type']); ?> Item</div>
                                            <div><i class="bi bi-calendar3 me-1"></i> <?php echo date('M j, Y', strtotime($claim['created_at'])); ?></div>
                                            <?php if (!empty($claim['owner_name'])): ?>
                                                <div><i class="bi bi-person-fill me-1"></i> Owner: <?php echo htmlspecialchars($claim['owner_name']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="status-badge status-<?php echo strtolower($claim['status']); ?>">
                                            <?php echo ucfirst($claim['status']); ?>
                                        </span>
                                        <?php if (!empty($claim['admin_notes'])): ?>
                                            <div class="mt-2 small text-muted">
                                                <strong>Note:</strong> <?php echo htmlspecialchars($claim['admin_notes']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($totalClaimsPages > 1): ?>
                            <div class="pagination-container">
                                <a href="?claims_page=<?php echo max(1, $claimsPage - 1); ?>#claimRequests" 
                                   class="btn btn-sm btn-outline-secondary <?php echo $claimsPage <= 1 ? 'disabled' : ''; ?>">
                                    Previous
                                </a>
                                <span class="page-info">Page <?php echo $claimsPage; ?> of <?php echo $totalClaimsPages; ?></span>
                                <a href="?claims_page=<?php echo min($totalClaimsPages, $claimsPage + 1); ?>#claimRequests" 
                                   class="btn btn-sm btn-outline-secondary <?php echo $claimsPage >= $totalClaimsPages ? 'disabled' : ''; ?>">
                                    Next
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Reported Items -->
            <div class="col-lg-6">
                <h3 class="list-section-title">My Reported Items</h3>
                <div class="bg-light p-3 p-md-4 rounded shadow-sm h-100">
                    <?php if (empty($reportedItems)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>You haven't reported any items yet.</p>
                            <div class="mt-3">
                                <a href="lost.php" class="btn btn-primary me-2">
                                    <i class="bi bi-plus-circle me-1"></i> Report Lost Item
                                </a>
                                <a href="found.php" class="btn btn-outline-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Report Found Item
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="scrollable-box" id="reportedItems">
                            <?php foreach ($reportedItems as $item): ?>
                                <div class="report-card d-flex">
                                    <?php if (!empty($item['image_path'])): ?>
                                        <img src="/Lost-Found/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                             class="item-image" 
                                             alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                                    <?php else: ?>
                                        <div class="item-image bg-light d-flex align-items-center justify-content-center">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="item-details">
                                        <h5 class="item-title"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                                        <div class="item-meta">
                                            <div><i class="bi bi-tag-fill me-1"></i> <?php echo ucfirst($item['item_type']); ?> Item</div>
                                            <div><i class="bi bi-calendar3 me-1"></i> <?php echo date('M j, Y', strtotime($item['created_at'])); ?></div>
                                            <div class="text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($item['description']); ?>">
                                                <?php 
                                                $shortDesc = strlen($item['description']) > 50 
                                                    ? substr($item['description'], 0, 50) . '...' 
                                                    : $item['description'];
                                                echo htmlspecialchars($shortDesc);
                                                ?>
                                            </div>
                                        </div>
                                        <span class="status-badge status-<?php echo strtolower($item['status']); ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                        <a href="item_detail.php?type=<?php echo $item['item_type']; ?>&id=<?php echo $item['item_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary mt-2 d-inline-block">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($totalReportsPages > 1): ?>
                            <div class="pagination-container">
                                <a href="?reports_page=<?php echo max(1, $reportsPage - 1); ?>#reportedItems" 
                                   class="btn btn-sm btn-outline-secondary <?php echo $reportsPage <= 1 ? 'disabled' : ''; ?>">
                                    Previous
                                </a>
                                <span class="page-info">Page <?php echo $reportsPage; ?> of <?php echo $totalReportsPages; ?></span>
                                <a href="?reports_page=<?php echo min($totalReportsPages, $reportsPage + 1); ?>#reportedItems" 
                                   class="btn btn-sm btn-outline-secondary <?php echo $reportsPage >= $totalReportsPages ? 'disabled' : ''; ?>">
                                    Next
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Handle scroll position after pagination
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('claims_page')) {
                document.getElementById('claimRequests').scrollIntoView({ behavior: 'smooth' });
            } else if (urlParams.has('reports_page')) {
                document.getElementById('reportedItems').scrollIntoView({ behavior: 'smooth' });
            }
        });
    </script>
</body>
</html>

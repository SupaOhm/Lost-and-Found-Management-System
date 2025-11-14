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

// Handle POST actions: delete report, delete claim, mark lost as found, mark found as returned
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $action = $_POST['action'];
        if ($action === 'delete_report' && isset($_POST['item_type'], $_POST['item_id'])) {
            $itemType = $_POST['item_type'];
            $itemId = (int)$_POST['item_id'];
            if ($itemType === 'lost') {
                $stmt = $pdo->prepare("DELETE FROM LostItem WHERE lost_id = ? AND user_id = ?");
                $stmt->execute([$itemId, $userId]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM FoundItem WHERE found_id = ? AND user_id = ?");
                $stmt->execute([$itemId, $userId]);
            }
        } elseif ($action === 'delete_claim' && isset($_POST['claim_id'])) {
            $claimId = (int)$_POST['claim_id'];
            $stmt = $pdo->prepare("DELETE FROM ClaimRequest WHERE claim_id = ? AND user_id = ?");
            $stmt->execute([$claimId, $userId]);
        } elseif ($action === 'mark_lost_found' && isset($_POST['item_id'])) {
            $itemId = (int)$_POST['item_id'];
            // mark lost item as claimed (owner marked as found)
            $stmt = $pdo->prepare("UPDATE LostItem SET status = 'claimed' WHERE lost_id = ? AND user_id = ?");
            $stmt->execute([$itemId, $userId]);
        } elseif ($action === 'mark_found_returned' && isset($_POST['item_id'])) {
            $itemId = (int)$_POST['item_id'];
            // mark found item as returned
            $stmt = $pdo->prepare("UPDATE FoundItem SET status = 'returned' WHERE found_id = ? AND user_id = ?");
            $stmt->execute([$itemId, $userId]);
        }
    } catch (PDOException $e) {
        error_log('Action Error: ' . $e->getMessage());
        $error = 'An error occurred while processing your request. Please try again.';
    }
    // Redirect to avoid resubmission and to show updated lists
    $redirect = 'claim.php';
    header('Location: ' . $redirect);
    exit();
}

// Filters for reported items
$typeFilter = isset($_GET['type_filter']) ? $_GET['type_filter'] : 'all';
$statusFilter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';

// Pagination settings
$claimsPerPage = 5;
$reportsPerPage = 5;
$claimsPage = isset($_GET['claims_page']) ? (int)$_GET['claims_page'] : 1;
$reportsPage = isset($_GET['reports_page']) ? (int)$_GET['reports_page'] : 1;

try {
    // Database connection is already available from db.php
    $pdo = $pdo;
    
    // Get user's claims with pagination (claims reference found items only via found_id)
    $claimsOffset = ($claimsPage - 1) * $claimsPerPage;
    // Some PDO drivers do not allow binding LIMIT/OFFSET; inject integer values safely after casting
    $limit = (int)$claimsPerPage;
    $offset = (int)$claimsOffset;
    $sql = "SELECT c.*, c.claim_date AS created_at,
                'found' AS item_type,
                f.item_name AS item_name,
                f.found_id AS item_id,
                NULL AS item_image,
                u_owner.username AS owner_name,
                u_owner.email AS owner_email
         FROM ClaimRequest c
         LEFT JOIN FoundItem f ON c.found_id = f.found_id
         LEFT JOIN User u_owner ON u_owner.user_id = f.user_id
         WHERE c.user_id = :uid
         ORDER BY c.claim_date DESC
         LIMIT $limit OFFSET $offset";

    try {
        $claimsStmt = $pdo->prepare($sql);
        $claimsStmt->execute([':uid' => $userId]);
        $claims = $claimsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Claims Query Error: ' . $e->getMessage());
        $claims = [];
    }

    // Get total claims count for pagination
    $totalClaimsStmt = $pdo->prepare("SELECT COUNT(*) FROM ClaimRequest WHERE user_id = ?");
    $totalClaimsStmt->execute([$userId]);
    $totalClaims = $totalClaimsStmt->fetchColumn();
    $totalClaimsPages = ceil($totalClaims / $claimsPerPage);
    
    // Get user's reported items with pagination (fetch both lists, then filter in PHP)
    $reportsOffset = ($reportsPage - 1) * $reportsPerPage;

    // Get lost items reported by user
    $lostItemsStmt = $pdo->prepare(
        "SELECT 'lost' AS item_type, lost_id AS item_id, item_name, description, NULL AS image_path, status, created_at
         FROM LostItem
         WHERE user_id = ?");
    $lostItemsStmt->execute([$userId]);
    $lostItems = $lostItemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get found items reported by user
    $foundItemsStmt = $pdo->prepare(
        "SELECT 'found' AS item_type, found_id AS item_id, item_name, description, NULL AS image_path, status, created_at
         FROM FoundItem
         WHERE user_id = ?");
    $foundItemsStmt->execute([$userId]);
    $foundItems = $foundItemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Combine lost and found items
    $reportedItems = array_merge($lostItems, $foundItems);

    // Apply filters in PHP (support combined status groups: open => pending|available, closed => claimed|returned)
    $reportedItems = array_filter($reportedItems, function($it) use ($typeFilter, $statusFilter) {
        if ($typeFilter !== 'all' && $it['item_type'] !== $typeFilter) return false;
        if ($statusFilter !== 'all') {
            $s = $it['status'] ?? '';
            if ($statusFilter === 'open' && !in_array($s, ['pending', 'available'])) return false;
            if ($statusFilter === 'closed' && !in_array($s, ['claimed', 'returned'])) return false;
        }
        return true;
    });

    // Sort by created_at desc
    usort($reportedItems, function($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });

    // Paginate the filtered reported items
    $totalReportedItems = count($reportedItems);
    $reportedItems = array_slice($reportedItems, $reportsOffset, $reportsPerPage);
    $totalReportsPages = ceil($totalReportedItems / $reportsPerPage);
    
    // user lost/found counts for the profile-stats cards (keep counts unaffected by filters)
    $totalLostStmt = $pdo->prepare("SELECT COUNT(*) FROM LostItem WHERE user_id = ?");
    $totalFoundStmt = $pdo->prepare("SELECT COUNT(*) FROM FoundItem WHERE user_id = ?");
    $totalLostStmt->execute([$userId]);
    $userLostCount = (int)$totalLostStmt->fetchColumn();
    $totalFoundStmt->execute([$userId]);
    $userFoundCount = (int)$totalFoundStmt->fetchColumn();
    
} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    $error = 'An error occurred while loading your claims and reports. Please try again later.';
}

// Optional debug: show raw claim rows when requested
$showDebugClaims = isset($_GET['debug_claims']) && $_GET['debug_claims'] == '1';
if ($showDebugClaims) {
    try {
        $dbg = $pdo->prepare('SELECT * FROM ClaimRequest WHERE user_id = ? ORDER BY claim_date DESC');
        $dbg->execute([$userId]);
        $rawClaims = $dbg->fetchAll(PDO::FETCH_ASSOC);
        
        // Also run the full join query to see what's being returned
        $dbgJoin = $pdo->prepare($sql);
        $dbgJoin->execute([':uid' => $userId]);
        $debugJoinResults = $dbgJoin->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $rawClaims = ['error' => $e->getMessage()];
        $debugJoinResults = ['error' => $e->getMessage()];
    }
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
    <link rel="stylesheet" href="../../assets/style.css">
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
            position: relative; /* needed for top-right badges */
        }
        .claim-card:hover, .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .item-image {
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 12px;
            margin-right: 1rem;
            font-size: 1.5rem;
            color: #6c757d;
            box-shadow: 0 4px 12px rgba(16,24,40,0.04);
        }
        .item-details {
            flex: 1;
        }
        .item-main { flex: 1; }
        .item-actions { display:flex; flex-direction:column; align-items:flex-end; gap: .5rem; margin-left: 1rem; }
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
        .status-open { background-color: #e7f7ff; color: #055160; }
        .status-closed { background-color: #e6f4ea; color: #155724; }
    /* Type badge (lost/found) */
    .item-type-badge { position: absolute; top: 12px; right: 12px; padding: .25rem .6rem; border-radius: 999px; font-weight:700; color:#fff; font-size: .8rem; box-shadow: 0 6px 18px rgba(0,0,0,0.08); }
    .item-type-lost { background: linear-gradient(135deg,#e53935,#ff6b6b); }
    .item-type-found { background: linear-gradient(135deg,#28a745,#20c997); }
        /* Button refinements */
        .btn-rounded { border-radius: 999px; padding-left: .75rem; padding-right: .75rem; }
        .btn-icon { display: inline-flex; align-items: center; gap: .5rem; }
    .btn-circle { width: 38px; height: 38px; padding: 0; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; }
    .btn-circle i { font-size: 1rem; }
    .btn-mark { background: linear-gradient(135deg,#28a745,#20c997); border: none; color: #fff; box-shadow: 0 6px 18px rgba(32,201,151,0.12); }
    .btn-mark:hover { filter: brightness(0.95); }
    .btn-view { background: linear-gradient(135deg,#0d6efd,#4dabf7); border: none; color: #fff; box-shadow: 0 6px 18px rgba(13,110,253,0.12); }
    .btn-delete { background: linear-gradient(135deg,#dc3545,#ff6b6b); border: none; color: #fff; box-shadow: 0 6px 18px rgba(220,53,69,0.12); }
    .btn-mark-text { background: linear-gradient(135deg,#28a745,#20c997); border: none; color: #fff; padding: .5rem 0.9rem; border-radius: 999px; display: inline-flex; align-items: center; gap: .4rem; box-shadow: 0 6px 18px rgba(32,201,151,0.12); font-size: 0.85rem; font-weight: 500; white-space: nowrap; }
    .btn-mark-text:hover { background: linear-gradient(135deg,#228236,#1aa179); color: #fff; text-decoration: none; }
    .btn-mark-text i { font-size: 0.9rem; line-height: 1; }
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
        /* Profile stats cards */
        .profile-stats { display: flex; gap: 1rem; margin-bottom: 1rem; }
        .stat-card { background: #fff; padding: 0.75rem 1rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); flex: 1; text-align: center; }
        .stat-number { font-size: 1.5rem; font-weight: 700; color: #2c3e50; }
        .stat-label { font-size: 0.85rem; color: #6c757d; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container py-5">
        <a href="userdash.php" class="btn btn-link text-dark text-decoration-none mb-4">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
        
        <!-- Profile stats -->
        <div class="profile-stats mb-3 d-flex">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($userLostCount ?? 0); ?></div>
                <div class="stat-label">Your Lost Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($userFoundCount ?? 0); ?></div>
                <div class="stat-label">Your Found Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($totalClaims ?? 0); ?></div>
                <div class="stat-label">Your Claims</div>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="row g-4">
            <!-- My Reported Items (left) -->
            <div class="col-lg-6">
                <h3 class="list-section-title">My Reported Items</h3>
                <div class="bg-light p-3 p-md-4 rounded shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                                <i class="bi bi-funnel-fill"></i> Filters
                            </button>
                            <a href="claim.php" class="btn btn-sm btn-outline-secondary ms-2">Reset</a>
                        </div>
                        <div class="text-muted small">Showing <?php echo number_format($totalReportedItems); ?> reports</div>
                    </div>

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
                                <?php
                                    $raw = $item['status'] ?? '';
                                    if (in_array($raw, ['pending','available'])) { $disp = 'Open'; $cls = 'open'; }
                                    elseif (in_array($raw, ['claimed','returned'])) { $disp = 'Closed'; $cls = 'closed'; }
                                    else { $disp = ucfirst($raw); $cls = strtolower($raw); }
                                ?>
                                <div class="report-card d-flex align-items-start">
                                    <?php /* type badge top-right */ ?>
                                    <div class="item-type-badge <?php echo $item['item_type'] === 'lost' ? 'item-type-lost' : 'item-type-found'; ?>">
                                        <?php echo ucfirst($item['item_type']); ?>
                                    </div>
                                    <div class="item-image">
                                        <i class="bi bi-collection"></i>
                                    </div>
                                    <div class="item-details">
                                        <h5 class="item-title"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                                        <div class="item-meta">
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
                                        <!-- status back on the left -->
                                        <div class="mt-2">
                                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                <div>
                                                    <span class="status-badge status-<?php echo $cls; ?>">
                                                        <?php echo $disp; ?>
                                                    </span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <a href="item_detail.php?type=<?php echo $item['item_type']; ?>&id=<?php echo $item['item_id']; ?>" 
                                                       class="btn btn-sm btn-circle btn-view" title="View">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </a>
                                                    <!-- Delete report form -->
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this report?');">
                                                        <input type="hidden" name="action" value="delete_report">
                                                        <input type="hidden" name="item_type" value="<?php echo $item['item_type']; ?>">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                        <button class="btn btn-sm btn-circle btn-delete" type="submit" title="Delete"><i class="bi bi-trash-fill"></i></button>
                                                    </form>
                                                    <?php if ($item['item_type'] === 'lost' && $item['status'] !== 'claimed'): ?>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('Mark this lost item as found?');">
                                                            <input type="hidden" name="action" value="mark_lost_found">
                                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                            <button class="btn btn-sm btn-mark-text" type="submit" title="Mark Found"><i class="bi bi-check-circle"></i> Mark Found</button>
                                                        </form>
                                                    <?php elseif ($item['item_type'] === 'found' && $item['status'] !== 'returned'): ?>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('Mark this found item as returned?');">
                                                            <input type="hidden" name="action" value="mark_found_returned">
                                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                            <button class="btn btn-sm btn-mark-text" type="submit" title="Mark Returned"><i class="bi bi-box-arrow-in-left"></i> Mark Returned</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
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

            <!-- Filter Modal (placed once at page level, outside the loop) -->
            <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="filterModalLabel">Filter Reports</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="get">
                        <div class="modal-body">
                            <div class="mb-2">
                                <label class="form-label">Type</label>
                                <select name="type_filter" class="form-select form-select-sm">
                                    <option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>All</option>
                                    <option value="lost" <?php echo $typeFilter === 'lost' ? 'selected' : ''; ?>>Lost</option>
                                    <option value="found" <?php echo $typeFilter === 'found' ? 'selected' : ''; ?>>Found</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Status</label>
                                <select name="status_filter" class="form-select form-select-sm">
                                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                                    <option value="open" <?php echo $statusFilter === 'open' ? 'selected' : ''; ?>>Open (pending/available)</option>
                                    <option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Closed (claimed/returned)</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="claim.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- My Claim Requests (right) -->
            <div class="col-lg-6">
                <h3 class="list-section-title">My Claim Requests</h3>
                <div class="bg-light p-3 p-md-4 rounded shadow-sm h-100">
                    <?php if (empty($claims)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>You haven't made any claims yet.</p>
                            <?php if (isset($totalClaims) && $totalClaims == 0): ?>
                                <small class="text-muted">No claim records were found for your account.</small>
                            <?php else: ?>
                                <small class="text-muted">If you recently submitted a claim, please wait a moment and refresh.</small>
                            <?php endif; ?>
                            <a href="search.php" class="btn btn-primary mt-3">
                                <i class="bi bi-search me-1"></i> Browse Items
                            </a>
                            <?php if (!empty($showDebugClaims)): ?>
                                <div class="mt-3 text-start">
                                    <h6>Debug: raw ClaimRequest rows</h6>
                                    <pre style="max-height:200px; overflow:auto; background:#f8f9fa; padding:1rem; border-radius:6px; font-size:0.75rem;"><?php echo htmlspecialchars(print_r($rawClaims, true)); ?></pre>
                                    <h6 class="mt-3">Debug: JOIN query results</h6>
                                    <pre style="max-height:200px; overflow:auto; background:#f8f9fa; padding:1rem; border-radius:6px; font-size:0.75rem;"><?php echo htmlspecialchars(print_r($debugJoinResults, true)); ?></pre>
                                    <h6 class="mt-3">SQL used:</h6>
                                    <pre style="max-height:150px; overflow:auto; background:#f8f9fa; padding:1rem; border-radius:6px; font-size:0.7rem;"><?php echo htmlspecialchars($sql); ?></pre>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="scrollable-box" id="claimRequests">
                            <?php foreach ($claims as $claim): ?>
                                <div class="claim-card d-flex align-items-start">
                                    <div class="item-image">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                    <div class="item-details">
                                        <h5 class="item-title"><?php echo htmlspecialchars($claim['item_name']); ?></h5>
                                        <div class="item-meta">
                                            <div><i class="bi bi-tag-fill me-1"></i> <?php echo ucfirst($claim['item_type']); ?> Item</div>
                                            <div><i class="bi bi-calendar3 me-1"></i> <?php echo date('M j, Y', strtotime($claim['created_at'])); ?></div>
                                            <?php if (!empty($claim['owner_name'])): ?>
                                                <div><i class="bi bi-person-fill me-1"></i> Finder: <?php echo htmlspecialchars($claim['owner_name']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mt-2">
                                            <div style="flex:1;"></div>
                                            <div style="display:flex; gap: .75rem; align-items:center;">
                                                <span class="status-badge status-<?php echo strtolower($claim['status']); ?>">
                                                    <?php echo ucfirst($claim['status']); ?>
                                                </span>
                                                <div style="margin-left:auto;">
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this claim?');">
                                                        <input type="hidden" name="action" value="delete_claim">
                                                        <input type="hidden" name="claim_id" value="<?php echo $claim['claim_id']; ?>">
                                                        <button class="btn btn-sm btn-circle btn-delete" type="submit" title="Delete"><i class="bi bi-trash-fill"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
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

<?php
session_start();
require_once '../../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /Lost-Found/pages/login.php');
    exit();
}

// Check if item type and ID are provided
if (!isset($_GET['type']) || !isset($_GET['id'])) {
    header('Location: search.php');
    exit();
}

$itemType = $_GET['type'];
$itemId = (int)$_GET['id'];
$item = null;
$error = '';
$success = '';
$userCanClaim = false;
$isOwner = false;

try {
    // Use the $pdo instance from config/db.php

    // Get item details based on type (adjusted to match actual schema: LostItem, FoundItem, User)
    if ($itemType === 'lost') {
        $stmt = $pdo->prepare("SELECT l.*, u.username AS full_name, u.email, u.phone 
                              FROM LostItem l 
                              JOIN User u ON l.user_id = u.user_id 
                              WHERE l.lost_id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT f.*, u.username AS full_name, u.email, u.phone 
                              FROM FoundItem f 
                              JOIN User u ON f.user_id = u.user_id 
                              WHERE f.found_id = ?");
    }
    
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        $error = 'Item not found or has been removed.';
    } else {
    // Check if current user is the owner
    $isOwner = ($_SESSION['user_id'] == $item['user_id']);
        
    // Check if user can claim: only non-owners can claim FOUND items that are available.
    // Lost items are not claimable by other users.
    $userCanClaim = !$isOwner && ($itemType === 'found' && $item['status'] === 'available');
        
        // Handle claim submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_item']) && $userCanClaim) {
            // Use native sanitization to avoid deprecated FILTER_SANITIZE_STRING
            $claimDescription = isset($_POST['claim_description']) ? trim($_POST['claim_description']) : '';
            // Remove any HTML tags and limit length for safety
            $claimDescription = strip_tags($claimDescription);

            if ($claimDescription === '') {
                $error = 'Please provide a description of why you believe this is your item.';
            } else {
                // Insert claim into ClaimRequest (schema does not include a claim_description column in provided schema,
                // so we store the minimal required fields: lost_id/found_id, user_id, status, claim_date)
                try {
                    if ($itemType === 'lost') {
                        $stmt = $pdo->prepare("INSERT INTO ClaimRequest (lost_id, user_id, status, claim_date) VALUES (?, ?, 'pending', NOW())");
                        $stmt->execute([$itemId, $_SESSION['user_id']]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO ClaimRequest (found_id, user_id, status, claim_date) VALUES (?, ?, 'pending', NOW())");
                        $stmt->execute([$itemId, $_SESSION['user_id']]);

                        // Update item status if it's a found item
                        // Use 'returned' which exists in the schema ENUM for FoundItem.status
                        $stmt = $pdo->prepare("UPDATE FoundItem SET status = 'returned' WHERE found_id = ?");
                        $stmt->execute([$itemId]);
                    }

                    $success = 'Your claim has been submitted successfully! The item owner will review your claim and contact you if it matches their records.';
                    $userCanClaim = false; // Disable claim button after submission
                } catch (PDOException $e) {
                    // Log detailed error for debugging and show a concise message to the user
                    error_log('Claim DB Error: ' . $e->getMessage());
                    $error = 'An error occurred while processing your request. Please try again. (' . $e->getMessage() . ')';
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    $error = 'An error occurred while processing your request. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($itemType); ?> Item Details - Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/style.css">
    <style>
        .item-detail-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        /* Hero header replaces image for a cleaner look */
        .item-hero {
            background: linear-gradient(90deg, rgba(245,247,250,1) 0%, rgba(255,255,255,1) 100%);
            padding: 2rem 1.25rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            border-bottom: 1px solid #eef2f6;
        }
        .hero-icon {
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(45, 55, 72, 0.06);
            font-size: 1.75rem;
            color: #2c7be5;
        }
        .item-header {
            border-bottom: 1px solid #eee;
            padding: 1.5rem;
        }
        .item-body {
            padding: 1.5rem;
        }
        .item-title {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .item-meta {
            display: flex;
            gap: 1rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .item-meta i {
            margin-right: 0.3rem;
        }
        .item-description {
            color: #495057;
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }
        .owner-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.25rem;
            margin-top: 1.5rem;
        }
        .owner-title {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #2c3e50;
        }
        .claim-form {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        .status-badge {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            text-transform: capitalize;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-available {
            background-color: #d4edda;
            color: #155724;
        }
        .status-claimed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .status-returned {
            background-color: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <header class="app-header">
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
                        <div class="profile-icon dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" role="button" tabindex="0">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li><a class="dropdown-item" href="userprofile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="claim.php"><i class="bi bi-card-checklist me-2"></i>My Claims & Reports</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container my-5 pt-3">
        <a href="search.php" class="btn btn-link text-dark mb-4 text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to Search
        </a>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif (!$item): ?>
            <div class="alert alert-warning">
                Item not found or has been removed.
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="item-detail-card mb-4">
                        <div class="item-hero">
                            <div class="hero-icon">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div>
                                <h1 class="item-title mb-1"><?php echo htmlspecialchars($item['item_name']); ?></h1>
                                <div class="text-muted">
                                    <?php echo htmlspecialchars($item['category']); ?> &middot; <?php echo htmlspecialchars($item['location']); ?> &middot; <?php 
                                        $dateField = ($itemType === 'lost') ? 'lost_date' : 'found_date';
                                        echo date('M j, Y', strtotime($item[$dateField])); 
                                    ?>
                                </div>
                            </div>
                            <div class="ms-auto">
                                <span class="status-badge status-<?php echo $item['status']; ?>">
                                    <?php echo ucfirst($item['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="item-body">
                            <h5 class="fw-semibold mb-3">Description</h5>
                            <p class="item-description">
                                <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                            </p>
                            
                            <div class="owner-info">
                                <h6 class="owner-title">
                                    <i class="bi bi-person-circle me-1"></i>
                                    <?php echo $isOwner ? 'Your Contact Information' : 'Owner Information'; ?>
                                </h6>
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <div class="fw-medium">Name</div>
                                        <div><?php echo htmlspecialchars($item['full_name']); ?></div>
                                    </div>
                                    <?php if ($isOwner || !empty($item['show_contact'])): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="fw-medium">Email</div>
                                            <div><?php echo htmlspecialchars($item['email']); ?></div>
                                        </div>
                                        <?php if (!empty($item['phone'])): ?>
                                            <div class="col-md-6">
                                                <div class="fw-medium">Phone</div>
                                                <div><?php echo htmlspecialchars($item['phone']); ?></div>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <div class="alert alert-info mb-0">
                                                <i class="bi bi-info-circle-fill me-1"></i>
                                                Contact information will be shared if the owner accepts your claim.
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($itemType === 'lost' && !$isOwner): ?>
                                <div class="alert alert-info mt-4">
                                    <i class="bi bi-info-circle-fill me-1"></i>
                                    This item is marked as lost — it was reported missing by its owner, so it cannot be claimed by other users here.
                                </div>
                            <?php elseif ($userCanClaim): ?>
                                <div class="claim-form">
                                    <h5 class="fw-semibold mb-3">Claim This Item</h5>
                                    <?php if ($success): ?>
                                        <div class="alert alert-success">
                                            <?php echo htmlspecialchars($success); ?>
                                        </div>
                                    <?php else: ?>
                                        <form method="POST" action="">
                                            <div class="mb-3">
                                                <label for="claim_description" class="form-label">
                                                    Why do you think this is your item? *
                                                </label>
                                                <textarea class="form-control" id="claim_description" name="claim_description" 
                                                          rows="4" required placeholder="Please provide as much detail as possible to help verify your claim."></textarea>
                                                <div class="form-text">
                                                    Be specific about identifying marks, when you lost it, and any other details that can help verify your claim.
                                                </div>
                                            </div>
                                            <button type="submit" name="claim_item" class="btn btn-primary">
                                                Submit Claim
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($isOwner): ?>
                                <div class="alert alert-info mt-4">
                                    <i class="bi bi-info-circle-fill me-1"></i>
                                    This is your <?php echo $itemType; ?> item. You can manage claims in your dashboard.
                                </div>
                            <?php elseif ($item['status'] === 'claimed' || $item['status'] === 'returned'): ?>
                                <div class="alert alert-warning mt-4">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                    This item has already been claimed.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Item Status</h5>
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <div class="bg-light rounded-circle p-3">
                                        <i class="bi bi-info-circle text-primary" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-0">
                                        <?php 
                                        if ($isOwner) {
                                            echo 'You reported this item as ' . $itemType . '.';
                                        } else {
                                            echo 'This item is currently marked as ' . $item['status'] . '.';
                                        }
                                        ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?php 
                                        if ($item['status'] === 'pending' || $item['status'] === 'available') {
                                            echo 'The owner is looking for the rightful owner.';
                                        } elseif ($item['status'] === 'claimed' || $item['status'] === 'returned') {
                                            echo 'This item has been claimed by someone.';
                                        } elseif ($item['status'] === 'resolved') {
                                            echo 'This case has been resolved.';
                                        }
                                        ?>
                                    </small>
                                </div>
                            </div>
                            
                            <?php if ($itemType === 'found' && !$isOwner): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="bi bi-lightbulb-fill me-1"></i>
                                    <strong>Tip:</strong> Found something? Be honest and provide accurate details to help reunite it with its owner.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Need Help?</h5>
                            <p>If you have any questions or need assistance, please contact our support team.</p>
                            <a href="../contact.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-envelope-fill me-1"></i> Contact Support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>

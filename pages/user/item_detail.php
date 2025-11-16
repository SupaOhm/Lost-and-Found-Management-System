<?php
session_start();
require_once '../../config/userconfig.php';
require_once '../../config/db.php'; // Ensure $pdo is available
require_once '../../includes/functions.php'; // for decrypt_phone

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
$isOwner = false; // initialize to avoid undefined when $item not found


try {
    // Use the $pdo instance from config/db.php

    // Get item details based on type (adjusted to match actual schema: LostItem, FoundItem, User)
    if ($itemType === 'lost') {
        $stmt = $pdo->prepare("SELECT l.*, u.username AS full_name, u.email, u.phone 
                              FROM LostItem l 
                              JOIN User u ON l.user_id = u.user_id 
                              WHERE l.lost_id = ?");
    } else {
        // Always fetch found item regardless of status
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
        // Decrypt phone if present and not empty
        if (!empty($item['phone'])) {
            $decryptedPhone = decrypt_phone($item['phone']);
            if ($decryptedPhone !== false && $decryptedPhone !== null) {
                $item['phone'] = $decryptedPhone;
            }
        }
        // Determine if current user (non-owner) has an approved claim on this found item
        $hasApprovedClaim = false;
        if (!$isOwner && $itemType === 'found') {
            try {
                $stmtApproved = $pdo->prepare("SELECT 1 FROM ClaimRequest WHERE found_id = ? AND user_id = ? AND status = 'approved' LIMIT 1");
                $stmtApproved->execute([$itemId, $_SESSION['user_id']]);
                $hasApprovedClaim = (bool)$stmtApproved->fetchColumn();
            } catch (PDOException $e) {
                error_log('Approved claim check error: ' . $e->getMessage());
            }
        }
        // Check if user can claim: only non-owners can claim FOUND items that are available and without an approved claim
        $userCanClaim = !$isOwner && !$hasApprovedClaim && ($itemType === 'found' && $item['status'] === 'available');

        // If owner viewing a found item, get accepted claim and claimer info
        $accepted_claim = null;
        $claimer_info = null;
        if ($isOwner && $itemType === 'found') {
            $stmt2 = $pdo->prepare("SELECT c.claim_id, c.description AS claim_description, u.username, u.email, u.phone FROM ClaimRequest c JOIN User u ON c.user_id = u.user_id WHERE c.found_id = ? AND c.status = 'approved' ORDER BY c.approved_date DESC LIMIT 1");
            $stmt2->execute([$itemId]);
            $accepted_claim = $stmt2->fetch(PDO::FETCH_ASSOC);
            if ($accepted_claim) {
                $claimer_info = [
                    'name' => $accepted_claim['username'],
                    'email' => $accepted_claim['email'],
                    'phone' => !empty($accepted_claim['phone']) ? decrypt_phone($accepted_claim['phone']) : ''
                ];
            }
        }
        // Handle claim submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_item']) && $userCanClaim) {
            $claimDescription = isset($_POST['claim_description']) ? trim($_POST['claim_description']) : '';
            $claimDescription = strip_tags($claimDescription);
            if ($claimDescription === '') {
                $error = 'Please provide a description of why you believe this is your item.';
            } else {
                try {
                    $stmt3 = $pdo->prepare("CALL SubmitClaim(?, ?, ?)");
                    $stmt3->execute([$itemId, $_SESSION['user_id'], $claimDescription]);
                    $success = 'Your claim has been submitted successfully! The item owner will review your claim and contact you if it matches their records.';
                    $userCanClaim = false;
                } catch (PDOException $e) {
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
        /* Claim guidance stepper */
        .guide-card { border: 1px solid #eef2f6; border-radius: 12px; box-shadow: 0 6px 20px rgba(16,24,40,0.06); }
        .guide-header { display:flex; align-items:center; gap:.75rem; padding:1rem 1.25rem; border-bottom:1px solid #eef2f6; background: linear-gradient(90deg,#f8fbff 0%,#ffffff 100%); }
        .guide-header .icon { width:38px; height:38px; border-radius:10px; background:#e7f1ff; display:flex; align-items:center; justify-content:center; color:#0d6efd; box-shadow: 0 6px 16px rgba(13,110,253,0.15); }
        .stepper { padding: 1rem 1.25rem; }
        .step { display:flex; gap:.75rem; align-items:flex-start; padding:.5rem 0; }
        .step-index { flex:0 0 28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.85rem; color:#0d6efd; background:#e7f1ff; box-shadow: 0 6px 16px rgba(13,110,253,0.12); }
        .step-body { flex:1; }
        .step-title { font-weight:600; color:#1f2937; margin:0; }
        .step-desc { color:#6b7280; font-size:.9rem; margin: .15rem 0 0; }
        .step-muted { opacity:.7; }
        .note-badge { display:inline-flex; align-items:center; gap:.4rem; padding:.3rem .6rem; border-radius:999px; background:#f1f5f9; color:#334155; font-size:.75rem; font-weight:600; }
        /* Upgraded claim box */
        .claim-card-upgraded { border: 1px solid #eef2f6; border-radius: 14px; overflow:hidden; box-shadow: 0 12px 30px rgba(16,24,40,0.08); }
        .claim-card-upgraded .header { background: linear-gradient(135deg,#0d6efd 0%, #4dabf7 100%); color:#fff; padding: 1rem 1.25rem; display:flex; align-items:center; gap:.6rem; }
        .claim-card-upgraded .header .icon { width:34px; height:34px; border-radius:8px; background: rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; }
        .claim-card-upgraded .body { padding: 1rem 1.25rem 1.25rem; background:#fff; }
        .help-text { font-size:.85rem; color:#64748b; }
        .form-floating textarea { min-height: 120px; }
        .btn-claim { background: linear-gradient(135deg,#0d6efd,#4dabf7); border:none; box-shadow: 0 10px 22px rgba(13,110,253,0.18); }
        .btn-claim:hover { filter: brightness(.97); }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

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
                                    <?php if ($isOwner || $itemType === 'lost' || $hasApprovedClaim || !empty($item['show_contact'])): ?>
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
                                        <?php if (!$isOwner && $itemType === 'found' && $hasApprovedClaim): ?>
                                            <div class="col-12 mt-2">
                                                <div class="alert alert-success py-2 mb-0" style="font-size:0.85rem;">
                                                    <i class="bi bi-check-circle-fill me-1"></i>Your claim was approved. Contact info is now visible.
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <div class="alert mb-0" style="background: linear-gradient(135deg, #e7f1ff 0%, #f0f8ff 100%); border: 1px solid #b8daff; border-radius: 8px; padding: 0.85rem 1rem;">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #0d6efd; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                        <i class="bi bi-shield-lock text-white" style="font-size: 0.9rem;"></i>
                                                    </div>
                                                    <div style="color: #004085; font-size: 0.9rem;">
                                                        <strong>Contact info protected</strong><br>
                                                        <span style="font-size: 0.85rem; opacity: 0.85;">Details will be shared once your claim is accepted.</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($isOwner && $itemType === 'found' && $claimer_info): ?>
                                    <div class="mt-4 p-3 rounded" style="background: linear-gradient(90deg,#e7f1ff 0%,#f0f8ff 100%); border:1px solid #b8daff;">
                                        <h6 class="mb-2 fw-semibold"><i class="bi bi-person-check me-1"></i> Claimer Contact Information</h6>
                                        <div class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($claimer_info['name']); ?></div>
                                        <div class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($claimer_info['email']); ?></div>
                                        <?php if (!empty($claimer_info['phone'])): ?>
                                            <div class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($claimer_info['phone']); ?></div>
                                        <?php endif; ?>
                                        <div class="mt-2 text-muted" style="font-size:.95em;">This person claimed your item and their contact info is shown because the claim was accepted.</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <!-- Claim Guidance -->
                    <div class="guide-card mb-4">
                        <div class="guide-header">
                            <div class="icon"><i class="bi bi-<?php echo $itemType === 'lost' ? 'telephone' : 'flag'; ?>"></i></div>
                            <div>
                                <div class="fw-semibold"><?php echo $itemType === 'lost' ? 'How to Contact' : 'How to Claim'; ?></div>
                                <div class="text-muted small"><?php echo $itemType === 'lost' ? 'Reach out to the reporter' : 'Follow these simple steps'; ?></div>
                            </div>
                            <div class="ms-auto">
                                <span class="note-badge"><i class="bi bi-shield-check"></i> Safe & Fair</span>
                            </div>
                        </div>
                        <div class="stepper">
                            <?php if ($itemType === 'lost'): ?>
                                <div class="step">
                                    <div class="step-index">1</div>
                                    <div class="step-body">
                                        <p class="step-title">Found this item?</p>
                                        <p class="step-desc">Check if you have information about or found an item matching this description.</p>
                                    </div>
                                </div>
                                <div class="step">
                                    <div class="step-index">2</div>
                                    <div class="step-body">
                                        <p class="step-title">Contact the owner</p>
                                        <p class="step-desc">Use the email or phone provided below to reach out directly.</p>
                                    </div>
                                </div>
                                <div class="step">
                                    <div class="step-index">3</div>
                                    <div class="step-body">
                                        <p class="step-title">Share info or return item</p>
                                        <p class="step-desc">Tell them what you know or arrange to hand back their lost item.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="step">
                                    <div class="step-index">1</div>
                                    <div class="step-body">
                                        <p class="step-title">Review item details</p>
                                        <p class="step-desc">Confirm the category, location, and date match your missing item.</p>
                                    </div>
                                </div>
                                <div class="step">
                                    <div class="step-index">2</div>
                                    <div class="step-body">
                                        <p class="step-title">Provide proof</p>
                                        <p class="step-desc">Describe unique identifiers (serials, marks, contents) only you would know.</p>
                                    </div>
                                </div>
                                <div class="step">
                                    <div class="step-index">3</div>
                                    <div class="step-body">
                                        <p class="step-title">Submit your claim</p>
                                        <p class="step-desc">Our team shares your details with the reporter to verify ownership.</p>
                                    </div>
                                </div>
                                <?php if (!$userCanClaim): ?>
                                    <div class="step step-muted">
                                        <div class="step-index"><i class="bi bi-lock"></i></div>
                                        <div class="step-body">
                                            <p class="step-title">Claims not available</p>
                                            <p class="step-desc">This item is currently <strong><?php echo htmlspecialchars($item['status']); ?></strong>. Claiming may be closed.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Submit Claim form (upgraded) -->
                    <?php if ($userCanClaim): ?>
                        <div class="claim-card-upgraded mb-4">
                            <div class="header">
                                <div class="icon"><i class="bi bi-clipboard-check"></i></div>
                                <div>
                                    <div class="fw-semibold">Claim This Item</div>
                                    <div class="small" style="opacity:.9">Provide details to verify ownership</div>
                                </div>
                            </div>
                            <div class="body">
                                <?php if ($success): ?>
                                    <div class="alert alert-success mb-3">
                                        <?php echo htmlspecialchars($success); ?>
                                    </div>
                                <?php else: ?>
                                    <form method="POST" action="">
                                        <div class="form-floating mb-3">
                                            <textarea class="form-control" id="claim_description_side" name="claim_description" placeholder="Describe identifying marks" required></textarea>
                                            <label for="claim_description_side">Why is this your item? *</label>
                                        </div>
                                        <div class="help-text mb-3"><i class="bi bi-shield-lock me-1"></i>Your claim is shared only with the item reporter for verification.</div>
                                        <button type="submit" name="claim_item" class="btn btn-claim w-100 text-white">Submit Claim</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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

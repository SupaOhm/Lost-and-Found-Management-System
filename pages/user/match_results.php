<?php
// Include database connection
require_once('../../config/userconfig.php');

// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get user data
$user_name = 'User';
try {
    $stmt = $pdo->prepare("CALL GetUserById(?)");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $user_name = $user['username'];
    }
    $stmt->closeCursor();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// Get potential matches
$potential_matches = [];
try {
    $stmt = $pdo->prepare("CALL FindPotentialMatches(?)");
    $stmt->execute([$_SESSION['user_id']]);
    $potential_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    error_log("Match detection error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potential Matches - Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/style.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main class="container py-5">
        <div class="text-center mb-5">
            <h2 class="section-title">
                Potential Matches
            </h2>
            <p class="text-muted">
                <?php if (count($potential_matches) > 0): ?>
                    We found <strong><?php echo count($potential_matches); ?></strong> potential match<?php echo count($potential_matches) != 1 ? 'es' : ''; ?> for your lost items
                <?php else: ?>
                    No potential matches found yet for your lost items
                <?php endif; ?>
            </p>
        </div>

        <div class="d-flex justify-content-between mb-4 align-items-center">
            <a href="userdash.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
        <?php if (empty($potential_matches)): ?>
        <div id="noItemsMessage" class="text-center mt-5">
            <div class="py-5">
                <i class="bi bi-inbox no-items-icon"></i>
                <h4 class="text-muted">No Matches Found</h4>
                <p class="text-muted mb-4">
                    We haven't found any potential matches for your lost items yet. We'll continue checking as new found items are reported.
                </p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="lost.php" class="btn btn-form-submit">Report Lost Item</a>
                    <a href="search.php" class="btn btn-outline-primary">Browse Items</a>
                </div>
            </div>
        </div>
        <?php else: ?>
        
        <?php foreach ($potential_matches as $match): ?>
        <div class="report-card d-flex align-items-start position-relative mb-3">
            <div class="item-type-badge badge bg-success">
                <i class="bi bi-star-fill me-1"></i>
                <?php echo $match['match_score']; ?>/10 Match
            </div>
            
            <div class="item-image">
                <i class="bi bi-arrow-left-right"></i>
            </div>
            
            <div class="item-details">
                <h5 class="item-title mb-2">Potential Match Found</h5>
                
                <div class="row g-3 mb-3">
                    <!-- Your Lost Item -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-danger bg-opacity-10 rounded-circle p-1 me-2">
                                    <i class="bi bi-exclamation-circle text-danger"></i>
                                </div>
                                <small class="fw-semibold text-muted">Your Lost Item</small>
                            </div>
                            
                            <div class="mb-2">
                                <strong><?php echo htmlspecialchars($match['lost_item_name']); ?></strong>
                            </div>
                            
                            <div class="item-meta small">
                                <div>
                                    <i class="bi bi-tag-fill me-1"></i>
                                    <?php echo htmlspecialchars($match['lost_category'] ?? 'N/A'); ?>
                                    <?php if ($match['lost_category'] == $match['found_category']): ?>
                                        <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <i class="bi bi-geo-alt-fill me-1"></i>
                                    <?php echo htmlspecialchars($match['lost_location'] ?? 'N/A'); ?>
                                    <?php if ($match['lost_location'] == $match['found_location']): ?>
                                        <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <i class="bi bi-calendar-event me-1"></i>
                                    Lost: <?php echo date('M d, Y', strtotime($match['lost_date'])); ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($match['lost_description'])): ?>
                            <div class="text-truncate mt-2" style="max-width: 350px;" title="<?php echo htmlspecialchars($match['lost_description']); ?>">
                                <i class="bi bi-chat-square-text text-muted me-1"></i>
                                <small>
                                    <?php 
                                    $desc = $match['lost_description'];
                                    echo htmlspecialchars(strlen($desc) > 60 ? substr($desc, 0, 60) . '...' : $desc); 
                                    ?>
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Found Item -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-success bg-opacity-10 rounded-circle p-1 me-2">
                                    <i class="bi bi-check-circle text-success"></i>
                                </div>
                                <small class="fw-semibold text-muted">Matched Found Item</small>
                            </div>
                            
                            <div class="mb-2">
                                <strong><?php echo htmlspecialchars($match['found_item_name']); ?></strong>
                            </div>
                            
                            <div class="item-meta small">
                                <div>
                                    <i class="bi bi-tag-fill me-1"></i>
                                    <?php echo htmlspecialchars($match['found_category'] ?? 'N/A'); ?>
                                    <?php if ($match['lost_category'] == $match['found_category']): ?>
                                        <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <i class="bi bi-geo-alt-fill me-1"></i>
                                    <?php echo htmlspecialchars($match['found_location'] ?? 'N/A'); ?>
                                    <?php if ($match['lost_location'] == $match['found_location']): ?>
                                        <i class="bi bi-check-circle-fill text-success ms-1"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <i class="bi bi-calendar-event me-1"></i>
                                    Found: <?php echo date('M d, Y', strtotime($match['found_date'])); ?>
                                </div>
                                <div>
                                    <i class="bi bi-person-circle me-1"></i>
                                    By: <?php echo htmlspecialchars($match['finder_username']); ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($match['found_description'])): ?>
                            <div class="text-truncate mt-2" style="max-width: 350px;" title="<?php echo htmlspecialchars($match['found_description']); ?>">
                                <i class="bi bi-chat-square-text text-muted me-1"></i>
                                <small>
                                    <?php 
                                    $desc = $match['found_description'];
                                    echo htmlspecialchars(strlen($desc) > 60 ? substr($desc, 0, 60) . '...' : $desc); 
                                    ?>
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="item_detail.php?type=found&id=<?php echo $match['found_id']; ?>" 
                       class="btn btn-mark-text btn-sm">
                        <i class="bi bi-eye"></i> View Details & Claim
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <a href="userdash.php" class="footer-logo">Lost&Found</a>
                    <p>Helping reunite people with their lost belongings through community collaboration.</p>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-4 mb-md-0">
                    <div class="footer-links">
                        <h5>Quick Links</h5>
                        <ul>
                            <li><a href="userdash.php">Home</a></li>
                            <li><a href="lost.php">Report Lost</a></li>
                            <li><a href="found.php">Report Found</a></li>
                            <li><a href="search.php">Search</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-4 mb-md-0">
                    <div class="footer-links">
                        <h5>Account</h5>
                        <ul>
                            <li><a href="claim.php">My Claims & Reports</a></li>
                            <li><a href="userprofile.php">Profile</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4">
                    <div class="footer-links">
                        <h5>Contact Us</h5>
                        <ul>
                            <li><i class="bi bi-envelope me-2"></i> help@lostfound.com</li>
                            <li><i class="bi bi-telephone me-2"></i> +1 (555) 123-4567</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Lost&Found. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        .report-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .item-image {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 8px;
            margin-right: 1rem;
            font-size: 1.3rem;
            color: white;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
        }
        .item-details {
            flex: 1;
        }
        .item-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        .item-meta {
            font-size: 0.9rem;
            color: #495057;
            line-height: 1.6;
        }
        .item-type-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .btn-mark-text {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: #fff;
            padding: 0.4rem 1rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: 0 4px 12px rgba(32, 201, 151, 0.15);
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-mark-text:hover {
            background: linear-gradient(135deg, #228236, #1aa179);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(32, 201, 151, 0.25);
        }
        .no-items-icon {
            font-size: 5rem;
            color: #dee2e6;
        }
    </style>
</body>
</html>

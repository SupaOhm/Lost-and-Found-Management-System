<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /Lost-Found/pages/login.php');
    exit();
}

// Check if this is a redirect from a successful report
if (!isset($_GET['type']) || !in_array($_GET['type'], ['lost', 'found'])) {
    header('Location: userdash.php');
    exit();
}

$itemType = htmlspecialchars($_GET['type']);
$message = '';
$title = '';

if ($itemType === 'lost') {
    $title = 'Lost Item Reported';
    $message = 'Your lost item has been successfully reported. We\'ll notify you if someone finds a matching item.';
} else {
    $title = 'Found Item Reported';
    $message = 'Thank you for reporting the found item! We\'ll help reunite it with its owner.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Lost-Found/assets/style.css">
    <style>
        .thank-you-container {
            min-height: calc(100vh - 180px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background-color: #f8f9fa;
        }
        .thank-you-box {
            text-align: center;
            max-width: 600px;
            padding: 3rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        .thank-you-box h2 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        .thank-you-box p {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
            line-height: 1.7;
        }
        .btn-return {
            background-color: #4a6cf7;
            color: white;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s;
            margin: 0.5rem;
        }
        .btn-return:hover {
            background-color: #3a5bd9;
            color: white;
            transform: translateY(-2px);
        }
        .btn-outline-primary {
            border-color: #4a6cf7;
            color: #4a6cf7;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s;
            margin: 0.5rem;
        }
        .btn-outline-primary:hover {
            background-color: #4a6cf7;
            color: white;
            transform: translateY(-2px);
        }
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 1.5rem;
            animation: bounce 1s ease infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
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
                        <div class="profile-icon" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="my_claims.php"><i class="bi bi-card-checklist me-2"></i>My Claims</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="thank-you-container">
        <div class="thank-you-box">
            <div class="mb-4">
                <i class="bi bi-check-circle-fill success-icon"></i>
            </div>
            <h2>Thank You for Your Report!</h2>
            <p><?php echo $message; ?></p>
            <p class="text-muted">You can view and manage your reported items in your dashboard at any time.</p>
            
            <div class="action-buttons">
                <a href="userdash.php" class="btn btn-return">
                    <i class="bi bi-house-door-fill me-2"></i>Go to Dashboard
                </a>
                <?php if ($itemType === 'lost'): ?>
                    <a href="found.php" class="btn btn-outline-primary">
                        <i class="bi bi-search me-2"></i>Browse Found Items
                    </a>
                <?php else: ?>
                    <a href="lost.php" class="btn btn-outline-primary">
                        <i class="bi bi-search me-2"></i>Browse Lost Items
                    </a>
                <?php endif; ?>
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
        });
    </script>
</body>
</html>

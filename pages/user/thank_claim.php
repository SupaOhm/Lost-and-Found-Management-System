<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /Lost-Found/pages/login.php');
    exit();
}

// Check if this is a redirect from a successful claim
if (!isset($_GET['success']) || $_GET['success'] !== '1') {
    header('Location: search.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Submitted - Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/style.css">
    <style>
        .thank-you-container {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
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
            margin-bottom: 1rem;
        }
        .thank-you-box p {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 2rem;
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
            transition: background-color 0.2s;
        }
        .btn-return:hover {
            background-color: #3a5bd9;
            color: white;
        }
        .text-success {
            color: #28a745 !important;
        }
        .display-1 {
            font-size: 5rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="thank-you-container">
        <div class="thank-you-box">
            <div class="mb-4">
                <i class="bi bi-check-circle-fill text-success display-1"></i>
            </div>
            <h2>Thank you for your claim!</h2>
            <p>Your claim has been successfully submitted. The item owner will review your claim and contact you if it matches their records. You can check the status of your claim in your dashboard.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="userdash.php" class="btn btn-return">
                    <i class="bi bi-house-door-fill me-2"></i>Go to Dashboard
                </a>
                <a href="search.php" class="btn btn-outline-primary">
                    <i class="bi bi-search me-2"></i>Continue Searching
                </a>
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

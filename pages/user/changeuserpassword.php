<?php
// Change user password page
require_once('../../config/userconfig.php');
require_once('../../includes/functions.php');

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            throw new Exception('Please fill in all fields.');
        }

        if ($new_password !== $confirm_password) {
            throw new Exception('New passwords do not match.');
        }

        // Fetch current hashed password
        $stmt = $pdo->prepare('SELECT password FROM User WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$row) {
            throw new Exception('User not found.');
        }

        $hashed = $row['password'];
        if (!password_verify($current_password, $hashed)) {
            throw new Exception('Current password is incorrect.');
        }

        // Optionally enforce password strength
        if (strlen($new_password) < 8) {
            throw new Exception('New password must be at least 8 characters long.');
        }

        // Update password
        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE User SET password = ? WHERE user_id = ?');
        $stmt->execute([$new_hashed, $_SESSION['user_id']]);
        $stmt->closeCursor();

        $success = 'Password updated successfully. Redirecting...';
        header('Refresh: 1; URL=userprofile.php');

    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log('Change password error: ' . $error);
    }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Change Password - Lost&Found</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="form-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0">Change Password</h3>
                        <a href="userprofile.php" class="text-muted">Back</a>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="post" action="changeuserpassword.php">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                            <div class="form-text">At least 8 characters recommended.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>

                        <div class="text-end">
                            <a href="userprofile.php" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

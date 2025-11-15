<?php
// Edit profile page - allows user to update email, full name and phone
require_once('../../config/userconfig.php');
require_once('../../includes/functions.php');

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';

// Load existing user data
try {
    $stmt = $pdo->prepare("CALL GetUserById(?)");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    if (!$user) {
        throw new Exception('User not found');
    }
    if (isset($user['phone'])) {
        $user['phone'] = decrypt_phone($user['phone']);
    }
} catch (PDOException $e) {
    $error = 'Error loading profile: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $username = sanitize_input($_POST['username']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = sanitize_phone($_POST['phone']);

        // Basic validation
        if (empty($username) || empty($email)) {
            throw new Exception('Please provide a name and email.');
        }

        // Encrypt phone before saving
        $encryptedPhone = encrypt_phone($phone);
        // Update using stored procedure (expects: user_id, username, email, phone)
        $stmt = $pdo->prepare("CALL UpdateUserProfile(?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $username, $email, $encryptedPhone]);
        $stmt->closeCursor();

        $success = 'Profile updated successfully. Redirecting...';
        header('Refresh: 2; URL=userprofile.php');

        // reload user data
        $stmt = $pdo->prepare("CALL GetUserById(?)");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log('Edit profile error: ' . $error);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profile - Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0">Edit Profile</h3>
                        <a href="userprofile.php" class="text-muted">Back</a>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="post" action="edit_profile.php">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" value="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>

                        <div class="text-end">
                            <a href="userprofile.php" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

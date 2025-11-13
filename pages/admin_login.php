<?php
session_start();
require_once '../config/db.php';

$error = '';

// Check if admin is already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/admin_dashboard.php');
    exit();
}

// Handle admin login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    try {
        // Call the stored procedure to verify admin login
        $stmt = $pdo->prepare("CALL VerifyAdminLogin(?)");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        if ($admin && password_verify($password, $admin['password'])) {
            // Get complete admin data using the stored procedure
            $stmt = $pdo->prepare("CALL GetAdminById(?)");
            $stmt->execute([$admin['admin_id']]);
            $adminData = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if ($adminData) {
                $_SESSION['admin_id'] = $adminData['admin_id'];
                $_SESSION['username'] = $adminData['username'];
                $_SESSION['is_admin'] = true;
                
                if (isset($adminData['email'])) {
                    $_SESSION['email'] = $adminData['email'];
                }
                
                header('Location: admin/admin_dashboard.php');
                exit();
            }
        }
        
        $error = 'Invalid username or password';
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .admin-login-container {
            max-width: 400px;
            margin: 5rem auto;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background: white;
        }
        .back-to-home {
            display: inline-block;
            margin-top: 1rem;
            color: #6c757d;
            text-decoration: none;
        }
        .back-to-home:hover {
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-login-container">
            <h2 class="text-center mb-4">Admin Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Admin Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <a href="login.php" class="back-to-home">
                    <i class="bi bi-arrow-left"></i> Back to User Login
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

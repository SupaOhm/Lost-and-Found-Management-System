<?php
session_start();
require_once '../config/db.php';

$error = '';
$success = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: user/userdash.html');
    exit();
} elseif (isset($_SESSION['admin_id'])) {
    header('Location: admin/admindash.html');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $userType = $_POST['userType'];
    
    try {
        if ($userType === 'user') {
            $stmt = $pdo->prepare("SELECT user_id, full_name, email, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                header('Location: user/userdash.php');
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } else { // admin
            $stmt = $pdo->prepare("SELECT admin_id, username, full_name, password FROM admins WHERE username = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['full_name'] = $admin['full_name'];
                $_SESSION['username'] = $admin['username'];
                header('Location: admin/admindash.html');
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        }
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
    <title>Login - Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .form-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background: white;
        }
        .form-title {
            text-align: center;
            margin-bottom: 2rem;
            color: #333;
        }
        .btn-group-sm > .btn {
            padding: 0.25rem 1rem;
            font-size: 0.875rem;
        }
        .btn-form-submit {
            width: 100%;
            padding: 0.75rem;
            font-weight: 600;
            background-color: #0d6efd;
            border: none;
            border-radius: 30px;
        }
        .btn-form-submit:hover {
            background-color: #0b5ed7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="form-title">Welcome Back to <span class="text-primary">Lost&Found</span></h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <input type="hidden" name="userType" id="userType" value="user">

            <form id="loginForm" method="POST" action="">
                <input type="hidden" name="userType" id="formUserType" value="user">
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email / Username</label>
                    <input type="text" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-form-submit">Login</button>
                </div>
                
                <div class="text-center mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-center gap-2 align-items-center mb-2">
                        <span class="small text-muted">Login as:</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary active" id="userToggle">User</button>
                            <button type="button" class="btn btn-outline-primary" id="adminToggle">Admin</button>
                        </div>
                    </div>
                    <p class="small text-muted mb-0">Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle between user and admin login
        document.getElementById('userToggle').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('adminToggle').classList.remove('active');
            document.getElementById('formUserType').value = 'user';
            document.getElementById('email').placeholder = 'Enter your email';
        });

        document.getElementById('adminToggle').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('userToggle').classList.remove('active');
            document.getElementById('formUserType').value = 'admin';
            document.getElementById('email').placeholder = 'Enter your username';
        });
    </script>
</body>
</html>

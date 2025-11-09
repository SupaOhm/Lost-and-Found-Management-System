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

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $userType = $_POST['userType'];
    
    // Basic validation
    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else {
        try {
            if ($userType === 'user') {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    $error = 'Email already registered';
                } else {
                    // Hash password and insert new user
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$fullName, $email, $hashedPassword, $phone]);
                    
                    // Log the user in after registration
                    $userId = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['full_name'] = $fullName;
                    $_SESSION['email'] = $email;
                    
                    $success = 'Registration successful! Redirecting...';
                    header('Refresh: 2; URL=user/userdash.html');
                }
            } else { // admin registration (for demo purposes only - in production, this should be restricted)
                $stmt = $pdo->prepare("SELECT admin_id FROM admins WHERE username = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    $error = 'Username already exists';
                } else {
                    // In a real application, admin registration should be restricted
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO admins (username, password, full_name) VALUES (?, ?, ?)");
                    $stmt->execute([$email, $hashedPassword, $fullName]);
                    
                    $adminId = $pdo->lastInsertId();
                    $_SESSION['admin_id'] = $adminId;
                    $_SESSION['full_name'] = $fullName;
                    $_SESSION['username'] = $email;
                    
                    $success = 'Admin registration successful! Redirecting...';
                    header('Refresh: 2; URL=admin/admindash.html');
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 30px auto;
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
        .form-text {
            font-size: 0.875rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="form-title">Create Your <span class="text-primary">Lost&Found</span> Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <input type="hidden" name="userType" id="userType" value="user">

            <form id="registerForm" method="POST" action="">
                <input type="hidden" name="userType" id="formUserType" value="user">
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="fullName" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="fullName" name="fullName" required 
                               value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="email" class="form-label" id="emailLabel">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">At least 8 characters</div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label for="confirmPassword" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-form-submit">Create Account</button>
                </div>
                
                <div class="text-center mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-center gap-2 align-items-center mb-2">
                        <span class="small text-muted">Register as:</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary active" id="userToggle">User</button>
                            <button type="button" class="btn btn-outline-primary" id="adminToggle">Admin</button>
                        </div>
                    </div>
                    <p class="small text-muted mb-0">Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle between user and admin registration
        document.getElementById('userToggle').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('adminToggle').classList.remove('active');
            document.getElementById('formUserType').value = 'user';
            document.getElementById('emailLabel').innerHTML = 'Email <span class="text-danger">*</span>';
            document.getElementById('email').type = 'email';
            document.getElementById('phone').parentElement.style.display = 'block';
        });

        document.getElementById('adminToggle').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('userToggle').classList.remove('active');
            document.getElementById('formUserType').value = 'admin';
            document.getElementById('emailLabel').innerHTML = 'Username <span class="text-danger">*</span>';
            document.getElementById('email').type = 'text';
            document.getElementById('phone').parentElement.style.display = 'none';
        });
    </script>
</body>
</html>

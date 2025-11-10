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
                // Check if email already exists using stored procedure
                $stmt = $pdo->prepare("CALL CheckEmailExists(?)");
                $stmt->execute([$email]);
                $exists = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor(); // Close the cursor
                
                if ($exists) {
                    $error = 'Email already registered';
                } else {
                    // Hash password and insert new user using stored procedure
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("CALL RegisterUser(?, ?, ?, ?)");
                    $stmt->execute([$fullName, $email, $hashedPassword, $phone]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stmt->closeCursor(); // Close the cursor
                    
                    if ($result && isset($result['user_id'])) {
                        // Log the user in after registration
                        $_SESSION['user_id'] = $result['user_id'];
                        $_SESSION['username'] = $fullName;
                        $_SESSION['email'] = $email;
                        
                        $success = 'Registration successful! Redirecting...';
                        header('Refresh: 2; URL=user/userdash.php');
                    } else {
                        $error = 'Failed to register user. Please try again.';
                    }
                }
            } else { // admin registration (for demo purposes only - in production, this should be restricted)
                // Check if admin username exists using stored procedure
                $stmt = $pdo->prepare("CALL CheckAdminUsernameExists(?)");
                $stmt->execute([$email]);
                $adminExists = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor(); // Close the cursor
                
                if ($adminExists) {
                    $error = 'Username already exists';
                } else {
                    // In a real application, admin registration should be restricted
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("CALL RegisterAdmin(?, ?, ?)");
                    $stmt->execute([$email, $hashedPassword, $email]); // Using email as username for admin
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stmt->closeCursor(); // Close the cursor
                    
                    if ($result && isset($result['admin_id'])) {
                        // Log the admin in after registration
                        $_SESSION['admin_id'] = $result['admin_id'];
                        $_SESSION['username'] = $email;
                        $_SESSION['is_admin'] = true;
                        
                        $success = 'Admin registration successful! Redirecting to dashboard...';
                        header('Refresh: 2; URL=admin/admindash.php');
                    } else {
                        $error = 'Failed to register admin. Please try again.';
                    }
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
    <link rel="stylesheet" href="/Lost-Found/assets/style.css">
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

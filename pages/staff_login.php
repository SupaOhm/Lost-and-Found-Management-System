<?php
session_start();
require_once '../config/staffconfig.php';

$error = '';

// Check if staff is already logged in
if (isset($_SESSION['staff_id'])) {
    header('Location: staff/staff_dashboard.php');
    exit();
}

// Handle staff login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../includes/functions.php';
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("CALL VerifyStaffLogin(?)");
        $stmt->execute([$username]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        if (!$staff) {
            $error = 'Username not found. Please check your credentials.';
        } elseif (!password_verify($password, $staff['password'])) {
            $error = 'Invalid password. Please try again.';
        } else {
            // Get complete staff data
            $stmt = $pdo->prepare("CALL GetStaffById(?)");
            $stmt->execute([$staff['staff_id']]);
            $staffData = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if ($staffData) {
                $_SESSION['staff_id'] = $staffData['staff_id'];
                $_SESSION['username'] = $staffData['username'];
                $_SESSION['is_staff'] = true;
                if (isset($staffData['email'])) {
                    $_SESSION['email'] = $staffData['email'];
                }
                if (isset($staffData['full_name'])) {
                    $_SESSION['full_name'] = $staffData['full_name'];
                }
                if (isset($staffData['phone'])) {
                    $_SESSION['phone'] = decrypt_phone($staffData['phone']);
                }
                header('Location: staff/staff_dashboard.php');
                exit();
            } else {
                $error = 'Error retrieving staff data. Please contact administrator.';
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
    <title>Staff Login - Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .staff-login-container {
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
        <div class="staff-login-container">
            <h2 class="text-center mb-4">Staff Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Staff Username</label>
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

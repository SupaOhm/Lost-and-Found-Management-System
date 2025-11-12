<?php
require_once 'config/db.php';

// Test user data
$email = 'test@example.com';
$password = 'password123';
$username = 'testuser';
$phone = '1234567890';

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if user already exists
    $stmt = $pdo->prepare("CALL GetUserByEmail(?)");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    $stmt->closeCursor();
    
    if ($user) {
        echo "User already exists. Updating password...\n";
        // Update existing user
        $stmt = $pdo->prepare("UPDATE User SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
    } else {
        echo "Creating new test user...\n";
        // Create new user
        $stmt = $pdo->prepare("CALL RegisterUser(?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword, $phone]);
        $result = $stmt->fetch();
        $stmt->closeCursor();
        
        if ($result && isset($result['user_id'])) {
            echo "Test user created successfully!\n";
            echo "User ID: " . $result['user_id'] . "\n";
        } else {
            echo "Failed to create test user.\n";
            print_r($result);
        }
    }
    
    // Verify the user was created
    $stmt = $pdo->prepare("CALL GetUserByEmail(?)");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    $stmt->closeCursor();
    
    if ($user) {
        echo "\nTest user details:\n";
        echo "ID: " . $user['user_id'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Password hash: " . $user['password'] . "\n";
        
        // Test password verification
        if (password_verify($password, $user['password'])) {
            echo "Password verification: SUCCESS\n";
        } else {
            echo "Password verification: FAILED\n";
        }
    } else {
        echo "Failed to verify test user creation.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

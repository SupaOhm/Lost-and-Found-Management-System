<?php
require_once 'config/db.php';

// Admin user data
$username = 'admin';
$email = 'admin@example.com';
$password = 'admin123'; // Change this to a secure password in production

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT admin_id FROM Admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "Admin user already exists. Updating password...\n";
        // Update existing admin
        $stmt = $pdo->prepare("UPDATE Admin SET password = ? WHERE username = ?");
        $stmt->execute([$hashedPassword, $username]);
    } else {
        echo "Creating new admin user...\n";
        // Create new admin
        $stmt = $pdo->prepare("INSERT INTO Admin (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $email]);
    }
    
    // Verify the admin was created
    $stmt = $pdo->prepare("CALL VerifyAdminLogin(?)");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    $stmt->closeCursor();
    
    if ($admin) {
        echo "\nAdmin user created/updated successfully!\n";
        echo "Username: " . $username . "\n";
        echo "Password: " . $password . "\n";
        echo "\nIMPORTANT: Change this password after first login!\n";
        
        // Test password verification
        if (password_verify($password, $admin['password'])) {
            echo "Password verification: SUCCESS\n";
        } else {
            echo "Password verification: FAILED\n";
        }
    } else {
        echo "Failed to verify admin user creation.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // If the VerifyAdminLogin procedure doesn't exist, try direct query
    if (strpos($e->getMessage(), 'PROCEDURE lost_found_db.VerifyAdminLogin does not exist') !== false) {
        echo "\nCreating missing stored procedures...\n";
        
        try {
            // Create the VerifyAdminLogin procedure
            $sql = "
            DELIMITER $$
            CREATE PROCEDURE VerifyAdminLogin(
                IN p_username VARCHAR(50)
            )
            BEGIN
                SELECT admin_id, username, password, email 
                FROM Admin 
                WHERE username = p_username 
                LIMIT 1;
            $$
            
            DELIMITER ;
            
            DELIMITER $$
            CREATE PROCEDURE GetAdminById(
                IN p_admin_id INT
            )
            BEGIN
                SELECT admin_id, username, email, created_at 
                FROM Admin 
                WHERE admin_id = p_admin_id 
                LIMIT 1;
            $$
            
            DELIMITER ;
            ";
            
            $pdo->exec($sql);
            echo "Stored procedures created successfully. Please run this script again.\n";
            
        } catch (PDOException $e2) {
            echo "Failed to create stored procedures: " . $e2->getMessage() . "\n";
        }
    }
}
?>

<?php
// Database configuration for MAMP
$db_host = 'localhost';
$db_port = '8889';
$db_name = 'lost_found_db';
$db_user = 'root';
$db_pass = 'root';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection...\n";

try {
    // Try with socket first
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4;unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock";
    echo "Connecting with: $dsn\n";
    
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "Connected successfully!\n";
    
    // Test GetUserByEmail procedure
    $email = 'test@example.com';
    echo "\nTesting GetUserByEmail procedure with email: $email\n";
    
    $stmt = $pdo->prepare("CALL GetUserByEmail(?)");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    $stmt->closeCursor();
    
    if ($user) {
        echo "User found:\n";
        print_r($user);
    } else {
        echo "No user found with email: $email\n";
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    
    // Try without socket if the first attempt fails
    try {
        echo "\nTrying without socket...\n";
        $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        echo "Connected successfully without socket!\n";
        
        // Test GetUserByEmail procedure
        $email = 'test@example.com';
        echo "\nTesting GetUserByEmail procedure with email: $email\n";
        
        $stmt = $pdo->prepare("CALL GetUserByEmail(?)");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        $stmt->closeCursor();
        
        if ($user) {
            echo "User found:\n";
            print_r($user);
        } else {
            echo "No user found with email: $email\n";
        }
        
    } catch (PDOException $e2) {
        echo "Connection failed without socket: " . $e2->getMessage() . "\n";
    }
}
?>

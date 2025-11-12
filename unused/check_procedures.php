<?php
// Database configuration for MAMP
$db_host = 'localhost';
$db_port = '8889';
$db_name = 'lost_found_db';
$db_user = 'root';
$db_pass = 'root';
$db_socket = '/Applications/MAMP/tmp/mysql/mysql.sock';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create connection
try {
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4;unix_socket=$db_socket";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    // Check if GetUserByEmail procedure exists
    $stmt = $pdo->prepare("SHOW PROCEDURE STATUS WHERE Db = ? AND Name = 'GetUserByEmail'");
    $stmt->execute([$db_name]);
    $procedureExists = $stmt->fetch();
    
    if (!$procedureExists) {
        // Create the procedure if it doesn't exist
        $sql = "
        DELIMITER $$
        CREATE PROCEDURE GetUserByEmail(
            IN p_email VARCHAR(100)
        )
        BEGIN
            SELECT user_id, username, email, password, phone, created_at
            FROM User 
            WHERE email = p_email
            LIMIT 1;
        $$
        
        DELIMITER ;
        ";
        
        $pdo->exec($sql);
        echo "GetUserByEmail procedure created successfully.\n";
    } else {
        echo "GetUserByEmail procedure already exists.\n";
    }
    
    // Add similar checks for other procedures if needed
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

echo "All procedures are up to date.\n";
?>

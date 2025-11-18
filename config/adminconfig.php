<?php
// Database configuration
$db_host = 'localhost';
$db_port = '8889'; // Replace with your mysql port
$db_name = 'lost_found_db';
$db_user = 'admin_user';
$db_pass = 'admin_password';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create connection
try {
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4;unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    
    // Set timezone to Asia/Bangkok (UTC+7)
    $pdo->exec("SET time_zone = '+07:00'");
    
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to close the database connection
function closeConnection() {
    global $pdo;
    $pdo = null;
}

// Register the closeConnection function to be called on script end
register_shutdown_function('closeConnection');
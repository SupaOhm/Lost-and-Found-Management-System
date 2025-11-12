<?php
// Test database connection and list stored procedures
require_once 'config/db.php';

try {
    // Test connection
    echo "<h2>Database Connection Test</h2>";
    echo "Connected to database successfully!<br><br>";
    
    // Get database version
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "<strong>Database Version:</strong> " . $version . "<br>";
    
    // List all tables
    echo "<h3>Tables in database:</h3>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // List all stored procedures
    echo "<h3>Stored Procedures:</h3>";
    $procedures = $pdo->query("SHOW PROCEDURE STATUS WHERE Db = DATABASE()")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($procedures)) {
        echo "No stored procedures found!<br>";
        echo "<h4>Creating test procedure...</h4>";
        
        // Try to create a test procedure
        try {
            $pdo->exec("DROP PROCEDURE IF EXISTS TestProcedure");
            $pdo->exec("CREATE PROCEDURE TestProcedure()
                       BEGIN
                           SELECT 'Test successful' AS message;
                       END");
            
            $test = $pdo->query("CALL TestProcedure()")->fetch();
            echo "<div style='color:green;'>Test procedure created and executed successfully: " . $test['message'] . "</div>";
            
        } catch (PDOException $e) {
            echo "<div style='color:red;'>Error creating test procedure: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<ul>";
        foreach ($procedures as $proc) {
            echo "<li>" . $proc['Name'] . "</li>";
        }
        echo "</ul>";
        
        // Test GetAllActiveItems procedure
        echo "<h3>Testing GetAllActiveItems:</h3>";
        try {
            $stmt = $pdo->query("CALL GetAllActiveItems()");
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($items)) {
                echo "No items found (this might be expected if the database is empty).<br>";
            } else {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>Type</th><th>ID</th><th>Item Name</th><th>Category</th><th>Location</th><th>Date</th></tr>";
                foreach ($items as $item) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($item['type']) . "</td>";
                    echo "<td>" . htmlspecialchars($item['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($item['item_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($item['category']) . "</td>";
                    echo "<td>" . htmlspecialchars($item['location']) . "</td>";
                    echo "<td>" . htmlspecialchars($item['item_date']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
        } catch (PDOException $e) {
            echo "<div style='color:red;'>Error calling GetAllActiveItems: " . $e->getMessage() . "</div>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }
    
} catch (PDOException $e) {
    die("<div style='color:red;'>Connection failed: " . $e->getMessage() . "</div>");
}
?>

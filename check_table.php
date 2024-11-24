<?php
require_once __DIR__ . '/config/database.php';

try {
    $conn = getConnection();
    
    // Check if table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'perguntas'");
    $tableExists = $stmt->rowCount() > 0;
    
    echo "Table exists: " . ($tableExists ? "Yes" : "No") . "\n";
    
    if ($tableExists) {
        // Show table structure
        $stmt = $conn->query("DESCRIBE perguntas");
        echo "\nTable structure:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

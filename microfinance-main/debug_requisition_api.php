<?php
// Debug script to check what's causing the 500 error

require_once 'config/db.php';

try {
    echo "=== DEBUGGING REQUISITION API 500 ERROR ===\n";
    
    // Test basic connection
    $stmt = $pdo->query("SELECT 1");
    echo "Database connection: OK\n";
    
    // Check if requisitions table exists and structure
    $stmt = $pdo->prepare("DESCRIBE requisitions");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Requisitions table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} ({$column['Extra']})\n";
    }
    
    // Check auto increment
    $stmt = $pdo->prepare("SHOW TABLE STATUS LIKE 'requisitions'");
    $stmt->execute();
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Auto increment status: {$status['Auto_increment']}\n";
    
    // Test a simple insert
    echo "\nTesting simple insert...\n";
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO requisitions (user_id, request_date, remarks) VALUES (1, NOW(), 'Test Requisition')");
    $stmt->execute();
    $inserted_id = $pdo->lastInsertId();
    
    echo "Inserted ID: $inserted_id\n";
    
    $pdo->rollBack(); // Rollback the test
    
    echo "Test completed\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    echo 'Stack trace: ' . $e->getTraceAsString() . "\n";
}
?>

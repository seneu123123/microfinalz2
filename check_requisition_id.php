<?php
session_start();
$_SESSION['user_id'] = 1; // Simulate logged in user

require_once 'config/db.php';

try {
    echo "Checking requisitions table structure...\n";
    
    // Check table structure
    $stmt = $pdo->prepare("DESCRIBE requisitions");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Requisitions table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} ({$column['Extra']})\n";
    }
    
    // Check auto increment value
    $stmt = $pdo->prepare("SHOW TABLE STATUS LIKE 'requisitions'");
    $stmt->execute();
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nAuto increment info:\n";
    echo "- Next auto increment: {$status['Auto_increment']}\n";
    
    // Check current data
    $stmt = $pdo->prepare("SELECT id, user_id, request_date, remarks FROM requisitions ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    $requisitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nRecent requisitions:\n";
    foreach ($requisitions as $req) {
        echo "- ID: {$req['id']}, User: {$req['user_id']}, Date: {$req['request_date']}, Remarks: {$req['remarks']}\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>

<?php
// Debug and fix requisitions table auto-increment issue

require_once 'config/db.php';

try {
    echo "=== REQUISITIONS TABLE ANALYSIS ===\n";
    
    // Check table structure
    $stmt = $pdo->prepare("DESCRIBE requisitions");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} ({$column['Extra']})\n";
    }
    
    // Check auto increment status
    $stmt = $pdo->prepare("SHOW TABLE STATUS LIKE 'requisitions'");
    $stmt->execute();
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nAuto increment status:\n";
    echo "- Auto_increment: {$status['Auto_increment']}\n";
    
    // Check if there are any records with ID 0
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM requisitions WHERE id = 0");
    $stmt->execute();
    $zeroCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "- Records with ID 0: {$zeroCount['count']}\n";
    
    // Get recent records
    $stmt = $pdo->prepare("SELECT id, remarks, request_date FROM requisitions ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nRecent requisitions:\n";
    foreach ($recent as $req) {
        echo "- ID: {$req['id']}, Date: {$req['request_date']}, Remarks: " . ($req['remarks'] ?? 'NULL') . "\n";
    }
    
    // Fix auto-increment if needed
    if ($zeroCount['count'] > 0 || $status['Auto_increment'] == 1) {
        echo "\n=== FIXING AUTO INCREMENT ===\n";
        
        // Get max ID
        $stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM requisitions");
        $stmt->execute();
        $maxId = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $newAutoIncrement = ($maxId['max_id'] ?? 0) + 1;
        
        echo "Setting new auto increment to: $newAutoIncrement\n";
        
        // Reset auto increment
        $stmt = $pdo->prepare("ALTER TABLE requisitions AUTO_INCREMENT = $newAutoIncrement");
        $stmt->execute();
        
        // Update any ID 0 records
        if ($zeroCount['count'] > 0) {
            $stmt = $pdo->prepare("UPDATE requisitions SET id = ? WHERE id = 0");
            $updateId = $newAutoIncrement;
            $stmt->execute([$updateId]);
            
            // Update requisition_items
            $stmt = $pdo->prepare("UPDATE requisition_items SET requisition_id = ? WHERE requisition_id = 0");
            $stmt->execute([$updateId]);
            
            echo "Updated ID 0 record to ID: $updateId\n";
        }
        
        echo "Auto increment fix completed!\n";
    }
    
    // Verify fix
    $stmt = $pdo->prepare("SHOW TABLE STATUS LIKE 'requisitions'");
    $stmt->execute();
    $newStatus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nFinal auto increment: {$newStatus['Auto_increment']}\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>

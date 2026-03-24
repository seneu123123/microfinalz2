<?php
// Fix requisitions with ID 0 - Reset auto increment and update any bad records

require_once 'config/db.php';

try {
    echo "Fixing requisitions with ID 0...\n";
    
    // Check current auto increment value
    $stmt = $pdo->prepare("SHOW TABLE STATUS LIKE 'requisitions'");
    $stmt->execute();
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Current auto increment: {$status['Auto_increment']}\n";
    
    // Find any requisitions with ID 0
    $stmt = $pdo->prepare("SELECT id FROM requisitions WHERE id = 0");
    $stmt->execute();
    $zeroIdReqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($zeroIdReqs) > 0) {
        echo "Found " . count($zeroIdReqs) . " requisitions with ID 0\n";
        
        // Get the next available ID
        $nextId = $status['Auto_increment'];
        
        // Update each requisition with proper ID
        foreach ($zeroIdReqs as $req) {
            $stmt = $pdo->prepare("UPDATE requisitions SET id = ? WHERE id = 0");
            $stmt->execute([$nextId]);
            echo "Updated requisition ID from 0 to $nextId\n";
            
            // Update requisition_items to match
            $stmt = $pdo->prepare("UPDATE requisition_items SET requisition_id = ? WHERE requisition_id = 0");
            $stmt->execute([$nextId]);
            echo "Updated requisition_items for requisition ID $nextId\n";
            
            $nextId++;
        }
        
        // Reset auto increment to next available value
        $stmt = $pdo->prepare("ALTER TABLE requisitions AUTO_INCREMENT = $nextId");
        $stmt->execute();
        echo "Reset auto increment to $nextId\n";
        
        echo "Fixed " . count($zeroIdReqs) . " requisitions with ID 0\n";
    } else {
        echo "No requisitions with ID 0 found\n";
    }
    
    // Verify the fix
    $stmt = $pdo->prepare("SELECT id, remarks FROM requisitions ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nRecent requisitions after fix:\n";
    foreach ($recent as $req) {
        echo "- ID: {$req['id']}, Remarks: {$req['remarks']}\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>

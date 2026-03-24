<?php
// Simple debug script to test the API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing API components...\n";

// Test 1: Check if config loads
try {
    require 'config/db.php';
    echo "✓ Config loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Config failed: " . $e->getMessage() . "\n";
    exit;
}

// Test 2: Check PDO connection
if (isset($pdo)) {
    echo "✓ PDO connection available\n";
    
    // Test 3: Simple query
    try {
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "✓ Basic query works: " . $result['test'] . "\n";
    } catch (Exception $e) {
        echo "✗ Query failed: " . $e->getMessage() . "\n";
    }
    
    // Test 4: Test requisitions table
    try {
        $stmt = $pdo->query("DESCRIBE requisitions");
        $columns = $stmt->fetchAll();
        echo "✓ Requisitions table exists with " . count($columns) . " columns\n";
    } catch (Exception $e) {
        echo "✗ Requisitions table error: " . $e->getMessage() . "\n";
    }
    
    // Test 5: Test requisition_items table
    try {
        $stmt = $pdo->query("DESCRIBE requisition_items");
        $columns = $stmt->fetchAll();
        echo "✓ Requisition_items table exists with " . count($columns) . " columns\n";
    } catch (Exception $e) {
        echo "✗ Requisition_items table error: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "✗ PDO connection NOT available\n";
}

echo "\nTesting session simulation...\n";
session_start();
$_SESSION['user_id'] = 1;
echo "✓ Session set with user_id: " . $_SESSION['user_id'] . "\n";

// Test the actual API query
try {
    $stmt = $pdo->prepare("SELECT r.*, ri.item_name, ri.quantity, ri.unit 
                           FROM requisitions r 
                           LEFT JOIN requisition_items ri ON r.id = ri.requisition_id 
                           WHERE r.user_id = ? 
                           ORDER BY r.id DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $requisitions = $stmt->fetchAll();
    echo "✓ API query executed successfully\n";
    echo "Found " . count($requisitions) . " rows\n";
    
    if (!empty($requisitions)) {
        print_r($requisitions[0]);
    }
} catch (Exception $e) {
    echo "✗ API query failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>

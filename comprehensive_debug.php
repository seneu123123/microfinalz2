<?php
// Comprehensive debug script for requisition API 500 error

require_once 'config/db.php';

try {
    echo "=== COMPREHENSIVE REQUISITION API DEBUG ===\n";
    
    // 1. Test basic database operations
    echo "1. Testing basic database operations...\n";
    
    // Test if we can create a simple table
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50))");
    echo "Created test table\n";
    
    // Test simple insert
    $stmt = $pdo->prepare("INSERT INTO test_table (name) VALUES (?)");
    $stmt->execute(['Test Record']);
    $insertId = $pdo->lastInsertId();
    echo "Simple insert ID: $insertId\n";
    
    // Test lastInsertId
    $stmt = $pdo->prepare("SELECT LAST_INSERT_ID()");
    $stmt->execute();
    $lastId = $stmt->fetchColumn();
    echo "LAST_INSERT_ID(): $lastId\n";
    
    // Clean up
    $pdo->exec("DROP TABLE IF EXISTS test_table");
    echo "Dropped test table\n";
    
    // 2. Check requisitions table structure and status
    echo "\n2. Checking requisitions table...\n";
    
    // Check if table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'requisitions'");
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "ERROR: requisitions table does not exist!\n";
        exit;
    }
    
    echo "Table exists: OK\n";
    
    // Check table structure
    $stmt = $pdo->prepare("DESCRIBE requisitions");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} ({$column['Extra']})\n";
    }
    
    // Check auto increment
    $stmt = $pdo->prepare("SHOW TABLE STATUS LIKE 'requisitions'");
    $stmt->execute();
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Auto increment status:\n";
    echo "- Auto_increment: {$status['Auto_increment']}\n";
    echo "- Engine: {$status['Engine']}\n";
    
    // Check current data
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM requisitions");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo "Total records: $count\n";
    
    // Check for ID 0 records
    $stmt = $pdo->prepare("SELECT COUNT(*) as zero_count FROM requisitions WHERE id = 0");
    $stmt->execute();
    $zeroCount = $stmt->fetchColumn();
    echo "Records with ID 0: $zeroCount\n";
    
    // Test actual API endpoint
    echo "\n3. Testing API endpoint directly...\n";
    
    // Simulate the exact API call
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SESSION['user_id'] = 1;
    
    $input = [
        'remarks' => 'Test Requisition',
        'items' => [
            ['name' => 'Test Item 1', 'qty' => 5, 'unit' => 'pcs'],
            ['name' => 'Test Item 2', 'qty' => 10, 'unit' => 'boxes']
        ]
    ];
    
    echo "Input data: " . json_encode($input) . "\n";
    
    // Include the actual API file
    ob_start();
    include 'api/requisition.php';
    $output = ob_get_clean();
    
    echo "API Output: $output\n";
    
    // Parse the response
    $response = json_decode($output, true);
    if ($response) {
        echo "Parsed response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Failed to parse JSON response\n";
    }
    
} catch (Exception $e) {
    echo 'Debug script error: ' . $e->getMessage() . "\n";
    echo 'Stack trace: ' . $e->getTraceAsString() . "\n";
}
?>

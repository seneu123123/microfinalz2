<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection...\n";

try {
    // Test with the exact same credentials as db.php
    $pdo = new PDO("mysql:host=localhost;dbname=logistics_db;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    echo "✓ PDO connection successful\n";
    
    // Test basic query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✓ Users table has " . $result['count'] . " rows\n";
    
    // Test requisitions table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM requisitions");
    $result = $stmt->fetch();
    echo "✓ Requisitions table has " . $result['count'] . " rows\n";
    
    // Test the problematic query
    session_start();
    $_SESSION['user_id'] = 1;
    
    $stmt = $pdo->prepare("SELECT r.*, ri.item_name, ri.quantity, ri.unit 
                           FROM requisitions r 
                           LEFT JOIN requisition_items ri ON r.id = ri.requisition_id 
                           WHERE r.user_id = ? 
                           ORDER BY r.id DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $requisitions = $stmt->fetchAll();
    echo "✓ Complex query successful, found " . count($requisitions) . " rows\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>

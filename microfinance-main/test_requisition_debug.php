<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config/db.php';

echo "Testing database connection...\n";

if (isset($pdo)) {
    echo "PDO connection available\n";
} else {
    echo "PDO connection NOT available\n";
}

if (isset($conn)) {
    echo "MySQLi connection available\n";
} else {
    echo "MySQLi connection NOT available\n";
}

// Test query on requisitions table
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM requisitions");
    $result = $stmt->fetch();
    echo "Requisitions table has " . $result['count'] . " rows\n";
} catch (Exception $e) {
    echo "Error querying requisitions: " . $e->getMessage() . "\n";
}

// Test the API endpoint logic
$_SESSION['user_id'] = 1; // Simulate logged in user

echo "\nTesting API logic...\n";

try {
    $stmt = $pdo->prepare("SELECT * FROM requisitions WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $data = $stmt->fetchAll();
    echo "Found " . count($data) . " requisitions for user 1\n";
    if (!empty($data)) {
        print_r($data[0]);
    }
} catch (Exception $e) {
    echo "Error in API logic: " . $e->getMessage() . "\n";
}
?>

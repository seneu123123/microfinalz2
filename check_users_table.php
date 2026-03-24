<?php
require_once 'config/db.php';

echo "Checking users table structure...\n";
$result = $conn->query('DESCRIBE users');

if ($result) {
    echo "Users table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Null'] . ' - ' . $row['Key'] . "\n";
    }
} else {
    echo "Users table not found. Checking available tables:\n";
    $tables = $conn->query('SHOW TABLES');
    while ($table = $tables->fetch_array()) {
        echo $table[0] . "\n";
    }
}

$conn->close();
?>

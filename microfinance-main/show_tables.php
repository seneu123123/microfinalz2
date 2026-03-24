<?php
// Show all tables in the database
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Available Tables in logistics_db</h2>";

$conn = new mysqli('localhost', 'root', '', 'logistics_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SHOW TABLES");
echo "<table border='1'><tr><th>Table Name</th><th>Records</th><th>Sample Fields</th></tr>";

while ($row = $result->fetch_assoc()) {
    $tableName = array_values($row)[0];
    
    // Get record count
    $countResult = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
    $count = $countResult->fetch_assoc()['count'];
    
    // Get sample fields
    $fieldsResult = $conn->query("DESCRIBE `$tableName`");
    $fields = [];
    while ($fieldRow = $fieldsResult->fetch_assoc()) {
        $fields[] = $fieldRow['Field'];
    }
    $sampleFields = implode(', ', array_slice($fields, 0, 5));
    
    echo "<tr>";
    echo "<td><strong>$tableName</strong></td>";
    echo "<td>$count</td>";
    echo "<td><small>$sampleFields...</small></td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Which table should I use instead of 'vendors'?</h3>";
echo "<p>Current configuration uses: <strong>vendors</strong> table</p>";
echo "<p>Please tell me which table to use, and I'll update all the APIs accordingly.</p>";

$conn->close();
?>

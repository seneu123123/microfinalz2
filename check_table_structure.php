<?php
/**
 * Check existing table structure
 */

require_once 'config/db.php';

echo "<h2>Checking Table Structure</h2>";

// Check if audit_schedules table exists and show its structure
$sql = "DESCRIBE audit_schedules";
$result = $conn->query($sql);

if ($result) {
    echo "<h3>audit_schedules table structure:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>audit_schedules table does not exist or error: " . $conn->error . "</p>";
}

// Show all tables
echo "<h3>All tables in database:</h3>";
$sql = "SHOW TABLES";
$result = $conn->query($sql);

if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . $row['Tables_in_' . $conn->real_query . ']'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Error showing tables: " . $conn->error . "</p>";
}
?>

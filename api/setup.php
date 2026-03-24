<?php
// api/setup.php
require 'db.php';

try {
    // Enable error reporting
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read the SQL file (Assuming you saved the SQL above as database.sql)
    // If you didn't save the file, you can paste the SQL string directly here.
    $sql = file_get_contents('../database.sql');

    if (!$sql) {
        die("Error: Could not find database.sql file.");
    }

    // Execute the SQL
    $pdo->exec($sql);

    echo "<h1>Database Setup Complete!</h1>";
    echo "<p>All tables have been created successfully.</p>";
    echo "<a href='../login.html'>Go to Login</a>";

} catch (PDOException $e) {
    echo "<h1>Setup Failed</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
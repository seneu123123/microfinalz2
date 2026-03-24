<?php
// Force PHP to show us the exact errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = '127.0.0.1'; 
$port = '3307'; // Let's test standard port first
$db   = 'microfinance_db';
$user = 'root';
$pass = '';

echo "<h3>Diagnostic Test</h3>";
echo "Attempting to connect to database '<b>$db</b>' on port <b>$port</b>...<br><br>";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "<strong style='color: green;'>SUCCESS! PHP is talking to phpMyAdmin perfectly.</strong>";
} catch (PDOException $e) {
    echo "<strong style='color: red;'>FAILED! The door is locked.</strong><br><br>";
    echo "<b>Exact Error Message:</b> " . $e->getMessage();
}
?>
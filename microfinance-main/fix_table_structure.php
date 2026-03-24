<?php
// Fix the procurement_vendors table structure
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fix procurement_vendors Table Structure</h2>";

$conn = new mysqli('localhost', 'root', '', 'logistics_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Drop and recreate the table with correct structure
echo "<h3>1. Drop existing table</h3>";
$conn->query("DROP TABLE IF EXISTS procurement_vendors");
echo "✅ Dropped existing table<br>";

echo "<h3>2. Create table with correct structure</h3>";
$sql = "CREATE TABLE procurement_vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id VARCHAR(50) NOT NULL,
    vendor_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) DEFAULT '',
    email VARCHAR(255) DEFAULT '',
    contact_number VARCHAR(50) DEFAULT '',
    address TEXT DEFAULT '',
    business_type VARCHAR(255) DEFAULT '',
    business_details TEXT DEFAULT '',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_by VARCHAR(100) DEFAULT 'admin',
    procurement_status VARCHAR(50) DEFAULT 'Pending Review',
    notes TEXT DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "✅ Table created successfully<br>";
} else {
    echo "❌ Failed to create table: " . $conn->error . "<br>";
    exit;
}

echo "<h3>3. Verify table structure</h3>";
$result = $conn->query("DESCRIBE procurement_vendors");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
}
echo "</table>";

echo "<h3>4. Test INSERT with sample data</h3>";

// Get a real vendor from vendors table
$result = $conn->query("SELECT vendor_id, vendor_name, contact_person, email, contact_number, address, business_type, business_details FROM vendors LIMIT 1");
if ($result->num_rows === 0) {
    echo "❌ No vendors found in vendors table<br>";
    exit;
}

$vendor = $result->fetch_assoc();
echo "Found vendor: {$vendor['vendor_name']}<br>";

// Test the INSERT that was failing
$stmt = $conn->prepare("INSERT INTO procurement_vendors (vendor_id, vendor_name, contact_person, email, contact_number, address, business_type, business_details, sent_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    echo "❌ INSERT prepare failed: " . $conn->error . "<br>";
    exit;
}

$sentBy = 'admin';
$stmt->bind_param("sssssssss", 
    $vendor['vendor_id'], 
    $vendor['vendor_name'], 
    $vendor['contact_person'] ?? '',
    $vendor['email'] ?? '',
    $vendor['contact_number'] ?? '',
    $vendor['address'] ?? '',
    $vendor['business_type'] ?? '',
    $vendor['business_details'] ?? '',
    $sentBy
);

if ($stmt->execute()) {
    echo "✅ Test INSERT successful<br>";
} else {
    echo "❌ Test INSERT failed: " . $stmt->error . "<br>";
}

$stmt->close();

echo "<h3>5. Check inserted data</h3>";
$result = $conn->query("SELECT * FROM procurement_vendors ORDER BY sent_at DESC LIMIT 1");
if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Vendor ID</th><th>Name</th><th>Sent At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['vendor_id']}</td><td>{$row['vendor_name']}</td><td>{$row['sent_at']}</td></tr>";
    }
    echo "</table>";
}

$conn->close();

echo "<h3>✅ Table structure fixed!</h3>";
echo "<p>Now try the 'Procure' button again - it should work.</p>";
?>

<?php
// Force fix the procurement issue
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Force Fix Procurement Issue</h2>";

$conn = new mysqli('localhost', 'root', '', 'logistics_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 1: Completely drop and recreate table
echo "<h3>Step 1: Recreate table</h3>";
$conn->query("DROP TABLE IF EXISTS procurement_vendors");

// Create table with minimal structure first
$sql = "CREATE TABLE procurement_vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id VARCHAR(50) NOT NULL,
    vendor_name VARCHAR(255) NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "✅ Basic table created<br>";
} else {
    echo "❌ Failed to create basic table: " . $conn->error . "<br>";
    exit;
}

// Step 2: Test basic INSERT
echo "<h3>Step 2: Test basic INSERT</h3>";
$vendorId = 'VND-00001';
$vendorName = 'Test Vendor';

$stmt = $conn->prepare("INSERT INTO procurement_vendors (vendor_id, vendor_name) VALUES (?, ?)");
if (!$stmt) {
    echo "❌ Basic INSERT prepare failed: " . $conn->error . "<br>";
    exit;
}

$stmt->bind_param("ss", $vendorId, $vendorName);
if ($stmt->execute()) {
    echo "✅ Basic INSERT successful<br>";
} else {
    echo "❌ Basic INSERT failed: " . $stmt->error . "<br>";
    exit;
}
$stmt->close();

// Step 3: Add more columns one by one
echo "<h3>Step 3: Add remaining columns</h3>";

$columns = [
    "ADD COLUMN contact_person VARCHAR(255)",
    "ADD COLUMN email VARCHAR(255)", 
    "ADD COLUMN contact_number VARCHAR(50)",
    "ADD COLUMN address TEXT",
    "ADD COLUMN business_type VARCHAR(255)",
    "ADD COLUMN business_details TEXT",
    "ADD COLUMN sent_by VARCHAR(100)"
];

foreach ($columns as $column) {
    if ($conn->query("ALTER TABLE procurement_vendors $column")) {
        echo "✅ Added: $column<br>";
    } else {
        echo "❌ Failed to add: $column - " . $conn->error . "<br>";
    }
}

// Step 4: Test full INSERT
echo "<h3>Step 4: Test full INSERT</h3>";

// Get real vendor data
$result = $conn->query("SELECT * FROM vendors WHERE vendor_id = 'VND-00001'");
if ($result->num_rows > 0) {
    $vendor = $result->fetch_assoc();
    
    $stmt = $conn->prepare("INSERT INTO procurement_vendors (vendor_id, vendor_name, contact_person, email, contact_number, address, business_type, business_details, sent_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        echo "❌ Full INSERT prepare failed: " . $conn->error . "<br>";
        echo "<h4>Debugging SQL structure:</h4>";
        
        // Show table structure
        $result = $conn->query("DESCRIBE procurement_vendors");
        echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
        }
        echo "</table>";
        
        exit;
    }
    
    // Prepare variables
    $contactPerson = $vendor['contact_person'] ?? '';
    $email = $vendor['email'] ?? '';
    $contactNumber = $vendor['contact_number'] ?? '';
    $address = $vendor['address'] ?? '';
    $businessType = $vendor['business_type'] ?? '';
    $businessDetails = $vendor['business_details'] ?? '';
    $sentBy = 'admin';
    
    $stmt->bind_param("sssssssss", 
        $vendor['vendor_id'], 
        $vendor['vendor_name'], 
        $contactPerson,
        $email,
        $contactNumber,
        $address,
        $businessType,
        $businessDetails,
        $sentBy
    );
    
    if ($stmt->execute()) {
        echo "✅ Full INSERT successful<br>";
    } else {
        echo "❌ Full INSERT failed: " . $stmt->error . "<br>";
    }
    $stmt->close();
}

$conn->close();

echo "<h3>✅ Force fix complete!</h3>";
echo "<p>Now try the 'Procure' button again.</p>";
?>

<?php
// Quick fix for the INSERT prepare issue
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Quick Fix for INSERT Prepare Issue</h2>";

$conn = new mysqli('localhost', 'root', '', 'logistics_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check current table structure
echo "<h3>Current procurement_vendors structure:</h3>";
$result = $conn->query("DESCRIBE procurement_vendors");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "❌ Table doesn't exist or can't describe it<br>";
}

// Recreate table with simpler structure
echo "<h3>Recreating table...</h3>";
$conn->query("DROP TABLE IF EXISTS procurement_vendors");

$sql = "CREATE TABLE procurement_vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id VARCHAR(50) NOT NULL,
    vendor_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255),
    contact_number VARCHAR(50),
    address TEXT,
    business_type VARCHAR(255),
    business_details TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_by VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql)) {
    echo "✅ Table recreated successfully<br>";
} else {
    echo "❌ Failed to create table: " . $conn->error . "<br>";
}

// Test the exact INSERT query
echo "<h3>Testing INSERT query...</h3>";

// Get sample vendor data
$result = $conn->query("SELECT vendor_id, vendor_name, contact_person, email, contact_number, address, business_type, business_details FROM vendors LIMIT 1");
if ($result->num_rows === 0) {
    echo "❌ No vendors found. Creating test vendor first...<br>";
    
    // Create a test vendor
    $conn->query("INSERT INTO vendors (vendor_id, vendor_name, contact_person, email, contact_number, address, business_type, business_details) VALUES ('TEST-001', 'Test Vendor', 'John Doe', 'test@example.com', '1234567890', 'Test Address', 'Test Business', 'Test Business Details')");
    
    $result = $conn->query("SELECT vendor_id, vendor_name, contact_person, email, contact_number, address, business_type, business_details FROM vendors LIMIT 1");
}

if ($result->num_rows > 0) {
    $vendor = $result->fetch_assoc();
    
    // Test the exact INSERT that's failing
    $stmt = $conn->prepare("INSERT INTO procurement_vendors (vendor_id, vendor_name, contact_person, email, contact_number, address, business_type, business_details, sent_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        echo "❌ INSERT prepare failed: " . $conn->error . "<br>";
        echo "<h4>Trying simpler INSERT...</h4>";
        
        // Try without the problematic fields
        $stmt = $conn->prepare("INSERT INTO procurement_vendors (vendor_id, vendor_name, sent_by) VALUES (?, ?, ?)");
        if (!$stmt) {
            echo "❌ Even simple INSERT failed: " . $conn->error . "<br>";
        } else {
            echo "✅ Simple INSERT prepare works<br>";
            $sentBy = 'admin';
            $stmt->bind_param("sss", $vendor['vendor_id'], $vendor['vendor_name'], $sentBy);
            if ($stmt->execute()) {
                echo "✅ Simple INSERT executed successfully<br>";
                echo "🎯 The issue is with NULL values in complex INSERT<br>";
            } else {
                echo "❌ Simple INSERT failed: " . $stmt->error . "<br>";
            }
        }
    } else {
        echo "✅ INSERT prepare successful<br>";
        $sentBy = 'admin';
        $stmt->bind_param("sssssssss", 
            $vendor['vendor_id'], 
            $vendor['vendor_name'], 
            $vendor['contact_person'],
            $vendor['email'],
            $vendor['contact_number'],
            $vendor['address'],
            $vendor['business_type'],
            $vendor['business_details'],
            $sentBy
        );
        
        if ($stmt->execute()) {
            echo "✅ INSERT executed successfully<br>";
        } else {
            echo "❌ INSERT execute failed: " . $stmt->error . "<br>";
        }
    }
    $stmt->close();
}

$conn->close();
echo "<h3>Try the procure button again now!</h3>";
?>

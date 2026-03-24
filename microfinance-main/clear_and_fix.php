<?php
// Clear procurement table and fix the issue
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Clear and Fix Procurement Issue</h2>";

$conn = new mysqli('localhost', 'root', '', 'logistics_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 1: Clear the procurement_vendors table completely
echo "<h3>Step 1: Clear procurement_vendors table</h3>";
$conn->query("DELETE FROM procurement_vendors");
echo "✅ Cleared all records from procurement_vendors<br>";

// Step 2: Reset auto-increment
echo "<h3>Step 2: Reset auto-increment</h3>";
$conn->query("ALTER TABLE procurement_vendors AUTO_INCREMENT = 1");
echo "✅ Reset auto-increment to 1<br>";

// Step 3: Verify table is empty
echo "<h3>Step 3: Verify table is empty</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM procurement_vendors");
$row = $result->fetch_assoc();
echo "<p>Records in procurement_vendors: <strong>{$row['count']}</strong></p>";

// Step 4: Test manual insert
echo "<h3>Step 4: Test manual insert</h3>";

// Get first vendor
$result = $conn->query("SELECT vendor_id, vendor_name, contact_person, email, contact_number, address, business_type, business_details FROM vendors LIMIT 1");
if ($result->num_rows > 0) {
    $vendor = $result->fetch_assoc();
    
    // Manual insert without prepared statement
    $sql = "INSERT INTO procurement_vendors (vendor_id, vendor_name, contact_person, email, contact_number, address, business_type, business_details, sent_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
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
            echo "✅ Manual insert successful<br>";
        } else {
            echo "❌ Manual insert failed: " . $stmt->error . "<br>";
        }
        $stmt->close();
    } else {
        echo "❌ Prepare failed: " . $conn->error . "<br>";
    }
    
    // Verify the insert
    $result = $conn->query("SELECT COUNT(*) as count FROM procurement_vendors");
    $row = $result->fetch_assoc();
    echo "<p>Records after insert: <strong>{$row['count']}</strong></p>";
    
    if ($row['count'] > 0) {
        echo "<h3>✅ SUCCESS! Table is working</h3>";
        echo "<p>Now try the 'Procure' button again - it should work!</p>";
    }
}

$conn->close();
?>

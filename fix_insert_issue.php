<?php
// Fix the INSERT issue in procurement_vendors.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fix INSERT Issue</h2>";

$conn = new mysqli('localhost', 'root', '', 'logistics_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 1: Check current state
echo "<h3>Step 1: Current State</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM procurement_vendors");
$row = $result->fetch_assoc();
echo "<p>Vendors in procurement table: <strong>{$row['count']}</strong></p>";

// Step 2: Test direct INSERT without API
echo "<h3>Step 2: Test Direct INSERT</h3>";

// Get a real vendor
$result = $conn->query("SELECT vendor_id, vendor_name, contact_person, email, contact_number, address, business_type, business_details FROM vendors LIMIT 1");
if ($result->num_rows > 0) {
    $vendor = $result->fetch_assoc();
    
    echo "<p>Testing with vendor: <strong>{$vendor['vendor_name']} ({$vendor['vendor_id']})</strong></p>";
    
    // Try the exact same INSERT that the API uses
    $sql = "INSERT INTO procurement_vendors (vendor_id, vendor_name, contact_person, email, contact_number, address, business_type, business_details, sent_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        echo "✅ SQL prepare successful<br>";
        
        // Prepare variables exactly like the API
        $contactPerson = $vendor['contact_person'] ?? '';
        $email = $vendor['email'] ?? '';
        $contactNumber = $vendor['contact_number'] ?? '';
        $address = $vendor['address'] ?? '';
        $businessType = $vendor['business_type'] ?? '';
        $businessDetails = $vendor['business_details'] ?? '';
        $sentBy = 'admin';
        
        echo "<p>Variables prepared:<br>";
        echo "- vendor_id: {$vendor['vendor_id']}<br>";
        echo "- vendor_name: {$vendor['vendor_name']}<br>";
        echo "- contact_person: '$contactPerson'<br>";
        echo "- email: '$email'<br>";
        echo "- contact_number: '$contactNumber'<br>";
        echo "- address: '$address'<br>";
        echo "- business_type: '$businessType'<br>";
        echo "- business_details: '$businessDetails'<br>";
        echo "- sent_by: '$sentBy'<br></p>";
        
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
            echo "✅ Direct INSERT successful<br>";
            echo "✅ Inserted ID: " . $conn->insert_id . "<br>";
        } else {
            echo "❌ Direct INSERT failed: " . $stmt->error . "<br>";
        }
        $stmt->close();
    } else {
        echo "❌ SQL prepare failed: " . $conn->error . "<br>";
    }
    
    // Check result
    $result = $conn->query("SELECT COUNT(*) as count FROM procurement_vendors");
    $row = $result->fetch_assoc();
    echo "<p>Vendors after INSERT: <strong>{$row['count']}</strong></p>";
    
    if ($row['count'] > 0) {
        echo "<h3>✅ SUCCESS! Direct INSERT works</h3>";
        echo "<p>The issue is in the API logic, not the database.</p>";
        
        // Show the inserted data
        $result = $conn->query("SELECT * FROM procurement_vendors ORDER BY sent_at DESC LIMIT 1");
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo "<h4>Inserted Data:</h4>";
            echo "<pre>" . print_r($row, true) . "</pre>";
        }
    }
} else {
    echo "❌ No vendors available for testing<br>";
}

$conn->close();

echo "<h3>Next Steps:</h3>";
echo "<p>If direct INSERT works, the API has a logic issue that needs fixing.</p>";
echo "<p>If direct INSERT fails, there's a database structure problem.</p>";
?>

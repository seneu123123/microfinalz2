<?php
// Debug the complete procurement flow
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Complete Procurement Flow</h2>";

$conn = new mysqli('localhost', 'root', '', 'logistics_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 1: Check vendors table
echo "<h3>1. Vendors Table (Source)</h3>";
$result = $conn->query("SELECT vendor_id, vendor_name, business_type, business_details FROM vendors LIMIT 3");
if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Business Type</th><th>Business Details</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['vendor_id']}</td><td>{$row['vendor_name']}</td><td>{$row['business_type'] ?? '-'}</td><td>" . substr($row['business_details'] ?? '-', 0, 30) . "...</td></tr>";
    }
    echo "</table>";
} else {
    echo "❌ No vendors in source table<br>";
}

// Step 2: Check procurement_vendors table
echo "<h3>2. Procurement_Vendors Table (Destination)</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM procurement_vendors");
$row = $result->fetch_assoc();
echo "<p>Vendors in procurement: <strong>{$row['count']}</strong></p>";

if ($row['count'] > 0) {
    $result = $conn->query("SELECT vendor_id, vendor_name, sent_at FROM procurement_vendors ORDER BY sent_at DESC LIMIT 3");
    echo "<table border='1'><tr><th>Vendor ID</th><th>Name</th><th>Sent At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['vendor_id']}</td><td>{$row['vendor_name']}</td><td>{$row['sent_at']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "❌ No vendors sent to procurement yet<br>";
}

// Step 3: Test manual API call
echo "<h3>3. Test Manual API Call</h3>";

// Get first vendor
$result = $conn->query("SELECT vendor_id, vendor_name FROM vendors LIMIT 1");
if ($result->num_rows > 0) {
    $vendor = $result->fetch_assoc();
    $vendorId = $vendor['vendor_id'];
    
    echo "<p>Testing with vendor: <strong>{$vendor['vendor_name']} ($vendorId)</strong></p>";
    
    // Simulate the API call
    $testData = [
        'vendor_id' => $vendorId,
        'sent_by' => 'admin'
    ];

    $ch = curl_init('http://localhost/microfinance-main/api/procurement_vendors.php?action=send_to_procurement');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($response);
    echo "</pre>";

    $decoded = json_decode($response, true);
    if ($decoded && $decoded['status'] === 'success') {
        echo "<p style='color: green;'>✅ API call successful!</p>";
        
        // Check if vendor was added
        $result = $conn->query("SELECT COUNT(*) as count FROM procurement_vendors WHERE vendor_id = '$vendorId'");
        $row = $result->fetch_assoc();
        echo "<p>Vendor in procurement table: <strong>{$row['count']}</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ API call failed: " . ($decoded['message'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "❌ No vendors available for testing<br>";
}

// Step 4: Check procurement.php API call
echo "<h3>4. Test procurement.php API Call</h3>";
$ch = curl_init('http://localhost/microfinance-main/api/procurement_vendors.php?action=get_procurement_vendors');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
echo htmlspecialchars($response);
echo "</pre>";

$conn->close();

echo "<h3>5. What This Means:</h3>";
echo "<p>If Step 3 shows success but procurement.php still shows 'No vendors', there might be a caching issue or the API call in procurement.php is failing.</p>";
?>

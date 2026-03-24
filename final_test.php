<?php
// Final test of the procurement fix
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Final Test - Procurement Fix</h2>";

// Get a real vendor ID from the database
$conn = new mysqli('localhost', 'root', '', 'logistics_db');
$result = $conn->query("SELECT vendor_id, vendor_name FROM vendors LIMIT 1");

if ($result->num_rows > 0) {
    $vendor = $result->fetch_assoc();
    $vendorId = $vendor['vendor_id'];
    echo "<p>Testing with vendor: <strong>{$vendor['vendor_name']} ($vendorId)</strong></p>";
    
    // Test the API call that was failing
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

    echo "<h3>Test Result:</h3>";
    echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($response);
    echo "</pre>";

    $decoded = json_decode($response, true);
    if ($decoded) {
        if ($decoded['status'] === 'success') {
            echo "<h3>✅ SUCCESS! The fix is working!</h3>";
            echo "<p style='color: green;'>{$decoded['message']}</p>";
            echo "<p><strong>Now try the 'Procure' button in vendor-registration.html - it should work!</strong></p>";
        } else {
            echo "<h3>❌ Still has issues:</h3>";
            echo "<p style='color: red;'>{$decoded['message']}</p>";
        }
    } else {
        echo "<h3>❌ Invalid JSON response</h3>";
    }
    
    // Check if vendor was added to procurement table
    $result = $conn->query("SELECT COUNT(*) as count FROM procurement_vendors WHERE vendor_id = '$vendorId'");
    $row = $result->fetch_assoc();
    echo "<h3>Database Check:</h3>";
    echo "<p>Vendors in procurement table: <strong>{$row['count']}</strong></p>";
    
} else {
    echo "<p>❌ No vendors found in database</p>";
}

$conn->close();
?>

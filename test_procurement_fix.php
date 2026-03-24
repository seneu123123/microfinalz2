<?php
// Test the procurement API fix
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Test Procurement API Fix</h2>";

// Test the POST request that was failing
$testData = [
    'vendor_id' => 'VND-00001', // Change this to a real vendor ID from your database
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

echo "<h3>Test Results:</h3>";
echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
echo htmlspecialchars($response);
echo "</pre>";

// Try to parse JSON
$decoded = json_decode($response, true);
if ($decoded) {
    echo "<h3>Parsed Response:</h3>";
    if ($decoded['status'] === 'success') {
        echo "<p style='color: green;'>✅ SUCCESS: {$decoded['message']}</p>";
    } else {
        echo "<p style='color: red;'>❌ ERROR: {$decoded['message']}</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Invalid JSON response</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<p>If this test shows success, try the 'Procure' button in vendor-registration.html again.</p>";
echo "<p>If it still shows an error, the error message above will tell us exactly what's wrong.</p>";
?>

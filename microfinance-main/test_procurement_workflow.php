<?php
// Test script to verify the complete procurement workflow
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Test Procurement Workflow</h2>";

try {
    $conn = new mysqli('localhost', 'root', '', 'logistics_db');
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h3>1. Check vendors table</h3>";
    $result = $conn->query("SELECT vendor_id, vendor_name, business_type, business_details FROM vendors LIMIT 3");
    
    if ($result->num_rows === 0) {
        echo "❌ No vendors found. Please add vendors first.<br>";
        echo "<a href='admin/vendor-registration.html' target='_blank'>📝 Add Vendors</a><br>";
    } else {
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Business Type</th><th>Business Details</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['vendor_id']}</td><td>{$row['vendor_name']}</td><td>{$row['business_type'] ?? '-'}</td><td>" . substr($row['business_details'] ?? '-', 0, 50) . "...</td></tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>2. Check procurement_vendors table</h3>";
    $result = $conn->query("SELECT pv.vendor_id, pv.vendor_name, pv.business_type, pv.business_details, pv.sent_at FROM procurement_vendors pv ORDER BY pv.sent_at DESC");
    
    if ($result->num_rows === 0) {
        echo "ℹ️ No vendors sent to procurement yet.<br>";
        echo "<p>Click the 'Procure' button in vendor-registration.html to send vendors to procurement.</p>";
    } else {
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Business Type</th><th>Business Details</th><th>Sent At</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['vendor_id']}</td><td>{$row['vendor_name']}</td><td>{$row['business_type'] ?? '-'}</td><td>" . substr($row['business_details'] ?? '-', 0, 50) . "...</td><td>{$row['sent_at']}</td></tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>3. Test API Endpoints</h3>";
    echo "<a href='../api/procurement_vendors.php?action=get_procurement_vendors' target='_blank'>🔍 Test Get Procurement Vendors API</a><br>";
    
    echo "<h3>4. Quick Actions</h3>";
    echo "<a href='admin/vendor-registration.html' target='_blank'>📝 Vendor Registration</a><br>";
    echo "<a href='admin/vendor-reports.html' target='_blank'>📊 Vendor Reports</a><br>";
    echo "<a href='admin/procurement.php' target='_blank'>📦 Procurement Dashboard</a><br>";
    
    echo "<h3>✅ Workflow Status</h3>";
    echo "<p><strong>Complete Workflow:</strong></p>";
    echo "<ol>";
    echo "<li>Register vendors in <strong>vendor-registration.html</strong></li>";
    echo "<li>Click <strong>'Procure' button</strong> to send vendors to procurement</li>";
    echo "<li>View sent vendors in <strong>procurement.php</strong></li>";
    echo "<li>Or use <strong>vendor-reports.html</strong> to send vendors from reports</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

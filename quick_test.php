<?php
// Quick test to find the 500 error source
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Quick 500 Error Test</h2>";

// Test 1: vendors.php
echo "<h3>Testing vendors.php...</h3>";
$vendorsFile = __DIR__ . '/api/vendors.php';
if (file_exists($vendorsFile)) {
    // Check for syntax errors
    $output = shell_exec("php -l \"$vendorsFile\" 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✅ vendors.php syntax OK<br>";
    } else {
        echo "❌ vendors.php syntax error:<br>";
        echo "<pre style='color: red;'>" . htmlspecialchars($output) . "</pre>";
    }
} else {
    echo "❌ vendors.php not found<br>";
}

// Test 2: procurement_vendors.php  
echo "<h3>Testing procurement_vendors.php...</h3>";
$procurementFile = __DIR__ . '/api/procurement_vendors.php';
if (file_exists($procurementFile)) {
    $output = shell_exec("php -l \"$procurementFile\" 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✅ procurement_vendors.php syntax OK<br>";
    } else {
        echo "❌ procurement_vendors.php syntax error:<br>";
        echo "<pre style='color: red;'>" . htmlspecialchars($output) . "</pre>";
    }
} else {
    echo "❌ procurement_vendors.php not found<br>";
}

// Test 3: Database connection
echo "<h3>Testing database connection...</h3>";
try {
    $conn = new mysqli('localhost', 'root', '', 'logistics_db');
    if ($conn->connect_error) {
        echo "❌ DB connection failed: " . $conn->connect_error . "<br>";
    } else {
        echo "✅ DB connection OK<br>";
        
        // Test vendors table
        $result = $conn->query("SELECT COUNT(*) as count FROM vendors");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✅ vendors table OK ({$row['count']} records)<br>";
        } else {
            echo "❌ vendors table error: " . $conn->error . "<br>";
        }
        
        // Test procurement_vendors table
        $result = $conn->query("SELECT COUNT(*) as count FROM procurement_vendors");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "✅ procurement_vendors table OK ({$row['count']} records)<br>";
        } else {
            echo "❌ procurement_vendors table error: " . $conn->error . "<br>";
        }
    }
    $conn->close();
} catch (Exception $e) {
    echo "❌ Database exception: " . $e->getMessage() . "<br>";
}

// Test 4: Direct API calls
echo "<h3>Testing API endpoints...</h3>";

// Test vendors API
echo "<p>Testing vendors.php endpoint...</p>";
$ch = curl_init('http://localhost/microfinance-main/api/vendors.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ vendors.php HTTP 200 - OK<br>";
} else {
    echo "❌ vendors.php HTTP $httpCode - ERROR<br>";
    echo "<small>" . htmlspecialchars(substr($response, 0, 200)) . "...</small><br>";
}

// Test procurement API
echo "<p>Testing procurement_vendors.php endpoint...</p>";
$ch = curl_init('http://localhost/microfinance-main/api/procurement_vendors.php?action=get_procurement_vendors');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ procurement_vendors.php HTTP 200 - OK<br>";
} else {
    echo "❌ procurement_vendors.php HTTP $httpCode - ERROR<br>";
    echo "<small>" . htmlspecialchars(substr($response, 0, 200)) . "...</small><br>";
}

echo "<h3>Next Steps</h3>";
echo "<p>If you see syntax errors above, I need to fix those PHP files.</p>";
echo "<p>If you see database errors, I need to fix the database connection/tables.</p>";
echo "<p>If you see HTTP errors, I need to fix the API logic.</p>";
?>

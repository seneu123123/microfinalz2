<?php
// Debug script to identify the HTTP 500 error
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug HTTP 500 Error</h2>";

// Test 1: Check if vendor-registration.html has syntax errors
echo "<h3>1. Check vendor-registration.html syntax</h3>";
$htmlFile = __DIR__ . '/admin/vendor-registration.html';
if (file_exists($htmlFile)) {
    // Simple syntax check - look for obvious issues
    $content = file_get_contents($htmlFile);
    
    // Check for common syntax issues
    $issues = [];
    
    // Check for unmatched quotes
    $singleQuotes = substr_count($content, "'") - substr_count($content, "\\'");
    $doubleQuotes = substr_count($content, '"') - substr_count($content, '\\"');
    
    if ($singleQuotes % 2 !== 0) {
        $issues[] = "Unmatched single quotes detected";
    }
    if ($doubleQuotes % 2 !== 0) {
        $issues[] = "Unmatched double quotes detected";
    }
    
    // Check for the specific issue we just fixed
    if (strpos($content, '()</button>') !== false) {
        $issues[] = "Found '()</button>' syntax error";
    }
    
    if (empty($issues)) {
        echo "✅ No obvious syntax errors found<br>";
    } else {
        echo "❌ Issues found:<br>";
        foreach ($issues as $issue) {
            echo "- $issue<br>";
        }
    }
} else {
    echo "❌ vendor-registration.html not found<br>";
}

// Test 2: Check if the APIs are working
echo "<h3>2. Test API endpoints</h3>";

$apis = [
    'vendors.php' => '../api/vendors.php',
    'procurement_vendors.php' => '../api/procurement_vendors.php'
];

foreach ($apis as $name => $url) {
    echo "<p><strong>$name:</strong> ";
    
    $ch = curl_init('http://localhost/microfinance-main/' . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "✅ HTTP $httpCode - OK<br>";
    } elseif ($httpCode === 500) {
        echo "❌ HTTP $httpCode - Internal Server Error<br>";
        echo "<small>Error: " . htmlspecialchars(substr($response, 0, 200)) . "...</small><br>";
    } else {
        echo "⚠️ HTTP $httpCode - Other issue<br>";
    }
}

// Test 3: Check database connection
echo "<h3>3. Test database connection</h3>";
try {
    $conn = new mysqli('localhost', 'root', '', 'logistics_db');
    if ($conn->connect_error) {
        echo "❌ Database connection failed: " . $conn->connect_error . "<br>";
    } else {
        echo "✅ Database connection successful<br>";
        
        // Check if vendors table exists
        $result = $conn->query("SHOW TABLES LIKE 'vendors'");
        if ($result->num_rows > 0) {
            echo "✅ vendors table exists<br>";
        } else {
            echo "❌ vendors table not found<br>";
        }
        
        // Check if procurement_vendors table exists
        $result = $conn->query("SHOW TABLES LIKE 'procurement_vendors'");
        if ($result->num_rows > 0) {
            echo "✅ procurement_vendors table exists<br>";
        } else {
            echo "❌ procurement_vendors table not found<br>";
        }
    }
    $conn->close();
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h3>4. Quick fixes to try</h3>";
echo "<ol>";
echo "<li><a href='admin/vendor-registration.html' target='_blank'>Open vendor-registration.html directly</a></li>";
echo "<li><a href='api/vendors.php' target='_blank'>Test vendors.php API</a></li>";
echo "<li><a href='api/procurement_vendors.php?action=get_procurement_vendors' target='_blank'>Test procurement_vendors.php API</a></li>";
echo "<li><a href='admin/procurement.php' target='_blank'>Open procurement.php directly</a></li>";
echo "</ol>";

echo "<h3>5. If still getting 500 errors</h3>";
echo "<p>Check the PHP error log (usually in C:/xampp/apache/logs/error.log)</p>";
echo "<p>Or restart XAMPP services if needed.</p>";
?>

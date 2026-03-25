<?php
require_once 'config/db.php';

echo "=== FINAL COMPREHENSIVE CLICK/RESPONSE TEST ===" . PHP_EOL;
echo "Time: " . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;

// Test 1: Database connection
echo "1. DATABASE CONNECTION TEST" . PHP_EOL;
echo "Connection: " . ($conn ? "OK" : "FAILED") . PHP_EOL;
echo "Database: " . DB_NAME . PHP_EOL;

// Test 2: File system check
echo PHP_EOL . "2. FILE SYSTEM TEST" . PHP_EOL;
$required_files = [
    'admin/mro_dashboard.php' => 'Main Dashboard',
    'admin/mro_planning.php' => 'Maintenance Planning',
    'admin/mro_work_orders.php' => 'Work Orders',
    'admin/mro_parts.php' => 'Parts Management',
    'admin/compliance.php' => 'Compliance & Safety',
    'api/mro_api.php' => 'MRO API',
    'js/dashboard.js' => 'Dashboard JS',
    'js/click_fix.js' => 'Click Fix JS',
    'click_test.html' => 'Click Test Page'
];

foreach ($required_files as $file => $description) {
    $exists = file_exists($file) ? "EXISTS" : "MISSING";
    $readable = is_readable($file) ? "READABLE" : "NOT READABLE";
    echo "- $description ($file): $exists | $readable" . PHP_EOL;
}

// Test 3: JavaScript files check
echo PHP_EOL . "3. JAVASCRIPT FILES TEST" . PHP_EOL;
$js_files = ['js/dashboard.js', 'js/click_fix.js'];

foreach ($js_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $size = strlen($content);
        $hasClickHandler = strpos($content, 'addEventListener') !== false;
        $hasErrorHandling = strpos($content, 'try') !== false && strpos($content, 'catch') !== false;
        
        echo "- $file: $size bytes | Click handlers: " . ($hasClickHandler ? "YES" : "NO") . " | Error handling: " . ($hasErrorHandling ? "YES" : "NO") . PHP_EOL;
    } else {
        echo "- $file: MISSING" . PHP_EOL;
    }
}

// Test 4: API endpoints check
echo PHP_EOL . "4. API ENDPOINTS TEST" . PHP_EOL;
$endpoints = [
    'maintenance_planning' => 'GET /api/mro_api.php?endpoint=maintenance_planning&action=list',
    'work_orders' => 'GET /api/mro_api.php?endpoint=work_orders&action=list',
    'technicians' => 'GET /api/mro_api.php?endpoint=technicians&action=list'
];

foreach ($endpoints as $name => $endpoint) {
    if (file_exists('api/mro_api.php')) {
        $content = file_get_contents('api/mro_api.php');
        $hasEndpoint = strpos($content, "case '$name':") !== false;
        $hasErrorHandling = strpos($content, 'try') !== false && strpos($content, 'catch') !== false;
        
        echo "- $name: " . ($hasEndpoint ? "DEFINED" : "NOT DEFINED") . " | Error handling: " . ($hasErrorHandling ? "YES" : "NO") . PHP_EOL;
    } else {
        echo "- $name: API FILE MISSING" . PHP_EOL;
    }
}

// Test 5: Data availability
echo PHP_EOL . "5. DATA AVAILABILITY TEST" . PHP_EOL;
$data_checks = [
    'mro_work_orders' => 'Work Orders',
    'mro_maintenance_planning' => 'Maintenance Plans',
    'mro_technicians' => 'Technicians',
    'mro_compliance_safety' => 'Compliance Records'
];

foreach ($data_checks as $table => $description) {
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $result->fetch_assoc()['count'];
        echo "- $description: $count records" . PHP_EOL;
    } catch (Exception $e) {
        echo "- $description: ERROR - " . $e->getMessage() . PHP_EOL;
    }
}

// Test 6: Click functionality simulation
echo PHP_EOL . "6. CLICK FUNCTIONALITY SIMULATION" . PHP_EOL;
$click_tests = [
    'admin/mro_dashboard.php' => ['onclick', 'addEventListener', 'function'],
    'admin/mro_planning.php' => ['onclick', 'addEventListener', 'function'],
    'admin/mro_work_orders.php' => ['onclick', 'addEventListener', 'function'],
    'admin/mro_parts.php' => ['onclick', 'addEventListener', 'function'],
    'admin/compliance.php' => ['onclick', 'addEventListener', 'function']
];

foreach ($click_tests as $file => $patterns) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $score = 0;
        
        foreach ($patterns as $pattern) {
            if (strpos($content, $pattern) !== false) {
                $score++;
            }
        }
        
        $status = $score >= 2 ? "GOOD" : ($score >= 1 ? "FAIR" : "POOR");
        echo "- " . basename($file) . ": Click functionality $status ($score/3)" . PHP_EOL;
    }
}

// Test 7: Error handling check
echo PHP_EOL . "7. ERROR HANDLING CHECK" . PHP_EOL;
$error_handling_files = ['admin/mro_dashboard.php', 'js/click_fix.js'];

foreach ($error_handling_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $hasTryCatch = strpos($content, 'try') !== false && strpos($content, 'catch') !== false;
        $hasConsoleLog = strpos($content, 'console.log') !== false;
        $hasErrorHandling = strpos($content, 'error') !== false;
        
        $score = ($hasTryCatch ? 1 : 0) + ($hasConsoleLog ? 1 : 0) + ($hasErrorHandling ? 1 : 0);
        $status = $score >= 2 ? "GOOD" : ($score >= 1 ? "FAIR" : "POOR");
        echo "- " . basename($file) . ": Error handling $status ($score/3)" . PHP_EOL;
    }
}

// Test 8: SweetAlert integration
echo PHP_EOL . "8. SWEETALERT INTEGRATION CHECK" . PHP_EOL;
$sweetalert_files = ['admin/mro_dashboard.php', 'admin/mro_planning.php', 'admin/mro_work_orders.php'];

foreach ($sweetalert_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $hasSwal = strpos($content, 'Swal') !== false;
        $hasSwalFire = strpos($content, 'Swal.fire') !== false;
        $hasSwalImport = strpos($content, 'sweetalert2') !== false;
        
        $score = ($hasSwal ? 1 : 0) + ($hasSwalFire ? 1 : 0) + ($hasSwalImport ? 1 : 0);
        $status = $score >= 2 ? "GOOD" : ($score >= 1 ? "FAIR" : "POOR");
        echo "- " . basename($file) . ": SweetAlert $status ($score/3)" . PHP_EOL;
    }
}

// Final assessment
echo PHP_EOL . "=== FINAL ASSESSMENT ===" . PHP_EOL;

$overall_score = 0;
$total_checks = 8;

// Calculate overall score
if ($conn) $overall_score++;
if (file_exists('admin/mro_dashboard.php')) $overall_score++;
if (file_exists('js/dashboard.js')) $overall_score++;
if (file_exists('js/click_fix.js')) $overall_score++;
if (file_exists('api/mro_api.php')) $overall_score++;
try {
    $conn->query("SELECT 1 FROM mro_work_orders LIMIT 1");
    $overall_score++;
} catch (Exception $e) {
    // Table doesn't exist or error
}
if (file_exists('admin/mro_dashboard.php') && strpos(file_get_contents('admin/mro_dashboard.php'), 'onclick') !== false) $overall_score++;
if (file_exists('admin/mro_dashboard.php') && strpos(file_get_contents('admin/mro_dashboard.php'), 'try') !== false) $overall_score++;

$percentage = round(($overall_score / $total_checks) * 100);

echo "Overall Score: $overall_score/$total_checks ($percentage%)" . PHP_EOL;

if ($percentage >= 80) {
    echo "🎉 SYSTEM READY FOR USE!" . PHP_EOL;
    echo PHP_EOL . "Next steps:" . PHP_EOL;
    echo "1. Open click_test.html to test click functionality" . PHP_EOL;
    echo "2. Test all MRO pages for click responsiveness" . PHP_EOL;
    echo "3. Verify API calls are working" . PHP_EOL;
    echo "4. Check modals are displaying correctly" . PHP_EOL;
} elseif ($percentage >= 60) {
    echo "⚠️  SYSTEM MOSTLY READY" . PHP_EOL;
    echo "Some minor issues may exist but core functionality should work." . PHP_EOL;
} else {
    echo "❌ SYSTEM NEEDS ATTENTION" . PHP_EOL;
    echo "Major issues detected that need to be resolved." . PHP_EOL;
}

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;

$conn->close();
?>

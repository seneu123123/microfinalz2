<?php
require_once 'config/db.php';

echo "=== COMPREHENSIVE CLICK/RESPONSE DIAGNOSTIC ===" . PHP_EOL;
echo "Time: " . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;

// 1. Check database connection and data
echo "1. DATABASE STATUS" . PHP_EOL;
echo "Connection: " . ($conn ? "OK" : "FAILED") . PHP_EOL;
echo "Database: " . DB_NAME . PHP_EOL;

// Check if there's actual data to work with
$asset_count = $conn->query("SELECT COUNT(*) as count FROM assets")->fetch_assoc()['count'];
$mro_count = $conn->query("SELECT COUNT(*) as count FROM mro_work_orders")->fetch_assoc()['count'];
$user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

echo "Assets: $asset_count | MRO Work Orders: $mro_count | Users: $user_count" . PHP_EOL . PHP_EOL;

// 2. Check file permissions and accessibility
echo "2. FILE SYSTEM CHECK" . PHP_EOL;
$files_to_check = [
    'admin/mro_dashboard.php',
    'admin/mro_planning.php',
    'admin/mro_work_orders.php',
    'admin/mro_parts.php',
    'admin/compliance.php',
    'api/mro_api.php',
    'js/dashboard.js',
    'includes/sidebar.php'
];

foreach ($files_to_check as $file) {
    $exists = file_exists($file) ? "EXISTS" : "MISSING";
    $readable = is_readable($file) ? "READABLE" : "NOT READABLE";
    $size = file_exists($file) ? filesize($file) . " bytes" : "0 bytes";
    echo "- $file: $exists | $readable | $size" . PHP_EOL;
}

echo PHP_EOL;

// 3. Check JavaScript syntax and dependencies
echo "3. JAVASCRIPT ANALYSIS" . PHP_EOL;
$js_files = ['js/dashboard.js', 'admin/mro_dashboard.php'];

foreach ($js_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check for common JavaScript issues
        $issues = [];
        
        // Check for syntax errors (basic)
        if (substr_count($content, '{') !== substr_count($content, '}')) {
            $issues[] = "Unmatched braces";
        }
        
        // Check for missing event listeners
        if (strpos($file, '.js') !== false && strpos($content, 'addEventListener') === false) {
            $issues[] = "No event listeners found";
        }
        
        // Check for console errors
        if (strpos($content, 'console.error') !== false) {
            $issues[] = "Contains error logging";
        }
        
        if (empty($issues)) {
            echo "- $file: OK" . PHP_EOL;
        } else {
            echo "- $file: ISSUES - " . implode(', ', $issues) . PHP_EOL;
        }
    } else {
        echo "- $file: MISSING" . PHP_EOL;
    }
}

echo PHP_EOL;

// 4. Check API endpoints for proper response
echo "4. API ENDPOINT ANALYSIS" . PHP_EOL;
$api_endpoints = [
    'maintenance_planning' => 'GET /api/mro_api.php?endpoint=maintenance_planning&action=list',
    'work_orders' => 'GET /api/mro_api.php?endpoint=work_orders&action=list',
    'parts_management' => 'GET /api/mro_api.php?endpoint=parts_management&action=usage',
    'technicians' => 'GET /api/mro_api.php?endpoint=technicians&action=list'
];

foreach ($api_endpoints as $name => $endpoint) {
    if (file_exists('api/mro_api.php')) {
        $api_content = file_get_contents('api/mro_api.php');
        if (strpos($api_content, $name) !== false) {
            echo "- $name endpoint: DEFINED" . PHP_EOL;
        } else {
            echo "- $name endpoint: NOT DEFINED" . PHP_EOL;
        }
    } else {
        echo "- $name endpoint: API FILE MISSING" . PHP_EOL;
    }
}

echo PHP_EOL;

// 5. Check for common click event issues
echo "5. CLICK EVENT ANALYSIS" . PHP_EOL;
$click_files = ['admin/mro_dashboard.php', 'admin/mro_planning.php', 'admin/mro_work_orders.php'];

foreach ($click_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        $click_handlers = substr_count($content, 'onclick=');
        $event_listeners = substr_count($content, 'addEventListener');
        $function_calls = substr_count($content, 'function ');
        $async_functions = substr_count($content, 'async function');
        
        echo "- $file:" . PHP_EOL;
        echo "  onclick handlers: $click_handlers" . PHP_EOL;
        echo "  event listeners: $event_listeners" . PHP_EOL;
        echo "  functions: $function_calls" . PHP_EOL;
        echo "  async functions: $async_functions" . PHP_EOL;
        
        if ($click_handlers === 0 && $event_listeners === 0) {
            echo "  ⚠️  WARNING: No click handlers detected!" . PHP_EOL;
        }
    }
}

echo PHP_EOL;

// 6. Check for network/API call issues
echo "6. NETWORK/API CALL ANALYSIS" . PHP_EOL;
foreach ($click_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        $fetch_calls = substr_count($content, 'fetch(');
        $api_calls = substr_count($content, '/api/mro_api.php');
        $error_handling = substr_count($content, 'catch');
        
        echo "- $file:" . PHP_EOL;
        echo "  fetch() calls: $fetch_calls" . PHP_EOL;
        echo "  API calls: $api_calls" . PHP_EOL;
        echo "  error handling: $error_handling" . PHP_EOL;
        
        if ($fetch_calls > 0 && $error_handling === 0) {
            echo "  ⚠️  WARNING: API calls without error handling!" . PHP_EOL;
        }
    }
}

echo PHP_EOL;

// 7. Check for CSS/display issues
echo "7. CSS/DISPLAY ANALYSIS" . PHP_EOL;
$css_files = ['css/dashboard.css'];
foreach ($css_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $size = filesize($file);
        echo "- $file: $size bytes" . PHP_EOL;
        
        // Check for common CSS issues
        if (strpos($content, 'pointer-events') !== false) {
            echo "  Contains pointer-events CSS" . PHP_EOL;
        }
        if (strpos($content, 'cursor:') !== false) {
            echo "  Contains cursor CSS" . PHP_EOL;
        }
        if (strpos($content, 'display: none') !== false) {
            echo "  Contains display:none (could hide elements)" . PHP_EOL;
        }
    } else {
        echo "- $file: MISSING" . PHP_EOL;
    }
}

echo PHP_EOL;

// 8. Generate specific fixes
echo "8. RECOMMENDED FIXES" . PHP_EOL;

$fixes = [];

// Check for common issues
if (!file_exists('js/dashboard.js')) {
    $fixes[] = "Create missing js/dashboard.js file";
}

if (!file_exists('admin/mro_dashboard.php')) {
    $fixes[] = "Create missing admin/mro_dashboard.php file";
}

if ($mro_count === 0) {
    $fixes[] = "Add sample MRO work orders for testing";
}

if ($user_count === 0) {
    $fixes[] = "Create test users for login";
}

echo "Recommended actions:" . PHP_EOL;
foreach ($fixes as $fix) {
    echo "- $fix" . PHP_EOL;
}

if (empty($fixes)) {
    echo "- System appears structurally complete" . PHP_EOL;
    echo "- Issue likely in JavaScript execution or API responses" . PHP_EOL;
}

$conn->close();
echo PHP_EOL . "=== DIAGNOSTIC COMPLETE ===" . PHP_EOL;
?>

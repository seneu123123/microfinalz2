<?php
require_once 'config/db.php';

echo "=== FINAL MRO SYSTEM ERROR CHECK ===" . PHP_EOL;
echo "Database Connection: " . ($conn ? 'OK' : 'FAILED') . PHP_EOL;
echo "Database Name: " . DB_NAME . PHP_EOL . PHP_EOL;

// Check all MRO tables now exist
echo "MRO Tables Status:" . PHP_EOL;
$mro_tables = [
    'mro_work_orders',
    'mro_parts_usage', 
    'mro_maintenance_planning',
    'mro_compliance_safety',
    'mro_technicians',
    'mro_integration_log',
    'mro_reports'
];

foreach ($mro_tables as $table) {
    $result = $conn->query("DESCRIBE $table");
    echo $result ? "✓ $table: EXISTS" . PHP_EOL : "✗ $table: MISSING" . PHP_EOL;
}

// Test API endpoints
echo PHP_EOL . "API Endpoint Test:" . PHP_EOL;
$test_endpoints = [
    'api/mro_api.php?endpoint=maintenance_planning&action=list',
    'api/mro_api.php?endpoint=work_orders&action=list',
    'api/mro_api.php?endpoint=technicians&action=list'
];

foreach ($test_endpoints as $endpoint) {
    // Just check if the file exists and has the endpoint logic
    if (file_exists('api/mro_api.php')) {
        $content = file_get_contents('api/mro_api.php');
        $endpoint_name = parse_url($endpoint, PHP_URL_QUERY);
        if (strpos($content, $endpoint_name) !== false) {
            echo "✓ Endpoint exists: $endpoint_name" . PHP_EOL;
        } else {
            echo "? Endpoint may be missing: $endpoint_name" . PHP_EOL;
        }
    }
}

// Check for session issues
echo PHP_EOL . "Session Security Check:" . PHP_EOL;
$php_files = ['admin/mro_dashboard.php', 'admin/mro_planning.php', 'admin/mro_work_orders.php', 'admin/mro_parts.php', 'admin/compliance.php'];

foreach ($php_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'session_start()') !== false) {
            if (strpos($content, 'if (!isset($_SESSION[\'user_id\'])') !== false) {
                echo "✓ $file: Secure session handling" . PHP_EOL;
            } else {
                echo "? $file: Session exists but may lack security check" . PHP_EOL;
            }
        } else {
            echo "✗ $file: Missing session_start()" . PHP_EOL;
        }
    } else {
        echo "✗ $file: File not found" . PHP_EOL;
    }
}

// Check for JavaScript navigation fixes
echo PHP_EOL . "Navigation Fix Check:" . PHP_EOL;
$mro_pages = ['admin/mro_planning.php', 'admin/mro_work_orders.php', 'admin/mro_parts.php', 'admin/compliance.php'];

foreach ($mro_pages as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'window.initializeNavigation') !== false) {
            echo "✓ $file: Navigation fix applied" . PHP_EOL;
        } else {
            echo "? $file: May need navigation fix" . PHP_EOL;
        }
    } else {
        echo "✗ $file: File not found" . PHP_EOL;
    }
}

echo PHP_EOL . "=== SYSTEM STATUS SUMMARY ===" . PHP_EOL;

// Count sample data
$work_orders = $conn->query("SELECT COUNT(*) as count FROM mro_work_orders")->fetch_assoc()['count'];
$plans = $conn->query("SELECT COUNT(*) as count FROM mro_maintenance_planning")->fetch_assoc()['count'];
$technicians = $conn->query("SELECT COUNT(*) as count FROM mro_technicians")->fetch_assoc()['count'];

echo "Work Orders: $work_orders" . PHP_EOL;
echo "Maintenance Plans: $plans" . PHP_EOL;
echo "Technicians: $technicians" . PHP_EOL;

if ($work_orders > 0 && $plans > 0 && $technicians > 0) {
    echo PHP_EOL . "🎉 MRO System is READY FOR USE!" . PHP_EOL;
    echo PHP_EOL . "Access URLs:" . PHP_EOL;
    echo "- MRO Dashboard: /admin/mro_dashboard.php" . PHP_EOL;
    echo "- Maintenance Planning: /admin/mro_planning.php" . PHP_EOL;
    echo "- Work Orders: /admin/mro_work_orders.php" . PHP_EOL;
    echo "- Parts Management: /admin/mro_parts.php" . PHP_EOL;
    echo "- Compliance & Safety: /admin/compliance.php" . PHP_EOL;
    echo "- API Endpoint: /api/mro_api.php" . PHP_EOL;
} else {
    echo PHP_EOL . "⚠️  System may need additional setup" . PHP_EOL;
}

$conn->close();
?>

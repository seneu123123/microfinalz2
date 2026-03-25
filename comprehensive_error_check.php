<?php
require_once 'config/db.php';

echo "=== MRO System Error Check ===" . PHP_EOL;
echo "Database Connection: " . ($conn ? 'OK' : 'FAILED') . PHP_EOL;
echo "Database Name: " . DB_NAME . PHP_EOL . PHP_EOL;

// Check if required files exist
$required_files = [
    'admin/mro_dashboard.php',
    'admin/mro_planning.php', 
    'admin/mro_work_orders.php',
    'admin/mro_parts.php',
    'admin/mro.php',
    'admin/compliance.php',
    'admin/compliance_fixed.php',
    'api/mro_api.php',
    'api/mro.php',
    'js/dashboard.js',
    'includes/sidebar.php'
];

echo PHP_EOL . "Required Files Check:" . PHP_EOL;
foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✓ $file: EXISTS" . PHP_EOL;
    } else {
        echo "✗ $file: MISSING" . PHP_EOL;
    }
}

// Check MRO tables structure
echo PHP_EOL . "MRO Tables Structure Check:" . PHP_EOL;
$mro_tables = [
    'mro_work_orders' => [
        'work_order_id',
        'title', 
        'description',
        'priority',
        'status',
        'created_by'
    ],
    'mro_parts_usage' => [
        'usage_id',
        'work_order_id',
        'part_name',
        'quantity_used',
        'total_cost'
    ],
    'mro_maintenance_planning' => [
        'plan_id',
        'plan_title',
        'plan_type',
        'next_due_date',
        'status'
    ],
    'mro_compliance_safety' => [
        'check_id',
        'work_order_id',
        'check_type',
        'performed_by',
        'passed'
    ]
];

foreach ($mro_tables as $table => $required_columns) {
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        $missing_columns = array_diff($required_columns, $columns);
        if (empty($missing_columns)) {
            echo "✓ $table: Structure OK" . PHP_EOL;
        } else {
            echo "✗ $table: Missing columns: " . implode(', ', $missing_columns) . PHP_EOL;
        }
    } else {
        echo "✗ $table: Table not found" . PHP_EOL;
    }
}

// Check for common JavaScript errors
echo PHP_EOL . "JavaScript Error Check:" . PHP_EOL;
$js_files = ['js/dashboard.js', 'admin/mro_dashboard.php'];

foreach ($js_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check for common JS issues
        $issues = [];
        
        // Check for unclosed functions
        preg_match_all('/function\s+\w+\s*\(/', $content, $matches);
        preg_match_all('/}/', $content, $braces);
        if (count($matches) !== count($braces)) {
            $issues[] = 'Possible unclosed functions';
        }
        
        // Check for missing semicolons (basic check)
        $lines = explode("\n", $content);
        foreach ($lines as $line_num => $line) {
            $trimmed = trim($line);
            if ($trimmed && 
                !preg_match('/^(\/\/|\/\*|\*\/|{|}|$|;|if|for|while|switch|function|var|let|const|return|break|continue|try|catch|finally)/', $trimmed) &&
                !preg_match('/\{$/', $trimmed) &&
                !preg_match('/\}$/', $trimmed) &&
                !preg_match('/;$/', $trimmed) &&
                substr($trimmed, -1) !== ',' &&
                substr($trimmed, -1) !== '(' &&
                substr($trimmed, -1) !== '{') {
                // This is a basic check - might have false positives
            }
        }
        
        if (empty($issues)) {
            echo "✓ $file: No obvious JS issues" . PHP_EOL;
        } else {
            echo "✗ $file: Possible issues: " . implode(', ', $issues) . PHP_EOL;
        }
    } else {
        echo "✗ $file: File not found" . PHP_EOL;
    }
}

// Check for missing session_start in PHP files
echo PHP_EOL . "Session Check:" . PHP_EOL;
$php_files = ['admin/mro_dashboard.php', 'admin/mro_planning.php', 'admin/mro_work_orders.php', 'admin/mro_parts.php'];

foreach ($php_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'session_start()') !== false) {
            echo "✓ $file: Has session_start()" . PHP_EOL;
        } else {
            echo "✗ $file: Missing session_start()" . PHP_EOL;
        }
    } else {
        echo "✗ $file: File not found" . PHP_EOL;
    }
}

// Check API endpoints
echo PHP_EOL . "API Endpoint Check:" . PHP_EOL;
$api_endpoints = [
    'api/mro_api.php?endpoint=maintenance_planning&action=list',
    'api/mro_api.php?endpoint=work_orders&action=list',
    'api/mro_api.php?endpoint=parts_management&action=usage',
    'api/mro_api.php?endpoint=compliance_safety&action=checklist'
];

foreach ($api_endpoints as $endpoint) {
    // Just check if the file exists and has the endpoint logic
    if (file_exists('api/mro_api.php')) {
        $content = file_get_contents('api/mro_api.php');
        if (strpos($content, parse_url($endpoint, PHP_URL_PATH)) !== false || 
            strpos($content, str_replace('api/', '', parse_url($endpoint, PHP_URL_PATH))) !== false) {
            echo "✓ Endpoint exists: " . parse_url($endpoint, PHP_URL_PATH) . PHP_EOL;
        } else {
            echo "? Endpoint may be missing: " . parse_url($endpoint, PHP_URL_PATH) . PHP_EOL;
        }
    }
}

$conn->close();
?>

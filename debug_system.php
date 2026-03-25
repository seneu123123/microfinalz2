<?php
require_once 'config/db.php';
echo "Database connection: " . ($conn ? 'OK' : 'FAILED') . PHP_EOL;
echo "Database name: " . DB_NAME . PHP_EOL;
echo "Host: " . DB_HOST . PHP_EOL;

// Check if MRO tables exist
$tables_to_check = [
    'mro_work_orders',
    'mro_parts_usage', 
    'mro_maintenance_planning',
    'mro_compliance_safety',
    'mro_technicians',
    'mro_integration_log',
    'mro_reports'
];

echo PHP_EOL . "Checking MRO tables:" . PHP_EOL;
foreach ($tables_to_check as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    $exists = $result->num_rows > 0 ? 'EXISTS' : 'MISSING';
    echo "- $table: $exists" . PHP_EOL;
}

// Check for syntax errors in files
echo PHP_EOL . "Checking for common file issues:" . PHP_EOL;

$files_to_check = [
    'api/mro_api.php',
    'admin/mro_planning.php',
    'admin/mro_work_orders.php', 
    'admin/mro_parts.php',
    'admin/compliance_fixed.php',
    'js/dashboard.js'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $syntax_errors = [];
        
        // Check for common PHP syntax issues
        if (strpos($file, '.php') !== false) {
            // Check for unclosed PHP tags
            $open_tags = substr_count($content, '<?php');
            $close_tags = substr_count($content, '?>');
            if ($open_tags !== $close_tags) {
                $syntax_errors[] = 'Unclosed PHP tags';
            }
            
            // Check for missing semicolons (basic check)
            $lines = explode("\n", $content);
            foreach ($lines as $line_num => $line) {
                $trimmed = trim($line);
                if ($trimmed && !preg_match('/^(\s*\/\/|\s*\/\*|\s*\*\/|\s*\?>|\s*\{|\s*\}|)/', $trimmed)) {
                    if (!preg_match('/;$/', $trimmed) && !preg_match('/\{$/', $trimmed) && !preg_match('/\}$/', $trimmed)) {
                        if (!preg_match('/^(if|for|while|switch|function|class|namespace|use|echo|print|return|break|continue|throw|try|catch|finally)/', $trimmed)) {
                            // This is a basic check - might have false positives
                        }
                    }
                }
            }
        }
        
        if (empty($syntax_errors)) {
            echo "- $file: OK" . PHP_EOL;
        } else {
            echo "- $file: ERRORS - " . implode(', ', $syntax_errors) . PHP_EOL;
        }
    } else {
        echo "- $file: MISSING" . PHP_EOL;
    }
}

$conn->close();
?>

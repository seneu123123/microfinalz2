<?php
require_once 'config/db.php';

echo "=== MISSING CODE DETECTION ===" . PHP_EOL;
echo "Time: " . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;

// Check for empty or incomplete files
echo "1. CHECKING FOR EMPTY/INCOMPLETE FILES" . PHP_EOL;

$critical_files = [
    'js/rolespermission.js' => 'Roles & Permissions JS',
    'js/dashboard.js' => 'Dashboard JS',
    'js/click_fix.js' => 'Click Fix JS',
    'admin/mro_planning.php' => 'Maintenance Planning',
    'admin/mro_work_orders.php' => 'Work Orders',
    'admin/mro_parts.php' => 'Parts Management',
    'admin/compliance.php' => 'Compliance',
    'admin/mro_dashboard.php' => 'MRO Dashboard',
    'api/mro_api.php' => 'MRO API'
];

$incomplete_files = [];

foreach ($critical_files as $file => $description) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $size = strlen($content);
        $lines = substr_count($content, "\n") + 1;
        
        // Check if file is essentially empty
        if ($size < 100 || $lines < 10) {
            $incomplete_files[] = [
                'file' => $file,
                'description' => $description,
                'size' => $size,
                'lines' => $lines,
                'status' => 'EMPTY/INCOMPLETE'
            ];
        } else {
            // Check for incomplete implementations
            $hasFunctions = strpos($content, 'function') !== false;
            $hasClasses = strpos($content, 'class') !== false;
            $hasLogic = strpos($content, 'if') !== false || strpos($content, 'for') !== false || strpos($content, 'while') !== false;
            
            if (!$hasFunctions && !$hasClasses && !$hasLogic) {
                $incomplete_files[] = [
                    'file' => $file,
                    'description' => $description,
                    'size' => $size,
                    'lines' => $lines,
                    'status' => 'MISSING LOGIC'
                ];
            }
        }
        
        echo "- $description: $size bytes, $lines lines" . PHP_EOL;
    } else {
        $incomplete_files[] = [
            'file' => $file,
            'description' => $description,
            'size' => 0,
            'lines' => 0,
            'status' => 'MISSING'
        ];
        echo "- $description: FILE MISSING" . PHP_EOL;
    }
}

if (!empty($incomplete_files)) {
    echo PHP_EOL . "2. INCOMPLETE FILES FOUND:" . PHP_EOL;
    foreach ($incomplete_files as $file) {
        echo "- {$file['description']} ({$file['file']}): {$file['status']}" . PHP_EOL;
    }
} else {
    echo PHP_EOL . "2. All critical files appear complete" . PHP_EOL;
}

// Check for incomplete PHP functions
echo PHP_EOL . "3. CHECKING FOR INCOMPLETE PHP IMPLEMENTATIONS" . PHP_EOL;

$php_files = [
    'admin/mro_planning.php',
    'admin/mro_work_orders.php', 
    'admin/mro_parts.php',
    'admin/compliance.php'
];

foreach ($php_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check for incomplete implementations
        $incomplete_patterns = [
            'TODO:',
            'FIXME:',
            'INCOMPLETE',
            'function load',
            'async function',
            '// Add implementation'
        ];
        
        $issues = [];
        foreach ($incomplete_patterns as $pattern) {
            if (strpos($content, $pattern) !== false) {
                $issues[] = $pattern;
            }
        }
        
        if (!empty($issues)) {
            echo "- " . basename($file) . ": " . implode(', ', $issues) . PHP_EOL;
        }
        
        // Check for missing closing tags
        $open_php = substr_count($content, '<?php');
        $close_php = substr_count($content, '?>');
        if ($open_php !== $close_php) {
            echo "- " . basename($file) . ": PHP tag mismatch" . PHP_EOL;
        }
    }
}

// Check for incomplete JavaScript implementations
echo PHP_EOL . "4. CHECKING FOR INCOMPLETE JAVASCRIPT IMPLEMENTATIONS" . PHP_EOL;

$js_files = [
    'js/dashboard.js',
    'js/click_fix.js',
    'js/rolespermission.js'
];

foreach ($js_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check for incomplete implementations
        $incomplete_patterns = [
            'TODO:',
            'FIXME:',
            'INCOMPLETE',
            '// Add implementation',
            'function() {',
            '// TODO'
        ];
        
        $issues = [];
        foreach ($incomplete_patterns as $pattern) {
            if (strpos($content, $pattern) !== false) {
                $issues[] = $pattern;
            }
        }
        
        if (!empty($issues)) {
            echo "- " . basename($file) . ": " . implode(', ', $issues) . PHP_EOL;
        }
        
        // Check for unclosed functions
        $open_braces = substr_count($content, '{');
        $close_braces = substr_count($content, '}');
        if ($open_braces !== $close_braces) {
            echo "- " . basename($file) . ": Brace mismatch ({$open_braces} vs {$close_braces})" . PHP_EOL;
        }
    }
}

$conn->close();
echo PHP_EOL . "=== SCAN COMPLETE ===" . PHP_EOL;
?>

<?php
echo "=== COMPREHENSIVE SYSTEM SCAN ===" . PHP_EOL;
echo "Time: " . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;

$issues = [];

// 1. Scan JS files for empty/broken files
echo "1. SCANNING JS FILES" . PHP_EOL;
$jsFiles = glob('js/*.js');
foreach ($jsFiles as $f) {
    $size = filesize($f);
    $lines = count(file($f));
    $status = '';
    if ($size < 10) {
        $status = ' ** EMPTY **';
        $issues[] = ['file' => $f, 'issue' => 'Empty file', 'severity' => 'HIGH'];
    }
    echo "  " . basename($f) . ": {$size} bytes, {$lines} lines{$status}" . PHP_EOL;
}

// 2. Scan admin PHP files for syntax errors
echo PHP_EOL . "2. SCANNING ADMIN PHP FILES (syntax check)" . PHP_EOL;
$adminFiles = glob('admin/*.php');
foreach ($adminFiles as $f) {
    $output = [];
    $ret = 0;
    exec("php -l " . escapeshellarg($f) . " 2>&1", $output, $ret);
    $result = implode(' ', $output);
    if ($ret !== 0) {
        echo "  FAIL: " . basename($f) . " - " . $result . PHP_EOL;
        $issues[] = ['file' => $f, 'issue' => $result, 'severity' => 'HIGH'];
    } else {
        echo "  OK: " . basename($f) . PHP_EOL;
    }
}

// 3. Scan API PHP files for syntax errors
echo PHP_EOL . "3. SCANNING API PHP FILES (syntax check)" . PHP_EOL;
$apiFiles = glob('api/*.php');
foreach ($apiFiles as $f) {
    $output = [];
    $ret = 0;
    exec("php -l " . escapeshellarg($f) . " 2>&1", $output, $ret);
    $result = implode(' ', $output);
    if ($ret !== 0) {
        echo "  FAIL: " . basename($f) . " - " . $result . PHP_EOL;
        $issues[] = ['file' => $f, 'issue' => $result, 'severity' => 'HIGH'];
    } else {
        echo "  OK: " . basename($f) . PHP_EOL;
    }
}

// 4. Scan root PHP files
echo PHP_EOL . "4. SCANNING ROOT PHP FILES (syntax check)" . PHP_EOL;
$rootFiles = glob('*.php');
foreach ($rootFiles as $f) {
    $output = [];
    $ret = 0;
    exec("php -l " . escapeshellarg($f) . " 2>&1", $output, $ret);
    $result = implode(' ', $output);
    if ($ret !== 0) {
        echo "  FAIL: " . basename($f) . " - " . $result . PHP_EOL;
        $issues[] = ['file' => $f, 'issue' => $result, 'severity' => 'HIGH'];
    } else {
        echo "  OK: " . basename($f) . PHP_EOL;
    }
}

// 5. Scan includes and config
echo PHP_EOL . "5. SCANNING INCLUDES/CONFIG PHP FILES" . PHP_EOL;
$otherFiles = array_merge(glob('includes/*.php'), glob('config/*.php'));
foreach ($otherFiles as $f) {
    $output = [];
    $ret = 0;
    exec("php -l " . escapeshellarg($f) . " 2>&1", $output, $ret);
    $result = implode(' ', $output);
    if ($ret !== 0) {
        echo "  FAIL: " . basename($f) . " - " . $result . PHP_EOL;
        $issues[] = ['file' => $f, 'issue' => $result, 'severity' => 'HIGH'];
    } else {
        echo "  OK: " . basename($f) . PHP_EOL;
    }
}

// 6. Check for duplicate function definitions in JS
echo PHP_EOL . "6. CHECKING JS FOR DUPLICATE FUNCTIONS" . PHP_EOL;
foreach ($jsFiles as $f) {
    $content = file_get_contents($f);
    preg_match_all('/function\s+(\w+)\s*\(/', $content, $matches);
    if (!empty($matches[1])) {
        $counts = array_count_values($matches[1]);
        foreach ($counts as $funcName => $count) {
            if ($count > 1) {
                echo "  DUPLICATE: " . basename($f) . " -> {$funcName}() defined {$count} times" . PHP_EOL;
                $issues[] = ['file' => $f, 'issue' => "Duplicate function: {$funcName}()", 'severity' => 'MEDIUM'];
            }
        }
    }
}

// 7. Check for duplicate function definitions in inline JS within PHP files
echo PHP_EOL . "7. CHECKING PHP FILES FOR DUPLICATE INLINE JS FUNCTIONS" . PHP_EOL;
foreach ($adminFiles as $f) {
    $content = file_get_contents($f);
    preg_match_all('/function\s+(\w+)\s*\(/', $content, $matches);
    if (!empty($matches[1])) {
        $counts = array_count_values($matches[1]);
        foreach ($counts as $funcName => $count) {
            if ($count > 1) {
                echo "  DUPLICATE: " . basename($f) . " -> {$funcName}() defined {$count} times" . PHP_EOL;
                $issues[] = ['file' => $f, 'issue' => "Duplicate function: {$funcName}()", 'severity' => 'MEDIUM'];
            }
        }
    }
}

// 8. Check for broken script/CSS references in admin PHP
echo PHP_EOL . "8. CHECKING FOR BROKEN SCRIPT/CSS REFERENCES" . PHP_EOL;
foreach ($adminFiles as $f) {
    $content = file_get_contents($f);
    // Check local script references
    preg_match_all('/src=["\']\.\.\/js\/([^"\']+)["\']/', $content, $matches);
    foreach ($matches[1] as $jsRef) {
        if (!file_exists("js/{$jsRef}")) {
            echo "  BROKEN REF: " . basename($f) . " -> js/{$jsRef} (not found)" . PHP_EOL;
            $issues[] = ['file' => $f, 'issue' => "Broken script ref: js/{$jsRef}", 'severity' => 'HIGH'];
        }
    }
    // Check local CSS references
    preg_match_all('/href=["\']\.\.\/css\/([^"\']+)["\']/', $content, $matches);
    foreach ($matches[1] as $cssRef) {
        if (!file_exists("css/{$cssRef}")) {
            echo "  BROKEN REF: " . basename($f) . " -> css/{$cssRef} (not found)" . PHP_EOL;
            $issues[] = ['file' => $f, 'issue' => "Broken CSS ref: css/{$cssRef}", 'severity' => 'HIGH'];
        }
    }
}

// 9. Check for unclosed HTML tags in PHP files
echo PHP_EOL . "9. CHECKING HTML STRUCTURE IN ADMIN FILES" . PHP_EOL;
foreach ($adminFiles as $f) {
    $content = file_get_contents($f);
    $openScript = substr_count($content, '<script');
    $closeScript = substr_count($content, '</script>');
    if ($openScript !== $closeScript) {
        echo "  MISMATCH: " . basename($f) . " -> <script> tags: {$openScript} open, {$closeScript} close" . PHP_EOL;
        $issues[] = ['file' => $f, 'issue' => "Script tag mismatch ({$openScript} open, {$closeScript} close)", 'severity' => 'HIGH'];
    }
    $openHTML = substr_count($content, '<html');
    $closeHTML = substr_count($content, '</html>');
    if ($openHTML > 0 && $openHTML !== $closeHTML) {
        echo "  MISMATCH: " . basename($f) . " -> <html> tags: {$openHTML} open, {$closeHTML} close" . PHP_EOL;
        $issues[] = ['file' => $f, 'issue' => "HTML tag mismatch", 'severity' => 'HIGH'];
    }
    $openBody = substr_count($content, '<body');
    $closeBody = substr_count($content, '</body>');
    if ($openBody > 0 && $openBody !== $closeBody) {
        echo "  MISMATCH: " . basename($f) . " -> <body> tags: {$openBody} open, {$closeBody} close" . PHP_EOL;
        $issues[] = ['file' => $f, 'issue' => "Body tag mismatch", 'severity' => 'HIGH'];
    }
}

// 10. Check DB connection
echo PHP_EOL . "10. CHECKING DATABASE CONNECTION" . PHP_EOL;
if (file_exists('config/db.php')) {
    require_once 'config/db.php';
    if (isset($conn) && $conn) {
        echo "  DB Connection: OK" . PHP_EOL;
        
        // Check critical tables
        $tables = ['users', 'mro_work_orders', 'mro_maintenance_planning', 'mro_technicians',
                   'mro_compliance_safety', 'mro_parts_usage', 'assets', 'inventory',
                   'fleet_management', 'maintenance_requests', 'suppliers'];
        foreach ($tables as $t) {
            $result = $conn->query("SHOW TABLES LIKE '{$t}'");
            if ($result && $result->num_rows > 0) {
                echo "  Table {$t}: EXISTS" . PHP_EOL;
            } else {
                echo "  Table {$t}: MISSING" . PHP_EOL;
                $issues[] = ['file' => 'database', 'issue' => "Missing table: {$t}", 'severity' => 'MEDIUM'];
            }
        }
    } else {
        echo "  DB Connection: FAILED" . PHP_EOL;
        $issues[] = ['file' => 'config/db.php', 'issue' => 'Database connection failed', 'severity' => 'HIGH'];
    }
}

// 11. Check for stale/duplicate files
echo PHP_EOL . "11. CHECKING FOR STALE DUPLICATE FILES" . PHP_EOL;
$staleFiles = [
    'admin/compliance_fixed.php',
    'admin/mro_dashboard_fixed.php', 
    'admin/mro_planning_fixed.php',
    'api/mro_api_backup.php'
];
foreach ($staleFiles as $f) {
    if (file_exists($f)) {
        echo "  STALE: {$f} (should be cleaned up)" . PHP_EOL;
        $issues[] = ['file' => $f, 'issue' => 'Stale duplicate file', 'severity' => 'LOW'];
    }
}

// 12. Check for JS brace balance
echo PHP_EOL . "12. CHECKING JS BRACE BALANCE" . PHP_EOL;
foreach ($jsFiles as $f) {
    $content = file_get_contents($f);
    if (strlen($content) < 10) continue;
    $open = substr_count($content, '{');
    $close = substr_count($content, '}');
    if ($open !== $close) {
        echo "  MISMATCH: " . basename($f) . " -> { = {$open}, } = {$close}" . PHP_EOL;
        $issues[] = ['file' => $f, 'issue' => "Brace mismatch ({$open} open, {$close} close)", 'severity' => 'HIGH'];
    }
}

// 13. Check admin PHP inline JS brace balance
echo PHP_EOL . "13. CHECKING ADMIN PHP INLINE JS BRACE BALANCE" . PHP_EOL;
foreach ($adminFiles as $f) {
    $content = file_get_contents($f);
    // Extract only JS content between <script> tags
    preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $content, $scriptMatches);
    foreach ($scriptMatches[1] as $idx => $jsContent) {
        if (strlen(trim($jsContent)) < 5) continue;
        $open = substr_count($jsContent, '{');
        $close = substr_count($jsContent, '}');
        if ($open !== $close) {
            echo "  MISMATCH: " . basename($f) . " script block #{$idx} -> { = {$open}, } = {$close}" . PHP_EOL;
            $issues[] = ['file' => $f, 'issue' => "JS brace mismatch in script block #{$idx}", 'severity' => 'HIGH'];
        }
    }
}

// SUMMARY
echo PHP_EOL . "=== SCAN SUMMARY ===" . PHP_EOL;
$high = array_filter($issues, function($i) { return $i['severity'] === 'HIGH'; });
$medium = array_filter($issues, function($i) { return $i['severity'] === 'MEDIUM'; });
$low = array_filter($issues, function($i) { return $i['severity'] === 'LOW'; });

echo "Total issues found: " . count($issues) . PHP_EOL;
echo "  HIGH severity: " . count($high) . PHP_EOL;
echo "  MEDIUM severity: " . count($medium) . PHP_EOL;
echo "  LOW severity: " . count($low) . PHP_EOL;

if (!empty($issues)) {
    echo PHP_EOL . "=== ALL ISSUES ===" . PHP_EOL;
    foreach ($issues as $i) {
        echo "  [{$i['severity']}] {$i['file']}: {$i['issue']}" . PHP_EOL;
    }
}

echo PHP_EOL . "=== SCAN COMPLETE ===" . PHP_EOL;

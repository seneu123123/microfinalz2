<?php
/**
 * Debug API to check database structure and create missing tables
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Disable HTML error output
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Custom error handler to ensure JSON responses
function handleError($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $errstr]);
    exit();
}
set_error_handler('handleError');

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Fatal server error']);
        error_log("Fatal error in debug_audit.php: " . var_export($err, true));
    }
});

// Helper functions
function sendError($message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function sendSuccess($message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

// Database connection
require_once '../config/db.php';

// Check database connection
if (!$conn || $conn->connect_error) {
    sendError('Database connection failed: ' . ($conn->connect_error ?? 'Unknown error'));
}

$action = $_GET['action'] ?? 'check_tables';

switch ($action) {
    case 'check_tables':
        checkTables();
        break;
    case 'create_tables':
        createTables();
        break;
    case 'test_query':
        testQuery();
        break;
    default:
        sendError('Invalid action');
}

function checkTables() {
    global $conn;
    
    try {
        // Get all tables in the database
        $result = $conn->query("SHOW TABLES");
        $tables = [];
        
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        
        $auditTables = [
            'audit_schedules',
            'audit_checklists', 
            'audit_criteria',
            'audit_progress',
            'audit_timeline',
            'audit_findings',
            'corrective_actions'
        ];
        
        $missingTables = array_diff($auditTables, $tables);
        $existingTables = array_intersect($auditTables, $tables);
        
        sendSuccess('Tables checked', [
            'all_tables' => $tables,
            'audit_tables' => $auditTables,
            'existing' => $existingTables,
            'missing' => $missingTables
        ]);
        
    } catch (Exception $e) {
        sendError('Error checking tables: ' . $e->getMessage());
    }
}

function createTables() {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // Create audit_schedules table
        $conn->query("CREATE TABLE IF NOT EXISTS audit_schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            audit_id VARCHAR(50) UNIQUE NOT NULL,
            audit_date DATE NOT NULL,
            audit_type VARCHAR(50) NOT NULL,
            assigned_auditor VARCHAR(255) NOT NULL,
            target_department VARCHAR(255) NOT NULL,
            description TEXT,
            status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'rescheduled', 'delayed', 'on_hold') DEFAULT 'scheduled',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Create audit_checklists table
        $conn->query("CREATE TABLE IF NOT EXISTS audit_checklists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            checklist_id VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(50) NOT NULL,
            version VARCHAR(20) DEFAULT '1.0',
            status ENUM('draft', 'active', 'archived') DEFAULT 'draft',
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Create audit_criteria table
        $conn->query("CREATE TABLE IF NOT EXISTS audit_criteria (
            id INT AUTO_INCREMENT PRIMARY KEY,
            checklist_id VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            weight INT DEFAULT 10,
            score_type ENUM('points', 'percentage', 'pass_fail') DEFAULT 'points',
            required_documents JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (checklist_id) REFERENCES audit_checklists(checklist_id) ON DELETE CASCADE
        )");
        
        // Create audit_progress table
        $conn->query("CREATE TABLE IF NOT EXISTS audit_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            audit_id VARCHAR(50) UNIQUE NOT NULL,
            progress_percentage INT DEFAULT 0,
            start_date DATE,
            expected_completion_date DATE,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (audit_id) REFERENCES audit_schedules(audit_id) ON DELETE CASCADE
        )");
        
        // Create audit_timeline table
        $conn->query("CREATE TABLE IF NOT EXISTS audit_timeline (
            id INT AUTO_INCREMENT PRIMARY KEY,
            audit_id VARCHAR(50) NOT NULL,
            action VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (audit_id) REFERENCES audit_schedules(audit_id) ON DELETE CASCADE
        )");
        
        // Create audit_findings table
        $conn->query("CREATE TABLE IF NOT EXISTS audit_findings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            finding_id VARCHAR(50) UNIQUE NOT NULL,
            audit_id VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            category VARCHAR(100),
            status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (audit_id) REFERENCES audit_schedules(audit_id) ON DELETE CASCADE
        )");
        
        // Create corrective_actions table
        $conn->query("CREATE TABLE IF NOT EXISTS corrective_actions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action_id VARCHAR(50) UNIQUE NOT NULL,
            finding_id VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            assigned_to VARCHAR(255),
            due_date DATE,
            status ENUM('pending', 'in_progress', 'completed', 'overdue') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (finding_id) REFERENCES audit_findings(finding_id) ON DELETE CASCADE
        )");
        
        $conn->commit();
        
        sendSuccess('All audit tables created successfully');
        
    } catch (Exception $e) {
        $conn->rollback();
        sendError('Error creating tables: ' . $e->getMessage());
    }
}

function testQuery() {
    global $conn;
    
    try {
        // Test a simple query on audit_schedules
        $result = $conn->query("SELECT COUNT(*) as count FROM audit_schedules");
        $row = $result->fetch_assoc();
        
        sendSuccess('Test query successful', ['count' => $row['count']]);
        
    } catch (Exception $e) {
        sendError('Test query failed: ' . $e->getMessage());
    }
}
?>

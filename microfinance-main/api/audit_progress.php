<?php
/**
 * Audit Progress API
 * Handles audit progress tracking with real-time monitoring
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

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
        error_log("Fatal error in audit_progress.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in audit_progress.php: ' . $ex->getMessage());
});

// Database connection
require_once '../config/db.php';

// Check database connection
if (!$conn || $conn->connect_error) {
    sendResponse(false, 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error'));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'timeline') {
        getTimeline();
    } else {
        listProgress();
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'update_progress':
            updateProgress($input);
            break;
        case 'pause_audit':
            pauseAudit($input);
            break;
        case 'resume_audit':
            resumeAudit($input);
            break;
        default:
            sendResponse(false, 'Invalid action');
    }
} else {
    sendResponse(false, 'Method not allowed');
}

function listProgress() {
    global $conn;
    
    try {
        $query = "SELECT 
                    a.audit_id,
                    a.audit_type,
                    a.assigned_auditor,
                    '' as assigned_auditor_id,
                    a.target_department,
                    a.status,
                    ap.progress_percentage,
                    ap.start_date,
                    ap.expected_completion_date,
                    ap.notes,
                    a.created_at,
                    a.updated_at
                  FROM audit_schedules a
                  LEFT JOIN audit_progress ap ON a.audit_id = ap.audit_id
                  ORDER BY a.created_at DESC";
        
        $result = $conn->query($query);
        if (!$result) {
            sendResponse(false, 'Query failed: ' . $conn->error);
        }
        
        $audits = [];
        while ($row = $result->fetch_assoc()) {
            // If no progress record exists, create default values
            if ($row['progress_percentage'] === null) {
                $row['progress_percentage'] = 0;
                $row['start_date'] = $row['created_at'];
                $row['notes'] = '';
            }
            
            // Calculate if audit is delayed
            if ($row['status'] !== 'completed' && $row['expected_completion_date']) {
                $expectedDate = new DateTime($row['expected_completion_date']);
                $today = new DateTime();
                if ($today > $expectedDate && $row['status'] !== 'delayed') {
                    $row['status'] = 'delayed';
                }
            }
            
            $audits[] = $row;
        }
        
        sendResponse(true, 'Audit progress retrieved', $audits);
    } catch (Exception $e) {
        sendResponse(false, 'Error: ' . $e->getMessage());
    }
}

function getTimeline() {
    global $conn;
    
    try {
        $query = "SELECT 
                    action,
                    description,
                    created_at
                  FROM audit_timeline 
                  ORDER BY created_at DESC 
                  LIMIT 20";
        
        $result = $conn->query($query);
        if (!$result) {
            sendResponse(false, 'Query failed: ' . $conn->error);
        }
        
        $timeline = [];
        while ($row = $result->fetch_assoc()) {
            $timeline[] = $row;
        }
        
        sendResponse(true, 'Timeline retrieved', $timeline);
    } catch (Exception $e) {
        sendResponse(false, 'Error: ' . $e->getMessage());
    }
}

function updateProgress($data) {
    global $conn;
    
    try {
        if (empty($data['audit_id']) || empty($data['status'])) {
            sendResponse(false, 'Missing audit ID or status');
        }
        
        $auditId = $data['audit_id'];
        $status = $data['status'];
        $progress = $data['progress_percentage'] ?? 0;
        $notes = $data['notes'] ?? '';
        $expectedDate = $data['expected_completion_date'] ?? null;
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update audit schedule status
            $updateScheduleQuery = "UPDATE audit_schedules 
                                   SET status = ?, updated_at = NOW()
                                   WHERE audit_id = ?";
            $updateScheduleStmt = $conn->prepare($updateScheduleQuery);
            $updateScheduleStmt->bind_param('ss', $status, $auditId);
            $updateScheduleStmt->execute();
            $updateScheduleStmt->close();
            
            // Update or insert progress record
            $progressQuery = "INSERT INTO audit_progress 
                              (audit_id, progress_percentage, start_date, expected_completion_date, notes, updated_at)
                              VALUES (?, ?, COALESCE(start_date, NOW()), ?, ?, NOW())
                              ON DUPLICATE KEY UPDATE
                              progress_percentage = VALUES(progress_percentage),
                              expected_completion_date = VALUES(expected_completion_date),
                              notes = VALUES(notes),
                              updated_at = VALUES(updated_at)";
            
            $progressStmt = $conn->prepare($progressQuery);
            $progressStmt->bind_param('ssss', $auditId, $progress, $expectedDate, $notes);
            $progressStmt->execute();
            $progressStmt->close();
            
            // Add timeline entry
            $timelineQuery = "INSERT INTO audit_timeline (audit_id, action, description, created_at)
                              VALUES (?, 'Progress Updated', ?, NOW())";
            $timelineStmt = $conn->prepare($timelineQuery);
            $description = "Status changed to {$status} with {$progress}% completion";
            $timelineStmt->bind_param('ss', $auditId, $description);
            $timelineStmt->execute();
            $timelineStmt->close();
            
            // Commit transaction
            $conn->commit();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            throw $e;
        }
        
        sendResponse(true, 'Progress updated successfully');
        
    } catch (Exception $e) {
        sendResponse(false, 'Error updating progress: ' . $e->getMessage());
    }
}

function pauseAudit($data) {
    global $conn;
    
    try {
        if (empty($data['audit_id'])) {
            sendResponse(false, 'Missing audit ID');
        }
        
        $auditId = $data['audit_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update audit schedule status
            $updateScheduleQuery = "UPDATE audit_schedules 
                                   SET status = 'on_hold', updated_at = NOW()
                                   WHERE audit_id = ?";
            $updateScheduleStmt = $conn->prepare($updateScheduleQuery);
            $updateScheduleStmt->bind_param('s', $auditId);
            $updateScheduleStmt->execute();
            $updateScheduleStmt->close();
            
            // Update progress record
            $progressQuery = "UPDATE audit_progress 
                              SET notes = CONCAT(IFNULL(notes, ''), '\n[Audit paused on ', DATE(NOW()), ']'), updated_at = NOW()
                              WHERE audit_id = ?";
            $progressStmt = $conn->prepare($progressQuery);
            $progressStmt->bind_param('s', $auditId);
            $progressStmt->execute();
            $progressStmt->close();
            
            // Add timeline entry
            $timelineQuery = "INSERT INTO audit_timeline (audit_id, action, description, created_at)
                              VALUES (?, 'Audit Paused', 'Audit was put on hold', NOW())";
            $timelineStmt = $conn->prepare($timelineQuery);
            $timelineStmt->bind_param('s', $auditId);
            $timelineStmt->execute();
            $timelineStmt->close();
            
            // Commit transaction
            $conn->commit();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            throw $e;
        }
        
        sendResponse(true, 'Audit paused successfully');
        
    } catch (Exception $e) {
        sendResponse(false, 'Error pausing audit: ' . $e->getMessage());
    }
}

function resumeAudit($data) {
    global $conn;
    
    try {
        if (empty($data['audit_id'])) {
            sendResponse(false, 'Missing audit ID');
        }
        
        $auditId = $data['audit_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update audit schedule status back to in_progress
            $updateScheduleQuery = "UPDATE audit_schedules 
                                   SET status = 'in_progress', updated_at = NOW()
                                   WHERE audit_id = ?";
            $updateScheduleStmt = $conn->prepare($updateScheduleQuery);
            $updateScheduleStmt->bind_param('s', $auditId);
            $updateScheduleStmt->execute();
            $updateScheduleStmt->close();
            
            // Update progress record
            $progressQuery = "UPDATE audit_progress 
                              SET notes = CONCAT(IFNULL(notes, ''), '\n[Audit resumed on ', DATE(NOW()), ']'), updated_at = NOW()
                              WHERE audit_id = ?";
            $progressStmt = $conn->prepare($progressQuery);
            $progressStmt->bind_param('s', $auditId);
            $progressStmt->execute();
            $progressStmt->close();
            
            // Add timeline entry
            $timelineQuery = "INSERT INTO audit_timeline (audit_id, action, description, created_at)
                              VALUES (?, 'Audit Resumed', 'Audit was resumed from hold', NOW())";
            $timelineStmt = $conn->prepare($timelineQuery);
            $timelineStmt->bind_param('s', $auditId);
            $timelineStmt->execute();
            $timelineStmt->close();
            
            // Commit transaction
            $conn->commit();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            throw $e;
        }
        
        sendResponse(true, 'Audit resumed successfully');
        
    } catch (Exception $e) {
        sendResponse(false, 'Error resuming audit: ' . $e->getMessage());
    }
}
?>

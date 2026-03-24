<?php
/**
 * Audit Schedule API
 * Handles audit schedule management with system integration
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
        error_log("Fatal error in audit_schedule.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in audit_schedule.php: ' . $ex->getMessage());
});

// Database connection
require_once '../config/db.php';

// Check database connection
if (!$conn || $conn->connect_error) {
    sendResponse(false, 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error'));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get all audit schedules
    try {
        $query = "SELECT 
                    audit_id,
                    audit_date,
                    audit_type,
                    assigned_auditor,
                    target_department,
                    description,
                    status,
                    created_at,
                    updated_at
                  FROM audit_schedules 
                  ORDER BY audit_date DESC";
        
        $result = $conn->query($query);
        if (!$result) {
            sendResponse(false, 'Query failed: ' . $conn->error);
        }
        
        $audits = [];
        while ($row = $result->fetch_assoc()) {
            $audits[] = $row;
        }
        
        sendResponse(true, 'Audit schedules retrieved', $audits);
    } catch (Exception $e) {
        sendResponse(false, 'Error: ' . $e->getMessage());
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create':
            createAuditSchedule($input);
            break;
        case 'reschedule':
            rescheduleAudit($input);
            break;
        case 'cancel':
            cancelAudit($input);
            break;
        case 'delete':
            deleteAudit($input);
            break;
        case 'notify_auditor':
            notifyAuditor($input);
            break;
        default:
            sendResponse(false, 'Invalid action');
    }
} else {
    sendResponse(false, 'Method not allowed');
}

function createAuditSchedule($data) {
    global $conn;
    
    try {
        // Validate required fields
        $required = ['audit_date', 'audit_type', 'assigned_auditor', 'target_department'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                sendResponse(false, "Missing required field: $field");
            }
        }
        
        // Generate audit ID
        $auditId = 'AUD-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert audit schedule
        $query = "INSERT INTO audit_schedules (
                    audit_id, audit_date, audit_type, assigned_auditor, 
                    target_department, description, status, created_at
                  ) VALUES (?, ?, ?, ?, ?, ?, 'scheduled', NOW())";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('sssss', 
            $auditId,
            $data['audit_date'],
            $data['audit_type'],
            $data['assigned_auditor'],
            $data['target_department'],
            $data['description']
        );
        
        if (!$stmt->execute()) {
            sendResponse(false, 'Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
        // Return created audit with ID
        $auditData = array_merge(['audit_id' => $auditId], $data);
        sendResponse(true, 'Audit schedule created successfully', $auditData);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error creating audit schedule: ' . $e->getMessage());
    }
}

function rescheduleAudit($data) {
    global $conn;
    
    try {
        if (empty($data['audit_id']) || empty($data['new_audit_date'])) {
            sendResponse(false, 'Missing audit ID or new audit date');
        }
        
        // Update audit schedule
        $query = "UPDATE audit_schedules 
                  SET audit_date = ?, status = 'rescheduled', updated_at = NOW()
                  WHERE audit_id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('ss', $data['new_audit_date'], $data['audit_id']);
        
        if (!$stmt->execute()) {
            sendResponse(false, 'Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
        // Log reschedule reason if provided
        if (!empty($data['reason'])) {
            $logQuery = "INSERT INTO audit_logs (audit_id, action, details, created_at) 
                        VALUES (?, 'reschedule', ?, NOW())";
            $logStmt = $conn->prepare($logQuery);
            $logStmt->bind_param('ss', $data['audit_id'], $data['reason']);
            $logStmt->execute();
            $logStmt->close();
        }
        
        sendResponse(true, 'Audit rescheduled successfully');
        
    } catch (Exception $e) {
        sendResponse(false, 'Error rescheduling audit: ' . $e->getMessage());
    }
}

function cancelAudit($data) {
    global $conn;
    
    try {
        if (empty($data['audit_id'])) {
            sendResponse(false, 'Missing audit ID');
        }
        
        // Update audit status to cancelled
        $query = "UPDATE audit_schedules 
                  SET status = 'cancelled', updated_at = NOW()
                  WHERE audit_id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('s', $data['audit_id']);
        
        if (!$stmt->execute()) {
            sendResponse(false, 'Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
        sendResponse(true, 'Audit cancelled successfully');
        
    } catch (Exception $e) {
        sendResponse(false, 'Error cancelling audit: ' . $e->getMessage());
    }
}

function deleteAudit($data) {
    global $conn;
    
    try {
        if (empty($data['audit_id'])) {
            sendResponse(false, 'Missing audit ID');
        }
        
        // Delete audit schedule
        $query = "DELETE FROM audit_schedules WHERE audit_id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('s', $data['audit_id']);
        
        if (!$stmt->execute()) {
            sendResponse(false, 'Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
        sendResponse(true, 'Audit deleted successfully');
        
    } catch (Exception $e) {
        sendResponse(false, 'Error deleting audit: ' . $e->getMessage());
    }
}

function notifyAuditor($data) {
    global $conn;
    
    try {
        if (empty($data['audit_id']) || empty($data['auditor_id'])) {
            sendResponse(false, 'Missing audit ID or auditor ID');
        }
        
        // Get auditor details
        $query = "SELECT name, email FROM users WHERE user_id = ? AND role = 'auditor'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $data['auditor_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(false, 'Auditor not found');
        }
        
        $auditor = $result->fetch_assoc();
        $stmt->close();
        
        // Log notification
        $logQuery = "INSERT INTO audit_logs (audit_id, action, details, created_at) 
                    VALUES (?, 'notification_sent', ?, NOW())";
        $logStmt = $conn->prepare($logQuery);
        $details = "Notified auditor: " . $auditor['name'] . " (" . $auditor['email'] . ")";
        $logStmt->bind_param('ss', $data['audit_id'], $details);
        $logStmt->execute();
        $logStmt->close();
        
        // In a real implementation, you would send an email here
        // mail($auditor['email'], 'Audit Assignment', 'You have been assigned to audit...');
        
        sendResponse(true, 'Auditor notified successfully');
        
    } catch (Exception $e) {
        sendResponse(false, 'Error notifying auditor: ' . $e->getMessage());
    }
}
?>

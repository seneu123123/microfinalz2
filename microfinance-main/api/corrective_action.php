<?php
/**
 * Corrective Action API
 * Handles corrective action assignment and tracking with system integration
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
        error_log("Fatal error in corrective_action.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in corrective_action.php: ' . $ex->getMessage());
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
    
    if ($action === 'get' && isset($_GET['id'])) {
        getAction($_GET['id']);
    } else {
        listActions();
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'assign':
            assignAction($input);
            break;
        case 'mark_resolved':
            markResolved($input);
            break;
        case 'escalate':
            escalateAction($input);
            break;
        case 'update_progress':
            updateProgress($input);
            break;
        default:
            sendResponse(false, 'Invalid action');
    }
} else {
    sendResponse(false, 'Method not allowed');
}

function listActions() {
    global $conn;
    
    try {
        $status = $_GET['status'] ?? '';
        $assignee = $_GET['assignee'] ?? '';
        
        $query = "SELECT 
                    ca.id,
                    ca.action_id,
                    ca.finding_id,
                    ca.title,
                    ca.description,
                    ca.assigned_to,
                    ca.due_date,
                    ca.status,
                    ca.created_at,
                    ca.updated_at,
                    f.title as finding_title,
                    f.severity as priority,
                    a.target_department
                  FROM corrective_actions ca
                  LEFT JOIN audit_findings f ON ca.finding_id = f.finding_id
                  LEFT JOIN audit_schedules a ON f.audit_id = a.audit_id
                  WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if (!empty($status)) {
            $query .= " AND ca.status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        if (!empty($assignee)) {
            $query .= " AND ca.assigned_to = ?";
            $params[] = $assignee;
            $types .= 's';
        }
        
        $query .= " ORDER BY ca.created_at DESC";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $actions = [];
        while ($row = $result->fetch_assoc()) {
            $actions[] = $row;
        }
        
        $stmt->close();
        
        sendResponse(true, 'Corrective actions retrieved', $actions);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error retrieving actions: ' . $e->getMessage());
    }
}

function getAction($actionId) {
    global $conn;
    
    try {
        $query = "SELECT ca.*, f.observation_notes, f.severity_level, a.target_department 
                  FROM corrective_actions ca
                  LEFT JOIN audit_findings f ON ca.finding_id = f.finding_id
                  LEFT JOIN audit_schedules a ON f.audit_id = a.audit_id
                  WHERE ca.action_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('s', $actionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(false, 'Corrective action not found');
        }
        
        $action = $result->fetch_assoc();
        
        // Get action progress updates
        $progressQuery = "SELECT * FROM action_progress WHERE action_id = ? ORDER BY created_at DESC";
        $progressStmt = $conn->prepare($progressQuery);
        $progressStmt->bind_param('s', $actionId);
        $progressStmt->execute();
        $progressResult = $progressStmt->get_result();
        
        $progress = [];
        while ($row = $progressResult->fetch_assoc()) {
            $progress[] = $row;
        }
        
        $action['progress_updates'] = $progress;
        
        $stmt->close();
        $progressStmt->close();
        
        sendResponse(true, 'Corrective action retrieved', $action);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error retrieving action: ' . $e->getMessage());
    }
}

function assignAction($data) {
    global $conn;
    
    try {
        // Validate required fields
        $required = ['finding_id', 'title', 'description', 'assigned_to', 'due_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                sendResponse(false, "Missing required field: $field");
            }
        }
        
        // Generate action ID
        $actionId = 'ACT-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert corrective action
        $query = "INSERT INTO corrective_actions (
                    action_id, finding_id, title, description, assigned_to, 
                    due_date, status, created_at
                  ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('ssssss', 
            $actionId,
            $data['finding_id'],
            $data['title'],
            $data['description'],
            $data['assigned_to'],
            $data['due_date']
        );
        
        if (!$stmt->execute()) {
            sendResponse(false, 'Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
        // System integration: Coordinate with HR/Scheduling for driver retraining
        if (strpos(strtolower($data['description']), 'retraining') !== false) {
            coordinateWithHR($actionId, $data);
        }
        
        // Update finding status
        $updateQuery = "UPDATE audit_findings SET status = 'in_progress' WHERE finding_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('s', $data['finding_id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Return created action with ID
        $actionData = array_merge(['action_id' => $actionId], $data);
        sendResponse(true, 'Corrective action assigned successfully', $actionData);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error assigning action: ' . $e->getMessage());
    }
}

function markResolved($data) {
    global $conn;
    
    try {
        if (empty($data['action_id'])) {
            sendResponse(false, 'Missing action ID');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update action status
            $query = "UPDATE corrective_actions 
                      SET status = 'resolved', progress_percentage = 100, 
                          updated_at = NOW()
                      WHERE action_id = ?";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                sendResponse(false, 'Prepare failed: ' . $conn->error);
            }
            
            $stmt->bind_param('s', $data['action_id']);
            
            if (!$stmt->execute()) {
                sendResponse(false, 'Execute failed: ' . $stmt->error);
            }
            
            $stmt->close();
            
            // Add progress update
            $progressId = 'PROG-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $progressQuery = "INSERT INTO action_progress (
                                progress_id, action_id, status, notes, 
                                percentage, created_at
                              ) VALUES (?, ?, 'resolved', ?, 100, NOW())";
            
            $progressStmt = $conn->prepare($progressQuery);
            $progressStmt->bind_param('sss', 
                $progressId,
                $data['action_id'],
                $data['resolution_notes'] ?? 'Action marked as resolved'
            );
            $progressStmt->execute();
            $progressStmt->close();
            
            // System integration: Store proof in document tracking
            if (!empty($data['proof_documents'])) {
                storeProofDocuments($data['action_id'], $data['proof_documents']);
            }
            
            // Update finding status
            $getActionQuery = "SELECT finding_id FROM corrective_actions WHERE action_id = ?";
            $getActionStmt = $conn->prepare($getActionQuery);
            $getActionStmt->bind_param('s', $data['action_id']);
            $getActionStmt->execute();
            $actionResult = $getActionStmt->get_result();
            
            if ($actionResult->num_rows > 0) {
                $action = $actionResult->fetch_assoc();
                
                $updateFindingQuery = "UPDATE audit_findings SET status = 'resolved' WHERE finding_id = ?";
                $updateFindingStmt = $conn->prepare($updateFindingQuery);
                $updateFindingStmt->bind_param('s', $action['finding_id']);
                $updateFindingStmt->execute();
                $updateFindingStmt->close();
            }
            
            $getActionStmt->close();
            
            // Commit transaction
            $conn->commit();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            throw $e;
        }
        
        sendResponse(true, 'Corrective action marked as resolved');
        
    } catch (Exception $e) {
        sendResponse(false, 'Error marking action as resolved: ' . $e->getMessage());
    }
}

function escalateAction($data) {
    global $conn;
    
    try {
        if (empty($data['action_id']) || empty($data['escalation_reason'])) {
            sendResponse(false, 'Missing action ID or escalation reason');
        }
        
        // Generate escalation ID
        $escalationId = 'ESC-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert escalation record
        $query = "INSERT INTO action_escalations (
                    escalation_id, action_id, escalation_reason, 
                    escalated_to, status, created_at
                  ) VALUES (?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('ssss', 
            $escalationId,
            $data['action_id'],
            $data['escalation_reason'],
            $data['escalated_to'] ?? 'Management'
        );
        
        if (!$stmt->execute()) {
            sendResponse(false, 'Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
        // Update action status
        $updateQuery = "UPDATE corrective_actions SET status = 'escalated', updated_at = NOW() WHERE action_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('s', $data['action_id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        $escalationData = array_merge(['escalation_id' => $escalationId], $data);
        sendResponse(true, 'Action escalated successfully', $escalationData);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error escalating action: ' . $e->getMessage());
    }
}

function updateProgress($data) {
    global $conn;
    
    try {
        if (empty($data['action_id']) || empty($data['progress_percentage'])) {
            sendResponse(false, 'Missing action ID or progress percentage');
        }
        
        // Generate progress ID
        $progressId = 'PROG-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update action progress
            $query = "UPDATE corrective_actions 
                      SET progress_percentage = ?, updated_at = NOW()
                      WHERE action_id = ?";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                sendResponse(false, 'Prepare failed: ' . $conn->error);
            }
            
            $stmt->bind_param('is', $data['progress_percentage'], $data['action_id']);
            
            if (!$stmt->execute()) {
                sendResponse(false, 'Execute failed: ' . $stmt->error);
            }
            
            $stmt->close();
            
            // Add progress update record
            $progressQuery = "INSERT INTO action_progress (
                                progress_id, action_id, status, notes, 
                                percentage, created_at
                              ) VALUES (?, ?, 'in_progress', ?, ?, NOW())";
            
            $progressStmt = $conn->prepare($progressQuery);
            $progressStmt->bind_param('ssi', 
                $progressId,
                $data['action_id'],
                $data['progress_notes'] ?? '',
                $data['progress_percentage']
            );
            $progressStmt->execute();
            $progressStmt->close();
            
            // Update action status based on progress
            $status = 'in_progress';
            if ($data['progress_percentage'] >= 100) {
                $status = 'completed';
            } elseif ($data['progress_percentage'] > 0) {
                $status = 'in_progress';
            }
            
            $statusQuery = "UPDATE corrective_actions SET status = ? WHERE action_id = ?";
            $statusStmt = $conn->prepare($statusQuery);
            $statusStmt->bind_param('ss', $status, $data['action_id']);
            $statusStmt->execute();
            $statusStmt->close();
            
            // Commit transaction
            $conn->commit();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            throw $e;
        }
        
        $progressData = array_merge(['progress_id' => $progressId], $data);
        sendResponse(true, 'Progress updated successfully', $progressData);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error updating progress: ' . $e->getMessage());
    }
}

function coordinateWithHR($actionId, $action) {
    global $conn;
    
    try {
        // This would integrate with HR/Scheduling system
        // For now, just log the coordination
        $query = "INSERT INTO hr_coordination_log (
                    action_id, coordination_type, details, created_at
                  ) VALUES (?, 'retraining', ?, NOW())";
        
        $stmt = $conn->prepare($query);
        $details = "Driver retraining required for corrective action: " . $action['action_plan'];
        $stmt->bind_param('ss', $actionId, $details);
        $stmt->execute();
        $stmt->close();
        
    } catch (Exception $e) {
        error_log('Error coordinating with HR: ' . $e->getMessage());
    }
}

function storeProofDocuments($actionId, $documents) {
    global $conn;
    
    try {
        // This would integrate with Document Tracking system
        // For now, just log the documents
        $query = "INSERT INTO document_tracking (
                    reference_id, reference_type, document_name, 
                    document_path, created_at
                  ) VALUES (?, 'corrective_action', ?, ?)";
        
        $stmt = $conn->prepare($query);
        
        foreach ($documents as $doc) {
            $stmt->bind_param('sss', $actionId, $doc['name'], $doc['path'] ?? '');
            $stmt->execute();
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log('Error storing proof documents: ' . $e->getMessage());
    }
}
?>

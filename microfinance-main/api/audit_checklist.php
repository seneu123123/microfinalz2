<?php
/**
 * Audit Checklist API
 * Handles audit checklist configuration with criteria and document requirements
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
        error_log("Fatal error in audit_checklist.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in audit_checklist.php: ' . $ex->getMessage());
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
        getChecklist($_GET['id']);
    } else {
        listChecklists();
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create':
            createChecklist($input);
            break;
        case 'update':
            updateChecklist($input);
            break;
        case 'archive':
            archiveChecklist($input);
            break;
        case 'delete':
            deleteChecklist($input);
            break;
        default:
            sendResponse(false, 'Invalid action');
    }
} else {
    sendResponse(false, 'Method not allowed');
}

function listChecklists() {
    global $conn;
    
    try {
        $query = "SELECT 
                    checklist_id,
                    name,
                    category,
                    version,
                    status,
                    description,
                    created_at,
                    updated_at,
                    (SELECT COUNT(*) FROM audit_criteria WHERE checklist_id = c.checklist_id) as criteria_count
                  FROM audit_checklists c 
                  ORDER BY created_at DESC";
        
        $result = $conn->query($query);
        if (!$result) {
            sendResponse(false, 'Query failed: ' . $conn->error);
        }
        
        $checklists = [];
        while ($row = $result->fetch_assoc()) {
            $checklists[] = $row;
        }
        
        sendResponse(true, 'Checklists retrieved', $checklists);
    } catch (Exception $e) {
        sendResponse(false, 'Error: ' . $e->getMessage());
    }
}

function getChecklist($checklistId) {
    global $conn;
    
    try {
        // Get checklist details
        $query = "SELECT * FROM audit_checklists WHERE checklist_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('s', $checklistId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(false, 'Checklist not found');
        }
        
        $checklist = $result->fetch_assoc();
        
        // Get criteria
        $criteriaQuery = "SELECT * FROM audit_criteria WHERE checklist_id = ? ORDER BY id";
        $criteriaStmt = $conn->prepare($criteriaQuery);
        $criteriaStmt->bind_param('s', $checklistId);
        $criteriaStmt->execute();
        $criteriaResult = $criteriaStmt->get_result();
        
        $criteria = [];
        while ($row = $criteriaResult->fetch_assoc()) {
            // Decode required documents from JSON
            if (!empty($row['required_documents'])) {
                $row['required_documents'] = json_decode($row['required_documents'], true) ?? [];
            }
            $criteria[] = $row;
        }
        
        $checklist['criteria'] = $criteria;
        
        $stmt->close();
        $criteriaStmt->close();
        
        sendResponse(true, 'Checklist retrieved', $checklist);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error retrieving checklist: ' . $e->getMessage());
    }
}

function createChecklist($data) {
    global $conn;
    
    try {
        // Validate required fields
        $required = ['category', 'name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                sendResponse(false, "Missing required field: $field");
            }
        }
        
        // Generate checklist ID
        $checklistId = 'CHK-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert checklist
        $query = "INSERT INTO audit_checklists (
                    checklist_id, name, category, version, status, 
                    description, created_at
                  ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $status = $data['status'] ?? 'draft';
        $version = $data['version'] ?? '1.0';
        
        $stmt->bind_param('ssssss', 
            $checklistId,
            $data['name'],
            $data['category'],
            $version,
            $status,
            $data['description']
        );
        
        if (!$stmt->execute()) {
            sendResponse(false, 'Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
        // Insert criteria if provided
        if (!empty($data['criteria']) && is_array($data['criteria'])) {
            insertCriteria($checklistId, $data['criteria']);
        }
        
        // Return created checklist with ID
        $checklistData = array_merge(['checklist_id' => $checklistId], $data);
        sendResponse(true, 'Checklist created successfully', $checklistData);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error creating checklist: ' . $e->getMessage());
    }
}

function insertCriteria($checklistId, $criteria) {
    global $conn;
    
    $query = "INSERT INTO audit_criteria (
                checklist_id, title, description, weight, score_type, 
                required_documents, created_at
              ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    foreach ($criteria as $criterion) {
        $requiredDocs = !empty($criterion['required_documents']) 
            ? json_encode($criterion['required_documents']) 
            : null;
        
        $stmt->bind_param('ssssss', 
            $checklistId,
            $criterion['title'],
            $criterion['description'],
            $criterion['weight'],
            $criterion['score_type'],
            $requiredDocs
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
    }
    
    $stmt->close();
}

function updateChecklist($data) {
    global $conn;
    
    try {
        if (empty($data['checklist_id'])) {
            sendResponse(false, 'Missing checklist ID');
        }
        
        // Update checklist
        $query = "UPDATE audit_checklists 
                  SET name = ?, category = ?, version = ?, status = ?, 
                      description = ?, updated_at = NOW()
                  WHERE checklist_id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('ssssss', 
            $data['name'],
            $data['category'],
            $data['version'],
            $data['status'],
            $data['description'],
            $data['checklist_id']
        );
        
        if (!$stmt->execute()) {
            sendResponse(false, 'Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
        // Update criteria if provided
        if (!empty($data['criteria']) && is_array($data['criteria'])) {
            // Delete existing criteria
            $deleteQuery = "DELETE FROM audit_criteria WHERE checklist_id = ?";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->bind_param('s', $data['checklist_id']);
            $deleteStmt->execute();
            $deleteStmt->close();
            
            // Insert new criteria
            insertCriteria($data['checklist_id'], $data['criteria']);
        }
        
        sendResponse(true, 'Checklist updated successfully');
        
    } catch (Exception $e) {
        sendResponse(false, 'Error updating checklist: ' . $e->getMessage());
    }
}

function archiveChecklist($data) {
    global $conn;
    
    try {
        if (empty($data['checklist_id'])) {
            sendResponse(false, 'Missing checklist ID');
        }
        
        // Update checklist status to archived
        $query = "UPDATE audit_checklists 
                  SET status = 'archived', updated_at = NOW()
                  WHERE checklist_id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('s', $data['checklist_id']);
        
        if (!$stmt->execute()) {
            sendResponse(false, 'Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
        sendResponse(true, 'Checklist archived successfully');
        
    } catch (Exception $e) {
        sendResponse(false, 'Error archiving checklist: ' . $e->getMessage());
    }
}

function deleteChecklist($data) {
    global $conn;
    
    try {
        if (empty($data['checklist_id'])) {
            sendResponse(false, 'Missing checklist ID');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete criteria first (foreign key constraint)
            $deleteCriteriaQuery = "DELETE FROM audit_criteria WHERE checklist_id = ?";
            $deleteCriteriaStmt = $conn->prepare($deleteCriteriaQuery);
            $deleteCriteriaStmt->bind_param('s', $data['checklist_id']);
            $deleteCriteriaStmt->execute();
            $deleteCriteriaStmt->close();
            
            // Delete checklist
            $deleteQuery = "DELETE FROM audit_checklists WHERE checklist_id = ?";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->bind_param('s', $data['checklist_id']);
            $deleteStmt->execute();
            $deleteStmt->close();
            
            // Commit transaction
            $conn->commit();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            throw $e;
        }
        
        sendResponse(true, 'Checklist deleted successfully');
        
    } catch (Exception $e) {
        sendResponse(false, 'Error deleting checklist: ' . $e->getMessage());
    }
}
?>

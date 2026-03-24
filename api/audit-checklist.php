<?php
/**
 * Audit Checklist API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

ini_set('display_errors', '0');
error_reporting(E_ALL);

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Server error']);
        error_log("Fatal error in audit-checklist.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in audit-checklist.php: ' . $ex->getMessage());
});

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            getAuditChecklists();
            break;
        case 'POST':
            createAuditChecklist();
            break;
        case 'PUT':
            updateAuditChecklist();
            break;
        case 'DELETE':
            deleteAuditChecklist();
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Error in audit-checklist.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

function getAuditChecklists() {
    global $conn;
    
    try {
        $sql = "SELECT * FROM audit_checklists ORDER BY created_at DESC";
        $result = $conn->query($sql);
        
        $checklists = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $checklists[] = $row;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $checklists
        ]);
    } catch (Exception $e) {
        error_log("Error fetching audit checklists: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching audit checklists']);
    }
}

function createAuditChecklist() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }
        
        // Generate checklist ID
        $checklist_id = 'CHK-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO audit_checklists (
            checklist_id, checklist_name, audit_type, department, 
            checklist_items, status, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $items_json = json_encode($data['checklist_items']);
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
            return;
        }
        
        $stmt->bind_param(
            "sssssss",
            $checklist_id,
            $data['checklist_name'],
            $data['audit_type'],
            $data['department'],
            $items_json,
            $data['status'],
            $data['created_by']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Audit checklist created successfully',
                'data' => ['checklist_id' => $checklist_id]
            ]);
        } else {
            error_log("Execute failed: " . $stmt->error);
            echo json_encode(['success' => false, 'message' => 'Failed to create audit checklist: ' . $stmt->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error creating audit checklist: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error creating audit checklist']);
    }
}

function updateAuditChecklist() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['checklist_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data or missing checklist_id']);
            return;
        }
        
        $sql = "UPDATE audit_checklists SET 
            checklist_name = ?, audit_type = ?, department = ?, 
            checklist_items = ?, status = ?, updated_at = NOW()
            WHERE checklist_id = ?";
        
        $items_json = json_encode($data['checklist_items']);
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssss",
            $data['checklist_name'],
            $data['audit_type'],
            $data['department'],
            $items_json,
            $data['status'],
            $data['checklist_id']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Audit checklist updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update audit checklist']);
        }
    } catch (Exception $e) {
        error_log("Error updating audit checklist: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error updating audit checklist']);
    }
}

function deleteAuditChecklist() {
    global $conn;
    
    try {
        $checklist_id = $_GET['checklist_id'] ?? '';
        
        if (empty($checklist_id)) {
            echo json_encode(['success' => false, 'message' => 'Missing checklist_id']);
            return;
        }
        
        $sql = "DELETE FROM audit_checklists WHERE checklist_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $checklist_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Audit checklist deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete audit checklist']);
        }
    } catch (Exception $e) {
        error_log("Error deleting audit checklist: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error deleting audit checklist']);
    }
}
?>

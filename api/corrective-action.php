<?php
/**
 * Corrective Action API
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
        error_log("Fatal error in corrective-action.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in corrective-action.php: ' . $ex->getMessage());
});

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Handle different endpoints
    if (strpos($path, '/assign-department') !== false) {
        handleAssignDepartment();
        return;
    } elseif (strpos($path, '/set-deadline') !== false) {
        handleSetDeadline();
        return;
    } elseif (strpos($path, '/close-action') !== false) {
        handleCloseAction();
        return;
    }
    
    switch ($method) {
        case 'GET':
            getCorrectiveActions();
            break;
        case 'POST':
            createCorrectiveAction();
            break;
        case 'PUT':
            updateCorrectiveAction();
            break;
        case 'DELETE':
            deleteCorrectiveAction();
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Error in corrective-action.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

function getCorrectiveActions() {
    global $conn;
    
    try {
        $sql = "SELECT * FROM corrective_actions ORDER BY created_at DESC";
        $result = $conn->query($sql);
        
        $actions = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $actions[] = $row;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $actions
        ]);
    } catch (Exception $e) {
        error_log("Error fetching corrective actions: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching corrective actions']);
    }
}

function createCorrectiveAction() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }
        
        // Generate action ID
        $action_id = 'ACT-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO corrective_actions (
            action_id, finding_id, action_title, priority, department,
            assigned_to, target_date, action_description, resources_required,
            status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
            return;
        }
        
        $stmt->bind_param(
            "sssssssss",
            $action_id,
            $data['finding_id'],
            $data['action_title'],
            $data['priority'],
            $data['department'],
            $data['assigned_to'],
            $data['target_date'],
            $data['action_description'],
            $data['resources_required']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Corrective action created successfully',
                'data' => ['action_id' => $action_id]
            ]);
        } else {
            error_log("Execute failed: " . $stmt->error);
            echo json_encode(['success' => false, 'message' => 'Failed to create corrective action: ' . $stmt->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error creating corrective action: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error creating corrective action']);
    }
}

function updateCorrectiveAction() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['action_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data or missing action_id']);
            return;
        }
        
        $sql = "UPDATE corrective_actions SET 
            action_title = ?, priority = ?, department = ?, assigned_to = ?,
            target_date = ?, action_description = ?, resources_required = ?,
            updated_at = NOW()
            WHERE action_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssss",
            $data['action_title'],
            $data['priority'],
            $data['department'],
            $data['assigned_to'],
            $data['target_date'],
            $data['action_description'],
            $data['resources_required'],
            $data['action_id']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Corrective action updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update corrective action']);
        }
    } catch (Exception $e) {
        error_log("Error updating corrective action: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error updating corrective action']);
    }
}

function deleteCorrectiveAction() {
    global $conn;
    
    try {
        $action_id = $_GET['action_id'] ?? '';
        
        if (empty($action_id)) {
            echo json_encode(['success' => false, 'message' => 'Missing action_id']);
            return;
        }
        
        $sql = "DELETE FROM corrective_actions WHERE action_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $action_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Corrective action deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete corrective action']);
        }
    } catch (Exception $e) {
        error_log("Error deleting corrective action: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error deleting corrective action']);
    }
}

function handleAssignDepartment() {
    global $conn;
    
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['action_id']) || !isset($data['new_department'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        $sql = "UPDATE corrective_actions SET 
            department = ?, assigned_to = ?, assignment_notes = ?, updated_at = NOW()
            WHERE action_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssss",
            $data['new_department'],
            $data['new_assigned_to'],
            $data['assignment_notes'],
            $data['action_id']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Action assigned successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to assign action']);
        }
    } catch (Exception $e) {
        error_log("Error assigning action: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error assigning action']);
    }
}

function handleSetDeadline() {
    global $conn;
    
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['action_id']) || !isset($data['new_target_date'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        $sql = "UPDATE corrective_actions SET 
            target_date = ?, deadline_reason = ?, updated_at = NOW()
            WHERE action_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sss",
            $data['new_target_date'],
            $data['reason'],
            $data['action_id']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Deadline updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update deadline']);
        }
    } catch (Exception $e) {
        error_log("Error setting deadline: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error setting deadline']);
    }
}

function handleCloseAction() {
    global $conn;
    
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $action_id = $_POST['action_id'] ?? '';
        $completion_date = $_POST['completion_date'] ?? '';
        $final_status = $_POST['final_status'] ?? '';
        $completion_notes = $_POST['completion_notes'] ?? '';
        
        if (empty($action_id) || empty($completion_date) || empty($final_status)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        // Handle file upload for completion evidence
        $evidence_path = null;
        if (isset($_FILES['completion_evidence']) && $_FILES['completion_evidence']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['completion_evidence'];
            $upload_dir = '../uploads/completion_evidence/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = uniqid() . '_' . basename($file['name']);
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $evidence_path = $filepath;
            }
        }
        
        $sql = "UPDATE corrective_actions SET 
            status = ?, completion_date = ?, completion_notes = ?, 
            completion_evidence = ?, updated_at = NOW()
            WHERE action_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssss",
            $final_status,
            $completion_date,
            $completion_notes,
            $evidence_path,
            $action_id
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Action closed successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to close action']);
        }
    } catch (Exception $e) {
        error_log("Error closing action: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error closing action']);
    }
}
?>

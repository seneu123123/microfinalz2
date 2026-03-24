<?php
/**
 * Audit Progress API
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
        error_log("Fatal error in audit-progress.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in audit-progress.php: ' . $ex->getMessage());
});

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            getAuditProgress();
            break;
        case 'POST':
            createAuditProgress();
            break;
        case 'PUT':
            updateAuditProgress();
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Error in audit-progress.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

function getAuditProgress() {
    global $conn;
    
    try {
        $sql = "SELECT * FROM audit_progress ORDER BY created_at DESC";
        $result = $conn->query($sql);
        
        $progress = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $progress[] = $row;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $progress
        ]);
    } catch (Exception $e) {
        error_log("Error fetching audit progress: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching audit progress']);
    }
}

function createAuditProgress() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }
        
        // Generate progress ID
        $progress_id = 'PRG-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO audit_progress (
            progress_id, audit_id, current_status, completion_percentage,
            notes, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssisss",
            $progress_id,
            $data['audit_id'],
            $data['current_status'],
            $data['completion_percentage'],
            $data['notes'],
            $data['created_by']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Audit progress created successfully',
                'data' => ['progress_id' => $progress_id]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create audit progress']);
        }
    } catch (Exception $e) {
        error_log("Error creating audit progress: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error creating audit progress']);
    }
}

function updateAuditProgress() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['progress_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data or missing progress_id']);
            return;
        }
        
        $sql = "UPDATE audit_progress SET 
            current_status = ?, completion_percentage = ?, notes = ?, updated_at = NOW()
            WHERE progress_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "siss",
            $data['current_status'],
            $data['completion_percentage'],
            $data['notes'],
            $data['progress_id']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Audit progress updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update audit progress']);
        }
    } catch (Exception $e) {
        error_log("Error updating audit progress: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error updating audit progress']);
    }
}
?>

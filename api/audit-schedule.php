<?php
/**
 * Audit Schedule API
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
        error_log("Fatal error in audit-schedule.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in audit-schedule.php: ' . $ex->getMessage());
});

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            getAuditSchedules();
            break;
        case 'POST':
            createAuditSchedule();
            break;
        case 'PUT':
            updateAuditSchedule();
            break;
        case 'DELETE':
            deleteAuditSchedule();
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Error in audit-schedule.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

function getAuditSchedules() {
    global $conn;
    
    try {
        $sql = "SELECT * FROM audit_schedules ORDER BY created_at DESC";
        $result = $conn->query($sql);
        
        $schedules = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $schedules[] = $row;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $schedules
        ]);
    } catch (Exception $e) {
        error_log("Error fetching audit schedules: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching audit schedules']);
    }
}

function createAuditSchedule() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }
        
        // Generate audit ID
        $audit_id = 'AUD-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO audit_schedules (
            audit_id, audit_title, audit_type, department, auditor, 
            start_date, end_date, status, description, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
            return;
        }
        
        $stmt->bind_param(
            "sssssssss",
            $audit_id,
            $data['audit_title'],
            $data['audit_type'],
            $data['department'],
            $data['auditor'],
            $data['start_date'],
            $data['end_date'],
            $data['status'],
            $data['description']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Audit schedule created successfully',
                'data' => ['audit_id' => $audit_id]
            ]);
        } else {
            error_log("Execute failed: " . $stmt->error);
            echo json_encode(['success' => false, 'message' => 'Failed to create audit schedule: ' . $stmt->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error creating audit schedule: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error creating audit schedule: ' . $e->getMessage()]);
    }
}

function updateAuditSchedule() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['audit_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data or missing audit_id']);
            return;
        }
        
        $sql = "UPDATE audit_schedules SET 
            audit_title = ?, audit_type = ?, department = ?, auditor = ?,
            start_date = ?, end_date = ?, status = ?, description = ?
            WHERE audit_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssss",
            $data['audit_title'],
            $data['audit_type'],
            $data['department'],
            $data['auditor'],
            $data['start_date'],
            $data['end_date'],
            $data['status'],
            $data['description'],
            $data['audit_id']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Audit schedule updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update audit schedule']);
        }
    } catch (Exception $e) {
        error_log("Error updating audit schedule: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error updating audit schedule']);
    }
}

function deleteAuditSchedule() {
    global $conn;
    
    try {
        $audit_id = $_GET['audit_id'] ?? '';
        
        if (empty($audit_id)) {
            echo json_encode(['success' => false, 'message' => 'Missing audit_id']);
            return;
        }
        
        $sql = "DELETE FROM audit_schedules WHERE audit_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $audit_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Audit schedule deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete audit schedule']);
        }
    } catch (Exception $e) {
        error_log("Error deleting audit schedule: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error deleting audit schedule']);
    }
}
?>

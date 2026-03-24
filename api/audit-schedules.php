<?php
/**
 * Audit Schedules API
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
        error_log("Fatal error in audit-schedules.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in audit-schedules.php: ' . $ex->getMessage());
});

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getAudits();
        break;
    case 'POST':
        createAudit();
        break;
    case 'PUT':
        updateAudit();
        break;
    case 'DELETE':
        deleteAudit();
        break;
    default:
        sendError('Invalid request method');
}

function getAudits() {
    global $conn;
    $result = $conn->query("SELECT * FROM audit_schedules ORDER BY scheduled_date DESC");
    if (!$result) {
        sendError('Query failed: ' . $conn->error);
        return;
    }
    $audits = [];
    while ($row = $result->fetch_assoc()) {
        $audits[] = $row;
    }
    sendSuccess('Audits retrieved', $audits);
}

function createAudit() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['audit_id'])) {
        sendError('Audit ID required');
        return;
    }

    $stmt = $conn->prepare("INSERT INTO audit_schedules (audit_id, reservation_id, scheduled_date, status) VALUES (?, ?, ?, ?)");
    $status = $data['status'] ?? 'Pending';
    $stmt->bind_param("ssss", $data['audit_id'], $data['reservation_id'] ?? '', $data['scheduled_date'] ?? date('Y-m-d'), $status);
    
    if ($stmt->execute()) {
        sendSuccess('Audit created', ['audit_id' => $data['audit_id']]);
    } else {
        sendError('Failed to create audit: ' . $stmt->error);
    }
}

function updateAudit() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['audit_id'])) {
        sendError('Audit ID required');
        return;
    }

    $stmt = $conn->prepare("UPDATE audit_schedules SET scheduled_date = ?, status = ? WHERE audit_id = ?");
    $status = $data['status'] ?? 'Pending';
    $stmt->bind_param("sss", $data['scheduled_date'] ?? date('Y-m-d'), $status, $data['audit_id']);
    
    if ($stmt->execute()) {
        sendSuccess('Audit updated');
    } else {
        sendError('Failed to update audit: ' . $stmt->error);
    }
}

function deleteAudit() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['audit_id'])) {
        sendError('Audit ID required');
        return;
    }

    $stmt = $conn->prepare("DELETE FROM audit_schedules WHERE audit_id = ?");
    $stmt->bind_param("s", $data['audit_id']);
    
    if ($stmt->execute()) {
        sendSuccess('Audit deleted');
    } else {
        sendError('Failed to delete audit: ' . $stmt->error);
    }
}

function sendSuccess($message, $data = null) {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

function sendError($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}
?>

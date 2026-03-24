<?php
/**
 * Reservation Schedules API (2.4)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

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
        error_log("Fatal error in reservation_schedules.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in reservation_schedules.php: ' . $ex->getMessage());
});

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getSchedules();
        break;
    case 'POST':
        createSchedule();
        break;
    case 'PUT':
        updateSchedule();
        break;
    case 'DELETE':
        deleteSchedule();
        break;
    default:
        sendError('Invalid request method');
}

function getSchedules() {
    global $conn;
    
    $result = $conn->query("SELECT * FROM reservation_schedules ORDER BY start_time ASC");
    if (!$result) {
        sendError('Query failed: ' . $conn->error);
        return;
    }
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    sendSuccess('Schedules retrieved', $schedules);
}

function createSchedule() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['schedule_id']) || empty($data['start_time']) || empty($data['end_time'])) {
        sendError('Schedule ID, start_time, and end_time required');
        return;
    }

    $stmt = $conn->prepare("INSERT INTO reservation_schedules (schedule_id, reservation_id, start_time, end_time, priority_level, location, assigned_driver, assigned_vehicle, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $scheduleId = $data['schedule_id'];
    $reservationId = $data['reservation_id'] ?? '';
    $startTime = $data['start_time'];
    $endTime = $data['end_time'];
    $priority = $data['priority_level'] ?? 'Normal';
    $location = $data['location'] ?? '';
    $driver = $data['assigned_driver'] ?? '';
    $vehicle = $data['assigned_vehicle'] ?? '';
    $status = $data['status'] ?? 'Scheduled';
    $notes = $data['notes'] ?? '';
    
    $stmt->bind_param("ssssssssss", $scheduleId, $reservationId, $startTime, $endTime, $priority, $location, $driver, $vehicle, $status, $notes);
    
    if ($stmt->execute()) {
        sendSuccess('Schedule created', ['schedule_id' => $data['schedule_id']]);
    } else {
        sendError('Failed to create schedule: ' . $stmt->error);
    }
}

function updateSchedule() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['schedule_id'])) {
        sendError('Schedule ID required');
        return;
    }

    $stmt = $conn->prepare("UPDATE reservation_schedules SET reservation_id = ?, start_time = ?, end_time = ?, priority_level = ?, location = ?, assigned_driver = ?, assigned_vehicle = ?, status = ?, notes = ? WHERE schedule_id = ?");
    
    $reservationId = $data['reservation_id'] ?? '';
    $startTime = $data['start_time'];
    $endTime = $data['end_time'];
    $priority = $data['priority_level'] ?? 'Normal';
    $location = $data['location'] ?? '';
    $driver = $data['assigned_driver'] ?? '';
    $vehicle = $data['assigned_vehicle'] ?? '';
    $status = $data['status'] ?? 'Scheduled';
    $notes = $data['notes'] ?? '';
    $scheduleId = $data['schedule_id'];
    
    $stmt->bind_param("ssssssssss", $reservationId, $startTime, $endTime, $priority, $location, $driver, $vehicle, $status, $notes, $scheduleId);
    
    if ($stmt->execute()) {
        sendSuccess('Schedule updated');
    } else {
        sendError('Failed to update schedule: ' . $stmt->error);
    }
}

function deleteSchedule() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['schedule_id'])) {
        sendError('Schedule ID required');
        return;
    }

    $stmt = $conn->prepare("DELETE FROM reservation_schedules WHERE schedule_id = ?");
    $stmt->bind_param("s", $data['schedule_id']);
    
    if ($stmt->execute()) {
        sendSuccess('Schedule deleted');
    } else {
        sendError('Failed to delete schedule: ' . $stmt->error);
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

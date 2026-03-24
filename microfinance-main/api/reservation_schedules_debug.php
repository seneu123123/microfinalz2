<?php
/**
 * Simplified Reservation Schedules API for debugging
 */

// Ensure no HTML output before this
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Disable all error display
ini_set('display_errors', '0');
error_reporting(0);

// Database connection
try {
    require_once '../config/db.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

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
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

function getSchedules() {
    global $conn;
    try {
        $result = $conn->query("SELECT * FROM reservation_schedules ORDER BY start_time ASC");
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
            return;
        }
        $schedules = [];
        while ($row = $result->fetch_assoc()) {
            $schedules[] = $row;
        }
        echo json_encode(['success' => true, 'message' => 'Schedules retrieved', 'data' => $schedules]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function createSchedule() {
    global $conn;
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['schedule_id']) || empty($data['start_time']) || empty($data['end_time'])) {
            echo json_encode(['success' => false, 'message' => 'Schedule ID, start_time, and end_time required']);
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
            echo json_encode(['success' => true, 'message' => 'Schedule created', 'data' => ['schedule_id' => $data['schedule_id']]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create schedule: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function updateSchedule() {
    global $conn;
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['schedule_id'])) {
            echo json_encode(['success' => false, 'message' => 'Schedule ID required']);
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
            echo json_encode(['success' => true, 'message' => 'Schedule updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update schedule: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function deleteSchedule() {
    global $conn;
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['schedule_id'])) {
            echo json_encode(['success' => false, 'message' => 'Schedule ID required']);
            return;
        }

        $stmt = $conn->prepare("DELETE FROM reservation_schedules WHERE schedule_id = ?");
        $stmt->bind_param("s", $data['schedule_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Schedule deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete schedule: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>

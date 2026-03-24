<?php
/**
 * Trip Logs API (2.5)
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
        error_log("Fatal error in trip-logs.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in trip-logs.php: ' . $ex->getMessage());
});

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getTrips();
        break;
    case 'POST':
        createTrip();
        break;
    case 'PUT':
        updateTrip();
        break;
    case 'DELETE':
        deleteTrip();
        break;
    default:
        sendError('Invalid request method');
}

function getTrips() {
    global $conn;
    $result = $conn->query("SELECT * FROM trip_logs ORDER BY created_at DESC");
    if (!$result) {
        sendError('Query failed: ' . $conn->error);
        return;
    }
    $trips = [];
    while ($row = $result->fetch_assoc()) {
        $trips[] = $row;
    }
    sendSuccess('Trips retrieved', $trips);
}

function createTrip() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['trip_id'])) {
        sendError('Trip ID required');
        return;
    }

    $stmt = $conn->prepare("INSERT INTO trip_logs (trip_id, reservation_id, vehicle_id, driver_id, start_time, end_time, fuel_used, incident, distance, vendor_service, audit_reference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssddsss", 
        $data['trip_id'], 
        $data['reservation_id'] ?? '', 
        $data['vehicle_id'] ?? '', 
        $data['driver_id'] ?? '', 
        $data['start_time'] ?? null, 
        $data['end_time'] ?? null, 
        $data['fuel_used'] ?? 0, 
        $data['incident'] ?? '', 
        $data['distance'] ?? 0, 
        $data['vendor_service'] ?? '', 
        $data['audit_reference'] ?? ''
    );
    
    if ($stmt->execute()) {
        sendSuccess('Trip created', ['trip_id' => $data['trip_id']]);
    } else {
        sendError('Failed to create trip: ' . $stmt->error);
    }
}

function updateTrip() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['trip_id'])) {
        sendError('Trip ID required');
        return;
    }

    $stmt = $conn->prepare("UPDATE trip_logs SET start_time = ?, end_time = ?, fuel_used = ?, incident = ?, distance = ? WHERE trip_id = ?");
    $stmt->bind_param("ssdds", 
        $data['start_time'] ?? null, 
        $data['end_time'] ?? null, 
        $data['fuel_used'] ?? 0, 
        $data['incident'] ?? '', 
        $data['distance'] ?? 0, 
        $data['trip_id']
    );
    
    if ($stmt->execute()) {
        sendSuccess('Trip updated');
    } else {
        sendError('Failed to update trip: ' . $stmt->error);
    }
}

function deleteTrip() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['trip_id'])) {
        sendError('Trip ID required');
        return;
    }

    $stmt = $conn->prepare("DELETE FROM trip_logs WHERE trip_id = ?");
    $stmt->bind_param("s", $data['trip_id']);
    
    if ($stmt->execute()) {
        sendSuccess('Trip deleted');
    } else {
        sendError('Failed to delete trip: ' . $stmt->error);
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

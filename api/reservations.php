<?php
/**
 * Reservation Request API (2.2)
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Server error']);
        error_log("Fatal error in reservations.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in reservations.php: ' . $ex->getMessage());
});

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getReservations();
        break;
    case 'POST':
        createReservation();
        break;
    case 'PUT':
        updateReservation();
        break;
    case 'DELETE':
        deleteReservation();
        break;
    default:
        sendError('Invalid request method');
}

function getReservations() {
    global $conn;
    $result = $conn->query("SELECT * FROM reservations ORDER BY created_at DESC");
    if (!$result) {
        sendError('Query failed: ' . $conn->error);
        return;
    }
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    sendSuccess('Reservations retrieved', $reservations);
}

function createReservation() {
    global $conn;
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('Invalid JSON input: ' . json_last_error_msg());
        return;
    }

    if (empty($data['reservation_id'])) {
        sendError('Reservation ID required');
        return;
    }

    $stmt = $conn->prepare("INSERT INTO reservations (reservation_id, vendor_id, vehicle_id, requestor, vehicle_type, schedule, purpose, approval_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error);
        return;
    }
    $reservation_id = $data['reservation_id'];
    $vendor_id = $data['vendor_id'] ?? '';
    $vehicle_id = $data['vehicle_id'] ?? null;
    $requestor = $data['requestor'] ?? '';
    $vehicle_type = $data['vehicle_type'] ?? '';
    $schedule = $data['schedule'] ?? '';
    $purpose = $data['purpose'] ?? '';
    $approval_status = $data['approval_status'] ?? 'Pending';
    $status = $data['status'] ?? 'Pending';
    $stmt->bind_param("sssssssss", $reservation_id, $vendor_id, $vehicle_id, $requestor, $vehicle_type, $schedule, $purpose, $approval_status, $status);
    
    if ($stmt->execute()) {
        sendSuccess('Reservation created', ['reservation_id' => $data['reservation_id']]);
    } else {
        sendError('Failed to create reservation: ' . $stmt->error);
    }
}

function updateReservation() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['reservation_id'])) {
        sendError('Reservation ID required');
        return;
    }

    // Build dynamic SET clause based on provided fields
    $updatable = ['approval_status', 'status', 'assigned_driver', 'assigned_vehicle', 'vendor_id', 'vehicle_id', 'requestor', 'vehicle_type', 'schedule', 'purpose', 'department'];
    $fields = [];
    $types = '';
    $values = [];

    foreach ($updatable as $col) {
        if (array_key_exists($col, $data)) {
            $fields[] = "$col = ?";
            $values[] = $data[$col];
            $types .= 's';
        }
    }

    if (empty($fields)) {
        sendError('No fields to update');
        return;
    }

    $types .= 's'; // for reservation_id in WHERE
    $values[] = $data['reservation_id'];

    $sql = "UPDATE reservations SET " . implode(', ', $fields) . " WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error);
        return;
    }
    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        sendSuccess('Reservation updated');
    } else {
        sendError('Failed to update reservation: ' . $stmt->error);
    }
}

function deleteReservation() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['reservation_id'])) {
        sendError('Reservation ID required');
        return;
    }

    $stmt = $conn->prepare("DELETE FROM reservations WHERE reservation_id = ?");
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error);
        return;
    }
    $reservation_id = $data['reservation_id'];
    $stmt->bind_param("s", $reservation_id);
    
    if ($stmt->execute()) {
        sendSuccess('Reservation deleted');
    } else {
        sendError('Failed to delete reservation: ' . $stmt->error);
    }
}

?>

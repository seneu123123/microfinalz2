<?php
/**
 * Vehicle Inventory API (2.1)
 */

// disable error display early to prevent HTML output
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
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $err['message']]);
        error_log("Fatal error in vehicles.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in vehicles.php: ' . $ex->getMessage());
});

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getVehicles();
        break;
    case 'POST':
        createVehicle();
        break;
    case 'PUT':
        updateVehicle();
        break;
    case 'DELETE':
        deleteVehicle();
        break;
    default:
        sendError('Invalid request method');
}

function getVehicles() {
    global $conn;
    $result = $conn->query("SELECT * FROM vehicles ORDER BY created_at DESC");
    if (!$result) {
        sendError('Query failed: ' . $conn->error);
        return;
    }
    $vehicles = [];
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
    sendSuccess('Vehicles retrieved', $vehicles);
}

function createVehicle() {
    global $conn;
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('Invalid JSON input: ' . json_last_error_msg());
        return;
    }

    if (empty($data['vehicle_id']) || empty($data['plate_no'])) {
        sendError('Required fields missing');
        return;
    }

    $stmt = $conn->prepare("INSERT INTO vehicles (vehicle_id, vendor_id, vehicle_type, registration_no, make_model, year, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error);
        return;
    }
    // prepare variables for bind_param (year = int or null)
    $vehicle_id = $data['vehicle_id'];
    $vendor_id = $data['vendor_id'] ?? '';
    $vehicle_type = $data['type'] ?? '';
    $registration_no = $data['plate_no'];
    $make_model = $data['make_model'] ?? '';
    $year = isset($data['year']) && $data['year'] !== '' ? intval($data['year']) : null;
    $status = $data['status'] ?? 'Active';
    // types: s=string for all except year (integer)
    $stmt->bind_param("sssssis", $vehicle_id, $vendor_id, $vehicle_type, $registration_no, $make_model, $year, $status);
    
    if ($stmt->execute()) {
        sendSuccess('Vehicle created', ['vehicle_id' => $data['vehicle_id']]);
    } else {
        sendError('Failed to create vehicle: ' . $stmt->error);
    }
}

function updateVehicle() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['vehicle_id'])) {
        sendError('Vehicle ID required');
        return;
    }

    $stmt = $conn->prepare("UPDATE vehicles SET vehicle_type = ?, registration_no = ?, status = ? WHERE vehicle_id = ?");
    if (!$stmt) { sendError('Prepare failed: ' . $conn->error); return; }
    $vehicle_type = $data['type'] ?? '';
    $registration_no = $data['plate_no'] ?? '';
    $status = $data['status'] ?? 'Active';
    $vehicle_id = $data['vehicle_id'];
    $stmt->bind_param("ssss", $vehicle_type, $registration_no, $status, $vehicle_id);
    
    if ($stmt->execute()) {
        sendSuccess('Vehicle updated');
    } else {
        sendError('Failed to update vehicle: ' . $stmt->error);
    }
}

function deleteVehicle() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['vehicle_id'])) {
        sendError('Vehicle ID required');
        return;
    }

    $stmt = $conn->prepare("DELETE FROM vehicles WHERE vehicle_id = ?");
    if (!$stmt) { sendError('Prepare failed: ' . $conn->error); return; }
    $vehicle_id = $data['vehicle_id'];
    $stmt->bind_param("s", $vehicle_id);
    
    if ($stmt->execute()) {
        sendSuccess('Vehicle deleted');
    } else {
        sendError('Failed to delete vehicle: ' . $stmt->error);
    }
}

?>

<?php
/**
 * Drivers API (2.3)
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
        error_log("Fatal error in drivers.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in drivers.php: ' . $ex->getMessage());
});

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getDrivers();
        break;
    case 'POST':
        createDriver();
        break;
    case 'PUT':
        updateDriver();
        break;
    case 'DELETE':
        deleteDriver();
        break;
    default:
        sendError('Invalid request method');
}

function getDrivers() {
    global $conn;
    $result = $conn->query("SELECT * FROM drivers ORDER BY name ASC");
    if (!$result) {
        sendError('Query failed: ' . $conn->error);
        return;
    }
    $drivers = [];
    while ($row = $result->fetch_assoc()) {
        $drivers[] = $row;
    }
    sendSuccess('Drivers retrieved', $drivers);
}

function createDriver() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['driver_id']) || empty($data['name'])) {
        sendError('Driver ID and Name required');
        return;
    }

    $stmt = $conn->prepare("INSERT INTO drivers (driver_id, name, license_number, contact_number, status) VALUES (?, ?, ?, ?, ?)");
    $status = $data['status'] ?? 'Available';
    // bind_param requires variables, not expressions
    $driver_id = $data['driver_id'];
    $name = $data['name'];
    $license = $data['license_number'] ?? '';
    $contact = $data['contact_number'] ?? '';
    $stmt->bind_param("sssss", $driver_id, $name, $license, $contact, $status);
    
    if ($stmt->execute()) {
        sendSuccess('Driver created', ['driver_id' => $driver_id]);
    } else {
        sendError('Failed to create driver: ' . $stmt->error);
    }
}

function updateDriver() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['driver_id'])) {
        sendError('Driver ID required');
        return;
    }

    $stmt = $conn->prepare("UPDATE drivers SET name = ?, license_number = ?, contact_number = ?, status = ? WHERE driver_id = ?");
    $status = $data['status'] ?? 'Available';
    $name = $data['name'];
    $license = $data['license_number'] ?? '';
    $contact = $data['contact_number'] ?? '';
    $driver_id = $data['driver_id'];
    $stmt->bind_param("sssss", $name, $license, $contact, $status, $driver_id);
    
    if ($stmt->execute()) {
        sendSuccess('Driver updated');
    } else {
        sendError('Failed to update driver: ' . $stmt->error);
    }
}

function deleteDriver() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['driver_id'])) {
        sendError('Driver ID required');
        return;
    }

    $stmt = $conn->prepare("DELETE FROM drivers WHERE driver_id = ?");
    $stmt->bind_param("s", $data['driver_id']);
    
    if ($stmt->execute()) {
        sendSuccess('Driver deleted');
    } else {
        sendError('Failed to delete driver: ' . $stmt->error);
    }
}
?>

<?php
/**
 * Trip Logs API
 * Handles CRUD operations for trip logs
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
        error_log("Fatal error in trip_logs.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in trip_logs.php: ' . $ex->getMessage());
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
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

function getTrips() {
    global $conn;
    try {
        $result = $conn->query("SELECT * FROM trip_logs ORDER BY start_time DESC");
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
            return;
        }
        $trips = [];
        while ($row = $result->fetch_assoc()) {
            $trips[] = $row;
        }
        echo json_encode(['success' => true, 'message' => 'Trips retrieved', 'data' => $trips]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function createTrip() {
    global $conn;
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['reservation_id']) || empty($data['vehicle_id']) || empty($data['driver_id']) || empty($data['start_time'])) {
            echo json_encode(['success' => false, 'message' => 'Reservation ID, Vehicle ID, Driver ID, and Start Time are required']);
            return;
        }

        // Generate automatic trip ID
        $tripId = generateTripId();

        // Check if status column exists
        $statusColumnExists = false;
        $result = $conn->query("SHOW COLUMNS FROM trip_logs LIKE 'status'");
        if ($result && $result->num_rows > 0) {
            $statusColumnExists = true;
        }

        // Build query based on whether status column exists
        if ($statusColumnExists) {
            $stmt = $conn->prepare("INSERT INTO trip_logs (trip_id, reservation_id, vehicle_id, driver_id, start_time, end_time, fuel_used, distance, status, incident, vendor_service, audit_reference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $reservationId = $data['reservation_id'];
            $vehicleId = $data['vehicle_id'];
            $driverId = $data['driver_id'];
            $startTime = $data['start_time'];
            $endTime = $data['end_time'] ?? null;
            $fuelUsed = $data['fuel_used'] ?? 0;
            $distance = $data['distance'] ?? 0;
            $status = $data['status'] ?? 'In Progress';
            $incident = $data['incident'] ?? '';
            $vendorService = $data['vendor_service'] ?? '';
            $auditReference = $data['audit_reference'] ?? '';
            
            $stmt->bind_param("ssssssdsssss", $tripId, $reservationId, $vehicleId, $driverId, $startTime, $endTime, $fuelUsed, $distance, $status, $incident, $vendorService, $auditReference);
        } else {
            // Fallback query without status column
            $stmt = $conn->prepare("INSERT INTO trip_logs (trip_id, reservation_id, vehicle_id, driver_id, start_time, end_time, fuel_used, distance, incident, vendor_service, audit_reference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $reservationId = $data['reservation_id'];
            $vehicleId = $data['vehicle_id'];
            $driverId = $data['driver_id'];
            $startTime = $data['start_time'];
            $endTime = $data['end_time'] ?? null;
            $fuelUsed = $data['fuel_used'] ?? 0;
            $distance = $data['distance'] ?? 0;
            $incident = $data['incident'] ?? '';
            $vendorService = $data['vendor_service'] ?? '';
            $auditReference = $data['audit_reference'] ?? '';
            
            $stmt->bind_param("ssssssdssss", $tripId, $reservationId, $vehicleId, $driverId, $startTime, $endTime, $fuelUsed, $distance, $incident, $vendorService, $auditReference);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Trip created', 'data' => ['trip_id' => $tripId]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create trip: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function generateTripId() {
    global $conn;
    try {
        // Get the highest existing trip_id
        $result = $conn->query("SELECT trip_id FROM trip_logs ORDER BY trip_id DESC LIMIT 1");
        
        if ($result && $row = $result->fetch_assoc()) {
            $lastId = $row['trip_id'];
            // Extract numeric part and increment
            if (preg_match('/TRIP-(\d+)/', $lastId, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
                return 'TRIP-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
        }
        
        // If no existing IDs or format doesn't match, start with TRIP-001
        return 'TRIP-001';
    } catch (Exception $e) {
        // Fallback to TRIP-001 if there's an error
        return 'TRIP-001';
    }
}

function updateTrip() {
    global $conn;
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['trip_id'])) {
            echo json_encode(['success' => false, 'message' => 'Trip ID required']);
            return;
        }

        // Check if status column exists
        $statusColumnExists = false;
        $result = $conn->query("SHOW COLUMNS FROM trip_logs LIKE 'status'");
        if ($result && $result->num_rows > 0) {
            $statusColumnExists = true;
        }

        // Build query based on whether status column exists
        if ($statusColumnExists) {
            $stmt = $conn->prepare("UPDATE trip_logs SET reservation_id = ?, vehicle_id = ?, driver_id = ?, start_time = ?, end_time = ?, fuel_used = ?, distance = ?, status = ?, incident = ?, vendor_service = ?, audit_reference = ? WHERE trip_id = ?");
            
            $reservationId = $data['reservation_id'];
            $vehicleId = $data['vehicle_id'];
            $driverId = $data['driver_id'];
            $startTime = $data['start_time'];
            $endTime = $data['end_time'];
            $fuelUsed = $data['fuel_used'];
            $distance = $data['distance'];
            $status = $data['status'];
            $incident = $data['incident'];
            $vendorService = $data['vendor_service'];
            $auditReference = $data['audit_reference'];
            $tripId = $data['trip_id'];
            
            $stmt->bind_param("ssssssdsssss", $reservationId, $vehicleId, $driverId, $startTime, $endTime, $fuelUsed, $distance, $status, $incident, $vendorService, $auditReference, $tripId);
        } else {
            // Fallback query without status column
            $stmt = $conn->prepare("UPDATE trip_logs SET reservation_id = ?, vehicle_id = ?, driver_id = ?, start_time = ?, end_time = ?, fuel_used = ?, distance = ?, incident = ?, vendor_service = ?, audit_reference = ? WHERE trip_id = ?");
            
            $reservationId = $data['reservation_id'];
            $vehicleId = $data['vehicle_id'];
            $driverId = $data['driver_id'];
            $startTime = $data['start_time'];
            $endTime = $data['end_time'];
            $fuelUsed = $data['fuel_used'];
            $distance = $data['distance'];
            $incident = $data['incident'];
            $vendorService = $data['vendor_service'];
            $auditReference = $data['audit_reference'];
            $tripId = $data['trip_id'];
            
            $stmt->bind_param("ssssssdssss", $reservationId, $vehicleId, $driverId, $startTime, $endTime, $fuelUsed, $distance, $incident, $vendorService, $auditReference, $tripId);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Trip updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update trip: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function deleteTrip() {
    global $conn;
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['trip_id'])) {
            echo json_encode(['success' => false, 'message' => 'Trip ID required']);
            return;
        }

        $stmt = $conn->prepare("DELETE FROM trip_logs WHERE trip_id = ?");
        $stmt->bind_param("s", $data['trip_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Trip deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete trip: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>

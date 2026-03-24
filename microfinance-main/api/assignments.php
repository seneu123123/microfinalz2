<?php
/**
 * Assignments API (2.3)
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
        error_log("Fatal error in assignments.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in assignments.php: ' . $ex->getMessage());
});

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getAssignments();
        break;
    case 'POST':
        createAssignment();
        break;
    case 'PUT':
        updateAssignment();
        break;
    case 'DELETE':
        deleteAssignment();
        break;
    default:
        sendError('Invalid request method');
}

function getAssignments() {
    global $conn;
    $result = $conn->query("SELECT * FROM assignments ORDER BY assigned_at DESC");
    if (!$result) {
        sendError('Query failed: ' . $conn->error);
        return;
    }
    $assignments = [];
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    sendSuccess('Assignments retrieved', $assignments);
}

function createAssignment() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['assignment_id']) || empty($data['driver_id']) || empty($data['vehicle_id'])) {
        sendError('Assignment ID, Driver ID, and Vehicle ID required');
        return;
    }

    $stmt = $conn->prepare("INSERT INTO assignments (assignment_id, reservation_id, driver_id, vehicle_id, status) VALUES (?, ?, ?, ?, ?)");
    $assignment_id = $data['assignment_id'];
    $reservation_id = $data['reservation_id'] ?? '';
    $driver_id = $data['driver_id'];
    $vehicle_id = $data['vehicle_id'];
    $status = $data['status'] ?? 'Active';
    $stmt->bind_param("sssss", $assignment_id, $reservation_id, $driver_id, $vehicle_id, $status);
    
    if ($stmt->execute()) {
        // if linked to a reservation update its assigned fields
        if (!empty($reservation_id)) {
            $upd = $conn->prepare("UPDATE reservations SET assigned_driver = ?, assigned_vehicle = ?, status = 'Assigned' WHERE reservation_id = ?");
            if ($upd) {
                $upd->bind_param("sss", $driver_id, $vehicle_id, $reservation_id);
                $upd->execute();
                $upd->close();
            }
        }
        sendSuccess('Assignment created', ['assignment_id' => $data['assignment_id']]);
    } else {
        sendError('Failed to create assignment: ' . $stmt->error);
    }
}

function updateAssignment() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['assignment_id'])) {
        sendError('Assignment ID required');
        return;
    }

    // fetch existing reservation linkage
    $oldReservation = '';
    $sel = $conn->prepare("SELECT reservation_id FROM assignments WHERE assignment_id = ?");
    if ($sel) {
        $sel->bind_param("s", $data['assignment_id']);
        $sel->execute();
        $sel->bind_result($oldReservation);
        $sel->fetch();
        $sel->close();
    }

    // build dynamic update for assignment table
    $fields = [];
    $types = '';
    $values = [];
    foreach (['driver_id', 'vehicle_id', 'reservation_id', 'status'] as $col) {
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
    $types .= 's';
    $values[] = $data['assignment_id'];
    $sql = "UPDATE assignments SET " . implode(', ', $fields) . " WHERE assignment_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendError('Prepare failed: ' . $conn->error);
        return;
    }
    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
        sendError('Failed to update assignment: ' . $stmt->error);
        return;
    }

    // handle reservation updates/clears
    $newReservation = $data['reservation_id'] ?? $oldReservation;
    $newDriver = $data['driver_id'] ?? null;
    $newVehicle = $data['vehicle_id'] ?? null;

    // clear old reservation if changed
    if ($oldReservation && $oldReservation !== $newReservation) {
        $updOld = $conn->prepare("UPDATE reservations SET assigned_driver = '', assigned_vehicle = '' WHERE reservation_id = ?");
        if ($updOld) {
            $updOld->bind_param("s", $oldReservation);
            $updOld->execute();
            $updOld->close();
        }
    }
    // set new reservation fields if present
    if ($newReservation) {
        $updNew = $conn->prepare("UPDATE reservations SET assigned_driver = ?, assigned_vehicle = ?, status = 'Assigned' WHERE reservation_id = ?");
        if ($updNew) {
            $d = $newDriver ?? '';
            $v = $newVehicle ?? '';
            $updNew->bind_param("sss", $d, $v, $newReservation);
            $updNew->execute();
            $updNew->close();
        }
    }

    sendSuccess('Assignment updated');
}

function deleteAssignment() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['assignment_id'])) {
        sendError('Assignment ID required');
        return;
    }

    // find reservation link before deleting
    $reservationId = '';
    $sel = $conn->prepare("SELECT reservation_id FROM assignments WHERE assignment_id = ?");
    if ($sel) {
        $sel->bind_param("s", $data['assignment_id']);
        $sel->execute();
        $sel->bind_result($reservationId);
        $sel->fetch();
        $sel->close();
    }

    $stmt = $conn->prepare("DELETE FROM assignments WHERE assignment_id = ?");
    $stmt->bind_param("s", $data['assignment_id']);
    
    if ($stmt->execute()) {
        if (!empty($reservationId)) {
            $upd = $conn->prepare("UPDATE reservations SET assigned_driver = '', assigned_vehicle = '', status = 'Pending' WHERE reservation_id = ?");
            if ($upd) {
                $upd->bind_param("s", $reservationId);
                $upd->execute();
                $upd->close();
            }
        }
        sendSuccess('Assignment deleted');
    } else {
        sendError('Failed to delete assignment: ' . $stmt->error);
    }
}
?>

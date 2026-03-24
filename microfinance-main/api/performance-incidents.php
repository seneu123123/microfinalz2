<?php
/**
 * Performance Incident API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getIncidents();
        break;
    case 'POST':
        createIncident();
        break;
    default:
        sendError('Invalid method');
}

function getIncidents() {
    global $conn;
    $result = $conn->query("SELECT * FROM performance_incidents ORDER BY created_at DESC");
    if (!$result) {
        sendError('Query failed');
    }
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    sendSuccess('Incidents retrieved', $records);
}

function createIncident() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['vendor_id']) || empty($data['performance_id'])) {
        sendError('Vendor ID and Performance ID required');
    }

    $nextId = getNextIncidentId();
    $incidentId = 'INC-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

    $stmt = $conn->prepare("INSERT INTO performance_incidents (incident_id, performance_id, vendor_id, incident_date, description, severity) VALUES (?, ?, ?, ?, ?, ?)");
    $vendor_id = $data['vendor_id'];
    $performance_id = $data['performance_id'];
    $incident_date = $data['incident_date'] ?? null;
    $description = $data['description'] ?? null;
    $severity = $data['severity'] ?? 'Low';

    $stmt->bind_param(
        "ssssss",
        $incidentId,
        $performance_id,
        $vendor_id,
        $incident_date,
        $description,
        $severity
    );

    if ($stmt->execute()) {
        // increment incident count
        $update = $conn->prepare("UPDATE vendor_performance SET incident_reports = COALESCE(incident_reports,0) + 1 WHERE performance_id = ?");
        $update->bind_param("s", $performance_id);
        $update->execute();

        sendSuccess('Incident logged', ['incident_id' => $incidentId]);
    } else {
        sendError('Failed to log incident');
    }
}

function getNextIncidentId() {
    global $conn;
    $result = $conn->query("SELECT MAX(CAST(SUBSTRING(incident_id, 5) AS UNSIGNED)) as max_id FROM performance_incidents");
    $row = $result->fetch_assoc();
    return ($row['max_id'] ?? 0) + 1;
}

function sendSuccess($message, $data = null) {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

function sendError($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

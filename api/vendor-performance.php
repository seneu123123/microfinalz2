<?php
/**
 * Vendor Performance API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getPerformanceRecords();
        break;
    case 'POST':
        createPerformanceRecord();
        break;
    case 'PUT':
        updatePerformanceRecord();
        break;
    default:
        sendError('Invalid method');
}

function getPerformanceRecords() {
    global $conn;
    $vendor_id = $_GET['vendor_id'] ?? null;
    if ($vendor_id) {
        $stmt = $conn->prepare("SELECT * FROM vendor_performance WHERE vendor_id = ? ORDER BY created_at DESC");
        $stmt->bind_param('s', $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT * FROM vendor_performance ORDER BY created_at DESC");
    }
    if (!$result) {
        sendError('Query failed');
    }
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    sendSuccess('Performance records retrieved', $records);
}

function createPerformanceRecord() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['vendor_id'])) {
        sendError('Vendor ID required');
    }

    $nextId = getNextPerformanceId();
    $performanceId = 'PERF-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

    $stmt = $conn->prepare("INSERT INTO vendor_performance (performance_id, vendor_id, on_time_rate, quality_score, incident_reports, average_cost, performance_rating, sla_document, performance_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $vendor_id = $data['vendor_id'];
    $on_time_rate = $data['on_time_rate'] ?? null;
    $quality_score = $data['quality_score'] ?? null;
    $incident_reports = $data['incident_reports'] ?? null;
    $average_cost = $data['average_cost'] ?? null;
    $performance_rating = $data['performance_rating'] ?? null;
    $sla_document = $data['sla_document'] ?? null;
    $performance_notes = $data['performance_notes'] ?? null;

    $stmt->bind_param(
        "sssssssss",
        $performanceId,
        $vendor_id,
        $on_time_rate,
        $quality_score,
        $incident_reports,
        $average_cost,
        $performance_rating,
        $sla_document,
        $performance_notes
    );

    if ($stmt->execute()) {
        sendSuccess('Performance record created', ['performance_id' => $performanceId]);
    } else {
        sendError('Creation failed');
    }
}

function updatePerformanceRecord() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['vendor_id'])) {
        sendError('Vendor ID required');
    }

    $stmt = $conn->prepare("UPDATE vendor_performance SET on_time_rate = ?, quality_score = ?, incident_reports = ?, average_cost = ?, performance_rating = ?, sla_document = ?, performance_notes = ? WHERE vendor_id = ?");
    
    $on_time_rate = $data['on_time_rate'] ?? null;
    $quality_score = $data['quality_score'] ?? null;
    $incident_reports = $data['incident_reports'] ?? null;
    $average_cost = $data['average_cost'] ?? null;
    $performance_rating = $data['performance_rating'] ?? null;
    $sla_document = $data['sla_document'] ?? null;
    $performance_notes = $data['performance_notes'] ?? null;
    $vendor_id = $data['vendor_id'];

    $stmt->bind_param(
        "ssssssss",
        $on_time_rate,
        $quality_score,
        $incident_reports,
        $average_cost,
        $performance_rating,
        $sla_document,
        $performance_notes,
        $vendor_id
    );

    if ($stmt->execute()) {
        sendSuccess('Performance record updated');
    } else {
        sendError('Update failed');
    }
}

function getNextPerformanceId() {
    global $conn;
    $result = $conn->query("SELECT MAX(CAST(SUBSTRING(performance_id, 6) AS UNSIGNED)) as max_id FROM vendor_performance");
    $row = $result->fetch_assoc();
    return ($row['max_id'] ?? 0) + 1;
}
?>

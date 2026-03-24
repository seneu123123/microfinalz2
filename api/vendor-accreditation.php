<?php
/**
 * Vendor Accreditation API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getAccreditations();
        break;
    case 'POST':
        createAccreditation();
        break;
    case 'PUT':
        updateAccreditation();
        break;
    default:
        sendError('Invalid method');
}

function getAccreditations() {
    global $conn;
    $result = $conn->query("SELECT * FROM vendor_accreditation ORDER BY created_at DESC");
    if (!$result) {
        sendError('Query failed');
    }
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    sendSuccess('Accreditations retrieved', $records);
}

function createAccreditation() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['vendor_id'])) {
        sendError('Vendor ID required');
    }

    $nextId = getNextAccreditationId();
    $accreditationId = 'ACC-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    $riskScore = rand(0, 100);
    $status = $data['status'] ?? 'Pending Review';

    $stmt = $conn->prepare("INSERT INTO vendor_accreditation (accreditation_id, vendor_id, license_expiry, insurance_expiry, compliance_checklist, risk_score, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $vendor_id = $data['vendor_id'];
    $license_expiry = $data['license_expiry'] ?? null;
    $insurance_expiry = $data['insurance_expiry'] ?? null;
    $compliance_checklist = $data['compliance_checklist'] ?? null;

    $stmt->bind_param(
        "sssssss",
        $accreditationId,
        $vendor_id,
        $license_expiry,
        $insurance_expiry,
        $compliance_checklist,
        $riskScore,
        $status
    );

    if ($stmt->execute()) {
        sendSuccess('Accreditation created', ['accreditation_id' => $accreditationId]);
    } else {
        sendError('Creation failed');
    }
}

function updateAccreditation() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['accreditation_id'])) {
        sendError('Accreditation ID required');
    }

    $stmt = $conn->prepare("UPDATE vendor_accreditation SET license_expiry = ?, insurance_expiry = ?, compliance_checklist = ?, status = ? WHERE accreditation_id = ?");
    
    $license_expiry = $data['license_expiry'] ?? null;
    $insurance_expiry = $data['insurance_expiry'] ?? null;
    $compliance_checklist = $data['compliance_checklist'] ?? null;
    $status = $data['status'] ?? 'Pending Review';
    $accreditation_id = $data['accreditation_id'];

    $stmt->bind_param(
        "sssss",
        $license_expiry,
        $insurance_expiry,
        $compliance_checklist,
        $status,
        $accreditation_id
    );

    if ($stmt->execute()) {
        sendSuccess('Accreditation updated');
    } else {
        sendError('Update failed');
    }
}

function getNextAccreditationId() {
    global $conn;
    $result = $conn->query("SELECT MAX(CAST(SUBSTRING(accreditation_id, 5) AS UNSIGNED)) as max_id FROM vendor_accreditation");
    $row = $result->fetch_assoc();
    return ($row['max_id'] ?? 0) + 1;
}
?>

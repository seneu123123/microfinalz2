<?php
/**
 * Vendor Contracts API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getContracts();
        break;
    case 'POST':
        createContract();
        break;
    case 'PUT':
        updateContract();
        break;
    default:
        sendError('Invalid method');
}

function getContracts() {
    global $conn;
    $vendor_id = $_GET['vendor_id'] ?? null;
    if ($vendor_id) {
        $stmt = $conn->prepare("SELECT * FROM vendor_contracts WHERE vendor_id = ? ORDER BY created_at DESC");
        $stmt->bind_param('s', $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT * FROM vendor_contracts ORDER BY created_at DESC");
    }
    if (!$result) {
        sendError('Query failed');
    }
    $contracts = [];
    while ($row = $result->fetch_assoc()) {
        $contracts[] = $row;
    }
    sendSuccess('Contracts retrieved', $contracts);
}

function createContract() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['vendor_id'])) {
        sendError('Vendor ID required');
    }

    $nextId = getNextContractId();
    $contractId = 'CTR-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    $status = $data['approval_status'] ?? 'Draft';

    $stmt = $conn->prepare("INSERT INTO vendor_contracts (contract_id, vendor_id, contract_type, start_date, end_date, contract_amount, contract_terms, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $vendor_id = $data['vendor_id'];
    $contract_type = $data['contract_type'] ?? null;
    $start_date = $data['start_date'] ?? null;
    $end_date = $data['end_date'] ?? null;
    $contract_amount = $data['contract_amount'] ?? null;
    $contract_terms = $data['contract_terms'] ?? null;

    $stmt->bind_param(
        "ssssssss",
        $contractId,
        $vendor_id,
        $contract_type,
        $start_date,
        $end_date,
        $contract_amount,
        $contract_terms,
        $status
    );

    if ($stmt->execute()) {
        sendSuccess('Contract created', ['contract_id' => $contractId]);
    } else {
        sendError('Creation failed');
    }
}

function updateContract() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['contract_id'])) {
        sendError('Contract ID required');
    }

    $stmt = $conn->prepare("UPDATE vendor_contracts SET contract_type = ?, start_date = ?, end_date = ?, contract_amount = ?, contract_terms = ?, approval_status = ? WHERE contract_id = ?");
    
    $contract_type = $data['contract_type'] ?? null;
    $start_date = $data['start_date'] ?? null;
    $end_date = $data['end_date'] ?? null;
    $contract_amount = $data['contract_amount'] ?? null;
    $contract_terms = $data['contract_terms'] ?? null;
    $approval_status = $data['approval_status'] ?? 'Draft';
    $contract_id = $data['contract_id'];

    $stmt->bind_param(
        "sssssss",
        $contract_type,
        $start_date,
        $end_date,
        $contract_amount,
        $contract_terms,
        $approval_status,
        $contract_id
    );

    if ($stmt->execute()) {
        sendSuccess('Contract updated');
    } else {
        sendError('Update failed');
    }
}

function getNextContractId() {
    global $conn;
    $result = $conn->query("SELECT MAX(CAST(SUBSTRING(contract_id, 5) AS UNSIGNED)) as max_id FROM vendor_contracts");
    $row = $result->fetch_assoc();
    return ($row['max_id'] ?? 0) + 1;
}
?>

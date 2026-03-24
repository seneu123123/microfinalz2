<?php
/**
 * Contract Documents API
 *
 * Simple endpoint to track documents associated with a vendor contract.
 *
 * GET    - list documents for a contract_id (query param)
 * POST   - add a new document record (expects contract_id, filename, description)
 * DELETE - remove a document by id (requires JSON body with document_id)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getDocuments();
        break;
    case 'POST':
        createDocument();
        break;
    case 'DELETE':
        deleteDocument();
        break;
    default:
        sendError('Invalid method');
}

function getDocuments() {
    global $conn;
    $contract_id = $_GET['contract_id'] ?? null;
    if (!$contract_id) {
        sendError('contract_id required');
    }
    $stmt = $conn->prepare("SELECT * FROM contract_documents WHERE contract_id = ? ORDER BY uploaded_at DESC");
    $stmt->bind_param('s', $contract_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $docs = [];
    while ($row = $result->fetch_assoc()) {
        $docs[] = $row;
    }
    sendSuccess('Documents retrieved', $docs);
}

function createDocument() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data['contract_id'])) {
        sendError('contract_id required');
    }
    if (empty($data['filename'])) {
        sendError('filename required');
    }
    $contract_id = $data['contract_id'];
    $filename = $data['filename'];
    $description = $data['description'] ?? null;
    $uploaded_at = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO contract_documents (contract_id, filename, description, uploaded_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $contract_id, $filename, $description, $uploaded_at);
    if ($stmt->execute()) {
        sendSuccess('Document added', ['document_id' => $stmt->insert_id]);
    } else {
        sendError('Failed to insert document');
    }
}

function deleteDocument() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data['document_id'])) {
        sendError('document_id required');
    }
    $stmt = $conn->prepare("DELETE FROM contract_documents WHERE id = ?");
    $stmt->bind_param('i', $data['document_id']);
    if ($stmt->execute()) {
        sendSuccess('Document deleted');
    } else {
        sendError('Failed to delete document');
    }
}

function sendSuccess($msg, $data = null) {
    $response = ['success' => true, 'message' => $msg];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

function sendError($msg) {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

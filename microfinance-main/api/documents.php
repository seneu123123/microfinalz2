<?php
/**
 * Vendor Documents API
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
        uploadDocument();
        break;
    default:
        sendError('Invalid method');
}

function getDocuments() {
    global $conn;
    $vendor = isset($_GET['vendor_id']) ? $_GET['vendor_id'] : null;
    $sql = "SELECT * FROM documents";
    if ($vendor) {
        $stmt = $conn->prepare($sql . " WHERE vendor_id = ? ORDER BY upload_date DESC");
        $stmt->bind_param("s", $vendor);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql . " ORDER BY upload_date DESC");
    }
    if (!$result) sendError('Query failed');
    $docs = [];
    while ($row = $result->fetch_assoc()) {
        $docs[] = $row;
    }
    sendSuccess('Documents retrieved', $docs);
}

function uploadDocument() {
    global $conn;
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['vendor_id']) || empty($data['doc_name']) || empty($data['file_path'])) {
        sendError('vendor_id, doc_name and file_path required');
    }
    $docId = 'DOC-' . str_pad(rand(1,99999),5,'0',STR_PAD_LEFT);
    $stmt = $conn->prepare("INSERT INTO documents (document_id, vendor_id, doc_type, doc_name, file_path, expiry_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $vendor_id = $data['vendor_id'];
    $doc_type = $data['doc_type'] ?? null;
    $doc_name = $data['doc_name'];
    $file_path = $data['file_path'];
    $expiry = $data['expiry_date'] ?? null;
    $status = $data['status'] ?? 'Pending';
    $stmt->bind_param("sssssss", $docId, $vendor_id, $doc_type, $doc_name, $file_path, $expiry, $status);
    if ($stmt->execute()) {
        sendSuccess('Document uploaded', ['document_id'=>$docId]);
    } else {
        sendError('Upload failed');
    }
}

function sendSuccess($msg, $data=null) {
    $resp=['success'=>true,'message'=>$msg];
    if($data) $resp['data']=$data;
    echo json_encode($resp);
    exit;
}

function sendError($msg) {
    echo json_encode(['success'=>false,'message'=>$msg]);
    exit;
}

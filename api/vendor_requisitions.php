<?php
/**
 * Vendor Requisitions API - Handle requisitions sent to vendor registration area
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: Log basic info
error_log("Vendor Requisitions API called - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Open access - no authentication required");

// Load database configuration
try {
    require '../config/db.php';
    error_log("Database config loaded successfully");
} catch (Exception $e) {
    error_log("Database config failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database config error: ' . $e->getMessage()]);
    exit;
}

// 1. GET REQUISITIONS
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        error_log("Processing GET request for vendor requisitions");
        
        // Fetch requisitions sent to vendor-registration area (no session check)
        $stmt = $pdo->prepare("SELECT r.*, ri.item_name, ri.quantity, ri.unit 
                               FROM vendor_requisitions vr
                               INNER JOIN requisitions r ON vr.requisition_id = r.id 
                               LEFT JOIN requisition_items ri ON r.id = ri.requisition_id 
                               WHERE vr.sent_to = 'vendor_registration' 
                               ORDER BY vr.id DESC");
        $stmt->execute();
        $requisitions = $stmt->fetchAll();
        
        error_log("Query successful, found " . count($requisitions) . " rows");
        
        // Group items by requisition ID
        $grouped = [];
        foreach ($requisitions as $row) {
            $id = $row['id'];
            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id' => $row['id'],
                    'request_date' => $row['request_date'],
                    'status' => $row['status'],
                    'remarks' => $row['remarks'],
                    'items' => []
                ];
            }
            if ($row['item_name']) {
                $grouped[$id]['items'][] = [
                    'name' => $row['item_name'],
                    'quantity' => $row['quantity'],
                    'unit' => $row['unit']
                ];
            }
        }
        
        echo json_encode(['status' => 'success', 'data' => array_values($grouped)]);
        
    } catch (Exception $e) {
        error_log("GET vendor requisitions error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 2. POST - Send requisition to vendor registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    error_log("POST input: " . print_r($input, true));
    
    if (!isset($input['requisition_id']) || !isset($input['vendor_id'])) {
        http_response_code(400);
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Check if requisition already sent to this vendor to prevent duplicates
        $checkStmt = $pdo->prepare("SELECT id FROM vendor_requisitions WHERE requisition_id = ? AND vendor_id = ? AND sent_to = ?");
        $checkStmt->execute([$input['requisition_id'], $input['vendor_id'], $input['sent_to'] ?? 'vendor_registration']);
        
        if ($checkStmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Requisition already sent to vendor registration']);
            $pdo->rollBack();
            exit;
        }
        
        // Insert into vendor_requisitions table (no authentication required)
        $stmt = $pdo->prepare("INSERT INTO vendor_requisitions (requisition_id, vendor_id, sent_to, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([
            $input['requisition_id'],
            $input['vendor_id'],
            $input['sent_to'] ?? 'vendor_registration'
        ]);
        
        $pdo->commit();
        
        echo json_encode(['status' => 'success', 'message' => 'Requisition sent to vendor registration successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("POST vendor requisitions error: " . $e->getMessage());
        http_response_code(500);
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 3. PUT - Update requisition status
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    error_log("PUT input: " . print_r($input, true));
    
    if (!isset($input['id']) || !isset($input['status'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Update requisition status
        $stmt = $pdo->prepare("UPDATE requisitions SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$input['status'], $input['id'], $_SESSION['user_id']]);
        
        $pdo->commit();
        
        echo json_encode(['status' => 'success', 'message' => 'Requisition status updated successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("PUT vendor requisitions error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 4. GET single requisition details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $req_id = $_GET['id'];
    
    try {
        error_log("Getting details for requisition ID: " . $req_id);
        
        // Fetch requisition with items
        $stmt = $pdo->prepare("SELECT r.*, ri.item_name, ri.quantity, ri.unit 
                               FROM vendor_requisitions vr
                               INNER JOIN requisitions r ON vr.requisition_id = r.id 
                               LEFT JOIN requisition_items ri ON r.id = ri.requisition_id 
                               WHERE r.id = ? AND vr.sent_to = 'vendor_registration'
                               ORDER BY r.id DESC");
        $stmt->execute([$req_id]);
        $requisitions = $stmt->fetchAll();
        
        error_log("Query successful, found " . count($requisitions) . " rows");
        
        // Group items by requisition ID
        $grouped = [];
        foreach ($requisitions as $row) {
            $id = $row['id'];
            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id' => $row['id'],
                    'request_date' => $row['request_date'],
                    'status' => $row['status'],
                    'remarks' => $row['remarks'],
                    'items' => []
                ];
            }
            if ($row['item_name']) {
                $grouped[$id]['items'][] = [
                    'name' => $row['item_name'],
                    'quantity' => $row['quantity'],
                    'unit' => $row['unit']
                ];
            }
        }
        
        echo json_encode(['status' => 'success', 'data' => array_values($grouped)]);
        
    } catch (Exception $e) {
        error_log("GET requisition details error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
?>

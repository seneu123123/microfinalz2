<?php
// api/po.php
header('Content-Type: application/json');
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

// 1. GET DATA
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if ($action === 'get_pending_reqs') {
        $stmt = $pdo->query("SELECT * FROM requisitions WHERE status IN ('Pending', 'Approved') ORDER BY id DESC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    }

    if ($action === 'get_orders') {
        $sql = "SELECT po.*, s.company_name, r.remarks 
                FROM purchase_orders po 
                JOIN suppliers s ON po.supplier_id = s.id 
                JOIN requisitions r ON po.requisition_id = r.id 
                ORDER BY po.id DESC";
        $stmt = $pdo->query($sql);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    }
    exit;
}

// 2. PROCESS ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // A. Approve Requisition
    if ($input['action'] === 'approve_req') {
        $stmt = $pdo->prepare("UPDATE requisitions SET status = 'Approved' WHERE id = ?");
        $stmt->execute([$input['req_id']]);
        echo json_encode(['status' => 'success', 'message' => 'Requisition Approved']);
        exit;
    }

    // B. Create Purchase Order (From existing Requisition)
    if ($input['action'] === 'create_po') {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO purchase_orders (requisition_id, supplier_id, total_cost) VALUES (?, ?, ?)");
            $stmt->execute([$input['req_id'], $input['supplier_id'], $input['cost']]);
            
            $stmt = $pdo->prepare("UPDATE requisitions SET status = 'PO Created' WHERE id = ?");
            $stmt->execute([$input['req_id']]);

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Purchase Order Generated']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // C. NEW: Create Direct Purchase Order (Bypassing Requisition UI)
    if ($input['action'] === 'create_direct_po') {
        try {
            $pdo->beginTransaction();

            // 1. Auto-create a Requisition
            $stmt = $pdo->prepare("INSERT INTO requisitions (user_id, request_date, status, remarks) VALUES (?, NOW(), 'PO Created', ?)");
            $stmt->execute([$_SESSION['user_id'], $input['description']]);
            $req_id = $pdo->lastInsertId();

            // 2. Add a default item for tracking
            $stmt = $pdo->prepare("INSERT INTO requisition_items (requisition_id, item_name, quantity, unit, estimated_cost) VALUES (?, ?, 1, 'lot', ?)");
            $stmt->execute([$req_id, $input['description'], $input['cost']]);

            // 3. Create the PO
            $stmt = $pdo->prepare("INSERT INTO purchase_orders (requisition_id, supplier_id, total_cost) VALUES (?, ?, ?)");
            $stmt->execute([$req_id, $input['supplier_id'], $input['cost']]);

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Direct Order Created Successfully!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}
?>
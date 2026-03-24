<?php
header('Content-Type: application/json');
session_start();
require 'db.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch only POs that are 'Ordered' (not yet received)
    $stmt = $pdo->query("SELECT po.*, s.supplier_name FROM purchase_orders po 
                         JOIN suppliers s ON po.supplier_id = s.id 
                         WHERE po.status = 'Ordered'");
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input['action'] === 'receive_items') {
        try {
            $pdo->beginTransaction();
            // 1. Update PO Status to 'Received'
            $stmt = $pdo->prepare("UPDATE purchase_orders SET status = 'Received' WHERE id = ?");
            $stmt->execute([$input['po_id']]);

            // 2. BPA Trigger: Add items to Inventory
            // We find or create the item in the inventory table based on PO description
            $checkInv = $pdo->prepare("SELECT id FROM inventory WHERE item_name = ?");
            $checkInv->execute([$input['item_name']]);
            $inv = $checkInv->fetch();

            if ($inv) {
                $upd = $pdo->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?");
                $upd->execute([$input['qty'], $inv['id']]);
            } else {
                $ins = $pdo->prepare("INSERT INTO inventory (item_name, quantity, unit) VALUES (?, ?, ?)");
                $ins->execute([$input['item_name'], $input['qty'], 'Units']);
            }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Items received and inventory updated!']);
        } catch (Exception $e) { $pdo->rollBack(); echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    }
}
?>
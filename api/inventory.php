<?php
// api/inventory.php
header('Content-Type: application/json');
session_start();
require 'db.php';

$action = $_GET['action'] ?? '';

// 1. GET INVENTORY
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT * FROM inventory ORDER BY item_name ASC");
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    exit;
}

// 2. RECEIVE PURCHASE ORDER (Complex Logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input['action'] === 'receive_po') {
        $po_id = $input['po_id'];

        try {
            $pdo->beginTransaction();

            // A. Get PO Details & Requisition Items
            $stmt = $pdo->prepare("
                SELECT ri.item_name, ri.quantity, ri.unit, ri.estimated_cost
                FROM purchase_orders po
                JOIN requisition_items ri ON po.requisition_id = ri.requisition_id
                WHERE po.id = ?
            ");
            $stmt->execute([$po_id]);
            $items = $stmt->fetchAll();

            if (!$items) throw new Exception("PO Items not found");

            // B. Loop items and Add to Inventory
            foreach ($items as $item) {
                // Check if item exists
                $check = $pdo->prepare("SELECT id FROM inventory WHERE item_name = ?");
                $check->execute([$item['item_name']]);
                $existing = $check->fetch();

                if ($existing) {
                    // Update Quantity
                    $update = $pdo->prepare("UPDATE inventory SET quantity = quantity + ? WHERE id = ?");
                    $update->execute([$item['quantity'], $existing['id']]);
                    $item_id = $existing['id'];
                } else {
                    // Insert New Item
                    $insert = $pdo->prepare("INSERT INTO inventory (item_name, quantity, unit, unit_price) VALUES (?, ?, ?, ?)");
                    $insert->execute([$item['item_name'], $item['quantity'], $item['unit'], $item['estimated_cost']]);
                    $item_id = $pdo->lastInsertId();
                }

                // Log Transaction
                $log = $pdo->prepare("INSERT INTO inventory_logs (item_id, change_amount, reason) VALUES (?, ?, ?)");
                $log->execute([$item_id, $item['quantity'], "Received PO #$po_id"]);
            }

            // C. Update PO Status
            $updatePO = $pdo->prepare("UPDATE purchase_orders SET status = 'Received' WHERE id = ?");
            $updatePO->execute([$po_id]);

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Items added to Inventory!']);

        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}
?>
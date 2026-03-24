<?php
// api/assets.php
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
    
    // Get all registered assets
    if ($action === 'get_assets') {
        try {
            $stmt = $pdo->query("SELECT * FROM assets ORDER BY id DESC");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Get items from inventory to populate the dropdown
    if ($action === 'get_inventory') {
        try {
            $stmt = $pdo->query("SELECT id, item_name, quantity FROM inventory WHERE quantity > 0");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// 2. REGISTER NEW ASSET
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input['action'] === 'register_asset') {
        try {
            $pdo->beginTransaction();

            // 1. Insert into Assets Table
            $stmt = $pdo->prepare("INSERT INTO assets (inventory_id, asset_name, serial_number, warranty_expiry, status) VALUES (?, ?, ?, ?, 'Active')");
            $stmt->execute([
                $input['inventory_id'] ? $input['inventory_id'] : null, 
                $input['asset_name'], 
                $input['serial_number'], 
                $input['warranty']
            ]);

            // 2. If it came from inventory, reduce the inventory count by 1 (since it's now an active asset in use)
            if (!empty($input['inventory_id'])) {
                $upd = $pdo->prepare("UPDATE inventory SET quantity = quantity - 1 WHERE id = ?");
                $upd->execute([$input['inventory_id']]);
            }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Asset Registered Successfully!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
?>
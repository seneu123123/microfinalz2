<?php
// api/storage.php
header('Content-Type: application/json');
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all storage zones and count how many items are in each
    if ($action === 'get_zones') {
        $sql = "SELECT sz.*, COUNT(i.id) as item_count 
                FROM storage_zones sz 
                LEFT JOIN inventory i ON sz.id = i.zone_id 
                GROUP BY sz.id";
        $stmt = $pdo->query($sql);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }

    // Get all inventory items to assign them to a zone
    if ($action === 'get_unassigned') {
        $stmt = $pdo->query("SELECT id, item_name, quantity FROM inventory");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input['action'] === 'assign_zone') {
        try {
            $stmt = $pdo->prepare("UPDATE inventory SET zone_id = ? WHERE id = ?");
            $stmt->execute([$input['zone_id'], $input['item_id']]);
            echo json_encode(['status' => 'success', 'message' => 'Storage location updated!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}
?>
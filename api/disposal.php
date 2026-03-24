<?php
// api/disposal.php
header('Content-Type: application/json');
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get assets that are 'Active' or 'In Maintenance' for disposal dropdown
    if ($action === 'get_disposable') {
        $stmt = $pdo->query("SELECT id, asset_name, serial_number FROM assets WHERE status IN ('Active', 'In Maintenance')");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }

    // Get disposal history
    if ($action === 'get_history') {
        $stmt = $pdo->query("SELECT id, asset_name, serial_number, status FROM assets WHERE status IN ('Retired', 'Lost') ORDER BY id DESC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input['action'] === 'dispose_asset') {
        try {
            $stmt = $pdo->prepare("UPDATE assets SET status = ? WHERE id = ?");
            $stmt->execute([$input['reason'], $input['asset_id']]);
            echo json_encode(['status' => 'success', 'message' => 'Asset officially decommissioned.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}
?>
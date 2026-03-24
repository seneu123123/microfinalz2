<?php
// api/monitoring.php
header('Content-Type: application/json');
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all assets and their latest monitoring status
    if ($action === 'get_status') {
        $sql = "SELECT a.id, a.asset_name, a.serial_number, am.health_percentage, am.usage_reading, am.last_check_date 
                FROM assets a 
                LEFT JOIN (
                    SELECT asset_id, health_percentage, usage_reading, last_check_date 
                    FROM asset_monitoring 
                    WHERE id IN (SELECT MAX(id) FROM asset_monitoring GROUP BY asset_id)
                ) am ON a.id = am.asset_id 
                WHERE a.status != 'Retired'";
        $stmt = $pdo->query($sql);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input['action'] === 'update_monitoring') {
        try {
            $stmt = $pdo->prepare("INSERT INTO asset_monitoring (asset_id, health_percentage, usage_reading, inspector) VALUES (?, ?, ?, ?)");
            $stmt->execute([$input['asset_id'], $input['health'], $input['reading'], $_SESSION['user_name'] ?? 'Admin']);
            echo json_encode(['status' => 'success', 'message' => 'Asset health updated!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}
?>
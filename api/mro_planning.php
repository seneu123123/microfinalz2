<?php
header('Content-Type: application/json');
session_start();
require 'db.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch schedule with asset names
    $stmt = $pdo->query("SELECT ms.*, a.asset_name FROM maintenance_schedule ms 
                         JOIN assets a ON ms.asset_id = a.id 
                         ORDER BY ms.next_due_date ASC");
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input['action'] === 'add_schedule') {
        try {
            $stmt = $pdo->prepare("INSERT INTO maintenance_schedule (asset_id, task_description, frequency_days, next_due_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$input['asset_id'], $input['task'], $input['days'], $input['due']]);
            echo json_encode(['status' => 'success', 'message' => 'Maintenance plan added!']);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    }
}
?>
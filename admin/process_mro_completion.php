<?php
// File: microfinance-main/admin/process_mro_completion.php

// 1. Include your database connection 
// (Change this path if your db connection file is named differently or in another folder)
require_once '../config/db.php'; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['work_order_id'])) {
    $work_order_id = intval($_POST['work_order_id']);

    try {
        // Start the transaction - if any update fails, none of them save.
        $pdo->beginTransaction();

        // Step A: Find the asset_id linked to this work order
        $stmtFind = $pdo->prepare("
            SELECT mr.asset_id 
            FROM work_orders wo 
            JOIN maintenance_requests mr ON wo.maintenance_request_id = mr.id 
            WHERE wo.id = :wo_id
        ");
        $stmtFind->execute([':wo_id' => $work_order_id]);
        $row = $stmtFind->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception("Could not find the vehicle associated with this Work Order.");
        }
        $asset_id = $row['asset_id'];

        // Step B: Update the Work Order to 'Completed' (MRO Module)
        $stmtMro = $pdo->prepare("UPDATE work_orders SET status = 'Completed', completed_at = NOW() WHERE id = :wo_id");
        $stmtMro->execute([':wo_id' => $work_order_id]);

        // Step C: Update the Vehicle's Last Service Date (Fleet Management Module)
        $stmtFleet = $pdo->prepare("UPDATE fleet_management SET last_service_date = CURDATE() WHERE asset_id = :asset_id");
        $stmtFleet->execute([':asset_id' => $asset_id]);

        // Step D: Change the Asset Status back to 'Active' (Asset Management Module)
        $stmtAsset = $pdo->prepare("UPDATE assets SET status = 'Active' WHERE id = :asset_id");
        $stmtAsset->execute([':asset_id' => $asset_id]);

        // Everything worked! Save the changes.
        $pdo->commit();
        
        echo json_encode(['status' => 'success', 'message' => 'MRO Job closed and Fleet Vehicle updated instantly.']);

    } catch (Exception $e) {
        // Something broke, undo all changes
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
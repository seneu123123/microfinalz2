<?php
header('Content-Type: application/json');
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Fetch project budget data
$sql = "SELECT p.project_name, b.allocated_budget, b.used_budget 
        FROM projects p 
        LEFT JOIN budget b ON p.id = b.project_id 
        WHERE b.allocated_budget IS NOT NULL";
$stmt = $pdo->query($sql);
echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
?>
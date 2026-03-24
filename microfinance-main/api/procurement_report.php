<?php
header('Content-Type: application/json');
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 1. Get Monthly Spending Trend
    if ($action === 'get_spend_trend') {
        $sql = "SELECT DATE_FORMAT(created_at, '%b %Y') as month, SUM(total_amount) as total 
                FROM purchase_orders 
                WHERE status != 'Cancelled'
                GROUP BY month 
                ORDER BY created_at ASC LIMIT 6";
        $stmt = $pdo->query($sql);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }

    // 2. Get Supplier Distribution (Who do we buy from most?)
    if ($action === 'get_supplier_stats') {
        $sql = "SELECT s.supplier_name, COUNT(po.id) as order_count, SUM(po.total_amount) as total_spend
                FROM purchase_orders po
                JOIN suppliers s ON po.supplier_id = s.id
                GROUP BY s.id";
        $stmt = $pdo->query($sql);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }
}
?>
<?php
header('Content-Type: application/json');
session_start();
require 'db.php';

// Fetch all inventory items - in a real system, you might filter by a 'Category' column
$stmt = $pdo->query("SELECT * FROM inventory ORDER BY item_name ASC");
echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
?>
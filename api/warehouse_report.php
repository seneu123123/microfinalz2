<?php
header('Content-Type: application/json');
session_start();
require 'db.php';

$sql = "SELECT sz.zone_name, COUNT(i.id) as item_count 
        FROM storage_zones sz 
        LEFT JOIN inventory i ON sz.id = i.zone_id 
        GROUP BY sz.id";
$stmt = $pdo->query($sql);
echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
?>
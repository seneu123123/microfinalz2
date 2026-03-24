<?php
// Simplified requisition API for debugging
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Debug response
echo json_encode([
    'status' => 'debug',
    'message' => 'API is reachable',
    'session' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'none',
    'method' => $_SERVER['REQUEST_METHOD'],
    'get_params' => $_GET,
    'post_params' => $_POST
]);
?>

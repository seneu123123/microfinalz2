<?php

/**

 * Database Configuration - Local XAMPP/phpMyAdmin

 */



// Database credentials

define('DB_HOST', 'localhost');

define('DB_USER', 'root');

define('DB_PASS', '');

define('DB_NAME', 'logistics_db');



// Enable mysqli extension if not loaded

if (!extension_loaded('mysqli')) {

    die('MySQLi extension is not loaded. Please enable it in php.ini');

}



// Create connection

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);



// Check connection

if ($conn->connect_error) {

    header('Content-Type: application/json');

    die(json_encode([

        'success' => false,

        'message' => 'Database connection failed: ' . $conn->connect_error

    ]));

}



// Set charset

$conn->set_charset("utf8mb4");



// PDO connection for APIs that use PDO

try {

    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    header('Content-Type: application/json');

    die(json_encode([

        'success' => false,

        'message' => 'PDO Database connection failed: ' . $e->getMessage()

    ]));

}



// Helper: Send JSON response

function sendResponse($success, $message = '', $data = null) {

    header('Content-Type: application/json');

    header('Access-Control-Allow-Origin: *');

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

    $response = [

        'success' => $success,

        'message' => $message

    ];

    if ($data !== null) {

        $response['data'] = $data;

    }

    echo json_encode($response);

    exit();

}



function sendError($message) {

    sendResponse(false, $message);

}



function sendSuccess($message, $data = null) {

    sendResponse(true, $message, $data);

}

?>


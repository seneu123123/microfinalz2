<?php
header('Content-Type: application/json');
require 'db.php'; // Ensure you have a db.php file for your PDO connection

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $input['email'];
    $username = $input['username'];
    $password = password_hash($input['password'], PASSWORD_DEFAULT); 
    
    // Generate a 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT); 

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, otp_code, status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->execute([$username, $email, $password, $otp]);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Account created! (Test OTP: ' . $otp . ')'
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Email or Username already exists.']);
    }
}
?>
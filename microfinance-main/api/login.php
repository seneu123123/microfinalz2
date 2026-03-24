<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$input['email']]);
$user = $stmt->fetch();

// 1. Verify the hashed password
if ($user && password_verify($input['password'], $user['password'])) {
    
    // 2. Check if the account needs OTP activation
    if ($user['status'] === 'Pending') {
        echo json_encode(['status' => 'otp_required']);
    } else {
        // Account is active, log them in
        $_SESSION['user_id'] = $user['id'];
        echo json_encode(['status' => 'success']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
}
?>
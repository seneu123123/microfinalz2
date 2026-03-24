<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);

// Look for the user with the matching email AND the exact OTP code
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND otp_code = ?");
$stmt->execute([$input['email'], $input['otp']]);
$user = $stmt->fetch();

if ($user) {
    // Correct OTP: Activate account and clear the code
    $activate = $pdo->prepare("UPDATE users SET status = 'Active', otp_code = NULL WHERE id = ?");
    $activate->execute([$user['id']]);
    
    // Log them in immediately after verification
    $_SESSION['user_id'] = $user['id'];
    
    echo json_encode(['status' => 'success', 'message' => 'Account activated successfully!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid OTP code. Please try again.']);
}
?>
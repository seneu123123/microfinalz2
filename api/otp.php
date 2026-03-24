<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/db.php';

date_default_timezone_set('Asia/Manila');

// Handle different OTP operations
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'send':
        handleSendOTP();
        break;
    case 'verify':
        handleVerifyOTP();
        break;
    case 'resend':
        handleResendOTP();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

function handleSendOTP() {
    global $conn;
    
    $email = $_POST['email'] ?? '';
    $userId = $_POST['user_id'] ?? null;
    
    if (empty($email)) {
        sendResponse(false, 'Email is required');
    }
    
    // Generate OTP
    $otp = generateOTP();
    
    // Store OTP in database
    $stored = storeOTP($userId, $email, $otp);
    
    if (!$stored) {
        sendResponse(false, 'Failed to store OTP');
    }
    
    // Send OTP email
    $emailSent = sendOtpEmail($email, $otp);
    
    if ($emailSent) {
        sendResponse(true, 'OTP sent successfully to your email', [
            'otp_expires_in' => '10 minutes'
        ]);
    } else {
        sendResponse(false, 'Failed to send OTP email');
    }
}

function handleVerifyOTP() {
    global $conn;
    
    $userId = $_POST['user_id'] ?? '';
    $otp = $_POST['otp'] ?? '';
    
    if (empty($userId) || empty($otp)) {
        sendResponse(false, 'User ID and OTP are required');
    }
    
    $isValid = verifyOTP($userId, $otp);
    
    if ($isValid) {
        sendResponse(true, 'OTP verified successfully');
    } else {
        sendResponse(false, 'Invalid or expired OTP');
    }
}

function handleResendOTP() {
    global $conn;
    
    $email = $_POST['email'] ?? '';
    $userId = $_POST['user_id'] ?? null;
    
    if (empty($email)) {
        sendResponse(false, 'Email is required');
    }
    
    // Check if there's an existing OTP that's less than 1 minute old
    $stmt = $conn->prepare("SELECT created_at FROM otp_codes WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE) ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        sendResponse(false, 'Please wait before requesting another OTP');
    }
    
    // Generate new OTP
    $otp = generateOTP();
    
    // Store OTP in database
    $stored = storeOTP($userId, $email, $otp);
    
    if (!$stored) {
        sendResponse(false, 'Failed to store OTP');
    }
    
    // Send OTP email
    $emailSent = sendOtpEmail($email, $otp);
    
    if ($emailSent) {
        sendResponse(true, 'OTP resent successfully', [
            'otp_expires_in' => '10 minutes'
        ]);
    } else {
        sendResponse(false, 'Failed to send OTP email');
    }
}
?>

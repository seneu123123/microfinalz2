<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/db.php';

date_default_timezone_set('Asia/Manila');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'send_otp':
        handleSendOTP();
        break;
    case 'verify_otp':
        handleVerifyOTP();
        break;
    case 'resend_otp':
        handleResendOTP();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

function handleLogin() {
    global $conn;
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        sendResponse(false, 'Email and password are required');
    }
    
    // Check user credentials
    $stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(false, 'Invalid email or password');
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password (assuming it's hashed, otherwise remove password_verify)
    if (!password_verify($password, $user['password'])) {
        sendResponse(false, 'Invalid email or password');
    }
    
    // Check user status
    if ($user['status'] !== 'active') {
        sendResponse(false, 'Account is not active');
    }
    
    // Generate and send OTP
    $otp = generateOTP();
    $stored = storeOTP($user['id'], $user['email'], $otp);
    
    if (!$stored) {
        sendResponse(false, 'Failed to generate OTP');
    }
    
    // Send OTP email
    $emailSent = sendOtpEmail($user['email'], $otp, $user['name']);
    
    if (!$emailSent) {
        sendResponse(false, 'Failed to send OTP email');
    }
    
    // Return user info (without password) for OTP verification
    unset($user['password']);
    
    sendResponse(true, 'Login successful. OTP sent to your email', [
        'user' => $user,
        'otp_expires_in' => '10 minutes'
    ]);
}

function handleSendOTP() {
    global $conn;
    
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        sendResponse(false, 'Email is required');
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(false, 'User not found');
    }
    
    $user = $result->fetch_assoc();
    
    // Generate and send OTP
    $otp = generateOTP();
    $stored = storeOTP($user['id'], $user['email'], $otp);
    
    if (!$stored) {
        sendResponse(false, 'Failed to generate OTP');
    }
    
    // Send OTP email
    $emailSent = sendOtpEmail($user['email'], $otp, $user['name']);
    
    if ($emailSent) {
        sendResponse(true, 'OTP sent successfully', [
            'user_id' => $user['id'],
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
        // Get user info for session
        $stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            // Determine redirect URL based on role
            $redirectUrl = 'admin/dashboard.php';
            if ($user['role'] === 'vendor_user') {
                $redirectUrl = 'vendor_user/dashboard_user.html';
            }
            
            sendResponse(true, 'OTP verified successfully', [
                'user' => $user,
                'redirect_url' => $redirectUrl
            ]);
        } else {
            sendResponse(false, 'User not found');
        }
    } else {
        sendResponse(false, 'Invalid or expired OTP');
    }
}

function handleResendOTP() {
    global $conn;
    
    $email = $_POST['email'] ?? '';
    
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
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(false, 'User not found');
    }
    
    $user = $result->fetch_assoc();
    
    // Generate new OTP
    $otp = generateOTP();
    
    // Store OTP in database
    $stored = storeOTP($user['id'], $user['email'], $otp);
    
    if (!$stored) {
        sendResponse(false, 'Failed to store OTP');
    }
    
    // Send OTP email
    $emailSent = sendOtpEmail($user['email'], $otp, $user['name']);
    
    if ($emailSent) {
        sendResponse(true, 'OTP resent successfully', [
            'user_id' => $user['id'],
            'otp_expires_in' => '10 minutes'
        ]);
    } else {
        sendResponse(false, 'Failed to send OTP email');
    }
}

function sendResponse($success, $message, $data = []) {
    $response = [
        'success' => $success,
        'message' => $message,
    ];
    if (!empty($data)) {
        $response['data'] = $data;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
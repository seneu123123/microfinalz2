<?php

date_default_timezone_set('Asia/Manila');

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

// Set database timezone
$conn->query("SET time_zone = '+08:00'");



// PDO connection for APIs that use PDO

try {

    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set PDO timezone
    $pdo->query("SET time_zone = '+08:00'");

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



// Email configuration for OTP

$mail_config = [

    'host' => 'smtp.gmail.com',

    'port' => 587,

    'smtp_secure' => 'tls',

    'smtp_auth' => true,

    'username' => 'suruiz.joshuabcp@gmail.com',

    'password' => 'aovb dqcb sqve rbsa',

    'from_email' => 'suruiz.joshuabcp@gmail.com',

    'from_name' => 'Microfinance System',

    'reply_to' => 'suruiz.joshuabcp@gmail.com',

];



// Generate 6-digit OTP

function generateOTP() {

    return sprintf("%06d", mt_rand(0, 999999));

}



// Send OTP Email

function sendOtpEmail($toEmail, $otp, $userName = '') {

    global $mail_config;

    

    require_once __DIR__ . '/../vendor/autoload.php';

    

    try {

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        

        $mail->isSMTP();

        $mail->Host = $mail_config['host'];

        $mail->SMTPAuth = $mail_config['smtp_auth'];

        $mail->Username = $mail_config['username'];

        $mail->Password = $mail_config['password'];

        $mail->SMTPSecure = $mail_config['smtp_secure'];

        $mail->Port = $mail_config['port'];

        

        $mail->setFrom($mail_config['from_email'], $mail_config['from_name']);

        $mail->addAddress($toEmail);

        $mail->addReplyTo($mail_config['reply_to']);

        

        $mail->isHTML(true);

        $mail->Subject = 'Microfinance Login OTP Code';

        

        $emailBody = "

        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4;'>

            <div style='background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>

                <div style='text-align: center; margin-bottom: 30px;'>

                    <h2 style='color: #2ca078; margin: 0;'>Microfinance System</h2>

                    <p style='color: #666; margin: 5px 0 0 0;'>Secure Login Verification</p>

                </div>

                

                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;'>

                    <h3 style='color: #333; margin: 0 0 10px 0;'>Your OTP Code</h3>

                    <div style='font-size: 32px; font-weight: bold; color: #2ca078; letter-spacing: 5px; margin: 15px 0;'>

                        $otp

                    </div>

                    <p style='color: #666; margin: 10px 0 0 0; font-size: 14px;'>This code will expire in 10 minutes</p>

                </div>

                

                <div style='margin: 30px 0;'>

                    <h4 style='color: #333; margin: 0 0 10px 0;'>Instructions:</h4>

                    <ol style='color: #666; margin: 0; padding-left: 20px;'>

                        <li>Enter the 6-digit code above in the login verification page</li>

                        <li>Do not share this code with anyone</li>

                        <li>If you didn't request this code, please ignore this email</li>

                    </ol>

                </div>

                

                <div style='border-top: 1px solid #eee; padding-top: 20px; margin-top: 30px; text-align: center;'>

                    <p style='color: #999; font-size: 12px; margin: 0;'>

                        This is an automated message from Microfinance System.<br>

                        Please do not reply to this email.

                    </p>

                </div>

            </div>

        </div>";

        

        $mail->Body = $emailBody;

        $mail->AltBody = "Your OTP code is: $otp\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this code, please ignore this email.";

        

        $mail->send();

        return true;

        

    } catch (Exception $e) {

        error_log("PHPMailer Error: " . $e->getMessage());

        return false;

    }

}



// Store OTP in database

function storeOTP($userId, $email, $otp) {

    global $conn;

    

    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    

    // Delete any existing OTPs for this user

    $stmt = $conn->prepare("DELETE FROM otp_codes WHERE user_id = ? OR email = ?");

    $stmt->bind_param("is", $userId, $email);

    $stmt->execute();

    $stmt->close();

    

    // Insert new OTP

    $stmt = $conn->prepare("INSERT INTO otp_codes (user_id, email, otp_code, expires_at, created_at) VALUES (?, ?, ?, ?, NOW())");

    $stmt->bind_param("isss", $userId, $email, $otp, $expiresAt);

    $result = $stmt->execute();

    $stmt->close();

    

    return $result;

}



// Verify OTP

function verifyOTP($userId, $otp) {

    global $conn;

    

    $stmt = $conn->prepare("SELECT id FROM otp_codes WHERE user_id = ? AND otp_code = ? AND expires_at > NOW() AND is_used = 0 ORDER BY created_at DESC LIMIT 1");

    $stmt->bind_param("is", $userId, $otp);

    $stmt->execute();

    $result = $stmt->get_result();

    

    if ($result->num_rows > 0) {

        // Mark OTP as used

        $updateStmt = $conn->prepare("UPDATE otp_codes SET is_used = 1, used_at = NOW() WHERE user_id = ? AND otp_code = ?");

        $updateStmt->bind_param("is", $userId, $otp);

        $updateStmt->execute();

        $updateStmt->close();

        

        $stmt->close();

        return true;

    }

    

    $stmt->close();

    return false;

}

?>


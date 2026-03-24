<?php
require_once 'config/db.php';

echo "Checking timezone and time...\n";
echo "Current timezone: " . date_default_timezone_get() . "\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n";
echo "Current time +10 minutes: " . date('Y-m-d H:i:s', strtotime('+10 minutes')) . "\n\n";

// Test the exact query used in verifyOTP
$testUserId = 1;
$testOtp = '479242';

echo "Testing verification query...\n";
$stmt = $conn->prepare("SELECT id, expires_at, created_at FROM otp_codes WHERE user_id = ? AND otp_code = ? AND expires_at > NOW() AND is_used = 0 ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("is", $testUserId, $testOtp);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Found OTP record:\n";
    echo "ID: {$row['id']}, Expires: {$row['expires_at']}, Created: {$row['created_at']}\n";
    echo "Current time: " . date('Y-m-d H:i:s') . "\n";
    echo "Is expired? " . (strtotime($row['expires_at']) < time() ? "Yes" : "No") . "\n";
} else {
    echo "No matching OTP found\n";
    
    // Let's see what's actually in the table
    echo "\nAll recent OTPs:\n";
    $allResult = $conn->query("SELECT user_id, otp_code, expires_at, is_used FROM otp_codes ORDER BY created_at DESC LIMIT 3");
    while ($row = $allResult->fetch_assoc()) {
        echo "User: {$row['user_id']}, OTP: {$row['otp_code']}, Expires: {$row['expires_at']}, Used: {$row['is_used']}\n";
    }
}

$stmt->close();
$conn->close();
?>

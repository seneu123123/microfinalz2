<?php
require_once 'config/db.php';

echo "Debugging OTP verification...\n\n";

// Check if table exists and has data
echo "Checking otp_codes table...\n";
$result = $conn->query("SELECT * FROM otp_codes ORDER BY created_at DESC LIMIT 5");

if ($result->num_rows > 0) {
    echo "Found OTP records:\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, User ID: {$row['user_id']}, Email: {$row['email']}, OTP: {$row['otp_code']}, Expires: {$row['expires_at']}, Used: {$row['is_used']}\n";
    }
} else {
    echo "No OTP records found\n";
}

echo "\nTesting verification with a fresh OTP...\n";
$testEmail = 'test@example.com';
$testUserId = 1;
$otp = generateOTP();
echo "Generated OTP: $otp\n";

$stored = storeOTP($testUserId, $testEmail, $otp);
echo "Stored: " . ($stored ? "Yes" : "No") . "\n";

$verified = verifyOTP($testUserId, $otp);
echo "Verified: " . ($verified ? "Yes" : "No") . "\n";

$conn->close();
?>

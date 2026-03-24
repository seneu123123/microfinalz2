<?php
require_once 'config/db.php';

echo "Testing OTP System...\n\n";

// Test 1: Generate OTP
echo "1. Generating OTP...\n";
$otp = generateOTP();
echo "Generated OTP: $otp\n\n";

// Test 2: Store OTP (using test email and user ID)
echo "2. Storing OTP in database...\n";
$testEmail = 'test@example.com';
$testUserId = 1;

$stored = storeOTP($testUserId, $testEmail, $otp);
if ($stored) {
    echo "OTP stored successfully\n";
} else {
    echo "Failed to store OTP\n";
}
echo "\n";

// Test 3: Verify OTP
echo "3. Verifying OTP...\n";
$verified = verifyOTP($testUserId, $otp);
if ($verified) {
    echo "OTP verified successfully\n";
} else {
    echo "OTP verification failed\n";
}
echo "\n";

// Test 4: Test with wrong OTP
echo "4. Testing with wrong OTP...\n";
$wrongOtp = '999999';
$verifiedWrong = verifyOTP($testUserId, $wrongOtp);
if ($verifiedWrong) {
    echo "ERROR: Wrong OTP was verified!\n";
} else {
    echo "Correctly rejected wrong OTP\n";
}
echo "\n";

// Test 5: Test email sending (optional)
echo "5. Testing email sending...\n";
echo "To test email sending, use the web interface at: test_otp.html\n";
echo "Enter your email address to receive an OTP\n\n";

echo "OTP System setup complete!\n";
echo "Database: hr4\n";
echo "Table: otp_codes\n";
echo "Test Interface: test_otp.html\n";
echo "API Endpoint: api/otp.php\n";
?>

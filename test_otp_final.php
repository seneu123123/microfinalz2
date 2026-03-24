<?php
require_once 'config/db.php';

echo "Testing OTP system...\n";
$otp = generateOTP();
echo "Generated OTP: " . $otp . "\n";
$stored = storeOTP(18, 'test@example.com', $otp);
echo "Stored: " . ($stored ? 'Yes' : 'No') . "\n";
$verified = verifyOTP(18, $otp);
echo "Verified: " . ($verified ? 'Yes' : 'No') . "\n";

// Test email sending
echo "\nTesting email to test@example.com...\n";
$emailSent = sendOtpEmail('test@example.com', $otp, 'Test User');
echo "Email sent: " . ($emailSent ? 'Yes' : 'No') . "\n";

echo "\nOTP system test complete!\n";
?>

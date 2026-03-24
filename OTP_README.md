# OTP System Setup

## Overview
A complete OTP (One-Time Password) system for the Microfinance application with email verification capabilities.

## Features
- Generate 6-digit OTP codes
- Email OTP delivery using PHPMailer and Gmail SMTP
- OTP storage in database with expiration (10 minutes)
- OTP verification with automatic cleanup
- Resend functionality with rate limiting (1 minute cooldown)
- Timezone synchronized (Asia/Manila)

## Files Created/Modified

### Database Configuration
- `config/db.php` - Updated with OTP functions and email configuration

### Database Table
- `otp_codes` table created in `hr4` database

### API Endpoints
- `api/otp.php` - Main OTP API with actions: send, verify, resend

### Test Files
- `test_otp.html` - Web interface for testing OTP functionality
- `test_otp_system.php` - PHP script to test OTP functions
- `setup_otp_table.php` - Script to create OTP database table

### Dependencies
- `composer.json` - PHPMailer dependency
- `vendor/` - PHPMailer library (installed via composer)

## API Usage

### Send OTP
```
POST /api/otp.php?action=send
Content-Type: application/x-www-form-urlencoded

email=user@example.com&user_id=123
```

### Verify OTP
```
POST /api/otp.php?action=verify
Content-Type: application/x-www-form-urlencoded

user_id=123&otp=123456
```

### Resend OTP
```
POST /api/otp.php?action=resend
Content-Type: application/x-www-form-urlencoded

email=user@example.com&user_id=123
```

## Database Schema

```sql
CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used_at` datetime DEFAULT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_otp_code` (`otp_code`),
  KEY `idx_expires_at` (`expires_at`)
);
```

## Configuration

### Email Settings (config/db.php)
```php
$mail_config = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'smtp_secure' => 'tls',
    'smtp_auth' => true,
    'username' => 'your-email@gmail.com',
    'password' => 'your-app-password',
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'Microfinance System',
    'reply_to' => 'your-email@gmail.com',
];
```

### Database Settings
- Database: `hr4`
- Connection: MySQL via mysqli and PDO
- Timezone: Asia/Manila (+08:00)

## Security Features
- OTP codes expire after 10 minutes
- OTPs are marked as used after successful verification
- Rate limiting on resend (1 minute cooldown)
- Automatic cleanup of expired OTPs
- Secure password storage (use app passwords for Gmail)

## Testing
1. Open `test_otp.html` in your browser
2. Enter your email address
3. Click "Send OTP"
4. Check your email for the 6-digit code
5. Enter the code in the verification form
6. Click "Verify OTP"

## Installation Steps
1. Database is already configured (hr4)
2. OTP table is created automatically
3. PHPMailer is installed via composer
4. All files are in place and ready to use

## Notes
- Gmail requires "App Passwords" for SMTP authentication
- Make sure PHPMailer is properly installed in vendor directory
- The system uses Asia/Manila timezone throughout
- OTPs are 6 digits and valid for 10 minutes

<?php
// api/reset_password.php
require 'db.php';

$email = 'admin@lemon.com';
$new_password = 'password123';

// 1. Generate a secure hash using your fresh server
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    // 2. Check if user exists, if not, create them. If yes, update password.
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        // User exists, update password
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update->execute([$hashed_password, $email]);
        echo "<h1>Success!</h1><p>Password updated for <b>$email</b> to: <b>$new_password</b></p>";
    } else {
        // User doesn't exist, insert them
        $insert = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES ('Admin User', ?, ?, 'Admin')");
        $insert->execute([$email, $hashed_password]);
        echo "<h1>Success!</h1><p>Admin user created with email <b>$email</b> and password <b>$new_password</b></p>";
    }
    
    echo "<br><a href='../login.php'>Go to Login Page</a>";

} catch (PDOException $e) {
    echo "<h1>Database Error</h1><p>" . $e->getMessage() . "</p>";
    echo "<p>Did you remember to run the Master SQL code in phpMyAdmin to create the tables?</p>";
}
?>

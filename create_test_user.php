<?php
require_once 'config/db.php';

echo "Creating test user for OTP testing...\n";

// Check if test user already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$email = 'test@example.com';
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Test user already exists.\n";
} else {
    // Create test user with hashed password
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $name = 'Test User';
    $role = 'admin';
    $status = 'active';
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("sssss", $name, $email, $password, $role, $status);
    
    if ($stmt->execute()) {
        echo "Test user created successfully.\n";
        echo "Email: test@example.com\n";
        echo "Password: password123\n";
    } else {
        echo "Error creating test user: " . $conn->error . "\n";
    }
}

// Show all users
echo "\nCurrent users in database:\n";
$result = $conn->query("SELECT id, name, email, role, status FROM users");
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Name: {$row['name']}, Email: {$row['email']}, Role: {$row['role']}, Status: {$row['status']}\n";
}

$conn->close();
?>

<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'get' && isset($_GET['id'])) {
        getUser($_GET['id']);
    } else {
        listUsers();
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'login':
            login($input);
            break;
        case 'register':
            register($input);
            break;
        case 'logout':
            logout($input);
            break;
        case 'update':
            updateUser($input);
            break;
        case 'delete':
            deleteUser($input);
            break;
        default:
            sendResponse(false, 'Invalid action');
    }
} else {
    sendResponse(false, 'Method not allowed');
}

function listUsers() {
    global $conn;
    
    try {
        $query = "SELECT user_id, username, email, full_name, role, department, position, is_active, created_at 
                  FROM users 
                  WHERE is_active = 1 
                  ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            unset($row['password_hash']); // Don't send password hash
            $row['status'] = $row['is_active'] ? 'active' : 'inactive'; // Convert to string
            unset($row['is_active']);
            $users[] = $row;
        }
        
        $stmt->close();
        sendResponse(true, 'Users retrieved', $users);
    } catch (Exception $e) {
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function getUser($userId) {
    global $conn;
    
    try {
        $query = "SELECT user_id, username, email, full_name, role, department, position, is_active, created_at 
                  FROM users 
                  WHERE user_id = ? AND is_active = 1";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $user = $result->fetch_assoc();
        
        $stmt->close();
        
        if ($user) {
            unset($user['password_hash']);
            $user['status'] = $user['is_active'] ? 'active' : 'inactive';
            unset($user['is_active']);
            sendResponse(true, 'User retrieved', $user);
        } else {
            sendResponse(false, 'User not found');
        }
    } catch (Exception $e) {
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function login($data) {
    global $conn;
    
    try {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            sendResponse(false, 'Username and password are required');
        }
        
        $query = "SELECT user_id, username, email, password_hash, full_name, role, department, position, is_active 
                  FROM users 
                  WHERE (username = ? OR email = ?) AND is_active = 1";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!$user) {
            sendResponse(false, 'Invalid username or password');
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            sendResponse(false, 'Invalid username or password');
        }
        
        // Update last login
        $updateQuery = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('s', $user['user_id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Create session
        $sessionId = session_create_id();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $sessionQuery = "INSERT INTO user_sessions (session_id, user_id, ip_address, user_agent, expires_at) 
                       VALUES (?, ?, ?, ?, ?)";
        $sessionStmt = $conn->prepare($sessionQuery);
        $sessionStmt->bind_param('sssss', 
            $sessionId, 
            $user['user_id'], 
            $ipAddress,
            $userAgent,
            $expiresAt
        );
        $sessionStmt->execute();
        $sessionStmt->close();
        
        // Prepare user data for response
        unset($user['password_hash']);
        $user['status'] = $user['is_active'] ? 'active' : 'inactive';
        unset($user['is_active']);
        $user['session_id'] = $sessionId;
        
        sendResponse(true, 'Login successful', $user);
    } catch (Exception $e) {
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function register($data) {
    global $conn;
    
    try {
        $username = $data['username'] ?? $data['email'] ?? ''; // Allow email as username
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $fullName = $data['full_name'] ?? '';
        $role = $data['role'] ?? 'user';
        $department = $data['department'] ?? '';
        $position = $data['position'] ?? '';
        
        // Validate required fields
        if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
            sendResponse(false, 'Username, email, password, and full name are required');
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendResponse(false, 'Invalid email format');
        }
        
        // Check if username exists
        $checkQuery = "SELECT user_id FROM users WHERE username = ? OR email = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param('ss', $username, $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            sendResponse(false, 'Username or email already exists');
        }
        $checkStmt->close();
        
        // Generate user ID and hash password
        $userId = 'USR-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $insertQuery = "INSERT INTO users (user_id, username, email, password_hash, full_name, role, department, position) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('ssssssss', 
            $userId, $username, $email, $passwordHash, $fullName, $role, $department, $position
        );
        
        if ($stmt->execute()) {
            $stmt->close();
            sendResponse(true, 'Registration successful', ['user_id' => $userId]);
        } else {
            sendResponse(false, 'Registration failed: ' . $stmt->error);
        }
    } catch (Exception $e) {
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function logout($data) {
    global $conn;
    
    try {
        $sessionId = $data['session_id'] ?? '';
        
        if (empty($sessionId)) {
            sendResponse(false, 'Session ID is required');
        }
        
        $query = "DELETE FROM user_sessions WHERE session_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $sessionId);
        
        if ($stmt->execute()) {
            sendResponse(true, 'Logout successful');
        } else {
            sendResponse(false, 'Logout failed');
        }
        
        $stmt->close();
    } catch (Exception $e) {
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function updateUser($data) {
    global $conn;
    
    try {
        $userId = $data['user_id'] ?? '';
        $fullName = $data['full_name'] ?? '';
        $department = $data['department'] ?? '';
        $position = $data['position'] ?? '';
        
        if (empty($userId)) {
            sendResponse(false, 'User ID is required');
        }
        
        $query = "UPDATE users SET full_name = ?, department = ?, position = ?, updated_at = NOW() 
                  WHERE user_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssss', $fullName, $department, $position, $userId);
        
        if ($stmt->execute()) {
            sendResponse(true, 'User updated successfully');
        } else {
            sendResponse(false, 'Update failed: ' . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function deleteUser($data) {
    global $conn;
    
    try {
        $userId = $data['user_id'] ?? '';
        
        if (empty($userId)) {
            sendResponse(false, 'User ID is required');
        }
        
        // Soft delete by updating status
        $query = "UPDATE users SET status = 'inactive', updated_at = NOW() WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $userId);
        
        if ($stmt->execute()) {
            sendResponse(true, 'User deleted successfully');
        } else {
            sendResponse(false, 'Delete failed: ' . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}
?>

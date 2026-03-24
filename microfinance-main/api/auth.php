<?php
/**
 * Authentication API with RBAC
 * Handles login, registration, and role-based access control
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Database configuration
$host = 'localhost';
$dbname = 'logistics_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    sendError('Database connection failed: ' . $e->getMessage());
}

// Create users table if not exists
$createUsersTable = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'vendor_user') NOT NULL DEFAULT 'vendor_user',
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$pdo->exec($createUsersTable);

// Create sessions table if not exists
$createSessionsTable = "
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

$pdo->exec($createSessionsTable);

// Helper functions
function sendError($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function sendSuccess($data = null, $message = 'Success') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateSessionToken() {
    return bin2hex(random_bytes(32));
}

function detectRoleFromEmail($email) {
    // Automatic role detection based on email domain
    $domain = strtolower(substr(strrchr($email, "@"), 1));
    
    // Admin domains - these will get admin role
    $adminDomains = ['admin.com', 'microfinance.com', 'logistics.com', 'company.admin'];
    
    // Vendor user domains - these will get vendor_user role
    $vendorDomains = ['vendor.com', 'supplier.com', 'partner.com'];
    
    if (in_array($domain, $adminDomains)) {
        return 'admin';
    } elseif (in_array($domain, $vendorDomains)) {
        return 'vendor_user';
    } else {
        // Default role for unknown domains
        return 'vendor_user';
    }
}

function validateSession($token) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, s.expires_at 
            FROM user_sessions s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.session_token = ? AND s.expires_at > NOW() AND u.status = 'active'
        ");
        $stmt->execute([$token]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($session) {
            return $session;
        }
        return false;
    } catch(PDOException $e) {
        return false;
    }
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'login':
        if ($method !== 'POST') {
            sendError('Method not allowed', 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendError('Invalid JSON input');
        }
        
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            sendError('Email and password are required');
        }
        
        try {
            // Check user credentials
            $stmt = $pdo->prepare("
                SELECT id, name, email, password, role, status 
                FROM users 
                WHERE email = ? AND status = 'active'
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !verifyPassword($password, $user['password'])) {
                sendError('Invalid email or password');
            }
            
            // Generate session token
            $sessionToken = generateSessionToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Clean old sessions
            $pdo->exec("DELETE FROM user_sessions WHERE expires_at < NOW()");
            
            // Create new session
            $stmt = $pdo->prepare("
                INSERT INTO user_sessions (user_id, session_token, expires_at) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user['id'], $sessionToken, $expiresAt]);
            
            // Return user data with session token
            sendSuccess([
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ],
                'session_token' => $sessionToken,
                'redirect' => $user['role'] === 'admin' ? 'admin/dashboard.html' : 'vendor_user/dashboard_user.html'
            ], 'Login successful');
            
        } catch(PDOException $e) {
            sendError('Login failed: ' . $e->getMessage());
        }
        break;
        
    case 'register':
        if ($method !== 'POST') {
            sendError('Method not allowed', 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendError('Invalid JSON input');
        }
        
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            sendError('Name, email, and password are required');
        }
        
        if (strlen($password) < 8) {
            sendError('Password must be at least 8 characters long');
        }
        
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                sendError('Email already exists');
            }
            
            // Auto-detect role from email
            $detectedRole = detectRoleFromEmail($email);
            
            // Create new user with auto-detected role
            $hashedPassword = hashPassword($password);
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$name, $email, $hashedPassword, $detectedRole]);
            
            sendSuccess([
                'role' => $detectedRole,
                'message' => "Your account has been created with role: $detectedRole. Please wait for admin approval."
            ], 'Registration successful');
            
        } catch(PDOException $e) {
            sendError('Registration failed: ' . $e->getMessage());
        }
        break;
        
    case 'validate':
        if ($method !== 'GET') {
            sendError('Method not allowed', 405);
        }
        
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            sendError('Session token is required');
        }
        
        $session = validateSession($token);
        
        if ($session) {
            sendSuccess([
                'user' => [
                    'id' => $session['id'],
                    'name' => $session['name'],
                    'email' => $session['email'],
                    'role' => $session['role']
                ]
            ], 'Session valid');
        } else {
            sendError('Invalid or expired session', 401);
        }
        break;
        
    case 'logout':
        if ($method !== 'POST') {
            sendError('Method not allowed', 405);
        }
        
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            sendError('Session token is required');
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->execute([$token]);
            
            sendSuccess(null, 'Logged out successfully');
        } catch(PDOException $e) {
            sendError('Logout failed: ' . $e->getMessage());
        }
        break;
        
    case 'create_admin':
        if ($method !== 'POST') {
            sendError('Method not allowed', 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            sendError('Invalid JSON input');
        }
        
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $adminKey = $input['admin_key'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            sendError('Name, email, and password are required');
        }
        
        if ($adminKey !== 'ADMIN_CREATE_KEY_2024') {
            sendError('Invalid admin creation key');
        }
        
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                sendError('Email already exists');
            }
            
            // Create admin user
            $hashedPassword = hashPassword($password);
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, status) 
                VALUES (?, ?, ?, 'admin', 'active')
            ");
            $stmt->execute([$name, $email, $hashedPassword]);
            
            sendSuccess(null, 'Admin user created successfully');
            
        } catch(PDOException $e) {
            sendError('Admin creation failed: ' . $e->getMessage());
        }
        break;
        
    default:
        sendError('Action not found', 404);
}
?>

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'get_vendors') {
    try {
        // Database connection
        $conn = new mysqli('localhost', 'root', '', 'logistics_db');
        
        if ($conn->connect_error) {
            echo json_encode(['status' => 'error', 'message' => 'DB connection failed']);
            exit;
        }
        
        // Create table if not exists
        $conn->query("CREATE TABLE IF NOT EXISTS vendors (
            vendor_id VARCHAR(50) PRIMARY KEY,
            vendor_name VARCHAR(255) NOT NULL,
            contact_person VARCHAR(255),
            email VARCHAR(255),
            contact_number VARCHAR(50),
            address TEXT,
            business_type VARCHAR(255),
            business_details TEXT,
            status ENUM('Pending', 'Approved', 'Rejected', 'Active') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Get vendors
        $result = $conn->query("SELECT vendor_id as id, vendor_name as company_name, contact_person, email, contact_number as phone, business_type, business_details, status FROM vendors ORDER BY created_at DESC");
        
        $vendors = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $vendors[] = $row;
            }
        }
        
        echo json_encode(['status' => 'success', 'data' => $vendors]);
        $conn->close();
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    try {
        // Get POST data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || $data['action'] !== 'add_vendor') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            exit;
        }
        
        // Validate required fields
        $required = ['company_name', 'contact_person', 'email', 'phone', 'address'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                echo json_encode(['status' => 'error', 'message' => "Missing: $field"]);
                exit;
            }
        }
        
        // Database connection
        $conn = new mysqli('localhost', 'root', '', 'logistics_db');
        
        if ($conn->connect_error) {
            echo json_encode(['status' => 'error', 'message' => 'DB connection failed']);
            exit;
        }
        
        // Create table if not exists
        $conn->query("CREATE TABLE IF NOT EXISTS vendors (
            vendor_id VARCHAR(50) PRIMARY KEY,
            vendor_name VARCHAR(255) NOT NULL,
            contact_person VARCHAR(255),
            email VARCHAR(255),
            contact_number VARCHAR(50),
            address TEXT,
            status ENUM('Pending', 'Approved', 'Rejected', 'Active') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Check duplicate email
        $stmt = $conn->prepare("SELECT vendor_id FROM vendors WHERE email = ?");
        $stmt->bind_param("s", $data['email']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
            exit;
        }
        
        // Generate vendor ID
        $result = $conn->query("SELECT MAX(CAST(SUBSTRING(vendor_id, 5) AS UNSIGNED)) as max_id FROM vendors");
        $row = $result->fetch_assoc();
        $nextId = ($row['max_id'] ?? 0) + 1;
        $vendorId = 'VND-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
        
        // Insert vendor
        $sql = "INSERT INTO vendors (vendor_id, vendor_name, contact_person, email, contact_number, address, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'Active')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $vendorId, $data['company_name'], $data['contact_person'], $data['email'], $data['phone'], $data['address']);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Vendor added successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add vendor']);
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>

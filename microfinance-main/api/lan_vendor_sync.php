<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Database connection
$conn = new mysqli("localhost", "root", "", "microfinance_db");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit();
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'sync_to_remote':
        syncToRemote($conn);
        break;
    case 'receive_from_remote':
        receiveFromRemote($conn);
        break;
    case 'send_vendor_to_procurement':
        sendVendorToProcurement($conn);
        break;
    case 'receive_supplier_approval':
        receiveSupplierApproval($conn);
        break;
    case 'check_remote_status':
        checkRemoteStatus($conn);
        break;
    default:
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
}

/**
 * Sync vendor data to remote system (192.168.1.16)
 */
function syncToRemote($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $vendor_id = $data['vendor_id'] ?? '';
    $remote_ip = $data['remote_ip'] ?? '192.168.1.16';
    
    if (empty($vendor_id)) {
        echo json_encode(["status" => "error", "message" => "Vendor ID is required"]);
        return;
    }
    
    // Get vendor details
    $sql = "SELECT * FROM vendors WHERE vendor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Vendor not found"]);
        return;
    }
    
    $vendor = $result->fetch_assoc();
    
    // Prepare data for remote system
    $remote_data = [
        'action' => 'receive_vendor',
        'vendor_id' => $vendor['vendor_id'],
        'vendor_name' => $vendor['vendor_name'],
        'business_type' => $vendor['business_type'],
        'contact_number' => $vendor['contact_number'],
        'email' => $vendor['email'],
        'address' => $vendor['address'],
        'registration_no' => $vendor['registration_no'],
        'tin' => $vendor['tin'],
        'contact_person' => $vendor['contact_person'],
        'bank_name' => $vendor['bank_name'],
        'bank_account' => $vendor['bank_account'],
        'status' => $vendor['status'],
        'source_ip' => '192.168.1.9'
    ];
    
    // Send to remote system
    $remote_url = "http://$remote_ip/api/receive_vendor.php";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $remote_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($remote_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo json_encode([
            "status" => "error", 
            "message" => "Failed to connect to remote system: " . $error
        ]);
        return;
    }
    
    if ($http_code !== 200) {
        echo json_encode([
            "status" => "error", 
            "message" => "Remote system returned HTTP $http_code"
        ]);
        return;
    }
    
    $result = json_decode($response, true);
    
    if ($result['status'] === 'success') {
        // Update local vendor status
        $update_sql = "UPDATE vendors SET status = 'Sent to Procurement' WHERE vendor_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("s", $vendor_id);
        $update_stmt->execute();
        
        echo json_encode([
            "status" => "success",
            "message" => "Vendor successfully sent to procurement",
            "remote_response" => $result
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Remote system error: " . $result['message']
        ]);
    }
}

/**
 * Receive vendor data from remote system
 */
function receiveFromRemote($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $vendor_id = $data['vendor_id'] ?? '';
    $vendor_name = $data['vendor_name'] ?? '';
    $business_type = $data['business_type'] ?? '';
    $contact_number = $data['contact_number'] ?? '';
    $email = $data['email'] ?? '';
    $address = $data['address'] ?? '';
    $registration_no = $data['registration_no'] ?? '';
    $tin = $data['tin'] ?? '';
    $contact_person = $data['contact_person'] ?? '';
    $bank_name = $data['bank_name'] ?? '';
    $bank_account = $data['bank_account'] ?? '';
    $source_ip = $data['source_ip'] ?? '';
    
    if (empty($vendor_id) || empty($vendor_name)) {
        echo json_encode(["status" => "error", "message" => "Vendor ID and name are required"]);
        return;
    }
    
    // Check if vendor already exists
    $check_sql = "SELECT vendor_id FROM vendors WHERE vendor_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $vendor_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing vendor
        $update_sql = "UPDATE vendors SET 
                        vendor_name = ?, business_type = ?, contact_number = ?, 
                        email = ?, address = ?, registration_no = ?, tin = ?, 
                        contact_person = ?, bank_name = ?, bank_account = ?, 
                        status = 'Received from Remote', updated_at = NOW()
                        WHERE vendor_id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssssssssss", 
            $vendor_name, $business_type, $contact_number, $email, $address, 
            $registration_no, $tin, $contact_person, $bank_name, $bank_account, $vendor_id
        );
        
        if ($update_stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => "Vendor updated successfully from remote system"
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update vendor"]);
        }
    } else {
        // Insert new vendor
        $insert_sql = "INSERT INTO vendors (vendor_id, vendor_name, business_type, contact_number, 
                        email, address, registration_no, tin, contact_person, bank_name, 
                        bank_account, status, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Received from Remote', NOW(), NOW())";
        
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sssssssssss", 
            $vendor_id, $vendor_name, $business_type, $contact_number, $email, 
            $address, $registration_no, $tin, $contact_person, $bank_name, $bank_account
        );
        
        if ($insert_stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => "Vendor received successfully from remote system"
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to insert vendor"]);
        }
    }
}

/**
 * Send vendor to local procurement system
 */
function sendVendorToProcurement($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $vendor_id = $data['vendor_id'] ?? '';
    
    if (empty($vendor_id)) {
        echo json_encode(["status" => "error", "message" => "Vendor ID is required"]);
        return;
    }
    
    // Get vendor details
    $sql = "SELECT * FROM vendors WHERE vendor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Vendor not found"]);
        return;
    }
    
    $vendor = $result->fetch_assoc();
    
    // Insert into suppliers table
    $supplier_sql = "INSERT INTO suppliers (company_name, contact_person, email, phone, address, description, status) 
                     VALUES (?, ?, ?, ?, ?, ?, 'Active')";
    
    $supplier_stmt = $conn->prepare($supplier_sql);
    $description = "From vendor: " . $vendor['vendor_id'] . " - " . $vendor['business_type'];
    $supplier_stmt->bind_param("ssssss", 
        $vendor['vendor_name'], 
        $vendor['contact_person'], 
        $vendor['email'], 
        $vendor['contact_number'], 
        $vendor['address'], 
        $description
    );
    
    if ($supplier_stmt->execute()) {
        $supplier_id = $conn->insert_id;
        
        // Update vendor status
        $update_sql = "UPDATE vendors SET status = 'Approved - Moved to Suppliers' WHERE vendor_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("s", $vendor_id);
        $update_stmt->execute();
        
        echo json_encode([
            "status" => "success",
            "message" => "Vendor approved and added to suppliers",
            "supplier_id" => $supplier_id,
            "vendor_id" => $vendor_id
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to add supplier"]);
    }
}

/**
 * Receive supplier approval from remote system
 */
function receiveSupplierApproval($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $vendor_id = $data['vendor_id'] ?? '';
    $supplier_id = $data['supplier_id'] ?? '';
    $status = $data['status'] ?? '';
    $message = $data['message'] ?? '';
    
    if (empty($vendor_id) || empty($supplier_id)) {
        echo json_encode(["status" => "error", "message" => "Vendor ID and Supplier ID are required"]);
        return;
    }
    
    // Update vendor status
    $update_sql = "UPDATE vendors SET status = ? WHERE vendor_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $vendor_status = "Supplier Status: " . $status . " - " . $message;
    $update_stmt->bind_param("ss", $vendor_status, $vendor_id);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Vendor status updated with supplier approval"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update vendor status"]);
    }
}

/**
 * Check remote system status
 */
function checkRemoteStatus($conn) {
    $remote_ip = $_GET['remote_ip'] ?? '192.168.1.16';
    
    $remote_url = "http://$remote_ip/api/status.php";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $remote_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo json_encode([
            "status" => "error",
            "message" => "Cannot connect to remote system",
            "remote_ip" => $remote_ip,
            "error" => $error
        ]);
        return;
    }
    
    echo json_encode([
        "status" => "success",
        "message" => "Remote system is accessible",
        "remote_ip" => $remote_ip,
        "http_code" => $http_code,
        "response" => json_decode($response, true)
    ]);
}

$conn->close();
?>

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
    case 'send_vendor_to_procurement':
        sendVendorToProcurement($conn);
        break;
    case 'get_vendors_for_procurement':
        getVendorsForProcurement($conn);
        break;
    case 'approve_vendor_to_supplier':
        approveVendorToSupplier($conn);
        break;
    case 'get_suppliers':
        getSuppliers($conn);
        break;
    case 'sync_vendor_status':
        syncVendorStatus($conn);
        break;
    case 'get_vendor_supplier_mapping':
        getVendorSupplierMapping($conn);
        break;
    default:
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
}

/**
 * Send vendor from vendors table to procurement for approval
 */
function sendVendorToProcurement($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $vendor_id = $data['vendor_id'] ?? '';
    
    if (empty($vendor_id)) {
        echo json_encode(["status" => "error", "message" => "Vendor ID is required"]);
        return;
    }
    
    // Get vendor details from vendors table
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
    
    // Update vendor status to show it's sent to procurement
    $update_sql = "UPDATE vendors SET status = 'Pending Procurement' WHERE vendor_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("s", $vendor_id);
    $update_stmt->execute();
    
    echo json_encode([
        "status" => "success", 
        "message" => "Vendor sent to procurement successfully",
        "vendor_data" => $vendor
    ]);
}

/**
 * Get vendors that are ready for procurement approval
 */
function getVendorsForProcurement($conn) {
    $sql = "SELECT * FROM vendors WHERE status IN ('Approved', 'Pending Procurement') ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    $vendors = [];
    while ($row = $result->fetch_assoc()) {
        $vendors[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "data" => $vendors
    ]);
}

/**
 * Approve vendor and move to suppliers table
 */
function approveVendorToSupplier($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $vendor_id = $data['vendor_id'] ?? '';
    $approved_by = $data['approved_by'] ?? 'System';
    
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
    
    // Check if supplier already exists
    $check_sql = "SELECT id FROM suppliers WHERE company_name = ? AND email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $vendor['vendor_name'], $vendor['email']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Supplier already exists"]);
        return;
    }
    
    // Insert into suppliers table
    $insert_sql = "INSERT INTO suppliers (company_name, contact_person, email, phone, address, description, status) 
                   VALUES (?, ?, ?, ?, ?, ?, 'Active')";
    $insert_stmt = $conn->prepare($insert_sql);
    $description = "From vendor: " . $vendor['vendor_id'] . " - " . $vendor['business_type'];
    $insert_stmt->bind_param("ssssss", 
        $vendor['vendor_name'], 
        $vendor['contact_person'], 
        $vendor['email'], 
        $vendor['contact_number'], 
        $vendor['address'], 
        $description
    );
    
    if ($insert_stmt->execute()) {
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
 * Get all suppliers
 */
function getSuppliers($conn) {
    $sql = "SELECT * FROM suppliers ORDER BY company_name";
    $result = $conn->query($sql);
    
    $suppliers = [];
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "data" => $suppliers
    ]);
}

/**
 * Sync status between vendor and supplier
 */
function syncVendorStatus($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    $vendor_id = $data['vendor_id'] ?? '';
    $supplier_id = $data['supplier_id'] ?? '';
    $status = $data['status'] ?? '';
    
    if (empty($vendor_id) || empty($supplier_id) || empty($status)) {
        echo json_encode(["status" => "error", "message" => "Missing required parameters"]);
        return;
    }
    
    // Update supplier status
    $supplier_sql = "UPDATE suppliers SET status = ? WHERE id = ?";
    $supplier_stmt = $conn->prepare($supplier_sql);
    $supplier_stmt->bind_param("si", $status, $supplier_id);
    $supplier_stmt->execute();
    
    // Update vendor status
    $vendor_sql = "UPDATE vendors SET status = ? WHERE vendor_id = ?";
    $vendor_stmt = $conn->prepare($vendor_sql);
    $vendor_status = "Supplier Status: " . $status;
    $vendor_stmt->bind_param("ss", $vendor_status, $vendor_id);
    $vendor_stmt->execute();
    
    echo json_encode([
        "status" => "success",
        "message" => "Status synchronized successfully"
    ]);
}

/**
 * Get mapping between vendors and suppliers
 */
function getVendorSupplierMapping($conn) {
    $sql = "SELECT 
                v.vendor_id, 
                v.vendor_name, 
                v.email as vendor_email,
                v.status as vendor_status,
                s.id as supplier_id,
                s.company_name,
                s.email as supplier_email,
                s.status as supplier_status
            FROM vendors v
            LEFT JOIN suppliers s ON v.vendor_name = s.company_name AND v.email = s.email
            ORDER BY v.vendor_name";
    
    $result = $conn->query($sql);
    
    $mappings = [];
    while ($row = $result->fetch_assoc()) {
        $mappings[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "data" => $mappings
    ]);
}

$conn->close();
?>

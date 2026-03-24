<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$method = $_SERVER["REQUEST_METHOD"];
$action = $_GET["action"] ?? "";

// Configuration - Change this to use a different table
$VENDOR_TABLE = "vendors"; // CHANGE THIS to your preferred table

try {
    $conn = new mysqli("localhost", "root", "", "logistics_db");
    
    if ($conn->connect_error) {
        echo json_encode(["status" => "error", "message" => "DB connection failed"]);
        exit;
    }
    
    // Ensure table exists with simple structure
    $conn->query("CREATE TABLE IF NOT EXISTS procurement_vendors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vendor_id VARCHAR(50) NOT NULL,
        vendor_name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255) DEFAULT '',
        email VARCHAR(255) DEFAULT '',
        contact_number VARCHAR(50) DEFAULT '',
        address TEXT DEFAULT '',
        business_type VARCHAR(255) DEFAULT '',
        business_details TEXT DEFAULT '',
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_by VARCHAR(100) DEFAULT 'admin',
        procurement_status VARCHAR(50) DEFAULT 'Pending Review',
        notes TEXT DEFAULT ''
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    if ($method === "GET" && $action === "get_procurement_vendors") {
        // Get vendors sent to procurement with live status from vendors table
        $result = $conn->query("SELECT pv.id, pv.vendor_id, pv.vendor_name, pv.contact_person, pv.email, pv.contact_number, pv.address, pv.business_type, pv.business_details, pv.sent_at, pv.sent_by, v.status as vendor_status FROM procurement_vendors pv LEFT JOIN vendors v ON pv.vendor_id = v.vendor_id ORDER BY pv.sent_at DESC");
        
        $vendors = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $vendors[] = [
                    'id' => $row['vendor_id'],
                    'company_name' => $row['vendor_name'],
                    'contact_person' => $row['contact_person'],
                    'email' => $row['email'],
                    'phone' => $row['contact_number'],
                    'business_type' => $row['business_type'],
                    'business_details' => $row['business_details'],
                    'status' => $row['vendor_status'] ?? 'Unknown', // Live status from vendors table
                    'sent_at' => $row['sent_at'],
                    'sent_by' => $row['sent_by']
                ];
            }
        }
        
        echo json_encode(["status" => "success", "data" => $vendors]);
        
    } elseif ($method === "POST" && $action === "send_to_procurement") {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        if (!$data || empty($data["vendor_id"])) {
            echo json_encode(["status" => "error", "message" => "Vendor ID required"]);
            exit;
        }
        
        // Get vendor from configured table (currently "vendors" - CHANGE THIS)
        $sql = "SELECT vendor_id, vendor_name, contact_person, email, contact_number, address, business_type, business_details FROM $VENDOR_TABLE WHERE vendor_id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "SQL prepare failed: " . $conn->error]);
            exit;
        }
        
        $stmt->bind_param("s", $data["vendor_id"]);
        if (!$stmt->execute()) {
            echo json_encode(["status" => "error", "message" => "SQL execute failed: " . $stmt->error]);
            exit;
        }
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(["status" => "error", "message" => "Vendor not found in $VENDOR_TABLE table"]);
            exit;
        }
        
        $vendor = $result->fetch_assoc();
        
        // Check if already sent to procurement (RE-ENABLED)
        $stmt = $conn->prepare("SELECT id FROM procurement_vendors WHERE vendor_id = ?");
        $stmt->bind_param("s", $data["vendor_id"]);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Vendor already sent to procurement"]);
            exit;
        }
        
        // Insert into procurement_vendors
        $stmt = $conn->prepare("INSERT INTO procurement_vendors (vendor_id, vendor_name, contact_person, email, contact_number, address, business_type, business_details, sent_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "INSERT prepare failed: " . $conn->error]);
            exit;
        }
        
        $sentBy = $data["sent_by"] ?? "admin";
        
        // Handle NULL values properly by converting to empty strings
        $contactPerson = $vendor["contact_person"] ?? '';
        $email = $vendor["email"] ?? '';
        $contactNumber = $vendor["contact_number"] ?? '';
        $address = $vendor["address"] ?? '';
        $businessType = $vendor["business_type"] ?? '';
        $businessDetails = $vendor["business_details"] ?? '';
        
        $stmt->bind_param("sssssssss", 
            $vendor["vendor_id"], 
            $vendor["vendor_name"], 
            $contactPerson,
            $email,
            $contactNumber,
            $address,
            $businessType,
            $businessDetails,
            $sentBy
        );
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Vendor sent to procurement successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "INSERT execute failed: " . $stmt->error]);
        }
        
    } elseif ($method === "PUT" && $action === "update_vendor_status") {
        // Update vendor status in procurement when changed in vendor-registration
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        if (!$data || empty($data["vendor_id"]) || empty($data["status"])) {
            echo json_encode(["status" => "error", "message" => "Vendor ID and status required"]);
            exit;
        }
        
        // Update the vendor's status in the main vendors table first
        $stmt = $conn->prepare("UPDATE vendors SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE vendor_id = ?");
        $stmt->bind_param("ss", $data["status"], $data["vendor_id"]);
        
        if ($stmt->execute()) {
            // Check if vendor exists in procurement_vendors
            $checkStmt = $conn->prepare("SELECT id FROM procurement_vendors WHERE vendor_id = ?");
            $checkStmt->bind_param("s", $data["vendor_id"]);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows > 0) {
                // Update procurement_vendors table as well to keep in sync
                $updateStmt = $conn->prepare("UPDATE procurement_vendors SET vendor_name = (SELECT vendor_name FROM vendors WHERE vendor_id = ?), contact_person = (SELECT contact_person FROM vendors WHERE vendor_id = ?), email = (SELECT email FROM vendors WHERE vendor_id = ?), contact_number = (SELECT contact_number FROM vendors WHERE vendor_id = ?), address = (SELECT address FROM vendors WHERE vendor_id = ?), business_type = (SELECT business_type FROM vendors WHERE vendor_id = ?), business_details = (SELECT business_details FROM vendors WHERE vendor_id = ?) WHERE vendor_id = ?");
                $updateStmt->bind_param("ssssssss", $data["vendor_id"], $data["vendor_id"], $data["vendor_id"], $data["vendor_id"], $data["vendor_id"], $data["vendor_id"], $data["vendor_id"], $data["vendor_id"]);
                $updateStmt->execute();
            }
            
            echo json_encode(["status" => "success", "message" => "Vendor status updated successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update vendor status"]);
        }
        
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid request"]);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
}
?>

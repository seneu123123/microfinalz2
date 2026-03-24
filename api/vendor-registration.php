<?php
header("Content-Type: application/json");

// DATABASE CONNECTION (EDIT IF NEEDED)
$conn = new mysqli("localhost", "root", "", "logistics_db");

// CHECK CONNECTION
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit();
}

// GET JSON DATA
$data = json_decode(file_get_contents("php://input"), true);

// VALIDATE REQUIRED FIELDS
if (
    empty($data['vendor_name']) ||
    empty($data['business_type']) ||
    empty($data['contact_number']) ||
    empty($data['email'])
) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit();
}

// AUTO GENERATE VENDOR ID (VND-00001)
$result = $conn->query("SELECT COUNT(*) as total FROM vendors");
$row = $result->fetch_assoc();
$nextId = $row['total'] + 1;
$vendor_id = "VND-" . str_pad($nextId, 5, "0", STR_PAD_LEFT);

// ASSIGN VALUES
$vendor_name = $data['vendor_name'];
$business_type = $data['business_type'];
$contact_number = $data['contact_number'];
$email = $data['email'];
$address = $data['address'] ?? '';
$registration_no = $data['registration_no'] ?? '';
$tin = $data['tin'] ?? '';
$contact_person = $data['contact_person'] ?? '';
$bank_name = $data['bank_name'] ?? '';
$bank_account = $data['bank_account'] ?? '';

// INSERT TO DATABASE
$sql = "INSERT INTO vendors 
(vendor_id, vendor_name, business_type, contact_number, email, address, registration_no, tin, contact_person, bank_name, bank_account) 
VALUES 
('$vendor_id','$vendor_name','$business_type','$contact_number','$email','$address','$registration_no','$tin','$contact_person','$bank_name','$bank_account')";

if ($conn->query($sql)) {

    // 🔥 SEND TO PROCUREMENT SYSTEM (INTEGRATION)
    $procurement_url = "http://192.168.1.9/microfinance/procurement_api.php";

    $payload = json_encode([
        "vendor_id" => $vendor_id,
        "vendor_name" => $vendor_name,
        "business_type" => $business_type,
        "contact_number" => $contact_number,
        "email" => $email
    ]);

    $ch = curl_init($procurement_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    curl_exec($ch);
    curl_close($ch);

    echo json_encode([
        "status" => "success",
        "message" => "Vendor registered and sent to procurement",
        "vendor_id" => $vendor_id
    ]);

} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}

$conn->close();
?>
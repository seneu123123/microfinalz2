<?php



/**

 * Vendor Registration API - Updated for logistics_db with complete vendor fields

 */







header('Content-Type: application/json');



header('Access-Control-Allow-Origin: *');



header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');



header('Access-Control-Allow-Headers: Content-Type');







require_once '../config/db.php';



 



// Turn off display errors (we'll return JSON instead)



ini_set('display_errors', '0');



error_reporting(E_ALL);







// Capture fatal errors and return JSON



register_shutdown_function(function() {



    $err = error_get_last();



    if ($err) {



        header('Content-Type: application/json');



        echo json_encode([



            'success' => false,



            'message' => 'Server fatal error: ' . ($err['message'] ?? 'unknown')



        ]);



        error_log("Fatal error in vendors.php: " . var_export($err, true));



    }



});







set_exception_handler(function($ex) {



    header('Content-Type: application/json');



    echo json_encode([



        'success' => false,



        'message' => 'Unhandled exception: ' . $ex->getMessage()



    ]);



    error_log('Unhandled exception in vendors.php: ' . $ex->getMessage());



});







// Create vendors table if it doesn't exist



createVendorsTable();







$method = $_SERVER['REQUEST_METHOD'];



$action = $_GET['action'] ?? '';





switch ($method) {



    case 'GET':



        if ($action === 'list') {



            getVendors();



        } else {



            getVendors();



        }



        break;



    case 'POST':



        createVendor();



        break;



    case 'PUT':



        updateVendor();



        break;



    case 'DELETE':



        deleteVendor($_GET['id'] ?? '');



        break;



    default:



        sendError('Invalid request');



}





function createVendorsTable() {

    global $conn;

    

    // Create vendors table with all required fields

    $sql = "CREATE TABLE IF NOT EXISTS vendors (

        vendor_id VARCHAR(50) PRIMARY KEY,

        vendor_name VARCHAR(255) NOT NULL,

        business_type VARCHAR(255),

        business_details TEXT,

        contact_number VARCHAR(50),

        email VARCHAR(255) UNIQUE NOT NULL,

        address TEXT,

        registration_no VARCHAR(100),

        tin VARCHAR(50),

        contact_person VARCHAR(255),

        bank_name VARCHAR(255),

        bank_account VARCHAR(100),

        status ENUM('Pending', 'Approved', 'Rejected', 'Active') DEFAULT 'Pending',

        business_permit_path VARCHAR(500),

        company_registration_path VARCHAR(500),

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    

    if (!$conn->query($sql)) {

        error_log("Error creating vendors table: " . $conn->error);

    }

    

    // Create documents table for file uploads

    $docSql = "CREATE TABLE IF NOT EXISTS vendor_documents (

        id INT AUTO_INCREMENT PRIMARY KEY,

        vendor_id VARCHAR(50) NOT NULL,

        document_type ENUM('business_permit', 'company_registration') NOT NULL,

        file_name VARCHAR(255) NOT NULL,

        file_path VARCHAR(500) NOT NULL,

        file_size INT NOT NULL,

        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        FOREIGN KEY (vendor_id) REFERENCES vendors(vendor_id) ON DELETE CASCADE

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    

    if (!$conn->query($docSql)) {

        error_log("Error creating vendor_documents table: " . $conn->error);

    }

}





function getVendors() {



    global $conn;



    $result = $conn->query("SELECT * FROM vendors ORDER BY created_at DESC");



    if (!$result) {



        sendError('Query failed: ' . $conn->error);



    }



    $vendors = [];



    while ($row = $result->fetch_assoc()) {



        $vendors[] = $row;



    }



    sendSuccess('Vendors retrieved', $vendors);



}







function createVendor() {

    global $conn;

    

    // Check if this is FormData (file upload) or JSON

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    

    if (strpos($contentType, 'multipart/form-data') !== false) {

        // Handle FormData (file upload) - get all admin form fields

        $data = [

            'vendor_name' => $_POST['vendor_name'] ?? '',

            'business_type' => $_POST['business_type'] ?? '',

            'business_details' => $_POST['business_details'] ?? '',

            'contact_number' => $_POST['contact_number'] ?? '',

            'email' => $_POST['email'] ?? '',

            'registration_no' => $_POST['registration_no'] ?? '',

            'tin' => $_POST['tin'] ?? '',

            'contact_person' => $_POST['contact_person'] ?? '',

            'bank_name' => $_POST['bank_name'] ?? '',

            'bank_account' => $_POST['bank_account'] ?? '',

            'status' => $_POST['status'] ?? 'Pending'

        ];

        

        error_log('vendors.php FormData input: ' . var_export($data, true));

        

        // Handle file uploads

        $businessPermit = $_FILES['business_permit'] ?? null;

        $companyRegistration = $_FILES['company_registration'] ?? null;

        

        if ($businessPermit && $businessPermit['error'] === UPLOAD_ERR_OK) {

            error_log('Business permit uploaded: ' . $businessPermit['name']);

        }

        if ($companyRegistration && $companyRegistration['error'] === UPLOAD_ERR_OK) {

            error_log('Company registration uploaded: ' . $companyRegistration['name']);

        }

    } else {

        // Handle JSON input

        $rawInput = file_get_contents("php://input");

        error_log('vendors.php raw input: ' . $rawInput);

        $data = json_decode($rawInput, true);



        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {

            $jsonErr = json_last_error_msg();

            error_log('vendors.php JSON decode error: ' . $jsonErr);

            sendError('Invalid JSON input: ' . $jsonErr);

        }

    }



    // Check for required fields

    if (empty($data['vendor_name']) || empty($data['contact_number']) || empty($data['email'])) {

        error_log('vendors.php missing required fields: ' . var_export($data, true));

        sendError('Required fields: vendor_name, contact_number, email');

    }



    // Validate document uploads for FormData requests

    if (strpos($contentType, 'multipart/form-data') !== false) {

        $businessPermit = $_FILES['business_permit'] ?? null;

        $companyRegistration = $_FILES['company_registration'] ?? null;



        if (!$businessPermit || $businessPermit['error'] !== UPLOAD_ERR_OK) {

            sendError('Business permit document is required');

        }



        if (!$companyRegistration || $companyRegistration['error'] !== UPLOAD_ERR_OK) {

            sendError('Company registration document is required');

        }



        // Validate file sizes (5MB max)

        $maxSize = 5 * 1024 * 1024;

        if ($businessPermit['size'] > $maxSize) {

            sendError('Business permit file size must be less than 5MB');

        }



        if ($companyRegistration['size'] > $maxSize) {

            sendError('Company registration file size must be less than 5MB');

        }

    }



    // Check duplicate email

    $stmt = $conn->prepare("SELECT vendor_id FROM vendors WHERE email = ?");

    $stmt->bind_param("s", $data['email']);

    $stmt->execute();



    if ($stmt->get_result()->num_rows > 0) {

        sendError('Email already exists');

    }



    // Generate vendor ID

    $nextId = getNextVendorId();

    $vendorId = 'VND-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

    $status = isset($data['status']) ? $data['status'] : 'Pending';

    

    // Map product_category to business_type for compatibility

    $business_type = isset($data['product_category']) ? $data['product_category'] : 

                    (isset($data['business_type']) ? $data['business_type'] : '');



    // Handle file uploads and get paths

    $businessPermitPath = '';

    $companyRegistrationPath = '';

    

    if (strpos($contentType, 'multipart/form-data') !== false) {

        // Create uploads directory if it doesn't exist

        $uploadDir = '../uploads/vendor_documents/';

        if (!file_exists($uploadDir)) {

            mkdir($uploadDir, 0777, true);

        }

        

        // Upload business permit

        if ($businessPermit && $businessPermit['error'] === UPLOAD_ERR_OK) {

            $businessPermitPath = $uploadDir . $vendorId . '_business_permit_' . basename($businessPermit['name']);

            move_uploaded_file($businessPermit['tmp_name'], $businessPermitPath);

        }

        

        // Upload company registration

        if ($companyRegistration && $companyRegistration['error'] === UPLOAD_ERR_OK) {

            $companyRegistrationPath = $uploadDir . $vendorId . '_company_reg_' . basename($companyRegistration['name']);

            move_uploaded_file($companyRegistration['tmp_name'], $companyRegistrationPath);

        }

    }



    // Complete INSERT with all fields

    $stmt = $conn->prepare("INSERT INTO vendors (vendor_id, vendor_name, business_type, business_details, contact_number, email, address, registration_no, tin, contact_person, bank_name, bank_account, status, business_permit_path, company_registration_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    

    $stmt->bind_param(

        "sssssssssssssss",

        $vendorId,

        $data['vendor_name'],

        $data['business_type'],

        $data['business_details'],

        $data['contact_number'],

        $data['email'],

        $data['address'],

        $data['registration_no'],

        $data['tin'],

        $data['contact_person'],

        $data['bank_name'],

        $data['bank_account'],

        $status,

        $businessPermitPath,

        $companyRegistrationPath

    );



    if ($stmt->execute()) {

        sendSuccess('Vendor created successfully', ['vendor_id' => $vendorId]);

    } else {

        sendError('Failed to create vendor: ' . $stmt->error);

    }

}







function updateVendor() {



    global $conn;



    $data = json_decode(file_get_contents("php://input"), true);







    if (empty($data['vendor_id'])) {



        sendError('Vendor ID required');



    }







    // Build dynamic UPDATE query based on provided fields



    $updateFields = [];



    $params = [];



    $types = '';







    // Map simplified fields to existing database structure



    if (isset($data['vendor_name'])) {



        $updateFields[] = "vendor_name = ?";



        $params[] = $data['vendor_name'];



        $types .= 's';



    }



    



    // Handle business_type field

    if (isset($data['business_type'])) {

        $updateFields[] = "business_type = ?";

        $params[] = $data['business_type'];

        $types .= 's';

    }

    

    // Handle business_details field

    if (isset($data['business_details'])) {

        $updateFields[] = "business_details = ?";

        $params[] = $data['business_details'];

        $types .= 's';

    }



    



    if (isset($data['contact_number'])) {



        $updateFields[] = "contact_number = ?";



        $params[] = $data['contact_number'];



        $types .= 's';



    }



    



    if (isset($data['email'])) {



        $updateFields[] = "email = ?";



        $params[] = $data['email'];



        $types .= 's';



    }



    



    if (isset($data['status'])) {



        $updateFields[] = "status = ?";



        $params[] = $data['status'];



        $types .= 's';



    }







    if (empty($updateFields)) {



        sendError('No fields to update');



        return;



    }







    // Add vendor_id as the last parameter



    $params[] = $data['vendor_id'];



    $types .= 's';







    $sql = "UPDATE vendors SET " . implode(', ', $updateFields) . " WHERE vendor_id = ?";



    $stmt = $conn->prepare($sql);



    $stmt->bind_param($types, ...$params);







    if ($stmt->execute()) {



        sendSuccess('Vendor updated');



    } else {



        sendError('Update failed: ' . $stmt->error);



    }



}







function deleteVendor($vendorId) {



    global $conn;



    if (empty($vendorId)) {



        sendError('ID required');



    }



    $stmt = $conn->prepare("DELETE FROM vendors WHERE vendor_id = ?");



    $stmt->bind_param("s", $vendorId);



    if ($stmt->execute()) {



        sendSuccess('Vendor deleted');



    } else {



        sendError('Delete failed');



    }



}







function getNextVendorId() {



    global $conn;



    $result = $conn->query("SELECT MAX(CAST(SUBSTRING(vendor_id, 5) AS UNSIGNED)) as max_id FROM vendors");



    $row = $result->fetch_assoc();



    return ($row['max_id'] ?? 0) + 1;



}



?>




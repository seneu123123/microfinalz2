<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

// Check database connection
if (!$conn || $conn->connect_error) {
    sendResponse(false, 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error'));
}

class AuditorsAPI {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Get JSON input for POST requests
        $input = [];
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        }
        $action = $input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';
        
        switch ($method) {
            case 'GET':
                return $this->getAllAuditors();
                
            case 'POST':
                switch ($action) {
                    case 'create':
                        return $this->createAuditor($input);
                    case 'delete':
                        return $this->deleteAuditor($input['auditor_id'] ?? '');
                    default:
                        sendResponse(false, 'Invalid action');
                }
                
            default:
                sendResponse(false, 'Method not allowed');
        }
    }
    
    private function getAllAuditors() {
        try {
            $query = "SELECT * FROM auditors WHERE status = 'active' ORDER BY name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $auditors = [];
            while ($row = $result->fetch_assoc()) {
                $auditors[] = $row;
            }
            
            sendResponse(true, 'Auditors retrieved', $auditors);
        } catch (Exception $e) {
            sendResponse(false, 'Database error: ' . $e->getMessage());
        }
    }
    
    private function createAuditor($input) {
        $auditorId = $input['auditor_id'] ?? '';
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $phone = $input['phone'] ?? '';
        $department = $input['department'] ?? '';
        $specialization = $input['specialization'] ?? '';
        $experienceLevel = $input['experience_level'] ?? '';
        $certifications = $input['certifications'] ?? '';
        $notes = $input['notes'] ?? '';
        
        if (empty($auditorId) || empty($name) || empty($email) || empty($department) || empty($specialization) || empty($experienceLevel)) {
            sendResponse(false, 'Missing required fields');
        }
        
        try {
            $query = "INSERT INTO auditors 
                     (auditor_id, name, email, phone, department, specialization, experience_level, certifications, notes, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('sssssssss', $auditorId, $name, $email, $phone, $department, $specialization, $experienceLevel, $certifications, $notes);
            
            if ($stmt->execute()) {
                sendResponse(true, 'Auditor created successfully', ['auditor_id' => $auditorId]);
            } else {
                sendResponse(false, 'Failed to create auditor');
            }
        } catch (Exception $e) {
            sendResponse(false, 'Database error: ' . $e->getMessage());
        }
    }
    
    private function deleteAuditor($auditorId) {
        try {
            $query = "UPDATE auditors SET status = 'inactive' WHERE auditor_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('s', $auditorId);
            
            if ($stmt->execute()) {
                sendResponse(true, 'Auditor deleted successfully');
            } else {
                sendResponse(false, 'Failed to delete auditor');
            }
        } catch (Exception $e) {
            sendResponse(false, 'Database error: ' . $e->getMessage());
        }
    }
}

// Handle the request
$api = new AuditorsAPI();
echo $api->handleRequest();
?>

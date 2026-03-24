<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

class MechanicsAPI {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                return $this->getAllMechanics();
                
            default:
                return $this->error('Method not allowed');
        }
    }
    
    private function getAllMechanics() {
        try {
            $query = "SELECT mechanic_id, name, specialization, phone, email, status 
                     FROM mechanics 
                     WHERE status = 'active' 
                     ORDER BY name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $mechanics = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->success($mechanics);
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function success($data = null, $message = 'Success') {
        return json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    private function error($message) {
        return json_encode([
            'success' => false,
            'message' => $message
        ]);
    }
}

// Handle the request
$api = new MechanicsAPI();
echo $api->handleRequest();
?>

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

class FuelUsageAPI {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        switch ($method) {
            case 'GET':
                if (isset($_GET['vehicle_id'])) {
                    return $this->getVehicleUsage($_GET['vehicle_id']);
                }
                return $this->getAllUsage();
                
            case 'POST':
                switch ($action) {
                    case 'create':
                        return $this->createUsage();
                    case 'flag_anomaly':
                        return $this->flagAnomaly();
                    case 'delete':
                        return $this->deleteUsage();
                    default:
                        return $this->error('Invalid action');
                }
                
            default:
                return $this->error('Method not allowed');
        }
    }
    
    private function getAllUsage() {
        try {
            $query = "SELECT fu.*, v.make, v.model 
                     FROM fuel_usage fu 
                     LEFT JOIN vehicle_inventory v ON fu.vehicle_id = v.vehicle_id 
                     ORDER BY fu.trip_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $usageRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->success($usageRecords);
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function getVehicleUsage($vehicleId) {
        try {
            $query = "SELECT * FROM fuel_usage WHERE vehicle_id = ? ORDER BY trip_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $vehicleId);
            $stmt->execute();
            
            $usageRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->success($usageRecords);
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function createUsage() {
        $vehicleId = $_POST['vehicle_id'] ?? '';
        $fuelConsumed = $_POST['fuel_consumed'] ?? '';
        $odometerReading = $_POST['odometer_reading'] ?? '';
        $tripDate = $_POST['trip_date'] ?? '';
        $tripDistance = $_POST['trip_distance'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if (empty($vehicleId) || empty($fuelConsumed) || empty($odometerReading) || empty($tripDate)) {
            return $this->error('Missing required fields');
        }
        
        try {
            // Generate usage ID
            $usageId = 'FUEL-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $query = "INSERT INTO fuel_usage 
                     (usage_id, vehicle_id, fuel_consumed, odometer_reading, trip_date, trip_distance, notes, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $usageId);
            $stmt->bindParam(2, $vehicleId);
            $stmt->bindParam(3, $fuelConsumed);
            $stmt->bindParam(4, $odometerReading);
            $stmt->bindParam(5, $tripDate);
            $stmt->bindParam(6, $tripDistance);
            $stmt->bindParam(7, $notes);
            
            if ($stmt->execute()) {
                // Integrate with Vehicle Reservation (2.5) - calculate fuel efficiency
                $this->updateVehicleEfficiency($vehicleId);
                
                return $this->success(['usage_id' => $usageId], 'Fuel usage logged successfully');
            } else {
                return $this->error('Failed to log fuel usage');
            }
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function flagAnomaly() {
        $usageId = $_POST['usage_id'] ?? '';
        $anomalyType = $_POST['anomaly_type'] ?? '';
        $severityLevel = $_POST['severity_level'] ?? '';
        $description = $_POST['description'] ?? '';
        $recommendedAction = $_POST['recommended_action'] ?? '';
        
        if (empty($usageId) || empty($anomalyType) || empty($severityLevel) || empty($description)) {
            return $this->error('Missing required fields');
        }
        
        try {
            $query = "UPDATE fuel_usage 
                     SET anomaly_flagged = 1, anomaly_type = ?, severity_level = ?, 
                         anomaly_description = ?, recommended_action = ?, anomaly_date = NOW() 
                     WHERE usage_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $anomalyType);
            $stmt->bindParam(2, $severityLevel);
            $stmt->bindParam(3, $description);
            $stmt->bindParam(4, $recommendedAction);
            $stmt->bindParam(5, $usageId);
            
            if ($stmt->execute()) {
                // Send notification to Shift and Scheduling system
                $this->notifyAnomaly($usageId, $anomalyType, $severityLevel);
                
                return $this->success(null, 'Anomaly flagged successfully');
            } else {
                return $this->error('Failed to flag anomaly');
            }
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function deleteUsage($usageId) {
        try {
            $query = "DELETE FROM fuel_usage WHERE usage_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $usageId);
            
            if ($stmt->execute()) {
                return $this->success(null, 'Usage record deleted successfully');
            } else {
                return $this->error('Failed to delete usage record');
            }
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function updateVehicleEfficiency($vehicleId) {
        // Calculate fuel efficiency for the vehicle
        $query = "SELECT AVG(fuel_consumed / trip_distance) as efficiency 
                 FROM fuel_usage 
                 WHERE vehicle_id = ? AND trip_distance > 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $vehicleId);
        $stmt->execute();
        
        $efficiency = $stmt->fetchColumn();
        
        if ($efficiency) {
            // Update vehicle inventory with efficiency data
            $updateQuery = "UPDATE vehicle_inventory SET fuel_efficiency = ? WHERE vehicle_id = ?";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(1, $efficiency);
            $updateStmt->bindParam(2, $vehicleId);
            $updateStmt->execute();
        }
    }
    
    private function notifyAnomaly($usageId, $anomalyType, $severityLevel) {
        // Integration with Shift and Scheduling system
        // This would send a notification about the anomaly
        
        $notificationData = [
            'usage_id' => $usageId,
            'anomaly_type' => $anomalyType,
            'severity_level' => $severityLevel,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // In a real implementation, this would call the scheduling API
        error_log('Fuel usage anomaly detected: ' . json_encode($notificationData));
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
$api = new FuelUsageAPI();
echo $api->handleRequest();
?>

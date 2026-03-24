<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

class MaintenanceScheduleAPI {
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
                return $this->getAllSchedules();
                
            case 'POST':
                switch ($action) {
                    case 'create':
                        return $this->createSchedule();
                    case 'log_service':
                        return $this->logService();
                    case 'alert_mro':
                        return $this->alertMRO();
                    case 'delete':
                        return $this->deleteSchedule();
                    default:
                        return $this->error('Invalid action');
                }
                
            default:
                return $this->error('Method not allowed');
        }
    }
    
    private function getAllSchedules() {
        try {
            $query = "SELECT ms.*, v.make, v.model, v.current_mileage,
                     ven.name as vendor_name, ven.specialization as vendor_specialization
                     FROM maintenance_schedules ms 
                     LEFT JOIN vehicle_inventory v ON ms.vehicle_id = v.vehicle_id 
                     LEFT JOIN vendors ven ON ms.preferred_vendor = ven.vendor_id 
                     ORDER BY ms.next_due_date ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->success($schedules);
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function createSchedule() {
        $vehicleId = $_POST['vehicle_id'] ?? '';
        $serviceType = $_POST['service_type'] ?? '';
        $lastMaintenanceDate = $_POST['last_maintenance_date'] ?? '';
        $nextDueDate = $_POST['next_due_date'] ?? '';
        $mileageThreshold = $_POST['mileage_threshold'] ?? 0;
        $priorityLevel = $_POST['priority_level'] ?? 'medium';
        $preferredVendor = $_POST['preferred_vendor'] ?? '';
        $maintenanceNotes = $_POST['maintenance_notes'] ?? '';
        
        if (empty($vehicleId) || empty($serviceType) || empty($lastMaintenanceDate) || empty($nextDueDate)) {
            return $this->error('Missing required fields');
        }
        
        try {
            // Generate schedule ID
            $scheduleId = 'MS-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $this->conn->beginTransaction();
            
            $query = "INSERT INTO maintenance_schedules 
                     (schedule_id, vehicle_id, service_type, last_maintenance_date, next_due_date, 
                      mileage_threshold, priority_level, preferred_vendor, maintenance_notes, 
                      status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $scheduleId);
            $stmt->bindParam(2, $vehicleId);
            $stmt->bindParam(3, $serviceType);
            $stmt->bindParam(4, $lastMaintenanceDate);
            $stmt->bindParam(5, $nextDueDate);
            $stmt->bindParam(6, $mileageThreshold);
            $stmt->bindParam(7, $priorityLevel);
            $stmt->bindParam(8, $preferredVendor);
            $stmt->bindParam(9, $maintenanceNotes);
            
            if ($stmt->execute()) {
                // Integration with Vendor Portal - cross-reference with approved repair/maintenance vendors
                $this->validateVendor($preferredVendor, $serviceType);
                
                // Integration with MRO - prepare for MAINTENANCE REQ
                $this->prepareMaintenanceRequest($scheduleId);
                
                $this->conn->commit();
                return $this->success(['schedule_id' => $scheduleId], 'Maintenance scheduled successfully');
            } else {
                $this->conn->rollback();
                return $this->error('Failed to schedule maintenance');
            }
        } catch (PDOException $e) {
            $this->conn->rollback();
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function logService() {
        $scheduleId = $_POST['schedule_id'] ?? '';
        $serviceDate = $_POST['service_date'] ?? '';
        $mileageAtService = $_POST['mileage_at_service'] ?? 0;
        $serviceCost = $_POST['service_cost'] ?? 0;
        $performedBy = $_POST['performed_by'] ?? '';
        $serviceDetails = $_POST['service_details'] ?? '';
        $nextMaintenanceDate = $_POST['next_maintenance_date'] ?? '';
        $nextMileageThreshold = $_POST['next_mileage_threshold'] ?? 0;
        
        if (empty($scheduleId) || empty($serviceDate) || empty($serviceDetails)) {
            return $this->error('Missing required fields');
        }
        
        try {
            $this->conn->beginTransaction();
            
            // Get schedule details
            $scheduleQuery = "SELECT * FROM maintenance_schedules WHERE schedule_id = ?";
            $scheduleStmt = $this->conn->prepare($scheduleQuery);
            $scheduleStmt->bindParam(1, $scheduleId);
            $scheduleStmt->execute();
            $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$schedule) {
                $this->conn->rollback();
                return $this->error('Maintenance schedule not found');
            }
            
            // Log service completion
            $query = "INSERT INTO service_logs 
                     (schedule_id, service_date, mileage_at_service, service_cost, performed_by, 
                      service_details, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $scheduleId);
            $stmt->bindParam(2, $serviceDate);
            $stmt->bindParam(3, $mileageAtService);
            $stmt->bindParam(4, $serviceCost);
            $stmt->bindParam(5, $performedBy);
            $stmt->bindParam(6, $serviceDetails);
            
            if ($stmt->execute()) {
                // Update schedule status
                $updateQuery = "UPDATE maintenance_schedules 
                              SET status = 'completed', completed_date = ?, completed_by = ? 
                              WHERE schedule_id = ?";
                
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(1, $serviceDate);
                $updateStmt->bindParam(2, $performedBy);
                $updateStmt->bindParam(3, $scheduleId);
                $updateStmt->execute();
                
                // Update vehicle mileage if provided
                if ($mileageAtService > 0) {
                    $vehicleUpdate = "UPDATE vehicle_inventory SET current_mileage = ? WHERE vehicle_id = ?";
                    $vehicleStmt = $this->conn->prepare($vehicleUpdate);
                    $vehicleStmt->bindParam(1, $mileageAtService);
                    $vehicleStmt->bindParam(2, $schedule['vehicle_id']);
                    $vehicleStmt->execute();
                }
                
                // Create next maintenance schedule if provided
                if ($nextMaintenanceDate && $nextMileageThreshold) {
                    $nextScheduleId = 'MS-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    
                    $nextQuery = "INSERT INTO maintenance_schedules 
                                 (schedule_id, vehicle_id, service_type, last_maintenance_date, next_due_date, 
                                  mileage_threshold, priority_level, preferred_vendor, maintenance_notes, 
                                  status, created_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())";
                    
                    $nextStmt = $this->conn->prepare($nextQuery);
                    $nextStmt->bindParam(1, $nextScheduleId);
                    $nextStmt->bindParam(2, $schedule['vehicle_id']);
                    $nextStmt->bindParam(3, $schedule['service_type']);
                    $nextStmt->bindParam(4, $serviceDate);
                    $nextStmt->bindParam(5, $nextMaintenanceDate);
                    $nextStmt->bindParam(6, $nextMileageThreshold);
                    $nextStmt->bindParam(7, $schedule['priority_level']);
                    $nextStmt->bindParam(8, $schedule['preferred_vendor']);
                    $nextStmt->bindParam(9, 'Auto-generated from previous service');
                    $nextStmt->execute();
                }
                
                // Integration with MRO - send REPAIR STATUS update
                $this->sendRepairStatus($scheduleId, 'completed', $serviceDetails);
                
                $this->conn->commit();
                return $this->success(null, 'Service logged successfully');
            } else {
                $this->conn->rollback();
                return $this->error('Failed to log service');
            }
        } catch (PDOException $e) {
            $this->conn->rollback();
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function alertMRO() {
        $scheduleId = $_POST['schedule_id'] ?? '';
        $alertType = $_POST['alert_type'] ?? '';
        $urgencyLevel = $_POST['urgency_level'] ?? '';
        $alertDescription = $_POST['alert_description'] ?? '';
        $requiredAction = $_POST['required_action'] ?? '';
        $expectedResolution = $_POST['expected_resolution'] ?? '';
        
        if (empty($scheduleId) || empty($alertType) || empty($urgencyLevel) || empty($alertDescription)) {
            return $this->error('Missing required fields');
        }
        
        try {
            // Get schedule details
            $scheduleQuery = "SELECT ms.*, v.make, v.model FROM maintenance_schedules ms 
                             LEFT JOIN vehicle_inventory v ON ms.vehicle_id = v.vehicle_id 
                             WHERE ms.schedule_id = ?";
            $scheduleStmt = $this->conn->prepare($scheduleQuery);
            $scheduleStmt->bindParam(1, $scheduleId);
            $scheduleStmt->execute();
            $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$schedule) {
                return $this->error('Maintenance schedule not found');
            }
            
            // Log MRO alert
            $query = "INSERT INTO mro_alerts 
                     (schedule_id, alert_type, urgency_level, alert_description, required_action, 
                      expected_resolution, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, 'sent', NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $scheduleId);
            $stmt->bindParam(2, $alertType);
            $stmt->bindParam(3, $urgencyLevel);
            $stmt->bindParam(4, $alertDescription);
            $stmt->bindParam(5, $requiredAction);
            $stmt->bindParam(6, $expectedResolution);
            
            if ($stmt->execute()) {
                // Integration with MRO - send MAINTENANCE REQ
                $this->sendMaintenanceRequest($schedule, $alertType, $urgencyLevel, $alertDescription, $requiredAction);
                
                return $this->success(null, 'MRO alert sent successfully');
            } else {
                return $this->error('Failed to send MRO alert');
            }
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function deleteSchedule($scheduleId) {
        try {
            $this->conn->beginTransaction();
            
            // Delete related service logs
            $deleteLogs = "DELETE FROM service_logs WHERE schedule_id = ?";
            $logsStmt = $this->conn->prepare($deleteLogs);
            $logsStmt->bindParam(1, $scheduleId);
            $logsStmt->execute();
            
            // Delete related MRO alerts
            $deleteAlerts = "DELETE FROM mro_alerts WHERE schedule_id = ?";
            $alertsStmt = $this->conn->prepare($deleteAlerts);
            $alertsStmt->bindParam(1, $scheduleId);
            $alertsStmt->execute();
            
            // Delete the schedule
            $query = "DELETE FROM maintenance_schedules WHERE schedule_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $scheduleId);
            
            if ($stmt->execute()) {
                $this->conn->commit();
                return $this->success(null, 'Maintenance schedule deleted successfully');
            } else {
                $this->conn->rollback();
                return $this->error('Failed to delete maintenance schedule');
            }
        } catch (PDOException $e) {
            $this->conn->rollback();
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function validateVendor($vendorId, $serviceType) {
        // Integration with Vendor Portal - cross-reference with approved repair/maintenance vendors
        if ($vendorId) {
            $query = "SELECT * FROM vendors 
                     WHERE vendor_id = ? AND status = 'approved' 
                     AND FIND_IN_SET(?, services_offered) > 0";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $vendorId);
            $stmt->bindParam(2, $serviceType);
            $stmt->execute();
            
            $vendor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$vendor) {
                // Log vendor validation issue
                error_log("Vendor validation failed: Vendor $vendorId not approved for service type $serviceType");
            }
        }
    }
    
    private function prepareMaintenanceRequest($scheduleId) {
        // Integration with MRO - prepare for MAINTENANCE REQ
        $query = "SELECT ms.*, v.make, v.model, v.current_mileage 
                 FROM maintenance_schedules ms 
                 LEFT JOIN vehicle_inventory v ON ms.vehicle_id = v.vehicle_id 
                 WHERE ms.schedule_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $scheduleId);
        $stmt->execute();
        
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($schedule) {
            $requestData = [
                'schedule_id' => $scheduleId,
                'vehicle_id' => $schedule['vehicle_id'],
                'vehicle_info' => $schedule['make'] . ' ' . $schedule['model'],
                'service_type' => $schedule['service_type'],
                'due_date' => $schedule['next_due_date'],
                'mileage_threshold' => $schedule['mileage_threshold'],
                'current_mileage' => $schedule['current_mileage'],
                'priority' => $schedule['priority_level'],
                'prepared_at' => date('Y-m-d H:i:s')
            ];
            
            // Log preparation for MRO request
            error_log('MRO request prepared: ' . json_encode($requestData));
        }
    }
    
    private function sendMaintenanceRequest($schedule, $alertType, $urgencyLevel, $description, $requiredAction) {
        // Integration with MRO - sends MAINTENANCE REQ and receives REPAIR STATUS updates
        $maintenanceRequest = [
            'request_id' => 'MRO-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'schedule_id' => $schedule['schedule_id'],
            'vehicle_id' => $schedule['vehicle_id'],
            'vehicle_info' => $schedule['make'] . ' ' . $schedule['model'],
            'service_type' => $schedule['service_type'],
            'alert_type' => $alertType,
            'urgency_level' => $urgencyLevel,
            'description' => $description,
            'required_action' => $requiredAction,
            'preferred_vendor' => $schedule['preferred_vendor'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // In a real implementation, this would call the MRO API
        error_log('MAINTENANCE REQ sent to MRO: ' . json_encode($maintenanceRequest));
        
        // Simulate receiving REPAIR STATUS update
        $this->receiveRepairStatus($maintenanceRequest['request_id'], 'received');
    }
    
    private function sendRepairStatus($scheduleId, $status, $details) {
        // Integration with MRO - receives REPAIR STATUS updates
        $repairStatus = [
            'schedule_id' => $scheduleId,
            'status' => $status,
            'details' => $details,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // In a real implementation, this would be received from MRO
        error_log('REPAIR STATUS update: ' . json_encode($repairStatus));
    }
    
    private function receiveRepairStatus($requestId, $status) {
        // Integration with MRO - receives REPAIR STATUS updates
        $query = "UPDATE mro_alerts SET status = ?, status_updated_at = NOW() 
                 WHERE request_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->bindParam(2, $requestId);
        $stmt->execute();
        
        error_log("MRO REPAIR STATUS received: Request $requestId - Status: $status");
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
$api = new MaintenanceScheduleAPI();
echo $api->handleRequest();
?>

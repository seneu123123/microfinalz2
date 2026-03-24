<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

class ServiceRepairAPI {
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
                    return $this->getVehicleHistory($_GET['vehicle_id']);
                }
                return $this->getAllRecords();
                
            case 'POST':
                switch ($action) {
                    case 'create':
                        return $this->createRecord();
                    case 'attach_invoice':
                        return $this->attachInvoice();
                    case 'delete':
                        return $this->deleteRecord();
                    default:
                        return $this->error('Invalid action');
                }
                
            default:
                return $this->error('Method not allowed');
        }
    }
    
    private function getAllRecords() {
        try {
            $query = "SELECT sr.*, v.make, v.model, m.name as mechanic_name 
                     FROM service_repair sr 
                     LEFT JOIN vehicle_inventory v ON sr.vehicle_id = v.vehicle_id 
                     LEFT JOIN mechanics m ON sr.mechanic_id = m.mechanic_id 
                     ORDER BY sr.repair_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate total cost for each record
            foreach ($records as &$record) {
                $totalCost = floatval($record['labor_cost'] ?? 0);
                
                // Get invoice amount if available
                $invoiceQuery = "SELECT invoice_amount FROM service_invoices WHERE record_id = ?";
                $invoiceStmt = $this->conn->prepare($invoiceQuery);
                $invoiceStmt->bindParam(1, $record['record_id']);
                $invoiceStmt->execute();
                $invoiceAmount = $invoiceStmt->fetchColumn();
                
                if ($invoiceAmount) {
                    $totalCost += floatval($invoiceAmount);
                }
                
                $record['total_cost'] = number_format($totalCost, 2);
            }
            
            return $this->success($records);
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function getVehicleHistory($vehicleId) {
        try {
            $query = "SELECT sr.*, m.name as mechanic_name 
                     FROM service_repair sr 
                     LEFT JOIN mechanics m ON sr.mechanic_id = m.mechanic_id 
                     WHERE sr.vehicle_id = ? 
                     ORDER BY sr.repair_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $vehicleId);
            $stmt->execute();
            
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->success($records);
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function createRecord() {
        $vehicleId = $_POST['vehicle_id'] ?? '';
        $repairDate = $_POST['repair_date'] ?? '';
        $mechanicId = $_POST['mechanic_id'] ?? '';
        $serviceType = $_POST['service_type'] ?? '';
        $laborCost = $_POST['labor_cost'] ?? 0;
        $replacedParts = $_POST['replaced_parts'] ?? '';
        $repairNotes = $_POST['repair_notes'] ?? '';
        $warrantyInfo = $_POST['warranty_info'] ?? '';
        
        if (empty($vehicleId) || empty($repairDate) || empty($mechanicId) || empty($serviceType) || empty($repairNotes)) {
            return $this->error('Missing required fields');
        }
        
        try {
            // Generate record ID
            $recordId = 'SRV-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $this->conn->beginTransaction();
            
            // Insert service record
            $query = "INSERT INTO service_repair 
                     (record_id, vehicle_id, repair_date, mechanic_id, service_type, labor_cost, 
                      replaced_parts, repair_notes, warranty_info, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $recordId);
            $stmt->bindParam(2, $vehicleId);
            $stmt->bindParam(3, $repairDate);
            $stmt->bindParam(4, $mechanicId);
            $stmt->bindParam(5, $serviceType);
            $stmt->bindParam(6, $laborCost);
            $stmt->bindParam(7, $replacedParts);
            $stmt->bindParam(8, $repairNotes);
            $stmt->bindParam(9, $warrantyInfo);
            
            if ($stmt->execute()) {
                // Integration with MRO - log to vehicle permanent history
                $this->updateVehicleHistory($vehicleId, $recordId);
                
                // Integration with Document Tracking - store receipts
                $this->processDocuments($recordId);
                
                $this->conn->commit();
                return $this->success(['record_id' => $recordId], 'Service record added successfully');
            } else {
                $this->conn->rollback();
                return $this->error('Failed to add service record');
            }
        } catch (PDOException $e) {
            $this->conn->rollback();
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function attachInvoice() {
        $recordId = $_POST['record_id'] ?? '';
        $invoiceNumber = $_POST['invoice_number'] ?? '';
        $invoiceDate = $_POST['invoice_date'] ?? '';
        $invoiceAmount = $_POST['invoice_amount'] ?? 0;
        
        if (empty($recordId) || empty($invoiceNumber) || empty($invoiceDate)) {
            return $this->error('Missing required fields');
        }
        
        try {
            $query = "INSERT INTO service_invoices 
                     (record_id, invoice_number, invoice_date, invoice_amount, created_at) 
                     VALUES (?, ?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE 
                     invoice_number = VALUES(invoice_number),
                     invoice_date = VALUES(invoice_date),
                     invoice_amount = VALUES(invoice_amount)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $recordId);
            $stmt->bindParam(2, $invoiceNumber);
            $stmt->bindParam(3, $invoiceDate);
            $stmt->bindParam(4, $invoiceAmount);
            
            if ($stmt->execute()) {
                return $this->success(null, 'Invoice attached successfully');
            } else {
                return $this->error('Failed to attach invoice');
            }
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function deleteRecord($recordId) {
        try {
            $this->conn->beginTransaction();
            
            // Delete related invoices first
            $deleteInvoices = "DELETE FROM service_invoices WHERE record_id = ?";
            $invoiceStmt = $this->conn->prepare($deleteInvoices);
            $invoiceStmt->bindParam(1, $recordId);
            $invoiceStmt->execute();
            
            // Delete the service record
            $query = "DELETE FROM service_repair WHERE record_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $recordId);
            
            if ($stmt->execute()) {
                $this->conn->commit();
                return $this->success(null, 'Service record deleted successfully');
            } else {
                $this->conn->rollback();
                return $this->error('Failed to delete service record');
            }
        } catch (PDOException $e) {
            $this->conn->rollback();
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function updateVehicleHistory($vehicleId, $recordId) {
        // Integration with MRO - log closed REPAIR STATUS to vehicle's permanent history
        $query = "INSERT INTO vehicle_permanent_history 
                 (vehicle_id, record_id, record_type, status, created_at) 
                 VALUES (?, ?, 'service_repair', 'completed', NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $vehicleId);
        $stmt->bindParam(2, $recordId);
        $stmt->execute();
    }
    
    private function processDocuments($recordId) {
        // Integration with Document Tracking - store physical/digital repair receipts and warranties
        // In a real implementation, this would:
        // 1. Send DOCU REQ for missing insurance papers
        // 2. Store uploaded documents in the document tracking system
        // 3. Update document status to COMPLIANCE DOCS when received
        
        $documentData = [
            'record_id' => $recordId,
            'document_type' => 'service_receipt',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Log document processing
        error_log('Processing documents for service record: ' . json_encode($documentData));
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
$api = new ServiceRepairAPI();
echo $api->handleRequest();
?>

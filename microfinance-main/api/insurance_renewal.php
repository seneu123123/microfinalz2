<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

class InsuranceRenewalAPI {
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
                return $this->getAllPolicies();
                
            case 'POST':
                switch ($action) {
                    case 'create':
                        return $this->createPolicy();
                    case 'renew':
                        return $this->renewPolicy();
                    case 'set_alert':
                        return $this->setAlert();
                    case 'upload_document':
                        return $this->uploadDocument();
                    case 'delete':
                        return $this->deletePolicy();
                    default:
                        return $this->error('Invalid action');
                }
                
            default:
                return $this->error('Method not allowed');
        }
    }
    
    private function getAllPolicies() {
        try {
            $query = "SELECT ip.*, v.make, v.model 
                     FROM insurance_policies ip 
                     LEFT JOIN vehicle_inventory v ON ip.vehicle_id = v.vehicle_id 
                     ORDER BY ip.expiry_date ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $policies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->success($policies);
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function createPolicy() {
        $vehicleId = $_POST['vehicle_id'] ?? '';
        $policyNumber = $_POST['policy_number'] ?? '';
        $provider = $_POST['provider'] ?? '';
        $policyType = $_POST['policy_type'] ?? '';
        $expiryDate = $_POST['expiry_date'] ?? '';
        $renewalCost = $_POST['renewal_cost'] ?? 0;
        $premiumAmount = $_POST['premium_amount'] ?? 0;
        $coverageDetails = $_POST['coverage_details'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if (empty($vehicleId) || empty($policyNumber) || empty($provider) || empty($policyType) || empty($expiryDate)) {
            return $this->error('Missing required fields');
        }
        
        try {
            // Generate policy ID
            $policyId = 'INS-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $this->conn->beginTransaction();
            
            $query = "INSERT INTO insurance_policies 
                     (policy_id, vehicle_id, policy_number, provider, policy_type, expiry_date, 
                      renewal_cost, premium_amount, coverage_details, notes, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $policyId);
            $stmt->bindParam(2, $vehicleId);
            $stmt->bindParam(3, $policyNumber);
            $stmt->bindParam(4, $provider);
            $stmt->bindParam(5, $policyType);
            $stmt->bindParam(6, $expiryDate);
            $stmt->bindParam(7, $renewalCost);
            $stmt->bindParam(8, $premiumAmount);
            $stmt->bindParam(9, $coverageDetails);
            $stmt->bindParam(10, $notes);
            
            if ($stmt->execute()) {
                // Integration with Document Tracking - send DOCU REQ for missing insurance papers
                $this->requestDocuments($policyId);
                
                // Integration with Audit Management - trigger FLEET CHECK REQ if nearing expiry
                $this->checkExpiryAlerts($policyId);
                
                $this->conn->commit();
                return $this->success(['policy_id' => $policyId], 'Insurance policy added successfully');
            } else {
                $this->conn->rollback();
                return $this->error('Failed to add insurance policy');
            }
        } catch (PDOException $e) {
            $this->conn->rollback();
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function renewPolicy() {
        $policyId = $_POST['policy_id'] ?? '';
        $newExpiryDate = $_POST['new_expiry_date'] ?? '';
        $renewalCost = $_POST['renewal_cost'] ?? 0;
        $newPremiumAmount = $_POST['new_premium_amount'] ?? 0;
        $renewalNotes = $_POST['renewal_notes'] ?? '';
        
        if (empty($policyId) || empty($newExpiryDate)) {
            return $this->error('Missing required fields');
        }
        
        try {
            $this->conn->beginTransaction();
            
            // Update policy with new expiry date and costs
            $query = "UPDATE insurance_policies 
                     SET expiry_date = ?, renewal_cost = ?, premium_amount = ?, 
                         renewal_notes = ?, status = 'active', renewed_at = NOW() 
                     WHERE policy_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $newExpiryDate);
            $stmt->bindParam(2, $renewalCost);
            $stmt->bindParam(3, $newPremiumAmount);
            $stmt->bindParam(4, $renewalNotes);
            $stmt->bindParam(5, $policyId);
            
            if ($stmt->execute()) {
                // Log renewal history
                $this->logRenewal($policyId, $renewalCost, $newPremiumAmount, $renewalNotes);
                
                // Update expiry alerts
                $this->checkExpiryAlerts($policyId);
                
                $this->conn->commit();
                return $this->success(null, 'Policy renewed successfully');
            } else {
                $this->conn->rollback();
                return $this->error('Failed to renew policy');
            }
        } catch (PDOException $e) {
            $this->conn->rollback();
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function setAlert() {
        $policyId = $_POST['policy_id'] ?? '';
        $alertDays = $_POST['alert_days'] ?? '';
        $alertType = $_POST['alert_type'] ?? '';
        $alertRecipients = $_POST['alert_recipients'] ?? '';
        $alertMessage = $_POST['alert_message'] ?? '';
        
        if (empty($policyId) || empty($alertDays) || empty($alertType)) {
            return $this->error('Missing required fields');
        }
        
        try {
            $query = "INSERT INTO insurance_alerts 
                     (policy_id, alert_days, alert_type, alert_recipients, alert_message, created_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE 
                     alert_days = VALUES(alert_days),
                     alert_type = VALUES(alert_type),
                     alert_recipients = VALUES(alert_recipients),
                     alert_message = VALUES(alert_message),
                     updated_at = NOW()";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $policyId);
            $stmt->bindParam(2, $alertDays);
            $stmt->bindParam(3, $alertType);
            $stmt->bindParam(4, $alertRecipients);
            $stmt->bindParam(5, $alertMessage);
            
            if ($stmt->execute()) {
                return $this->success(null, 'Alert set successfully');
            } else {
                return $this->error('Failed to set alert');
            }
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function uploadDocument() {
        $policyId = $_POST['policy_id'] ?? '';
        $documentType = $_POST['document_type'] ?? '';
        $documentDescription = $_POST['document_description'] ?? '';
        
        if (empty($policyId) || empty($documentType)) {
            return $this->error('Missing required fields');
        }
        
        try {
            // In a real implementation, handle file upload here
            $documentPath = $this->handleFileUpload();
            
            $query = "INSERT INTO insurance_documents 
                     (policy_id, document_type, document_description, document_path, status, created_at) 
                     VALUES (?, ?, ?, ?, 'uploaded', NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $policyId);
            $stmt->bindParam(2, $documentType);
            $stmt->bindParam(3, $documentDescription);
            $stmt->bindParam(4, $documentPath);
            
            if ($stmt->execute()) {
                // Update document status to COMPLIANCE DOCS
                $this->updateDocumentStatus($policyId, 'compliance_docs');
                
                return $this->success(null, 'Document uploaded successfully');
            } else {
                return $this->error('Failed to upload document');
            }
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function deletePolicy($policyId) {
        try {
            $this->conn->beginTransaction();
            
            // Delete related documents
            $deleteDocs = "DELETE FROM insurance_documents WHERE policy_id = ?";
            $docsStmt = $this->conn->prepare($deleteDocs);
            $docsStmt->bindParam(1, $policyId);
            $docsStmt->execute();
            
            // Delete related alerts
            $deleteAlerts = "DELETE FROM insurance_alerts WHERE policy_id = ?";
            $alertsStmt = $this->conn->prepare($deleteAlerts);
            $alertsStmt->bindParam(1, $policyId);
            $alertsStmt->execute();
            
            // Delete the policy
            $query = "DELETE FROM insurance_policies WHERE policy_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $policyId);
            
            if ($stmt->execute()) {
                $this->conn->commit();
                return $this->success(null, 'Insurance policy deleted successfully');
            } else {
                $this->conn->rollback();
                return $this->error('Failed to delete insurance policy');
            }
        } catch (PDOException $e) {
            $this->conn->rollback();
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function requestDocuments($policyId) {
        // Integration with Document Tracking - send DOCU REQ for missing insurance papers
        $documentRequest = [
            'policy_id' => $policyId,
            'request_type' => 'docu_req',
            'document_types' => ['policy_certificate', 'insurance_card'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // In a real implementation, this would call the Document Tracking API
        error_log('Document request sent: ' . json_encode($documentRequest));
    }
    
    private function checkExpiryAlerts($policyId) {
        // Integration with Audit Management - trigger FLEET CHECK REQ if insurance or registration is nearing expiry
        $query = "SELECT expiry_date FROM insurance_policies WHERE policy_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $policyId);
        $stmt->execute();
        
        $expiryDate = $stmt->fetchColumn();
        
        if ($expiryDate) {
            $daysUntilExpiry = $this->calculateDaysUntilExpiry($expiryDate);
            
            if ($daysUntilExpiry <= 30) {
                // Trigger FLEET CHECK REQ
                $this->triggerFleetCheck($policyId, $expiryDate, $daysUntilExpiry);
            }
        }
    }
    
    private function triggerFleetCheck($policyId, $expiryDate, $daysUntilExpiry) {
        // Integration with Audit Management
        $fleetCheck = [
            'policy_id' => $policyId,
            'check_type' => 'fleet_check_req',
            'expiry_date' => $expiryDate,
            'days_until_expiry' => $daysUntilExpiry,
            'priority' => $daysUntilExpiry <= 7 ? 'urgent' : 'normal',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // In a real implementation, this would call the Audit Management API
        error_log('Fleet check request triggered: ' . json_encode($fleetCheck));
    }
    
    private function logRenewal($policyId, $renewalCost, $premiumAmount, $notes) {
        $query = "INSERT INTO insurance_renewal_history 
                 (policy_id, renewal_cost, new_premium_amount, renewal_notes, created_at) 
                 VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $policyId);
        $stmt->bindParam(2, $renewalCost);
        $stmt->bindParam(3, $premiumAmount);
        $stmt->bindParam(4, $notes);
        $stmt->execute();
    }
    
    private function updateDocumentStatus($policyId, $status) {
        // Update document status to COMPLIANCE DOCS
        $query = "UPDATE insurance_documents SET status = ? WHERE policy_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->bindParam(2, $policyId);
        $stmt->execute();
    }
    
    private function handleFileUpload() {
        // In a real implementation, handle actual file upload
        // For now, return a placeholder path
        return '/uploads/insurance/' . date('Y/m/d/') . 'document_' . time() . '.pdf';
    }
    
    private function calculateDaysUntilExpiry($expiryDate) {
        $today = new Date();
        $expiry = new Date($expiryDate);
        $diff = $expiry - $today;
        return ceil($diff / (1000 * 60 * 60 * 24));
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
$api = new InsuranceRenewalAPI();
echo $api->handleRequest();
?>

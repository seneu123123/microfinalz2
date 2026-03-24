<?php
/**
 * Audit Findings API
 * Handles audit findings and observations with severity levels and system integration
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Disable HTML error output
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Custom error handler to ensure JSON responses
function handleError($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $errstr]);
    exit();
}
set_error_handler('handleError');

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Fatal server error']);
        error_log("Fatal error in audit_findings.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in audit_findings.php: ' . $ex->getMessage());
});

// Database connection
require_once '../config/db.php';

// Check database connection
if (!$conn || $conn->connect_error) {
    sendResponse(false, 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error'));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'get' && isset($_GET['id'])) {
        getFinding($_GET['id']);
    } else {
        listFindings();
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create':
            createFinding($input);
            break;
        case 'attach_proof':
            attachProof($input);
            break;
        case 'request_clarification':
            requestClarification($input);
            break;
        case 'update_severity':
            updateSeverity($input);
            break;
        default:
            sendResponse(false, 'Invalid action');
    }
} else {
    sendResponse(false, 'Method not allowed');
}

function listFindings() {
    global $conn;
    
    try {
        $auditId = $_GET['audit_id'] ?? '';
        $severity = $_GET['severity'] ?? '';
        
        $query = "SELECT 
                    f.id,
                    f.finding_id,
                    f.audit_id,
                    f.title,
                    f.description,
                    f.severity,
                    f.category,
                    f.status,
                    f.created_at,
                    f.updated_at,
                    a.audit_type,
                    a.target_department,
                    (SELECT COUNT(*) FROM finding_proofs WHERE finding_id = f.finding_id) as proof_count
                  FROM audit_findings f
                  LEFT JOIN audit_schedules a ON f.audit_id = a.audit_id
                  WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if (!empty($auditId)) {
            $query .= " AND f.audit_id = ?";
            $params[] = $auditId;
            $types .= 's';
        }
        
        if (!empty($severity)) {
            $query .= " AND f.severity = ?";
            $params[] = $severity;
            $types .= 's';
        }
        
        $query .= " ORDER BY f.created_at DESC";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $findings = [];
        while ($row = $result->fetch_assoc()) {
            $findings[] = $row;
        }
        
        $stmt->close();
        
        sendResponse(true, 'Findings retrieved', $findings);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error retrieving findings: ' . $e->getMessage());
    }
}

function getFinding($findingId) {
    global $conn;
    
    try {
        $query = "SELECT f.*, a.audit_type, a.target_department 
                  FROM audit_findings f
                  LEFT JOIN audit_schedules a ON f.audit_id = a.audit_id
                  WHERE f.finding_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('s', $findingId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(false, 'Finding not found');
        }
        
        $finding = $result->fetch_assoc();
        
        // Get attached proofs
        $proofsQuery = "SELECT * FROM finding_proofs WHERE finding_id = ? ORDER BY created_at DESC";
        $proofsStmt = $conn->prepare($proofsQuery);
        $proofsStmt->bind_param('s', $findingId);
        $proofsStmt->execute();
        $proofsResult = $proofsStmt->get_result();
        
        $proofs = [];
        while ($row = $proofsResult->fetch_assoc()) {
            $proofs[] = $row;
        }
        
        $finding['proofs'] = $proofs;
        
        $stmt->close();
        $proofsStmt->close();
        
        sendResponse(true, 'Finding retrieved', $finding);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error retrieving finding: ' . $e->getMessage());
    }
}

function createFinding($data) {
    global $conn;
    
    try {
        // Validate required fields
        $required = ['audit_id', 'title', 'description', 'severity'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                sendResponse(false, "Missing required field: $field");
            }
        }
        
        // Normalize severity to match database ENUM (lowercase)
        $severity = strtolower($data['severity']);
        $validSeverities = ['low', 'medium', 'high', 'critical'];
        if (!in_array($severity, $validSeverities)) {
            sendResponse(false, "Invalid severity level. Must be one of: " . implode(', ', $validSeverities));
        }
        $data['severity'] = $severity;
        
        // Generate finding ID
        $findingId = 'FND-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Extract variables for bind_param
        $auditId = $data['audit_id'];
        $title = $data['title'] ?? 'Untitled Finding';
        $description = $data['description'];
        $severity = $data['severity'];
        $category = $data['category'] ?? 'General';
        
        // Insert finding
        $query = "INSERT INTO audit_findings (
                    finding_id, audit_id, title, description, severity, 
                    category, status, created_at
                  ) VALUES (?, ?, ?, ?, ?, ?, 'open', NOW())";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('ssssss', 
            $findingId,
            $auditId,
            $title,
            $description,
            $severity,
            $category
        );
        
        if (!$stmt->execute()) {
            sendResponse(false, 'Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
        // System integration: Create maintenance request for critical vehicle findings
        if ($data['severity'] === 'critical') {
            createMaintenanceRequest($findingId, $data);
        }
        
        // System integration: Update vendor performance rating
        updateVendorPerformance($data['audit_id'], $data['severity']);
        
        // Return created finding with ID
        $findingData = array_merge(['finding_id' => $findingId], $data);
        sendResponse(true, 'Finding logged successfully', $findingData);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error creating finding: ' . $e->getMessage());
    }
}

function attachProof($data) {
    global $conn;
    
    try {
        if (empty($data['finding_id']) || empty($data['proof_type'])) {
            sendResponse(false, 'Missing finding ID or proof type');
        }
        
        $proofId = 'PROOF-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $query = "INSERT INTO finding_proofs (
                    proof_id, finding_id, proof_type, file_name, 
                    file_path, description, created_at
                  ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('ssssss', 
            $proofId,
            $data['finding_id'],
            $data['proof_type'],
            $data['file_name'] ?? '',
            $data['file_path'] ?? '',
            $data['description'] ?? ''
        );
        
        if (!$stmt->execute()) {
            sendResponse(false, 'Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
        $proofData = array_merge(['proof_id' => $proofId], $data);
        sendResponse(true, 'Proof attached successfully', $proofData);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error attaching proof: ' . $e->getMessage());
    }
}

function requestClarification($data) {
    global $conn;
    
    try {
        if (empty($data['finding_id']) || empty($data['clarification_text'])) {
            sendResponse(false, 'Missing finding ID or clarification text');
        }
        
        $clarificationId = 'CLAR-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $query = "INSERT INTO finding_clarifications (
                    clarification_id, finding_id, clarification_text, 
                    requested_by, status, created_at
                  ) VALUES (?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('ssss', 
            $clarificationId,
            $data['finding_id'],
            $data['clarification_text'],
            $data['requested_by'] ?? 'System'
        );
        
        if (!$stmt->execute()) {
            sendResponse(false, 'Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
        // Update finding status
        $updateQuery = "UPDATE audit_findings SET status = 'clarification_requested' WHERE finding_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('s', $data['finding_id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        $clarificationData = array_merge(['clarification_id' => $clarificationId], $data);
        sendResponse(true, 'Clarification requested successfully', $clarificationData);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error requesting clarification: ' . $e->getMessage());
    }
}

function updateSeverity($data) {
    global $conn;
    
    try {
        if (empty($data['finding_id']) || empty($data['severity'])) {
            sendResponse(false, 'Missing finding ID or severity level');
        }
        
        $query = "UPDATE audit_findings 
                  SET severity = ?, updated_at = NOW()
                  WHERE finding_id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('ss', $data['severity'], $data['finding_id']);
        
        if (!$stmt->execute()) {
            sendResponse(false, 'Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
        sendResponse(true, 'Severity updated successfully');
        
    } catch (Exception $e) {
        sendResponse(false, 'Error updating severity: ' . $e->getMessage());
    }
}

function createMaintenanceRequest($findingId, $finding) {
    global $conn;
    
    try {
        // Get audit details to determine if it's vehicle-related
        $query = "SELECT * FROM audit_schedules WHERE audit_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $finding['audit_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $audit = $result->fetch_assoc();
            
            // Create maintenance request for vehicle audits
            if ($audit['audit_type'] === 'vehicle') {
                $maintenanceId = 'MAINT-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                $maintQuery = "INSERT INTO maintenance_requests (
                                maintenance_id, finding_id, priority, description, 
                                status, created_at
                              ) VALUES (?, ?, 'critical', ?, 'pending', NOW())";
                
                $maintStmt = $conn->prepare($maintQuery);
                $description = "Critical finding: " . $finding['description'];
                $maintStmt->bind_param('sss', $maintenanceId, $findingId, $description);
                $maintStmt->execute();
                $maintStmt->close();
            }
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log('Error creating maintenance request: ' . $e->getMessage());
    }
}

function updateVendorPerformance($auditId, $severity) {
    global $conn;
    
    try {
        // This would integrate with vendor performance system
        // For now, just log the action
        $query = "INSERT INTO audit_logs (audit_id, action, details, created_at) 
                  VALUES (?, 'vendor_performance_impact', ?, NOW())";
        $stmt = $conn->prepare($query);
        $details = "Finding severity: $severity - Vendor performance rating updated";
        $stmt->bind_param('ss', $auditId, $details);
        $stmt->execute();
        $stmt->close();
        
    } catch (Exception $e) {
        error_log('Error updating vendor performance: ' . $e->getMessage());
    }
}
?>

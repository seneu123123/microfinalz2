<?php
/**
 * Audit Findings API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

ini_set('display_errors', '0');
error_reporting(E_ALL);

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Server error']);
        error_log("Fatal error in audit-findings.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in audit-findings.php: ' . $ex->getMessage());
});

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Handle different endpoints
    if (strpos($path, '/attach-evidence') !== false) {
        handleAttachEvidence();
        return;
    } elseif (strpos($path, '/update-severity') !== false) {
        handleUpdateSeverity();
        return;
    }
    
    switch ($method) {
        case 'GET':
            getAuditFindings();
            break;
        case 'POST':
            createAuditFinding();
            break;
        case 'PUT':
            updateAuditFinding();
            break;
        case 'DELETE':
            deleteAuditFinding();
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Error in audit-findings.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

function getAuditFindings() {
    global $conn;
    
    try {
        $sql = "SELECT * FROM audit_findings ORDER BY date_identified DESC";
        $result = $conn->query($sql);
        
        $findings = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $findings[] = $row;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $findings
        ]);
    } catch (Exception $e) {
        error_log("Error fetching audit findings: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching audit findings']);
    }
}

function createAuditFinding() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }
        
        // Generate finding ID
        $finding_id = 'FND-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO audit_findings (
            finding_id, audit_id, category, severity, date_identified,
            department, description, recommendation, evidence_count, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
            return;
        }
        
        $stmt->bind_param(
            "ssssssss",
            $finding_id,
            $data['audit_id'],
            $data['category'],
            $data['severity'],
            $data['date_identified'],
            $data['department'],
            $data['description'],
            $data['recommendation']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Audit finding created successfully',
                'data' => ['finding_id' => $finding_id]
            ]);
        } else {
            error_log("Execute failed: " . $stmt->error);
            echo json_encode(['success' => false, 'message' => 'Failed to create audit finding: ' . $stmt->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error creating audit finding: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error creating audit finding']);
    }
}

function updateAuditFinding() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['finding_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data or missing finding_id']);
            return;
        }
        
        $sql = "UPDATE audit_findings SET 
            category = ?, severity = ?, department = ?, description = ?,
            recommendation = ?, updated_at = NOW()
            WHERE finding_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssss",
            $data['category'],
            $data['severity'],
            $data['department'],
            $data['description'],
            $data['recommendation'],
            $data['finding_id']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Audit finding updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update audit finding']);
        }
    } catch (Exception $e) {
        error_log("Error updating audit finding: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error updating audit finding']);
    }
}

function deleteAuditFinding() {
    global $conn;
    
    try {
        $finding_id = $_GET['finding_id'] ?? '';
        
        if (empty($finding_id)) {
            echo json_encode(['success' => false, 'message' => 'Missing finding_id']);
            return;
        }
        
        $sql = "DELETE FROM audit_findings WHERE finding_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $finding_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Audit finding deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete audit finding']);
        }
    } catch (Exception $e) {
        error_log("Error deleting audit finding: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error deleting audit finding']);
    }
}

function handleAttachEvidence() {
    global $conn;
    
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $finding_id = $_POST['finding_id'] ?? '';
        $evidence_type = $_POST['evidence_type'] ?? '';
        $description = $_POST['description'] ?? '';
        
        if (empty($finding_id) || empty($evidence_type)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        // Handle file upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'File upload failed']);
            return;
        }
        
        $file = $_FILES['file'];
        $upload_dir = '../uploads/evidence/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = uniqid() . '_' . basename($file['name']);
        $filepath = $upload_dir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save file']);
            return;
        }
        
        // Save evidence record
        $evidence_id = 'EVD-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $sql = "INSERT INTO audit_evidence (
            evidence_id, finding_id, evidence_type, file_path, 
            description, uploaded_at
        ) VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssss",
            $evidence_id,
            $finding_id,
            $evidence_type,
            $filepath,
            $description
        );
        
        if ($stmt->execute()) {
            // Update evidence count in findings table
            $update_sql = "UPDATE audit_findings SET evidence_count = evidence_count + 1 WHERE finding_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("s", $finding_id);
            $update_stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Evidence attached successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save evidence record']);
        }
    } catch (Exception $e) {
        error_log("Error attaching evidence: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error attaching evidence']);
    }
}

function handleUpdateSeverity() {
    global $conn;
    
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['finding_id']) || !isset($data['new_severity'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        $sql = "UPDATE audit_findings SET 
            severity = ?, severity_reason = ?, updated_at = NOW()
            WHERE finding_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sss",
            $data['new_severity'],
            $data['reason'],
            $data['finding_id']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Severity updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update severity']);
        }
    } catch (Exception $e) {
        error_log("Error updating severity: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error updating severity']);
    }
}
?>

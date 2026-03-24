<?php
/**
 * Audit Reporting API
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
        error_log("Fatal error in audit-reporting.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in audit-reporting.php: ' . $ex->getMessage());
});

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Handle different endpoints
    if (strpos($path, '/generate') !== false) {
        handleGenerateReport();
        return;
    } elseif (strpos($path, '/export-pdf') !== false) {
        handleExportPDF();
        return;
    } elseif (strpos($path, '/archive') !== false) {
        handleArchive();
        return;
    }
    
    switch ($method) {
        case 'GET':
            getAuditReports();
            break;
        case 'POST':
            createAuditReport();
            break;
        case 'PUT':
            updateAuditReport();
            break;
        case 'DELETE':
            deleteAuditReport();
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Error in audit-reporting.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

function getAuditReports() {
    global $conn;
    
    try {
        $sql = "SELECT * FROM audit_reports ORDER BY generated_date DESC";
        $result = $conn->query($sql);
        
        $reports = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $reports[] = $row;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $reports
        ]);
    } catch (Exception $e) {
        error_log("Error fetching audit reports: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error fetching audit reports']);
    }
}

function createAuditReport() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }
        
        // Generate report ID
        $report_id = 'RPT-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO audit_reports (
            report_id, report_type, audit_period, department, start_date,
            end_date, report_description, include_sections, status, generated_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Draft', NOW())";
        
        $sections_json = json_encode($data['include_sections']);
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
            return;
        }
        
        $stmt->bind_param(
            "ssssssss",
            $report_id,
            $data['report_type'],
            $data['audit_period'],
            $data['department'],
            $data['start_date'],
            $data['end_date'],
            $data['report_description'],
            $sections_json
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Audit report created successfully',
                'data' => ['report_id' => $report_id]
            ]);
        } else {
            error_log("Execute failed: " . $stmt->error);
            echo json_encode(['success' => false, 'message' => 'Failed to create audit report: ' . $stmt->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error creating audit report: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error creating audit report']);
    }
}

function updateAuditReport() {
    global $conn;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['report_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data or missing report_id']);
            return;
        }
        
        $sql = "UPDATE audit_reports SET 
            report_type = ?, audit_period = ?, department = ?, start_date = ?,
            end_date = ?, report_description = ?, include_sections = ?, updated_at = NOW()
            WHERE report_id = ?";
        
        $sections_json = json_encode($data['include_sections']);
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssss",
            $data['report_type'],
            $data['audit_period'],
            $data['department'],
            $data['start_date'],
            $data['end_date'],
            $data['report_description'],
            $sections_json,
            $data['report_id']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Audit report updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update audit report']);
        }
    } catch (Exception $e) {
        error_log("Error updating audit report: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error updating audit report']);
    }
}

function deleteAuditReport() {
    global $conn;
    
    try {
        $report_id = $_GET['report_id'] ?? '';
        
        if (empty($report_id)) {
            echo json_encode(['success' => false, 'message' => 'Missing report_id']);
            return;
        }
        
        $sql = "DELETE FROM audit_reports WHERE report_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $report_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Audit report deleted successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete audit report']);
        }
    } catch (Exception $e) {
        error_log("Error deleting audit report: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error deleting audit report']);
    }
}

function handleGenerateReport() {
    global $conn;
    
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['report_id']) || !isset($data['report_format'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        // Update report status to Generated
        $sql = "UPDATE audit_reports SET 
            status = 'Generated', report_format = ?, additional_notes = ?, generated_at = NOW()
            WHERE report_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sss",
            $data['report_format'],
            $data['additional_notes'],
            $data['report_id']
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Report generated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to generate report']);
        }
    } catch (Exception $e) {
        error_log("Error generating report: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error generating report']);
    }
}

function handleExportPDF() {
    global $conn;
    
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['report_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing report_id']);
            return;
        }
        
        // Generate PDF file (simplified version)
        $report_id = $data['report_id'];
        $pdf_filename = $report_id . '_report.pdf';
        $pdf_path = '../uploads/reports/' . $pdf_filename;
        
        // Create uploads directory if it doesn't exist
        if (!is_dir('../uploads/reports/')) {
            mkdir('../uploads/reports/', 0777, true);
        }
        
        // For now, create a simple text file as placeholder for PDF
        $content = "Audit Report - $report_id\nGenerated on: " . date('Y-m-d H:i:s') . "\n";
        file_put_contents($pdf_path . '.txt', $content);
        
        // Update export info
        $sql = "UPDATE audit_reports SET 
            pdf_options = ?, watermark = ?, email_to = ?, exported_at = NOW()
            WHERE report_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssss",
            $data['pdf_options'],
            $data['watermark'],
            $data['email_to'],
            $report_id
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'PDF exported successfully',
                'data' => ['download_url' => '../uploads/reports/' . $pdf_filename . '.txt']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to export PDF']);
        }
    } catch (Exception $e) {
        error_log("Error exporting PDF: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error exporting PDF']);
    }
}

function handleArchive() {
    global $conn;
    
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['report_id']) || !isset($data['document_category'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        // Create document record in document tracking
        $document_id = 'DOC-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO archived_documents (
            document_id, report_id, document_category, retention_period,
            access_level, archive_notes, archived_at, archived_by
        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'System')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssss",
            $document_id,
            $data['report_id'],
            $data['document_category'],
            $data['retention_period'],
            $data['access_level'],
            $data['archive_notes']
        );
        
        if ($stmt->execute()) {
            // Update report status to Archived
            $update_sql = "UPDATE audit_reports SET status = 'Archived', archived_at = NOW() WHERE report_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("s", $data['report_id']);
            $update_stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Document archived successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to archive document']);
        }
    } catch (Exception $e) {
        error_log("Error archiving document: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error archiving document']);
    }
}
?>

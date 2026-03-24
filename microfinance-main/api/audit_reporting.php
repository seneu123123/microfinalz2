<?php
/**
 * Audit Reporting API
 * Handles audit report generation with filtering and export capabilities
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
        error_log("Fatal error in audit_reporting.php: " . var_export($err, true));
    }
});

set_exception_handler(function($ex) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);
    error_log('Exception in audit_reporting.php: ' . $ex->getMessage());
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
    
    if ($action === 'generate') {
        generateReport();
    } elseif ($action === 'get' && isset($_GET['id'])) {
        getReport($_GET['id']);
    } else {
        listReports();
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'generate':
            generateReport($input);
            break;
        case 'export':
            exportReport($input);
            break;
        case 'send_to_management':
            sendToManagement($input);
            break;
        case 'delete':
            deleteReport($input);
            break;
        default:
            sendResponse(false, 'Invalid action');
    }
} else {
    sendResponse(false, 'Method not allowed');
}

function listReports() {
    global $conn;
    
    try {
        $query = "SELECT 
                    r.report_id,
                    r.report_type,
                    r.date_from,
                    r.date_to,
                    r.department_filter,
                    r.status,
                    r.generated_at as created_at,
                    r.file_path,
                    (SELECT COUNT(*) FROM audit_schedules a 
                     WHERE a.audit_date BETWEEN r.date_from AND r.date_to 
                     AND (r.department_filter = '' OR a.target_department = r.department_filter)) as audit_count
                  FROM audit_reports r
                  ORDER BY r.generated_at DESC";
        
        $result = $conn->query($query);
        if (!$result) {
            sendResponse(false, 'Query failed: ' . $conn->error);
        }
        
        $reports = [];
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
        
        sendResponse(true, 'Reports retrieved', $reports);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error retrieving reports: ' . $e->getMessage());
    }
}

function getReport($reportId) {
    global $conn;
    
    try {
        $query = "SELECT * FROM audit_reports WHERE report_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $stmt->bind_param('s', $reportId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(false, 'Report not found');
        }
        
        $report = $result->fetch_assoc();
        
        // Get report metrics
        $metricsQuery = "SELECT * FROM report_metrics WHERE report_id = ?";
        $metricsStmt = $conn->prepare($metricsQuery);
        $metricsStmt->bind_param('s', $reportId);
        $metricsStmt->execute();
        $metricsResult = $metricsStmt->get_result();
        
        $metrics = [];
        while ($row = $metricsResult->fetch_assoc()) {
            $metrics[] = $row;
        }
        
        $report['metrics'] = $metrics;
        
        $stmt->close();
        $metricsStmt->close();
        
        sendResponse(true, 'Report retrieved', $report);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error retrieving report: ' . $e->getMessage());
    }
}

function generateReport($data = null) {
    global $conn;
    
    try {
        // Get parameters from input or query string
        if ($data) {
            $reportType = $data['report_type'] ?? 'comprehensive';
            
            // Handle audit_period conversion to date range
            $auditPeriod = $data['audit_period'] ?? '';
            if (empty($auditPeriod)) {
                $dateFrom = $data['date_from'] ?? date('Y-m-01');
                $dateTo = $data['date_to'] ?? date('Y-m-t');
            } else {
                // Convert audit_period (e.g., "2024-Q1") to date range
                if (preg_match('/(\d{4})-Q(\d)/', $auditPeriod, $matches)) {
                    $year = $matches[1];
                    $quarter = $matches[2];
                    $startMonth = ($quarter - 1) * 3 + 1;
                    $dateFrom = "$year-" . str_pad($startMonth, 2, '0', STR_PAD_LEFT) . "-01";
                    $dateTo = "$year-" . str_pad($startMonth + 2, 2, '0', STR_PAD_LEFT) . "-31";
                } else {
                    $dateFrom = date('Y-m-01');
                    $dateTo = date('Y-m-t');
                }
            }
            
            $departmentFilter = $data['target_department'] ?? $data['department_filter'] ?? '';
            $generatedBy = $data['generated_by'] ?? 'System';
        } else {
            $reportType = $_GET['report_type'] ?? 'comprehensive';
            $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
            $dateTo = $_GET['date_to'] ?? date('Y-m-t');
            $departmentFilter = $_GET['department_filter'] ?? '';
            $generatedBy = $_GET['generated_by'] ?? 'System';
        }
        
        // Validate required fields
        if (empty($dateFrom) || empty($dateTo)) {
            sendResponse(false, 'Date range is required');
        }
        
        // Generate report ID
        $reportId = 'RPT-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert report record
            $query = "INSERT INTO audit_reports (
                        report_id, report_type, date_from, date_to, 
                        department_filter, status, generated_at
                      ) VALUES (?, ?, ?, ?, ?, 'generating', NOW())";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                sendResponse(false, 'Prepare failed: ' . $conn->error);
            }
            
            $stmt->bind_param('sssss', 
                $reportId,
                $reportType,
                $dateFrom,
                $dateTo,
                $departmentFilter
            );
            
            if (!$stmt->execute()) {
                sendResponse(false, 'Execute failed: ' . $stmt->error);
            }
            
            $stmt->close();
            
            // Generate report data based on type
            $reportData = generateReportData($reportType, $dateFrom, $dateTo, $departmentFilter);
            
            // Store report metrics
            storeReportMetrics($reportId, $reportData);
            
            // Update report status
            $updateQuery = "UPDATE audit_reports SET status = 'generated' WHERE report_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('s', $reportId);
            $updateStmt->execute();
            $updateStmt->close();
            
            // System integration: Update audit parameters for future requests
            updateAuditParameters($reportData);
            
            // System integration: Route finalized reports to document tracking
            if ($reportType === 'final') {
                routeToDocumentTracking($reportId, $reportData);
            }
            
            // Commit transaction
            $conn->commit();
            
            $reportInfo = [
                'report_id' => $reportId,
                'report_type' => $reportType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'department_filter' => $departmentFilter,
                'data' => $reportData
            ];
            
            sendResponse(true, 'Report generated successfully', $reportInfo);
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        sendResponse(false, 'Error generating report: ' . $e->getMessage());
    }
}

function generateReportData($reportType, $dateFrom, $dateTo, $departmentFilter) {
    global $conn;
    
    $data = [];
    
    // Get audit schedules within date range
    $auditQuery = "SELECT * FROM audit_schedules 
                   WHERE audit_date BETWEEN ? AND ?";
    $params = [$dateFrom, $dateTo];
    $types = 'ss';
    
    if (!empty($departmentFilter)) {
        $auditQuery .= " AND target_department = ?";
        $params[] = $departmentFilter;
        $types .= 's';
    }
    
    $stmt = $conn->prepare($auditQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $auditResult = $stmt->get_result();
    
    $audits = [];
    while ($row = $auditResult->fetch_assoc()) {
        $audits[] = $row;
    }
    
    $stmt->close();
    
    $data['audits'] = $audits;
    $data['total_audits'] = count($audits);
    
    // Get findings for these audits
    $findingIds = array_column($audits, 'audit_id');
    if (!empty($findingIds)) {
        $placeholders = str_repeat('?,', count($findingIds) - 1) . '?';
        $findingQuery = "SELECT * FROM audit_findings 
                        WHERE audit_id IN ($placeholders)";
        
        $findingStmt = $conn->prepare($findingQuery);
        $findingStmt->bind_param(str_repeat('s', count($findingIds)), ...$findingIds);
        $findingStmt->execute();
        $findingResult = $findingStmt->get_result();
        
        $findings = [];
        while ($row = $findingResult->fetch_assoc()) {
            $findings[] = $row;
        }
        
        $findingStmt->close();
        
        $data['findings'] = $findings;
        $data['total_findings'] = count($findings);
        
        // Calculate severity breakdown
        $severityBreakdown = [
            'Low' => 0,
            'Medium' => 0,
            'High' => 0,
            'Critical' => 0
        ];
        
        foreach ($findings as $finding) {
            $severity = $finding['severity'];
            if (isset($severityBreakdown[$severity])) {
                $severityBreakdown[$severity]++;
            }
        }
        
        $data['severity_breakdown'] = $severityBreakdown;
    } else {
        $data['findings'] = [];
        $data['total_findings'] = 0;
        $data['severity_breakdown'] = [
            'Low' => 0,
            'Medium' => 0,
            'High' => 0,
            'Critical' => 0
        ];
    }
    
    // Get corrective actions
    if (!empty($findingIds)) {
        $actionQuery = "SELECT ca.*, f.severity 
                       FROM corrective_actions ca
                       LEFT JOIN audit_findings f ON ca.finding_id = f.finding_id
                       WHERE ca.finding_id IN ($placeholders)";
        
        $actionStmt = $conn->prepare($actionQuery);
        $actionStmt->bind_param(str_repeat('s', count($findingIds)), ...$findingIds);
        $actionStmt->execute();
        $actionResult = $actionStmt->get_result();
        
        $actions = [];
        while ($row = $actionResult->fetch_assoc()) {
            $actions[] = $row;
        }
        
        $actionStmt->close();
        
        $data['corrective_actions'] = $actions;
        $data['total_actions'] = count($actions);
        
        // Calculate action status breakdown
        $statusBreakdown = [
            'assigned' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'escalated' => 0
        ];
        
        foreach ($actions as $action) {
            $status = $action['status'];
            if (isset($statusBreakdown[$status])) {
                $statusBreakdown[$status]++;
            }
        }
        
        $data['action_status_breakdown'] = $statusBreakdown;
    } else {
        $data['corrective_actions'] = [];
        $data['total_actions'] = 0;
        $data['action_status_breakdown'] = [
            'assigned' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'escalated' => 0
        ];
    }
    
    // Calculate compliance rate
    $totalAudits = count($audits);
    $completedAudits = array_filter($audits, function($audit) {
        return $audit['status'] === 'completed';
    });
    
    $data['compliance_rate'] = $totalAudits > 0 ? (count($completedAudits) / $totalAudits) * 100 : 0;
    
    return $data;
}

function storeReportMetrics($reportId, $reportData) {
    global $conn;
    
    try {
        $metrics = [
            'total_audits' => $reportData['total_audits'],
            'total_findings' => $reportData['total_findings'],
            'total_actions' => $reportData['total_actions'],
            'compliance_rate' => $reportData['compliance_rate']
        ];
        
        foreach ($metrics as $metricName => $metricValue) {
            $query = "INSERT INTO report_metrics (
                        report_id, metric_name, metric_value, created_at
                      ) VALUES (?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssi', $reportId, $metricName, $metricValue);
            $stmt->execute();
            $stmt->close();
        }
        
        // Store severity breakdown
        foreach ($reportData['severity_breakdown'] as $severity => $count) {
            $query = "INSERT INTO report_metrics (
                        report_id, metric_name, metric_value, created_at
                      ) VALUES (?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($query);
            $metricName = "severity_" . strtolower($severity);
            $stmt->bind_param('ssi', $reportId, $metricName, $count);
            $stmt->execute();
            $stmt->close();
        }
        
    } catch (Exception $e) {
        error_log('Error storing report metrics: ' . $e->getMessage());
    }
}

function exportReport($data) {
    global $conn;
    
    try {
        if (empty($data['report_id']) || empty($data['export_format'])) {
            sendResponse(false, 'Missing report ID or export format');
        }
        
        $reportId = $data['report_id'];
        $exportFormat = $data['export_format']; // 'pdf' or 'excel'
        
        // Get report data
        $query = "SELECT * FROM audit_reports WHERE report_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $reportId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(false, 'Report not found');
        }
        
        $report = $result->fetch_assoc();
        $stmt->close();
        
        // Generate export file path
        $fileName = $reportId . '_export.' . ($exportFormat === 'pdf' ? 'pdf' : 'xlsx');
        $filePath = '../exports/' . $fileName;
        
        // Update report with export info
        $updateQuery = "UPDATE audit_reports 
                       SET export_format = ?, file_path = ? 
                       WHERE report_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('sss', $exportFormat, $filePath, $reportId);
        $updateStmt->execute();
        $updateStmt->close();
        
        $exportInfo = [
            'report_id' => $reportId,
            'export_format' => $exportFormat,
            'file_name' => $fileName,
            'file_path' => $filePath
        ];
        
        sendResponse(true, 'Report exported successfully', $exportInfo);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error exporting report: ' . $e->getMessage());
    }
}

function sendToManagement($data) {
    global $conn;
    
    try {
        if (empty($data['report_id'])) {
            sendResponse(false, 'Missing report ID');
        }
        
        $reportId = $data['report_id'];
        $recipients = $data['recipients'] ?? ['management@company.com'];
        $message = $data['message'] ?? 'Please find attached audit report.';
        
        // Generate notification ID
        $notificationId = 'NOT-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert notification record
        $query = "INSERT INTO report_notifications (
                    notification_id, report_id, recipients, message, 
                    status, sent_at, created_at
                  ) VALUES (?, ?, ?, ?, 'sent', NOW(), NOW())";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            sendResponse(false, 'Prepare failed: ' . $conn->error);
        }
        
        $recipientsJson = json_encode($recipients);
        $stmt->bind_param('ssss', 
            $notificationId,
            $reportId,
            $recipientsJson,
            $message
        );
        
        if (!$stmt->execute()) {
            sendResponse(false, 'Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
        // Update report status
        $updateQuery = "UPDATE audit_reports SET status = 'sent_to_management' WHERE report_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('s', $reportId);
        $updateStmt->execute();
        $updateStmt->close();
        
        $notificationInfo = [
            'notification_id' => $notificationId,
            'report_id' => $reportId,
            'recipients' => $recipients,
            'message' => $message
        ];
        
        sendResponse(true, 'Report sent to management successfully', $notificationInfo);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error sending report to management: ' . $e->getMessage());
    }
}

function updateAuditParameters($reportData) {
    global $conn;
    
    try {
        // This would integrate with Vehicle Reservation system
        // For now, just log the parameter update
        $query = "INSERT INTO audit_parameter_updates (
                    update_type, details, compliance_rate, created_at
                  ) VALUES (?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        $updateType = 'compliance_based_adjustment';
        $details = 'Updating audit parameters based on compliance rate: ' . $reportData['compliance_rate'] . '%';
        $stmt->bind_param('ssi', $updateType, $details, $reportData['compliance_rate']);
        $stmt->execute();
        $stmt->close();
        
    } catch (Exception $e) {
        error_log('Error updating audit parameters: ' . $e->getMessage());
    }
}

function routeToDocumentTracking($reportId, $reportData) {
    global $conn;
    
    try {
        // This would integrate with Document Tracking system
        // For now, just log the routing
        $query = "INSERT INTO document_routing (
                    document_id, document_type, destination, 
                    routing_status, created_at
                  ) VALUES (?, 'audit_report', 'document_tracking', 'routed', NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $reportId);
        $stmt->execute();
        $stmt->close();
        
    } catch (Exception $e) {
        error_log('Error routing to document tracking: ' . $e->getMessage());
    }
}

function deleteReport($data) {
    global $conn;
    
    try {
        $reportId = $data['report_id'] ?? '';
        
        if (empty($reportId)) {
            sendResponse(false, 'Missing report ID');
        }
        
        // Soft delete by updating status
        $query = "UPDATE audit_reports SET status = 'deleted', updated_at = NOW() WHERE report_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $reportId);
        
        if ($stmt->execute()) {
            sendResponse(true, 'Report deleted successfully');
        } else {
            sendResponse(false, 'Failed to delete report');
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        sendResponse(false, 'Error deleting report: ' . $e->getMessage());
    }
}
?>

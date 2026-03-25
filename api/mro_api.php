<?php
/**
 * MRO System API - Logistics 1 (Maintenance, Repair, and Operations)
 * Integrates with Fleet Management (Logistics 2) via IP-based API endpoints
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Forwarded-For');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/db.php';
require_once '../vendor/autoload.php';

date_default_timezone_set('Asia/Manila');

// Log API access
function logApiAccess($source, $action, $data = null) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO mro_integration_log (source_system, source_id, action, data, ip_address, processed_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $sourceId = $data['id'] ?? 'unknown';
    $jsonData = $data ? json_encode($data) : null;
    $stmt->bind_param("sssss", $source, $sourceId, $action, $jsonData, $ip);
    $stmt->execute();
}

// Send JSON response
function sendResponse($success, $message = '', $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

// Validate API key (simple IP-based authentication for now)
function validateApiAccess() {
    $allowedIPs = ['127.0.0.1', '::1']; // Add your fleet system IP here
    $clientIP = $_SERVER['REMOTE_ADDR'];
    
    // For development, allow all IPs. In production, restrict to specific IPs
    if (!in_array($clientIP, $allowedIPs) && false) {
        sendResponse(false, 'Access denied', null, 403);
    }
    
    return true;
}

// Get request data
function getRequestData() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?: [];
    } else {
        return $_POST;
    }
}

// Generate unique IDs
function generateWorkOrderId() {
    return 'WO-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function generatePlanId() {
    return 'PLAN-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Main API router
$action = $_GET['action'] ?? '';
$endpoint = $_GET['endpoint'] ?? '';

validateApiAccess();

switch ($endpoint) {
    // Fleet Integration Endpoints
    case 'maintenance_request':
        handleMaintenanceRequest();
        break;
        
    case 'work_order_status':
        handleWorkOrderStatus();
        break;
        
    case 'maintenance_report':
        handleMaintenanceReport();
        break;
        
    // MRO Management Endpoints
    case 'work_orders':
        handleWorkOrders($action);
        break;
        
    case 'maintenance_planning':
        handleMaintenancePlanning($action);
        break;
        
    case 'parts_management':
        handlePartsManagement($action);
        break;
        
    case 'compliance_safety':
        handleComplianceSafety($action);
        break;
        
    case 'technicians':
        handleTechnicians($action);
        break;
        
    case 'reports':
        handleReports($action);
        break;
        
    default:
        sendResponse(false, 'Invalid endpoint');
}

/**
 * Fleet Integration Handlers
 */

function handleMaintenanceRequest() {
    global $conn;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Method not allowed');
    }
    
    $data = getRequestData();
    
    // Validate required fields
    $required = ['fleet_vehicle_id', 'issue_description', 'priority', 'requested_by'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendResponse(false, "Missing required field: $field");
        }
    }
    
    try {
        $conn->begin_transaction();
        
        // Create maintenance request
        $stmt = $conn->prepare("INSERT INTO maintenance_requests (asset_id, requested_by, issue_description, priority, fleet_vehicle_id, source_system, integration_data) VALUES (?, ?, ?, ?, ?, 'Fleet', ?)");
        $integrationData = json_encode($data);
        $asset_id = $data['asset_id'] ?? 0;
        $requested_by = $data['requested_by'];
        $issue_description = $data['issue_description'];
        $priority = $data['priority'];
        $fleet_vehicle_id = $data['fleet_vehicle_id'];
        $stmt->bind_param("iissss", $asset_id, $requested_by, $issue_description, $priority, $fleet_vehicle_id, $integrationData);
        $stmt->execute();
        $requestId = $conn->insert_id;
        
        // Create work order
        $workOrderId = generateWorkOrderId();
        $stmt = $conn->prepare("INSERT INTO mro_work_orders (work_order_id, maintenance_request_id, fleet_vehicle_id, work_order_type, title, description, priority, status, created_by) VALUES (?, ?, ?, 'Corrective', ?, ?, ?, 'Pending', ?)");
        $title = 'Fleet Maintenance - ' . $data['fleet_vehicle_id'];
        $description = $data['issue_description'];
        $created_by = $data['requested_by'];
        $stmt->bind_param("sissssi", $workOrderId, $requestId, $fleet_vehicle_id, $title, $description, $priority, $created_by);
        $stmt->execute();
        
        // Update maintenance request with work order ID
        $stmt = $conn->prepare("UPDATE maintenance_requests SET work_order_id = ? WHERE id = ?");
        $stmt->bind_param("si", $workOrderId, $requestId);
        $stmt->execute();
        
        $conn->commit();
        
        // Log the integration
        logApiAccess('Fleet', 'Request_Received', $data);
        
        sendResponse(true, 'Maintenance request received and work order created', [
            'request_id' => $requestId,
            'work_order_id' => $workOrderId,
            'status' => 'Pending'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function handleWorkOrderStatus() {
    global $conn;
    
    $workOrderId = $_GET['work_order_id'] ?? '';
    
    if (empty($workOrderId)) {
        sendResponse(false, 'Work order ID is required');
    }
    
    $stmt = $conn->prepare("SELECT wo.*, u.name as created_by_name, fm.license_plate, a.asset_name 
                           FROM mro_work_orders wo 
                           LEFT JOIN users u ON wo.created_by = u.id 
                           LEFT JOIN fleet_management fm ON wo.fleet_vehicle_id = fm.license_plate 
                           LEFT JOIN assets a ON wo.asset_id = a.id 
                           WHERE wo.work_order_id = ?");
    $stmt->bind_param("s", $workOrderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(false, 'Work order not found', null, 404);
    }
    
    $workOrder = $result->fetch_assoc();
    
    // Get parts usage
    $partsStmt = $conn->prepare("SELECT * FROM mro_parts_usage WHERE work_order_id = ?");
    $partsStmt->bind_param("s", $workOrderId);
    $partsStmt->execute();
    $partsResult = $partsStmt->get_result();
    $parts = [];
    while ($row = $partsResult->fetch_assoc()) {
        $parts[] = $row;
    }
    $workOrder['parts_used'] = $parts;
    
    // Get compliance checks
    $compStmt = $conn->prepare("SELECT * FROM mro_compliance_safety WHERE work_order_id = ?");
    $compStmt->bind_param("s", $workOrderId);
    $compStmt->execute();
    $compResult = $compStmt->get_result();
    $compliance = [];
    while ($row = $compResult->fetch_assoc()) {
        $compliance[] = $row;
    }
    $workOrder['compliance_checks'] = $compliance;
    
    sendResponse(true, 'Work order status retrieved', $workOrder);
}

function handleMaintenanceReport() {
    global $conn;
    
    $workOrderId = $_GET['work_order_id'] ?? '';
    
    if (empty($workOrderId)) {
        sendResponse(false, 'Work order ID is required');
    }
    
    $stmt = $conn->prepare("SELECT wo.*, u.name as technician_name, fm.license_plate, a.asset_name 
                           FROM mro_work_orders wo 
                           LEFT JOIN users u ON wo.assigned_technician = u.name 
                           LEFT JOIN fleet_management fm ON wo.fleet_vehicle_id = fm.license_plate 
                           LEFT JOIN assets a ON wo.asset_id = a.id 
                           WHERE wo.work_order_id = ?");
    $stmt->bind_param("s", $workOrderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(false, 'Work order not found', null, 404);
    }
    
    $workOrder = $result->fetch_assoc();
    
    // Prepare report data for fleet system
    $reportData = [
        'work_order_id' => $workOrderId,
        'vehicle_id' => $workOrder['fleet_vehicle_id'],
        'asset_id' => $workOrder['asset_id'],
        'completion_date' => $workOrder['completed_date'],
        'status' => $workOrder['status'],
        'total_cost' => $workOrder['total_cost'],
        'labor_cost' => $workOrder['labor_cost'],
        'parts_cost' => $workOrder['parts_cost'],
        'actual_hours' => $workOrder['actual_hours'],
        'technician' => $workOrder['technician_name'],
        'description' => $workOrder['description'],
        'parts_used' => [],
        'compliance_status' => 'Passed'
    ];
    
    // Get parts used
    $partsStmt = $conn->prepare("SELECT part_name, part_number, quantity_used, unit_cost, total_cost FROM mro_parts_usage WHERE work_order_id = ?");
    $partsStmt->bind_param("s", $workOrderId);
    $partsStmt->execute();
    $partsResult = $partsStmt->get_result();
    while ($row = $partsResult->fetch_assoc()) {
        $reportData['parts_used'][] = $row;
    }
    
    // Get compliance status
    $compStmt = $conn->prepare("SELECT passed FROM mro_compliance_safety WHERE work_order_id = ? AND check_type = 'Post-Work'");
    $compStmt->bind_param("s", $workOrderId);
    $compStmt->execute();
    $compResult = $compStmt->get_result();
    if ($compResult->num_rows > 0) {
        $compRow = $compResult->fetch_assoc();
        $reportData['compliance_status'] = $compRow['passed'] ? 'Passed' : 'Failed';
    }
    
    // Log the report sent
    logApiAccess('Fleet', 'Report_Sent', $reportData);
    
    sendResponse(true, 'Maintenance report generated', $reportData);
}

/**
 * MRO Management Handlers
 */

function handleWorkOrders($action) {
    global $conn;
    
    switch ($action) {
        case 'list':
            $status = $_GET['status'] ?? '';
            $technician = $_GET['technician'] ?? '';
            
            $sql = "SELECT wo.*, u.name as created_by_name, fm.license_plate, a.asset_name 
                   FROM mro_work_orders wo 
                   LEFT JOIN users u ON wo.created_by = u.id 
                   LEFT JOIN fleet_management fm ON wo.fleet_vehicle_id = fm.license_plate 
                   LEFT JOIN assets a ON wo.asset_id = a.id 
                   WHERE 1=1";
            
            $params = [];
            $types = '';
            
            if (!empty($status)) {
                $sql .= " AND wo.status = ?";
                $params[] = $status;
                $types .= 's';
            }
            
            if (!empty($technician)) {
                $sql .= " AND wo.assigned_technician = ?";
                $params[] = $technician;
                $types .= 's';
            }
            
            $sql .= " ORDER BY wo.created_at DESC";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $workOrders = [];
            while ($row = $result->fetch_assoc()) {
                $workOrders[] = $row;
            }
            
            sendResponse(true, 'Work orders retrieved', $workOrders);
            break;
            
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Method not allowed');
            }
            
            $data = getRequestData();
            $required = ['title', 'description', 'priority', 'created_by'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendResponse(false, "Missing required field: $field");
                }
            }
            
            $workOrderId = generateWorkOrderId();
            $stmt = $conn->prepare("INSERT INTO mro_work_orders (work_order_id, title, description, priority, work_order_type, assigned_technician, estimated_hours, scheduled_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $work_order_type = $data['work_order_type'] ?? 'Corrective';
            $assigned_technician = $data['assigned_technician'] ?? '';
            $estimated_hours = $data['estimated_hours'] ?? 0;
            $scheduled_date = $data['scheduled_date'] ?? null;
            $stmt->bind_param("ssssssdsi", $workOrderId, $data['title'], $data['description'], $data['priority'], $work_order_type, $assigned_technician, $estimated_hours, $scheduled_date, $data['created_by']);
            
            if ($stmt->execute()) {
                sendResponse(true, 'Work order created', ['work_order_id' => $workOrderId]);
            } else {
                sendResponse(false, 'Failed to create work order');
            }
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                sendResponse(false, 'Method not allowed');
            }
            
            $data = getRequestData();
            $workOrderId = $data['work_order_id'] ?? '';
            
            if (empty($workOrderId)) {
                sendResponse(false, 'Work order ID is required');
            }
            
            $updateFields = [];
            $params = [];
            $types = '';
            
            $allowedFields = ['title', 'description', 'priority', 'status', 'assigned_technician', 'estimated_hours', 'actual_hours', 'labor_cost', 'parts_cost', 'total_cost', 'scheduled_date', 'started_date', 'completed_date'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $data[$field];
                    $types .= 's';
                }
            }
            
            if (empty($updateFields)) {
                sendResponse(false, 'No valid fields to update');
            }
            
            $params[] = $workOrderId;
            $types .= 's';
            
            $sql = "UPDATE mro_work_orders SET " . implode(', ', $updateFields) . " WHERE work_order_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                sendResponse(true, 'Work order updated');
            } else {
                sendResponse(false, 'Failed to update work order');
            }
            break;
            
        default:
            sendResponse(false, 'Invalid action');
    }
}

function handleMaintenancePlanning($action) {
    global $conn;
    
    switch ($action) {
        case 'list':
            $stmt = $conn->prepare("SELECT mp.*, u.name as created_by_name, a.asset_name, fm.license_plate 
                                   FROM mro_maintenance_planning mp 
                                   LEFT JOIN users u ON mp.created_by = u.id 
                                   LEFT JOIN assets a ON mp.asset_id = a.id 
                                   LEFT JOIN fleet_management fm ON mp.fleet_vehicle_id = fm.license_plate 
                                   WHERE mp.status = 'Active' 
                                   ORDER BY mp.next_due_date ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $plans = [];
            while ($row = $result->fetch_assoc()) {
                $plans[] = $row;
            }
            
            sendResponse(true, 'Maintenance plans retrieved', $plans);
            break;
            
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Method not allowed');
            }
            
            $data = getRequestData();
            $required = ['plan_title', 'plan_type', 'next_due_date', 'created_by'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendResponse(false, "Missing required field: $field");
                }
            }
            
            $planId = generatePlanId();
            $stmt = $conn->prepare("INSERT INTO mro_maintenance_planning (plan_id, plan_title, plan_type, description, frequency_days, next_due_date, estimated_cost, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $description = $data['description'] ?? '';
            $frequency_days = $data['frequency_days'] ?? 0;
            $estimated_cost = $data['estimated_cost'] ?? 0;
            $stmt->bind_param("ssssidsi", $planId, $data['plan_title'], $data['plan_type'], $description, $frequency_days, $data['next_due_date'], $estimated_cost, $data['created_by']);
            
            if ($stmt->execute()) {
                sendResponse(true, 'Maintenance plan created', ['plan_id' => $planId]);
            } else {
                sendResponse(false, 'Failed to create maintenance plan');
            }
            break;
            
        default:
            sendResponse(false, 'Invalid action');
    }
}

function handlePartsManagement($action) {
    global $conn;
    
    switch ($action) {
        case 'usage':
            $workOrderId = $_GET['work_order_id'] ?? '';
            
            if (empty($workOrderId)) {
                sendResponse(false, 'Work order ID is required');
            }
            
            $stmt = $conn->prepare("SELECT pu.*, i.item_name, s.company_name as supplier_name 
                                   FROM mro_parts_usage pu 
                                   LEFT JOIN inventory i ON pu.inventory_id = i.id 
                                   LEFT JOIN suppliers s ON pu.supplier_id = s.id 
                                   WHERE pu.work_order_id = ?");
            $stmt->bind_param("s", $workOrderId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $parts = [];
            while ($row = $result->fetch_assoc()) {
                $parts[] = $row;
            }
            
            sendResponse(true, 'Parts usage retrieved', $parts);
            break;
            
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Method not allowed');
            }
            
            $data = getRequestData();
            $required = ['work_order_id', 'part_name', 'quantity_used'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendResponse(false, "Missing required field: $field");
                }
            }
            
            $totalCost = ($data['quantity_used'] * ($data['unit_cost'] ?? 0));
            $stmt = $conn->prepare("INSERT INTO mro_parts_usage (work_order_id, inventory_id, part_name, part_number, quantity_used, unit_cost, total_cost, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $inventory_id = $data['inventory_id'] ?? 0;
            $part_number = $data['part_number'] ?? '';
            $unit_cost = $data['unit_cost'] ?? 0;
            $supplier_id = $data['supplier_id'] ?? 0;
            $stmt->bind_param("sisdsddi", $data['work_order_id'], $inventory_id, $data['part_name'], $part_number, $data['quantity_used'], $unit_cost, $totalCost, $supplier_id);
            
            if ($stmt->execute()) {
                sendResponse(true, 'Parts usage recorded');
            } else {
                sendResponse(false, 'Failed to record parts usage');
            }
            break;
            
        default:
            sendResponse(false, 'Invalid action');
    }
}

function handleComplianceSafety($action) {
    global $conn;
    
    switch ($action) {
        case 'checklist':
            $workOrderId = $_GET['work_order_id'] ?? '';
            
            if (empty($workOrderId)) {
                sendResponse(false, 'Work order ID is required');
            }
            
            $stmt = $conn->prepare("SELECT * FROM mro_compliance_safety WHERE work_order_id = ? ORDER BY check_date DESC");
            $stmt->bind_param("s", $workOrderId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $checks = [];
            while ($row = $result->fetch_assoc()) {
                $checks[] = $row;
            }
            
            sendResponse(true, 'Compliance checks retrieved', $checks);
            break;
            
        case 'submit':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Method not allowed');
            }
            
            $data = getRequestData();
            $required = ['work_order_id', 'check_type', 'performed_by'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendResponse(false, "Missing required field: $field");
                }
            }
            
            $stmt = $conn->prepare("INSERT INTO mro_compliance_safety (work_order_id, check_type, checklist_items, performed_by, check_date, results, passed, issues_found, corrective_actions) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?)");
            $checklist_items = json_encode($data['checklist_items'] ?? []);
            $results = json_encode($data['results'] ?? []);
            $passed = $data['passed'] ?? 0;
            $issues_found = $data['issues_found'] ?? '';
            $corrective_actions = $data['corrective_actions'] ?? '';
            $stmt->bind_param("ssssssiss", $data['work_order_id'], $data['check_type'], $checklist_items, $data['performed_by'], $results, $passed, $issues_found, $corrective_actions);
            
            if ($stmt->execute()) {
                sendResponse(true, 'Compliance check submitted');
            } else {
                sendResponse(false, 'Failed to submit compliance check');
            }
            break;
            
        default:
            sendResponse(false, 'Invalid action');
    }
}

function handleTechnicians($action) {
    global $conn;
    
    switch ($action) {
        case 'list':
            $stmt = $conn->prepare("SELECT t.*, u.name, u.email, u.status 
                                   FROM mro_technicians t 
                                   JOIN users u ON t.user_id = u.id 
                                   ORDER BY t.availability_status, u.name");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $technicians = [];
            while ($row = $result->fetch_assoc()) {
                $technicians[] = $row;
            }
            
            sendResponse(true, 'Technicians retrieved', $technicians);
            break;
            
        case 'workload':
            $stmt = $conn->prepare("SELECT * FROM v_technician_workload ORDER BY active_work_orders DESC");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $workload = [];
            while ($row = $result->fetch_assoc()) {
                $workload[] = $row;
            }
            
            sendResponse(true, 'Technician workload retrieved', $workload);
            break;
            
        default:
            sendResponse(false, 'Invalid action');
    }
}

function handleReports($action) {
    global $conn;
    
    switch ($action) {
        case 'generate':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Method not allowed');
            }
            
            $data = getRequestData();
            $reportType = $data['report_type'] ?? '';
            
            if (empty($reportType)) {
                sendResponse(false, 'Report type is required');
            }
            
            $reportId = 'RPT-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Generate report data based on type
            $reportData = [];
            
            switch ($reportType) {
                case 'Work_Order_Summary':
                    $stmt = $conn->prepare("SELECT status, COUNT(*) as count, AVG(total_cost) as avg_cost 
                                           FROM mro_work_orders 
                                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                                           GROUP BY status");
                    $stmt->execute();
                    $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    break;
                    
                case 'Technician_Performance':
                    $stmt = $conn->prepare("SELECT t.assigned_technician, COUNT(*) as work_orders, AVG(actual_hours) as avg_hours, SUM(total_cost) as total_cost 
                                           FROM mro_work_orders t 
                                           WHERE t.status = 'Completed' AND t.completed_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                                           GROUP BY t.assigned_technician");
                    $stmt->execute();
                    $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    break;
                    
                default:
                    sendResponse(false, 'Invalid report type');
            }
            
            $stmt = $conn->prepare("INSERT INTO mro_reports (report_id, report_type, title, parameters, data, generated_by) VALUES (?, ?, ?, ?, ?, ?)");
            $title = $data['title'] ?? '';
            $parameters = json_encode($data['parameters'] ?? []);
            $generated_by = $data['generated_by'] ?? 1;
            $stmt->bind_param("sssssi", $reportId, $reportType, $title, $parameters, json_encode($reportData), $generated_by);
            
            if ($stmt->execute()) {
                sendResponse(true, 'Report generated', ['report_id' => $reportId, 'data' => $reportData]);
            } else {
                sendResponse(false, 'Failed to generate report');
            }
            break;
            
        default:
            sendResponse(false, 'Invalid action');
    }
}

?>

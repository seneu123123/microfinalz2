<?php
/**
 * Simple debug API for reporting
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Disable all HTML output
ini_set('display_errors', '0');
error_reporting(0);

try {
    // Test database connection
    require_once '../config/db.php';
    
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'vehicle_usage':
            // Simple test query
            $result = $conn->query("SELECT vehicle_id, COUNT(*) as usage_count FROM reservations GROUP BY vehicle_id LIMIT 5");
            if (!$result) {
                echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
                exit;
            }
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            echo json_encode(['success' => true, 'message' => 'Vehicle usage retrieved', 'data' => $data]);
            break;
            
        case 'driver_performance':
            // Simple test query
            $result = $conn->query("SELECT driver_id, COUNT(*) as trip_count FROM trip_logs GROUP BY driver_id LIMIT 5");
            if (!$result) {
                echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
                exit;
            }
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            echo json_encode(['success' => true, 'message' => 'Driver performance retrieved', 'data' => $data]);
            break;
            
        case 'fuel_trends':
            // Simple test query
            $result = $conn->query("SELECT DATE_FORMAT(start_time, '%Y-%m') as month, SUM(fuel_used) as total_liters FROM trip_logs WHERE fuel_used > 0 GROUP BY month LIMIT 5");
            if (!$result) {
                echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
                exit;
            }
            
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            echo json_encode(['success' => true, 'message' => 'Fuel trends retrieved', 'data' => $data]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

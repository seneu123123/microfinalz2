<?php

/**

 * Reporting API (2.6)

 */



header('Content-Type: application/json');

header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET');

header('Access-Control-Allow-Headers: Content-Type');



require_once '../config/db.php';



ini_set('display_errors', '0');

error_reporting(E_ALL);



register_shutdown_function(function() {

    $err = error_get_last();

    if ($err) {

        header('Content-Type: application/json');

        echo json_encode(['success' => false, 'message' => 'Server error']);

        error_log("Fatal error in reporting.php: " . var_export($err, true));

    }

});



set_exception_handler(function($ex) {

    header('Content-Type: application/json');

    echo json_encode(['success' => false, 'message' => 'Exception: ' . $ex->getMessage()]);

    error_log('Exception in reporting.php: ' . $ex->getMessage());

});



$method = $_SERVER['REQUEST_METHOD'];

$action = $_GET['action'] ?? '';



if ($method !== 'GET') {

    sendError('Only GET method allowed');

}



switch ($action) {

    case 'vehicle_usage':

        reportVehicleUsage();

        break;

    case 'driver_performance':

        reportDriverPerformance();

        break;

    case 'fuel_trends':

        reportFuelTrends();

        break;

    default:

        sendError('Invalid action');

}



function reportVehicleUsage() {

    global $conn;

    $result = $conn->query("SELECT vehicle_id, COUNT(*) as usage_count FROM reservations GROUP BY vehicle_id ORDER BY usage_count DESC LIMIT 10");

    if (!$result) {

        sendError('Query failed: ' . $conn->error);

        return;

    }

    $data = [];

    while ($row = $result->fetch_assoc()) {

        $data[] = $row;

    }

    sendSuccess('Vehicle usage report', $data);

}



function reportDriverPerformance() {

    global $conn;

    $result = $conn->query("SELECT driver_id, COUNT(*) as trip_count FROM trip_logs GROUP BY driver_id ORDER BY trip_count DESC LIMIT 10");

    if (!$result) {

        sendError('Query failed: ' . $conn->error);

        return;

    }

    $data = [];

    while ($row = $result->fetch_assoc()) {

        $data[] = $row;

    }

    sendSuccess('Driver performance report', $data);

}



function reportFuelTrends() {

    global $conn;

    $result = $conn->query("SELECT DATE_FORMAT(recorded_at, '%Y-%m') as month, SUM(liters) as total_liters FROM fuel_usage GROUP BY month ORDER BY month DESC LIMIT 12");

    if (!$result) {

        sendError('Query failed: ' . $conn->error);

        return;

    }

    $data = [];

    while ($row = $result->fetch_assoc()) {

        $data[] = $row;

    }

    sendSuccess('Fuel trends report', $data);

}



function sendSuccess($message, $data = null) {

    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);

    exit;

}



function sendError($message) {

    echo json_encode(['success' => false, 'message' => $message]);

    exit;

}

?>


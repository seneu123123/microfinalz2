<?php



// api/requisition.php - Minimal working version



// Clean output buffer to prevent mixed content

ob_clean();



header('Content-Type: application/json');

header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

header('Access-Control-Allow-Headers: Content-Type, Authorization');



error_reporting(E_ALL);

ini_set('display_errors', 0); // Turn off display errors to prevent JSON contamination



session_start();



// Debug: Log basic info

error_log("API called - Method: " . $_SERVER['REQUEST_METHOD']);

error_log("Direct access allowed - no session required");



// Load database configuration

try {

    require '../config/db.php';

    error_log("Database config loaded successfully");

} catch (Exception $e) {

    error_log("Database config failed: " . $e->getMessage());

    http_response_code(500);

    ob_clean();

    echo json_encode(['status' => 'error', 'message' => 'Database config error: ' . $e->getMessage()]);

    exit;

}



// 1. GET REQUISITIONS

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    try {

        error_log("Processing GET request - no user filter");

        

        // Simple query without user filter

        $stmt = $pdo->prepare("SELECT * FROM requisitions ORDER BY id DESC");

        $stmt->execute();

        $requisitions = $stmt->fetchAll();

        

        error_log("Query successful, found " . count($requisitions) . " rows");

        

        // Simple response

        ob_clean();

        echo json_encode(['status' => 'success', 'data' => $requisitions]);

        

    } catch (Exception $e) {

        error_log("GET requisitions error: " . $e->getMessage());

        http_response_code(500);

        ob_clean();

        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);

    }

    exit;

}



// 2. POST - CREATE NEW REQUISITION

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(file_get_contents('php://input'), true);

    

    error_log("POST input: " . print_r($input, true));

    

    if (!$input || !isset($input['items']) || !is_array($input['items'])) {

        http_response_code(400);

        ob_clean();

        echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);

        exit;

    }

    

    if (empty($input['items'])) {

        http_response_code(400);

        ob_clean();

        echo json_encode(['status' => 'error', 'message' => 'At least one item is required']);

        exit;

    }

    

    try {

        $pdo->beginTransaction();

        

        // Simple insert without user_id

        $stmt = $pdo->prepare("INSERT INTO requisitions (request_date, remarks) VALUES (NOW(), ?)");

        $stmt->execute([$input['remarks'] ?? '']);

        $req_id = $pdo->lastInsertId();

        

        error_log("Created requisition with ID: " . $req_id);

        

        // Simple item insert

        $sql_item = "INSERT INTO requisition_items (requisition_id, item_name, quantity, unit) VALUES (?, ?, ?, ?)";

        $stmt_item = $pdo->prepare($sql_item);

        

        foreach ($input['items'] as $index => $item) {

            if (!isset($item['name']) || !isset($item['qty'])) {

                throw new Exception("Item at index $index is missing required fields");

            }

            $stmt_item->execute([$req_id, $item['name'], $item['qty'], $item['unit'] ?? '']);

            error_log("Added item: " . $item['name'] . " qty: " . $item['qty']);

        }

        

        $pdo->commit();

        

        ob_clean();

        echo json_encode(['status' => 'success', 'message' => 'Requisition Created! ID: ' . $req_id]);

        

    } catch (Exception $e) {

        $pdo->rollBack();

        error_log("POST requisitions error: " . $e->getMessage());

        http_response_code(500);

        ob_clean();

        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);

    }

    exit;

}



// Default response

http_response_code(405);

ob_clean();

echo json_encode(['status' => 'error', 'message' => 'Invalid method']);

?>
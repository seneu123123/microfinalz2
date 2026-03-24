<?php
/**
 * Vendor Profiles API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getProfiles();
        break;
    case 'POST':
        createProfile();
        break;
    case 'PUT':
        updateProfile();
        break;
    default:
        sendError('Invalid method');
}

function getProfiles() {
    global $conn;
    $result = $conn->query("SELECT * FROM vendor_profiles ORDER BY created_at DESC");
    if (!$result) {
        sendError('Query failed');
    }
    $profiles = [];
    while ($row = $result->fetch_assoc()) {
        $profiles[] = $row;
    }
    sendSuccess('Profiles retrieved', $profiles);
}

function createProfile() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['vendor_id'])) {
        sendError('Vendor ID required');
    }

    // Check duplicate
    $stmt = $conn->prepare("SELECT profile_id FROM vendor_profiles WHERE vendor_id = ?");
    $vendorIdCheck = $data['vendor_id'];
    $stmt->bind_param("s", $vendorIdCheck);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        sendError('Profile already exists for this vendor');
    }

    $stmt = $conn->prepare("INSERT INTO vendor_profiles (vendor_id, service_category, coverage_area, payment_terms, performance_rating, active_status) VALUES (?, ?, ?, ?, ?, ?)");
    
    $rating = $data['performance_rating'] ?? 0;
    $status = $data['active_status'] ?? 'Active';
    $vendor_id = $data['vendor_id'];
    $service_category = $data['service_category'] ?? null;
    $coverage_area = $data['coverage_area'] ?? null;
    $payment_terms = $data['payment_terms'] ?? null;

    $stmt->bind_param(
        "ssssss",
        $vendor_id,
        $service_category,
        $coverage_area,
        $payment_terms,
        $rating,
        $status
    );

    if ($stmt->execute()) {
        sendSuccess('Profile created', ['profile_id' => $conn->insert_id]);
    } else {
        sendError('Creation failed');
    }
}

function updateProfile() {
    global $conn;
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['vendor_id'])) {
        sendError('Vendor ID required');
    }

    $stmt = $conn->prepare("UPDATE vendor_profiles SET service_category = ?, coverage_area = ?, payment_terms = ?, performance_rating = ?, active_status = ? WHERE vendor_id = ?");
    
    $rating = $data['performance_rating'] ?? 0;
    $service_category = $data['service_category'] ?? null;
    $coverage_area = $data['coverage_area'] ?? null;
    $payment_terms = $data['payment_terms'] ?? null;
    $active_status = $data['active_status'] ?? 'Active';
    $vendor_id = $data['vendor_id'];

    $stmt->bind_param(
        "ssssss",
        $service_category,
        $coverage_area,
        $payment_terms,
        $rating,
        $active_status,
        $vendor_id
    );

    if ($stmt->execute()) {
        sendSuccess('Profile updated');
    } else {
        sendError('Update failed');
    }
}
?>

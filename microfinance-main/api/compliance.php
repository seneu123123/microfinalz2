<?php
// api/compliance.php
header('Content-Type: application/json');
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get Audit Logs
    if ($action === 'get_audits') {
        $stmt = $pdo->query("SELECT * FROM safety_audits ORDER BY audit_date DESC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }
    // Get Incident Reports
    if ($action === 'get_incidents') {
        $stmt = $pdo->query("SELECT * FROM safety_incidents ORDER BY incident_date DESC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Add New Safety Audit
    if ($input['action'] === 'add_audit') {
        try {
            $stmt = $pdo->prepare("INSERT INTO safety_audits (audit_type, auditor_name, audit_date, status, remarks) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$input['type'], $input['auditor'], $input['date'], $input['status'], $input['remarks']]);
            echo json_encode(['status' => 'success', 'message' => 'Safety Audit Logged!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Report Safety Incident
    if ($input['action'] === 'report_incident') {
        try {
            $stmt = $pdo->prepare("INSERT INTO safety_incidents (incident_type, location, incident_date, description, severity, status) VALUES (?, ?, ?, ?, ?, 'Reported')");
            $stmt->execute([$input['type'], $input['location'], $input['date'], $input['description'], $input['severity']]);
            echo json_encode(['status' => 'success', 'message' => 'Incident successfully reported.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}
?>
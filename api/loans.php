<?php
// api/loans.php
header('Content-Type: application/json');
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

// 1. GET DATA
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'get_clients') {
        $stmt = $pdo->query("SELECT * FROM clients ORDER BY full_name ASC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }

    if ($action === 'get_loans') {
        $stmt = $pdo->query("SELECT l.*, c.full_name, c.risk_profile FROM loans l JOIN clients c ON l.client_id = c.id ORDER BY l.id DESC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }
}

// 2. PROCESS ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // A. Add New Client
    if ($input['action'] === 'add_client') {
        try {
            $stmt = $pdo->prepare("INSERT INTO clients (full_name, contact_number, address, risk_profile) VALUES (?, ?, ?, ?)");
            $stmt->execute([$input['name'], $input['contact'], $input['address'], $input['risk']]);
            echo json_encode(['status' => 'success', 'message' => 'Client Registered!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // B. Submit Loan Application
    if ($input['action'] === 'create_loan') {
        try {
            $stmt = $pdo->prepare("INSERT INTO loans (client_id, principal_amount, interest_rate, term_months, status) VALUES (?, ?, ?, ?, 'Pending Review')");
            $stmt->execute([$input['client_id'], $input['amount'], $input['interest'], $input['terms']]);
            echo json_encode(['status' => 'success', 'message' => 'Loan Application Submitted!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // C. BPA TRIGGER: Approve Loan -> Auto-Generate Contract
    if ($input['action'] === 'approve_loan') {
        try {
            $pdo->beginTransaction();
            $loan_id = $input['loan_id'];

            // 1. Approve Loan
            $upd = $pdo->prepare("UPDATE loans SET status = 'Approved' WHERE id = ?");
            $upd->execute([$loan_id]);

            // 2. Fetch Client Name for the Document
            $stmt = $pdo->prepare("SELECT c.full_name FROM loans l JOIN clients c ON l.client_id = c.id WHERE l.id = ?");
            $stmt->execute([$loan_id]);
            $client = $stmt->fetch();

            // 3. BPA: Push to Document Control System
            $doc_name = "Loan Agreement - " . $client['full_name'];
            $docStmt = $pdo->prepare("INSERT INTO documents (reference_type, reference_id, document_name, signature_status) VALUES ('Loan Contract', ?, ?, 'Pending Signature')");
            $docStmt->execute([$loan_id, $doc_name]);

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Loan Approved! Contract auto-generated in Document Control.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}
?>
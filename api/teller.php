<?php
// api/teller.php
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
    
    // Get Teller Drawer Balance
    if ($action === 'get_drawer') {
        $stmt = $pdo->query("SELECT * FROM teller_drawers WHERE id = 1");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetch()]);
        exit;
    }

    // Get Active/Disbursed Loans for the dropdown
    if ($action === 'get_active_loans') {
        $stmt = $pdo->query("SELECT l.id, l.principal_amount, c.full_name 
                             FROM loans l 
                             JOIN clients c ON l.client_id = c.id 
                             WHERE l.status = 'Disbursed'");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }

    // Get Collection History
    if ($action === 'get_collections') {
        $sql = "SELECT p.*, c.full_name, t.teller_name 
                FROM loan_payments p 
                JOIN loans l ON p.loan_id = l.id 
                JOIN clients c ON l.client_id = c.id 
                JOIN teller_drawers t ON p.teller_id = t.id 
                ORDER BY p.id DESC LIMIT 50";
        $stmt = $pdo->query($sql);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }
}

// 2. PROCESS PAYMENT (Inflow)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input['action'] === 'process_payment') {
        try {
            $pdo->beginTransaction();
            $teller_id = 1; // Default Counter 1
            $loan_id = $input['loan_id'];
            $amount = floatval($input['amount']);

            // 1. Record the Payment
            $payStmt = $pdo->prepare("INSERT INTO loan_payments (loan_id, teller_id, amount_paid) VALUES (?, ?, ?)");
            $payStmt->execute([$loan_id, $teller_id, $amount]);

            // 2. Add Cash to Teller Drawer
            $updTeller = $pdo->prepare("UPDATE teller_drawers SET current_balance = current_balance + ? WHERE id = ?");
            $updTeller->execute([$amount, $teller_id]);

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Payment received and added to Cash Drawer!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
// C. END OF DAY REMITTANCE (Push Teller Cash to Main Vault)
    if ($input['action'] === 'remit_cash') {
        try {
            $pdo->beginTransaction();
            $teller_id = 1; // Counter 1
            $vault_id = 1;  // Main HQ Vault
            
            // 1. Check how much cash the teller has
            $stmt = $pdo->prepare("SELECT current_balance FROM teller_drawers WHERE id = ?");
            $stmt->execute([$teller_id]);
            $teller = $stmt->fetch();
            $amount_to_remit = floatval($teller['current_balance']);

            if ($amount_to_remit <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Drawer is empty. Nothing to remit!']);
                $pdo->rollBack();
                exit;
            }

            // 2. Empty the Teller Drawer (Set to 0)
            $emptyTeller = $pdo->prepare("UPDATE teller_drawers SET current_balance = 0 WHERE id = ?");
            $emptyTeller->execute([$teller_id]);

            // 3. Add that Cash to the Main HQ Vault
            $fillVault = $pdo->prepare("UPDATE vaults SET current_balance = current_balance + ? WHERE id = ?");
            $fillVault->execute([$amount_to_remit, $vault_id]);

            // 4. Log the Transaction in the Vault Ledger
            $logStmt = $pdo->prepare("INSERT INTO vault_transactions (vault_id, transaction_type, amount) VALUES (?, 'Inflow', ?)");
            $logStmt->execute([$vault_id, $amount_to_remit]);

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Remittance successful! $' . number_format($amount_to_remit, 2) . ' securely transferred to Main Vault.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
        }
        exit;
    }
?>
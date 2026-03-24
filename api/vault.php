<?php
// api/vault.php
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
    
    // Get the Main Vault Balance
    if ($action === 'get_balance') {
        $stmt = $pdo->query("SELECT * FROM vaults WHERE id = 1");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetch()]);
        exit;
    }

    // Get the Transaction History
    if ($action === 'get_transactions') {
        // We join with the loans and clients table so we can see EXACTLY who took the money during auto-disbursements
        $sql = "SELECT vt.*, l.id as loan_id, c.full_name 
                FROM vault_transactions vt 
                LEFT JOIN loans l ON vt.reference_id = l.id AND vt.transaction_type = 'Disbursement'
                LEFT JOIN clients c ON l.client_id = c.id
                ORDER BY vt.id DESC LIMIT 50";
        $stmt = $pdo->query($sql);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }
}

// 2. PROCESS MANUAL TRANSACTIONS (Cash In / Cash Out)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input['action'] === 'manual_transaction') {
        try {
            $pdo->beginTransaction();
            $vault_id = 1; // Default Main HQ Vault
            $amount = floatval($input['amount']);
            $type = $input['type']; // 'Inflow' or 'Outflow'

            // Record the transaction
            $transStmt = $pdo->prepare("INSERT INTO vault_transactions (vault_id, transaction_type, amount) VALUES (?, ?, ?)");
            $transStmt->execute([$vault_id, $type, $amount]);

            // Adjust the balance
            if ($type === 'Inflow') {
                $updVault = $pdo->prepare("UPDATE vaults SET current_balance = current_balance + ? WHERE id = ?");
            } else {
                $updVault = $pdo->prepare("UPDATE vaults SET current_balance = current_balance - ? WHERE id = ?");
            }
            $updVault->execute([$amount, $vault_id]);

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Vault balance updated successfully!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
?>
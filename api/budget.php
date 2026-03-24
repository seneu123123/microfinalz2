<?php
// api/budget.php
header('Content-Type: application/json');
session_start();
require 'db.php';

// Security Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

// Auto-initialize a default Master Budget if one doesn't exist yet
$pdo->exec("INSERT IGNORE INTO budgets (id, department, fiscal_year, total_budget, allocated_amount) VALUES (1, 'Master Company Budget', '2026', 500000.00, 0.00)");

// 1. GET DASHBOARD DATA
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_overview') {
    try {
        // A. Get Master Budget
        $stmt = $pdo->query("SELECT * FROM budgets WHERE id = 1");
        $budget = $stmt->fetch();
        
        // B. BPA WORKFLOW: Get POs that need funding (Ordered but not yet disbursed)
        $poSql = "SELECT po.id as po_id, po.total_cost, s.company_name, po.created_at 
                  FROM purchase_orders po 
                  JOIN suppliers s ON po.supplier_id = s.id 
                  WHERE po.id NOT IN (SELECT po_id FROM disbursements WHERE po_id IS NOT NULL)";
        $poStmt = $pdo->query($poSql);
        $pending_funding = $poStmt->fetchAll();

        // C. Get Recent Disbursements
        $disbSql = "SELECT d.*, po.supplier_id 
                    FROM disbursements d 
                    LEFT JOIN purchase_orders po ON d.po_id = po.id 
                    ORDER BY d.id DESC LIMIT 20";
        $disbStmt = $pdo->query($disbSql);
        $disbursements = $disbStmt->fetchAll();

        echo json_encode([
            'status' => 'success', 
            'budget' => $budget, 
            'pending' => $pending_funding, 
            'disbursements' => $disbursements
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
    }
    exit;
}

// 2. PROCESS BPA ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Release Funds for a Purchase Order
    if ($input['action'] === 'release_funds') {
        try {
            $pdo->beginTransaction();
            $po_id = $input['po_id'];
            $amount = $input['amount'];

            // 1. Record the Disbursement
            $stmt = $pdo->prepare("INSERT INTO disbursements (po_id, amount, status, release_date) VALUES (?, ?, 'Released', NOW())");
            $stmt->execute([$po_id, $amount]);

            // 2. Deduct from the Master Budget (increase allocated amount)
            $upd = $pdo->prepare("UPDATE budgets SET allocated_amount = allocated_amount + ? WHERE id = 1");
            $upd->execute([$amount]);

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Funds Released & Budget Updated!']);
        } catch(Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
?>
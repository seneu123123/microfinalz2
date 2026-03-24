<?php
// api/projects.php
header('Content-Type: application/json');
session_start();
require 'db.php';

// Security check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

// 1. GET ALL PROJECTS
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_projects') {
    try {
        $stmt = $pdo->query("SELECT * FROM projects ORDER BY id DESC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
    }
    exit;
}

// 2. POST ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // A. Create a New Project
    if ($input['action'] === 'create_project') {
        try {
            $stmt = $pdo->prepare("INSERT INTO projects (project_name, description, budget_limit, status, start_date) VALUES (?, ?, ?, 'Planning', NOW())");
            $stmt->execute([$input['project_name'], $input['description'], $input['budget_limit']]);
            echo json_encode(['status' => 'success', 'message' => 'Project Created Successfully!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // B. BPA WORKFLOW: Auto-Draft Requisition from Project
    if ($input['action'] === 'auto_draft_req') {
        try {
            $pdo->beginTransaction();
            
            // 1. Create Requisition linked to this Project
            $remarks = "Auto-drafted materials for Project: " . $input['project_name'];
            $stmt = $pdo->prepare("INSERT INTO requisitions (user_id, project_id, request_date, status, remarks, priority) VALUES (?, ?, NOW(), 'Pending', ?, 'High')");
            $stmt->execute([$_SESSION['user_id'], $input['project_id'], $remarks]);
            $req_id = $pdo->lastInsertId();

            // 2. Add the requested items to requisition_items
            $itemStmt = $pdo->prepare("INSERT INTO requisition_items (requisition_id, item_name, quantity, unit, estimated_cost) VALUES (?, ?, ?, ?, ?)");
            foreach($input['items'] as $item) {
                $itemStmt->execute([$req_id, $item['name'], $item['qty'], $item['unit'], $item['cost']]);
            }

            // 3. Update the Project Status to 'Active' automatically
            $updateProj = $pdo->prepare("UPDATE projects SET status = 'Active' WHERE id = ?");
            $updateProj->execute([$input['project_id']]);

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'BPA Triggered: Requisition sent to Procurement!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
?>
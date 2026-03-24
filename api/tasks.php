<?php
// api/tasks.php
header('Content-Type: application/json');
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all projects for the dropdown
    if ($action === 'get_projects') {
        $stmt = $pdo->query("SELECT id, project_name FROM projects ORDER BY project_name ASC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }

    // Get all tasks with project names
    if ($action === 'get_tasks') {
        $sql = "SELECT t.*, p.project_name FROM tasks t 
                JOIN projects p ON t.project_id = p.id 
                ORDER BY t.due_date ASC, t.id DESC";
        $stmt = $pdo->query($sql);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($input['action'] === 'create_task') {
        try {
            $stmt = $pdo->prepare("INSERT INTO tasks (project_id, task_name, assigned_to, due_date, priority, status) VALUES (?, ?, ?, ?, ?, 'To Do')");
            $stmt->execute([$input['project_id'], $input['task_name'], $input['assigned_to'], $input['due_date'], $input['priority']]);
            echo json_encode(['status' => 'success', 'message' => 'Task assigned successfully!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($input['action'] === 'update_status') {
        try {
            $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
            $stmt->execute([$input['status'], $input['task_id']]);
            echo json_encode(['status' => 'success', 'message' => 'Status updated!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}
?>
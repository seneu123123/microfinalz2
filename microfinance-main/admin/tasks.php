<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
$page = 'tasks.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>2.2 Task Management</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../img/logo.png" />
  </head>
  <body>
    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
      <i data-lucide="sun" class="sun-icon"></i>
      <i data-lucide="moon" class="moon-icon"></i>
    </button>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
      <header class="page-header">
        <div class="header-left">
          <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i data-lucide="menu"></i>
          </button>
          <div class="header-title">
            <h1>Task Management</h1>
          </div>
        </div>
        <div class="header-right">
          <button class="action-btn" onclick="openTaskModal()" style="background: var(--brand-green); color: white">
            <i data-lucide="check-square"></i> Assign New Task
          </button>
        </div>
      </header>

      <div class="content-wrapper">
        <div class="content-card">
          <div class="card-header">
            <h2 class="card-title">Project Task List</h2>
            <small>Detailed breakdown of Logistics 1 project milestones.</small>
          </div>
          <div class="card-body">
            <div class="data-table" id="tasksTable">
              <p style="text-align: center; padding: 20px">Loading tasks...</p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      let projects = [];

      async function fetchProjects() {
          const res = await fetch("../api/tasks.php?action=get_projects");
          const data = await res.json();
          if(data.status === 'success') projects = data.data;
      }

      async function loadTasks() {
        try {
          const res = await fetch("../api/tasks.php?action=get_tasks");
          const data = await res.json();

          if (data.status === "success" && data.data.length > 0) {
            const html = data.data.map(t => {
                let statusClass = t.status.toLowerCase().replace(" ", "");
                let priorityColor = t.priority === 'Urgent' ? '#ef4444' : (t.priority === 'High' ? '#f59e0b' : '#3b82f6');

                return `
                <div class="table-row">
                    <div class="client-info">
                        <strong>${t.task_name}</strong>
                        <br><small style="color:var(--text-tertiary);">Project: ${t.project_name} | Assigned to: ${t.assigned_to || 'Unassigned'}</small>
                    </div>
                    <div class="amount">
                        <span style="color:${priorityColor}; font-weight:700;">${t.priority}</span>
                        <br><small style="color:var(--text-secondary);">Due: ${t.due_date || 'No date'}</small>
                    </div>
                    <div>
                        <select onchange="updateTaskStatus(${t.id}, this.value)" class="swal2-select" style="margin:0; padding:4px 8px; font-size:12px; width:auto;">
                            <option value="To Do" ${t.status === 'To Do' ? 'selected' : ''}>To Do</option>
                            <option value="In Progress" ${t.status === 'In Progress' ? 'selected' : ''}>In Progress</option>
                            <option value="Done" ${t.status === 'Done' ? 'selected' : ''}>Done</option>
                            <option value="Blocked" ${t.status === 'Blocked' ? 'selected' : ''}>Blocked</option>
                        </select>
                    </div>
                </div>
            `}).join("");
            document.getElementById("tasksTable").innerHTML = html;
          } else {
            document.getElementById("tasksTable").innerHTML = '<p style="text-align:center; padding:20px; color:var(--text-tertiary);">No tasks assigned yet.</p>';
          }
        } catch (e) { console.error(e); }
      }

      async function openTaskModal() {
        if(projects.length === 0) return Swal.fire("Notice", "Create a project in 2.1 Project Planning first.", "info");

        const options = projects.map(p => `<option value="${p.id}">${p.project_name}</option>`).join("");
        
        const { value: formValues } = await Swal.fire({
          title: "Assign New Task",
          width: '500px',
          html: `
            <div style="text-align:left; padding-top: 10px;">
                <label>Select Project</label>
                <select id="task-proj" class="swal2-select">${options}</select>

                <label>Task Description</label>
                <input id="task-name" class="swal2-input" placeholder="e.g. Conduct Inventory Audit">

                <label>Assigned Staff</label>
                <input id="task-staff" class="swal2-input" placeholder="e.g. John Doe">

                <div style="display: flex; gap: 16px;">
                    <div style="flex: 1;">
                        <label>Due Date</label>
                        <input id="task-due" type="date" class="swal2-input">
                    </div>
                    <div style="flex: 1;">
                        <label>Priority</label>
                        <select id="task-priority" class="swal2-select">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                </div>
            </div>
          `,
          showCancelButton: true,
          confirmButtonColor: "#2ca078",
          confirmButtonText: "Assign Task",
          preConfirm: () => {
            const name = document.getElementById("task-name").value;
            if (!name) return Swal.showValidationMessage("Task description is required.");
            return { 
                action: 'create_task', 
                project_id: document.getElementById("task-proj").value,
                task_name: name,
                assigned_to: document.getElementById("task-staff").value,
                due_date: document.getElementById("task-due").value,
                priority: document.getElementById("task-priority").value
            };
          },
        });

        if (formValues) {
            const res = await fetch("../api/tasks.php", { method: "POST", body: JSON.stringify(formValues) });
            const data = await res.json();
            if (data.status === "success") {
                Swal.fire("Success!", data.message, "success");
                loadTasks();
            } else { Swal.fire("Error", data.message, "error"); }
        }
      }

      async function updateTaskStatus(taskId, newStatus) {
          const res = await fetch("../api/tasks.php", { 
              method: "POST", 
              body: JSON.stringify({ action: 'update_status', task_id: taskId, status: newStatus }) 
          });
          const data = await res.json();
          if (data.status === "success") {
              loadTasks();
          }
      }

      fetchProjects();
      loadTasks();
    </script>
  </body>
</html>
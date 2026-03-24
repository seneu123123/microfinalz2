<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
$page = 'mro_planning.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" /><title>4.1 Maintenance Planning</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  </head>
  <body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
      <header class="page-header">
          <h1>Preventive Maintenance Planning</h1>
          <button class="action-btn" onclick="openPlanModal()" style="background:var(--brand-green); color:white;"><i data-lucide="calendar"></i> Schedule Task</button>
      </header>
      <div class="content-wrapper">
        <div class="content-card">
          <div class="card-header"><h2 class="card-title">Maintenance Schedule</h2></div>
          <div class="card-body"><div class="data-table" id="planningTable"></div></div>
        </div>
      </div>
    </main>
    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      async function loadSchedule() {
          const res = await fetch("../api/mro_planning.php");
          const data = await res.json();
          if (data.status === "success") {
              document.getElementById("planningTable").innerHTML = data.data.map(s => `
                <div class="table-row">
                    <div class="client-info"><strong>${s.asset_name}</strong><br><small>${s.task_description}</small></div>
                    <div class="amount">Next Due: ${s.next_due_date}</div>
                    <div style="text-align:right;"><span class="badge-status pending">${s.status}</span></div>
                </div>
              `).join("");
          }
      }
      loadSchedule();
    </script>
  </body>
</html>
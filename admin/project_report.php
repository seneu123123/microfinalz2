<?php
session_start();
$page = 'project_report.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" /><title>2.4 Project Reporting</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  </head>
  <body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
      <header class="page-header"><h1>Project Financial Analytics</h1></header>
      <div class="content-wrapper">
        <div class="content-card">
          <div class="card-header"><h2 class="card-title">Budget Utilization by Project</h2></div>
          <div class="card-body"><canvas id="projectChart" height="150"></canvas></div>
        </div>
      </div>
    </main>
    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      async function initProjectReport() {
          const res = await fetch("../api/project_report.php");
          const data = await res.json();
          if (data.status === 'success') {
              const ctx = document.getElementById('projectChart').getContext('2d');
              new Chart(ctx, {
                  type: 'bar',
                  data: {
                      labels: data.data.map(p => p.project_name),
                      datasets: [
                          { label: 'Allocated', data: data.data.map(p => p.allocated_budget), backgroundColor: '#3b82f6' },
                          { label: 'Used', data: data.data.map(p => p.used_budget), backgroundColor: '#ef4444' }
                      ]
                  },
                  options: { responsive: true, scales: { y: { beginAtZero: true } } }
              });
          }
      }
      initProjectReport();
    </script>
  </body>
</html>
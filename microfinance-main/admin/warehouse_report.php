<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
$page = 'warehouse_report.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" /><title>5.5 Warehousing Reporting</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  </head>
  <body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
      <header class="page-header"><h1>Warehouse Operations Report</h1></header>
      <div class="content-wrapper">
        <div class="content-card">
          <div class="card-header"><h2 class="card-title">Inventory Distribution by Zone</h2></div>
          <div class="card-body"><canvas id="warehouseChart" height="150"></canvas></div>
        </div>
      </div>
    </main>
    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      async function initWarehouseReport() {
          const res = await fetch("../api/warehouse_report.php");
          const data = await res.json();
          if (data.status === 'success') {
              const ctx = document.getElementById('warehouseChart').getContext('2d');
              new Chart(ctx, {
                  type: 'pie',
                  data: {
                      labels: data.data.map(z => z.zone_name),
                      datasets: [{
                          data: data.data.map(z => z.item_count),
                          backgroundColor: ['#2ca078', '#3b82f6', '#f59e0b', '#ef4444']
                      }]
                  }
              });
          }
      }
      initWarehouseReport();
    </script>
  </body>
</html>
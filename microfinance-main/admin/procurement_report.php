<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
$page = 'procurement_report.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>1.4 Procurement Reporting</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="../img/logo.png" />
  </head>
  <body>
    <button class="theme-toggle" id="themeToggle"><i data-lucide="sun" class="sun-icon"></i><i data-lucide="moon" class="moon-icon"></i></button>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
      <header class="page-header">
        <div class="header-left"><h1>Procurement Analytics</h1></div>
        <div class="header-right">
          <button class="action-btn" onclick="window.print()" style="background:var(--surface-hover); color:var(--text-primary); border:1px solid var(--border-color);">
            <i data-lucide="printer"></i> Export PDF
          </button>
        </div>
      </header>

      <div class="content-wrapper">
        <div class="stats-grid" style="margin-bottom: 24px;">
            <div class="stat-card">
                <span class="stat-label">Total Procurement Value</span>
                <h3 class="stat-value" id="total-spend-val">$0.00</h3>
            </div>
            <div class="stat-card">
                <span class="stat-label">Active Suppliers</span>
                <h3 class="stat-value" id="active-suppliers-val">0</h3>
            </div>
        </div>

        <div class="content-grid" style="grid-template-columns: 2fr 1fr; gap: 24px;">
          <div class="content-card">
            <div class="card-header"><h2 class="card-title">Monthly Spend Trend</h2></div>
            <div class="card-body"><canvas id="spendChart" height="200"></canvas></div>
          </div>

          <div class="content-card">
            <div class="card-header"><h2 class="card-title">Supplier Distribution</h2></div>
            <div class="card-body"><canvas id="supplierChart" height="300"></canvas></div>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();

      async function initReports() {
        const trendRes = await fetch("../api/procurement_report.php?action=get_spend_trend");
        const trendData = await trendRes.json();
        const suppRes = await fetch("../api/procurement_report.php?action=get_supplier_stats");
        const suppData = await suppRes.json();

        if (trendData.status === 'success') {
          const ctx = document.getElementById('spendChart').getContext('2d');
          new Chart(ctx, {
            type: 'line',
            data: {
              labels: trendData.data.map(d => d.month),
              datasets: [{
                label: 'Monthly Spend ($)',
                data: trendData.data.map(d => d.total),
                borderColor: '#2ca078',
                backgroundColor: 'rgba(44, 160, 120, 0.1)',
                fill: true,
                tension: 0.4
              }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
          });

          const total = trendData.data.reduce((acc, curr) => acc + parseFloat(curr.total), 0);
          document.getElementById('total-spend-val').innerText = '$' + total.toLocaleString();
        }

        if (suppData.status === 'success') {
          document.getElementById('active-suppliers-val').innerText = suppData.data.length;
          const ctx = document.getElementById('supplierChart').getContext('2d');
          new Chart(ctx, {
            type: 'doughnut',
            data: {
              labels: suppData.data.map(s => s.supplier_name),
              datasets: [{
                data: suppData.data.map(s => s.total_spend),
                backgroundColor: ['#2ca078', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6']
              }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
          });
        }
      }
      initReports();
    </script>
  </body>
</html>
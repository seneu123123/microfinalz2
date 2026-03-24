<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
$page = 'monitoring.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" /><title>3.2 Asset Monitoring</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../img/logo.png" />
  </head>
  <body>
    <button class="theme-toggle" id="themeToggle"><i data-lucide="sun" class="sun-icon"></i><i data-lucide="moon" class="moon-icon"></i></button>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
      <header class="page-header">
        <div class="header-left"><h1>Asset Health Monitoring</h1></div>
        <div class="header-right">
          <button class="action-btn" onclick="openMonitorModal()" style="background:var(--brand-green); color:white;">
            <i data-lucide="activity"></i> Log Inspection
          </button>
        </div>
      </header>

      <div class="content-wrapper">
        <div class="content-card">
          <div class="card-header"><h2 class="card-title">Real-time Asset Status</h2><small>Monitor mechanical health and usage readings.</small></div>
          <div class="card-body">
            <div class="data-table" id="monitorTable"><p style="text-align:center; padding:20px;">Loading assets...</p></div>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      let assets = [];

      async function loadStatus() {
          const res = await fetch("../api/monitoring.php?action=get_status");
          const data = await res.json();
          if(data.status === 'success') {
              assets = data.data;
              document.getElementById("monitorTable").innerHTML = assets.map(a => {
                  let health = a.health_percentage || 100;
                  let color = health < 40 ? '#ef4444' : (health < 75 ? '#f59e0b' : 'var(--brand-green)');
                  return `
                <div class="table-row">
                    <div class="client-info"><strong>${a.asset_name}</strong><br><small>S/N: ${a.serial_number}</small></div>
                    <div>${a.usage_reading || 'No data'}</div>
                    <div style="width:150px;">
                        <div style="font-size:11px; margin-bottom:4px;">Health: ${health}%</div>
                        <div style="height:6px; background:#eee; border-radius:10px; overflow:hidden;">
                            <div style="width:${health}%; height:100%; background:${color};"></div>
                        </div>
                    </div>
                </div>
              `}).join("");
          }
      }

      async function openMonitorModal() {
        const options = assets.map(a => `<option value="${a.id}">${a.asset_name}</option>`).join("");
        const { value: formValues } = await Swal.fire({
          title: "Log Asset Inspection",
          html: `<label>Select Asset</label><select id="m-asset" class="swal2-select">${options}</select>
                 <label>Estimated Health (%)</label><input id="m-health" type="number" class="swal2-input" value="100">
                 <label>Usage Reading (e.g., KM or Hours)</label><input id="m-read" class="swal2-input" placeholder="e.g. 12,450 KM">`,
          confirmButtonText: "Save Inspection",
          preConfirm: () => { return { action: 'update_monitoring', asset_id: document.getElementById('m-asset').value, health: document.getElementById('m-health').value, reading: document.getElementById('m-read').value }; }
        });
        if (formValues) {
            const res = await fetch("../api/monitoring.php", { method: "POST", body: JSON.stringify(formValues) });
            const data = await res.json();
            if (data.status === "success") { Swal.fire("Logged", data.message, "success"); loadStatus(); }
        }
      }
      loadStatus();
    </script>
  </body>
</html> 
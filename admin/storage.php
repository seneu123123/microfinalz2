<?php
session_start();
$page = 'storage.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>5.2 Storage Management</title>
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
        <div class="header-left"><h1>Storage Management</h1></div>
        <div class="header-right">
          <button class="action-btn" onclick="openAssignModal()" style="background:var(--brand-green); color:white;">
            <i data-lucide="map-pin"></i> Assign Item Location
          </button>
        </div>
      </header>

      <div class="content-wrapper">
        <div class="content-card">
          <div class="card-header"><h2 class="card-title">Warehouse Zones</h2><small>Monitor capacity and item distribution.</small></div>
          <div class="card-body">
            <div class="data-table" id="zoneTable"><p style="text-align:center; padding:20px;">Loading...</p></div>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      let zones = [];
      let items = [];

      async function loadData() {
          const zRes = await fetch("../api/storage.php?action=get_zones");
          const zData = await zRes.json();
          const iRes = await fetch("../api/storage.php?action=get_unassigned");
          const iData = await iRes.json();

          if(zData.status === 'success') {
              zones = zData.data;
              document.getElementById("zoneTable").innerHTML = zones.map(z => `
                <div class="table-row">
                    <div class="client-info"><strong>${z.zone_name}</strong><br><small>${z.description}</small></div>
                    <div>${z.item_count} Items Stored</div>
                    <div style="text-align:right;"><span class="badge-status ${z.item_count > 10 ? 'pending' : 'approved'}">Capacity: ${z.capacity_level}</span></div>
                </div>
              `).join("");
          }
          if(iData.status === 'success') items = iData.data;
      }

      async function openAssignModal() {
        const zOpt = zones.map(z => `<option value="${z.id}">${z.zone_name}</option>`).join("");
        const iOpt = items.map(i => `<option value="${i.id}">${i.item_name} (${i.quantity} in stock)</option>`).join("");
        
        const { value: formValues } = await Swal.fire({
          title: "Assign Storage Location",
          html: `<label>Select Item</label><select id="s-item" class="swal2-select">${iOpt}</select>
                 <label>Target Storage Zone</label><select id="s-zone" class="swal2-select">${zOpt}</select>`,
          confirmButtonColor: "#2ca078", confirmButtonText: "Update Location",
          preConfirm: () => { return { action: 'assign_zone', item_id: document.getElementById('s-item').value, zone_id: document.getElementById('s-zone').value }; }
        });
        if (formValues) {
            const res = await fetch("../api/storage.php", { method: "POST", body: JSON.stringify(formValues) });
            const data = await res.json();
            if (data.status === "success") { Swal.fire("Updated", data.message, "success"); loadData(); }
        }
      }
      loadData();
    </script>
  </body>
</html> 
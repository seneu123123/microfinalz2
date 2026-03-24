<?php
session_start();
$page = 'disposal.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" /><title>3.4 Asset Disposal</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../img/logo.png" />
  </head>
  <body>
    <button class="theme-toggle" id="themeToggle"><i data-lucide="sun" class="sun-icon"></i><i data-lucide="moon" class="moon-icon"></i></button>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
      <header class="page-header"><div class="header-left"><h1>Asset Disposal</h1></div>
        <div class="header-right"><button class="action-btn" onclick="openDisposalModal()" style="background:#ef4444; color:white;"><i data-lucide="trash-2"></i> Retire Asset</button></div>
      </header>
      <div class="content-wrapper">
        <div class="content-card">
          <div class="card-header"><h2 class="card-title">Decommissioned Assets</h2></div>
          <div class="card-body"><div class="data-table" id="disposalTable"><p style="text-align:center; padding:20px;">Loading...</p></div></div>
        </div>
      </div>
    </main>
    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      let assetList = [];
      async function fetchAssets() {
          const res = await fetch("../api/disposal.php?action=get_disposable");
          const data = await res.json();
          if(data.status === 'success') assetList = data.data;
      }
      async function loadHistory() {
          const res = await fetch("../api/disposal.php?action=get_history");
          const data = await res.json();
          if(data.status === 'success') {
              const html = data.data.map(a => `<div class="table-row">
                  <div class="client-info"><strong>${a.asset_name}</strong><br><small>S/N: ${a.serial_number}</small></div>
                  <div><span class="badge-status rejected">${a.status}</span></div>
              </div>`).join("");
              document.getElementById("disposalTable").innerHTML = html || '<p style="padding:20px; text-align:center;">No retired assets.</p>';
          }
      }
      async function openDisposalModal() {
        const options = assetList.map(a => `<option value="${a.id}">${a.asset_name}</option>`).join("");
        const { value: formValues } = await Swal.fire({
          title: "Dispose of Asset",
          html: `<label>Select Asset</label><select id="d-asset" class="swal2-select">${options}</select>
                 <label>Disposal Reason</label><select id="d-reason" class="swal2-select"><option value="Retired">End of Life (Retired)</option><option value="Lost">Lost / Stolen</option></select>`,
          confirmButtonColor: "#ef4444", confirmButtonText: "Decommission",
          preConfirm: () => { return { action: 'dispose_asset', asset_id: document.getElementById("d-asset").value, reason: document.getElementById("d-reason").value }; }
        });
        if (formValues) {
            const res = await fetch("../api/disposal.php", { method: "POST", body: JSON.stringify(formValues) });
            const data = await res.json();
            if (data.status === "success") { Swal.fire("Success", data.message, "success"); fetchAssets(); loadHistory(); }
        }
      }
      fetchAssets(); loadHistory();
    </script>
  </body>
</html>
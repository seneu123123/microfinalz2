<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
$page = 'mro.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>MRO & Maintenance</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../img/logo.png" />
    <style>
      /* Clean up SweetAlert spacing */
      .swal2-html-container label:empty { display: none !important; }
      .swal2-html-container label { display: block; text-align: left; margin-top: 15px; font-weight: 600; color: var(--brand-green); font-size: 12px; text-transform: uppercase; }
    </style>
  </head>
  <body>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
      <header class="page-header">
        <div class="header-left"><h1>Maintenance & Repair (MRO)</h1></div>
        <div class="header-right">
          <button class="action-btn" onclick="openIssueModal()" style="background: #ef4444; color: white"><i data-lucide="alert-triangle"></i> Report Issue</button>
        </div>
      </header>

      <div class="content-wrapper">
        <div class="content-card">
          <div class="card-header"><h2 class="card-title">Work Order Queue</h2><small>Manage repairs and automated parts procurement (BPA).</small></div>
          <div class="card-body"><div class="data-table" id="mroTable"><p style="text-align:center; padding:20px;">Loading...</p></div></div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      let assetList = [];

      async function fetchAssets() {
          const res = await fetch("../api/mro.php?action=get_assets");
          const data = await res.json();
          if(data.status === 'success') assetList = data.data;
      }

      async function loadRequests() {
        try {
          const res = await fetch("../api/mro.php?action=get_requests");
          const data = await res.json();
          if (data.status === "success" && data.data.length > 0) {
            document.getElementById("mroTable").innerHTML = data.data.map(r => `
                <div class="table-row">
                    <div class="client-info"><strong>${r.asset_name}</strong><br><small style="color:#ef4444;">${r.issue_description}</small></div>
                    <div class="amount">Priority: ${r.priority}</div>
                    <div><span class="badge-status ${r.status === 'Resolved' ? 'approved' : 'pending'}">${r.status}</span></div>
                    <div style="text-align:right;">
                        ${r.status === 'Pending' ? `<button onclick="openWorkOrderModal(${r.id}, '${r.asset_name}')" class="action-btn" style="background:#3b82f6; color:white; font-size:11px;">Create Work Order</button>` : `<small>ID: #${r.id}</small>`}
                    </div>
                </div>`).join("");
          } else { document.getElementById("mroTable").innerHTML = '<p style="text-align:center; padding:20px;">No active requests.</p>'; }
        } catch (e) { console.error(e); }
      }

      async function openIssueModal() {
        const options = assetList.map(a => `<option value="${a.id}">${a.asset_name}</option>`).join("");
        const { value: formValues } = await Swal.fire({
          title: "Report Issue",
          html: `<label>Select Asset</label><select id="swal-asset" class="swal2-select">${options}</select>
                 <label>Issue Description</label><textarea id="swal-desc" class="swal2-textarea"></textarea>
                 <label>Priority</label><select id="swal-priority" class="swal2-select"><option value="Normal">Normal</option><option value="Urgent">Urgent</option></select>`,
          confirmButtonText: "Submit",
          preConfirm: () => { return { action: 'create_request', asset_id: document.getElementById("swal-asset").value, description: document.getElementById("swal-desc").value, priority: document.getElementById("swal-priority").value }; }
        });
        if (formValues) {
            const res = await fetch("../api/mro.php", { method: "POST", body: JSON.stringify(formValues) });
            const data = await res.json();
            if (data.status === "success") { loadRequests(); Swal.fire("Success", data.message, "success"); }
        }
      }

      async function openWorkOrderModal(reqId, assetName) {
        const { value: formValues } = await Swal.fire({
          title: "Dispatch Work Order",
          html: `<div style="text-align:left;"><label>Assign Technician</label><input id="wo-tech" class="swal2-input" placeholder="Technician Name">
                 <hr style="margin:20px 0; opacity:0.1;"><label style="color:#3b82f6;">Spare Parts Automation</label>
                 <div style="display:flex; gap:10px; align-items:center; margin-top:10px;">
                    <input type="checkbox" id="wo-parts-needed" onchange="document.getElementById('p-form').style.display = this.checked ? 'block' : 'none'">
                    <span>Request parts from Procurement?</span>
                 </div>
                 <div id="p-form" style="display:none; margin-top:15px;">
                    <label>Part Name</label><input id="p-name" class="swal2-input">
                    <label>Qty</label><input id="p-qty" type="number" class="swal2-input">
                 </div></div>`,
          confirmButtonText: "Dispatch",
          preConfirm: () => { return { action: 'generate_work_order', request_id: reqId, technician: document.getElementById("wo-tech").value, parts_needed: document.getElementById("wo-parts-needed").checked, part_name: document.getElementById("p-name").value, part_qty: document.getElementById("p-qty").value || 1 }; }
        });
        if (formValues) {
            const res = await fetch("../api/mro.php", { method: "POST", body: JSON.stringify(formValues) });
            const data = await res.json();
            if (data.status === "success") { loadRequests(); Swal.fire("Dispatched", data.message, "success"); }
        }
      }

      fetchAssets();
      loadRequests();
    </script>
  </body>
</html>
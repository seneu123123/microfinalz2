<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
$page = 'mro.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MRO & Maintenance</title>
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
            <h1>Maintenance, Repair & Operations</h1>
          </div>
        </div>
        <div class="header-right">
          <button class="action-btn" onclick="openIssueModal()" style="background: #ef4444; color: white">
            <i data-lucide="alert-triangle"></i> Report Issue
          </button>
        </div>
      </header>

      <div class="content-wrapper">
        <div class="content-card">
          <div class="card-header">
            <h2 class="card-title">Maintenance Queue</h2>
            <small>Track broken assets and auto-request spare parts (BPA).</small>
          </div>
          <div class="card-body">
            <div class="data-table" id="mroTable">
              <p style="text-align: center; padding: 20px">Loading requests...</p>
            </div>
          </div>
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
            const html = data.data.map(r => {
                let statusColor = r.status === 'Resolved' ? 'approved' : (r.status === 'Pending' ? 'pending' : 'ordered');
                
                let actionBtn = '';
                if (r.status === 'Pending') {
                    actionBtn = `<button onclick="openWorkOrderModal(${r.id}, '${r.asset_name}')" class="action-btn" style="background:#3b82f6; color:white; padding:6px 12px; font-size:12px;">Create Work Order</button>`;
                } else {
                    actionBtn = `<span style="font-size:12px; color:#666;">WO: ${r.wo_status || 'N/A'}</span>`;
                }

                return `
                <div class="table-row">
                    <div class="client-info">
                        <strong>${r.asset_name}</strong>
                        <br><small style="color:#ef4444;">Issue: ${r.issue_description}</small>
                    </div>
                    <div class="amount">Priority: ${r.priority}</div>
                    <div>
                        <span class="badge-status ${statusColor}">${r.status}</span>
                    </div>
                    <div style="text-align:right;">${actionBtn}</div>
                </div>
            `}).join("");
            document.getElementById("mroTable").innerHTML = html;
          } else {
            document.getElementById("mroTable").innerHTML = '<p style="text-align:center; padding:20px; color:#888;">No maintenance requests found.</p>';
          }
        } catch (e) { console.error(e); }
      }

      async function openIssueModal() {
        const options = assetList.map(a => `<option value="${a.id}">${a.asset_name} (SN: ${a.serial_number || 'N/A'})</option>`).join("");
        
        const { value: formValues } = await Swal.fire({
          title: "Report Asset Issue",
          html: `
            <div style="text-align:left;">
                <label>Select Asset</label>
                <select id="swal-asset" class="swal2-input">${options}</select>
                <label>Describe the Issue</label>
                <textarea id="swal-desc" class="swal2-textarea" placeholder="What is broken?"></textarea>
                <label>Priority</label>
                <select id="swal-priority" class="swal2-input">
                    <option value="Normal">Normal</option>
                    <option value="High">High</option>
                    <option value="Urgent">Urgent (Operation Halted)</option>
                </select>
            </div>
          `,
          showCancelButton: true,
          confirmButtonColor: "#ef4444",
          confirmButtonText: "Submit Request",
          preConfirm: () => {
            const asset = document.getElementById("swal-asset").value;
            const desc = document.getElementById("swal-desc").value;
            if (!asset || !desc) return Swal.showValidationMessage("Asset and description are required.");
            return { 
                action: 'create_request',
                asset_id: asset, 
                description: desc, 
                priority: document.getElementById("swal-priority").value
            };
          },
        });

        if (formValues) {
          const res = await fetch("../api/mro.php", { method: "POST", body: JSON.stringify(formValues) });
          const data = await res.json();
          if (data.status === "success") {
            Swal.fire("Success", data.message, "success");
            loadRequests();
          } else { Swal.fire("Error", data.message, "error"); }
        }
      }

      async function openWorkOrderModal(reqId, assetName) {
        const { value: formValues } = await Swal.fire({
          title: "Generate Work Order",
          width: '600px',
          html: `
            <div style="text-align:left; padding-top: 10px;">
                <div style="margin-bottom: 24px;">
                    <label>Step 1: Asset Details</label>
                    <div style="background: var(--surface-hover); padding: 14px; border-radius: 8px; border: 1px solid var(--border-color); font-size: 14px; margin-top: 8px;">
                        <strong>Asset Name:</strong> ${assetName}<br>
                        <span style="color: var(--text-secondary); font-size: 12px;">Maintenance Request ID: #${reqId}</span>
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label>Step 2: Task Assignment</label>
                    <input id="wo-tech" class="swal2-input" placeholder="e.g. John Doe">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label>Step 3: Procurement Automation (BPA)</label>
                    <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); padding: 16px; border-radius: 10px;">
                        <label class="checkbox-wrapper" style="cursor:pointer; display:flex; gap:12px; align-items:flex-start;">
                            <input type="checkbox" id="wo-parts-needed" onchange="document.getElementById('parts-container').style.display = this.checked ? 'block' : 'none';" style="margin-top:4px;">
                            <div>
                                <strong style="color: #1e3a8a; font-size: 14px;">Trigger Spare Parts Requisition?</strong>
                                <span style="display:block; color: #3b82f6; font-size: 11px;">Automatically drafts an order to the Procurement department.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div id="parts-container" style="display:none; border-left: 3px solid #3b82f6; padding-left: 18px; margin-top: 16px;">
                    <label>Part Name</label>
                    <input id="part-name" class="swal2-input" placeholder="e.g. Engine Alternator">
                    <div style="display: flex; gap: 16px;">
                        <div style="flex: 1;"><label>Qty</label><input id="part-qty" type="number" class="swal2-input"></div>
                        <div style="flex: 1;"><label>Cost</label><input id="part-cost" type="number" class="swal2-input"></div>
                    </div>
                </div>
            </div>
          `,
          showCancelButton: true,
          confirmButtonColor: "#2ca078",
          confirmButtonText: "Generate & Dispatch",
          preConfirm: () => {
            const tech = document.getElementById("wo-tech").value;
            if (!tech) return Swal.showValidationMessage("Please assign a technician.");
            return { 
                action: 'generate_work_order',
                request_id: reqId,
                technician: tech,
                parts_needed: document.getElementById("wo-parts-needed").checked,
                part_name: document.getElementById("part-name").value,
                part_qty: document.getElementById("part-qty").value || 1,
                cost: document.getElementById("part-cost").value || 0
            };
          },
        });

        if (formValues) {
          const res = await fetch("../api/mro.php", { method: "POST", body: JSON.stringify(formValues) });
          const data = await res.json();
          if (data.status === "success") {
            Swal.fire("Success", data.message, "success");
            loadRequests();
          } else { Swal.fire("Error", data.message, "error"); }
        }
      }

      fetchAssets();
      loadRequests();
    </script>
  </body>
</html>
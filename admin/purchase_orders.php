<?php
session_start();
$page = 'purchase_orders.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Purchase Orders</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../img/logo.png" />
    <style>
      .badge-status.pending { background: #ffeeba; color: #856404; }
      .badge-status.approved { background: #d4edda; color: #155724; }
      .badge-status.ordered { background: #cce5ff; color: #004085; }
      .badge-status.received { background: #d1ecf1; color: #0c5460; }
      .badge-status.pocreated { background: #e2e8f0; color: #475569; }
    </style>
  </head>
  <body>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
      <header class="page-header">
        <div class="header-left">
          <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i data-lucide="menu"></i>
          </button>
          <div class="header-title">
            <h1>Purchase Order Management</h1>
          </div>
        </div>
        <div class="header-right">
          <button class="action-btn" onclick="openDirectPOModal()" style="background: var(--brand-green); color: white">
            <i data-lucide="plus-circle"></i> New Direct Order
          </button>
        </div>
      </header>

      <div class="content-wrapper">
        <div class="content-card" style="margin-bottom: 20px">
          <div class="card-header">
            <h2 class="card-title">1. Pending Requisitions</h2>
            <small>Approve requests here to start the PO process.</small>
          </div>
          <div class="card-body">
            <div class="data-table" id="pendingTable">Loading...</div>
          </div>
        </div>

        <div class="content-card">
          <div class="card-header">
            <h2 class="card-title">2. Active Purchase Orders</h2>
            <small>Receive items here to add them to Inventory.</small>
          </div>
          <div class="card-body">
            <div class="data-table" id="poTable">Loading...</div>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      let suppliers = [];

      async function fetchSuppliers() {
        try {
          const res = await fetch("../api/procurement.php?action=get_suppliers");
          const data = await res.json();
          if (data.status === "success") suppliers = data.data;
        } catch (e) { console.error(e); }
      }

      async function loadPending() {
        const res = await fetch("../api/po.php?action=get_pending_reqs");
        const data = await res.json();

        if (data.data && data.data.length > 0) {
          const html = data.data.map((r) => {
            let actionBtn = "";
            if (r.status === "Pending") {
              actionBtn = `<button onclick="approveReq(${r.id})" class="action-btn" style="background:#64748b; color:white; padding:6px 12px; font-size:12px;">Approve</button>`;
            } else if (r.status === "Approved") {
              actionBtn = `<button onclick="createPO(${r.id})" class="action-btn" style="background:var(--brand-yellow); color:black; padding:6px 12px; font-size:12px;">Create PO</button>`;
            } else {
              actionBtn = '<span style="color:grey; font-size:12px;">Processing...</span>';
            }

            return `
                <div class="table-row">
                    <div class="client-info">
                        <strong>REQ #${r.id}</strong><br><small>${r.remarks || "No remarks"}</small>
                    </div>
                    <div class="amount">${r.request_date}</div>
                    <div><span class="badge-status ${r.status.toLowerCase().replace(" ", "")}">${r.status}</span></div>
                    <div style="text-align:right;">${actionBtn}</div>
                </div>`;
          }).join("");
          document.getElementById("pendingTable").innerHTML = html;
        } else {
          document.getElementById("pendingTable").innerHTML = '<p style="padding:20px; text-align:center; color:#888;">No pending requests.</p>';
        }
      }

      async function loadPOs() {
        const res = await fetch("../api/po.php?action=get_orders");
        const data = await res.json();

        if (data.data && data.data.length > 0) {
          const html = data.data.map((po) => {
            let statusBtn = "";
            if (po.status === "Ordered") {
              statusBtn = `<button onclick="receivePO(${po.id})" class="action-btn" style="background:#3b82f6; color:white; padding:6px 12px; font-size:12px;">Receive Items</button>`;
            } else {
              statusBtn = `<span class="badge-status received">Received</span>`;
            }

            return `
                <div class="table-row">
                    <div class="client-info">
                        <strong>PO #${po.id}</strong> <span style="color:#888;">→</span> ${po.company_name}
                        <br><small style="color:#888;">Ref: ${po.remarks}</small>
                    </div>
                    <div class="amount">$${po.total_cost}</div>
                    <div style="text-align:right;">${statusBtn}</div>
                </div>
            `;
          }).join("");
          document.getElementById("poTable").innerHTML = html;
        } else {
          document.getElementById("poTable").innerHTML = '<p style="padding:20px; text-align:center; color:#888;">No orders yet.</p>';
        }
      }

      async function openDirectPOModal() {
        const options = suppliers.map((s) => `<option value="${s.id}">${s.company_name}</option>`).join("");
        const { value: formValues } = await Swal.fire({
          title: "Create Direct Order",
          html: `
            <label style="display:block; text-align:left; font-size:14px; margin-top:10px;">Select Supplier</label>
            <select id="swal-supplier" class="swal2-input">${options}</select>
            <label style="display:block; text-align:left; font-size:14px; margin-top:10px;">Order Description / Item</label>
            <input id="swal-desc" class="swal2-input" placeholder="e.g. Bulk Office Paper">
            <label style="display:block; text-align:left; font-size:14px; margin-top:10px;">Total Cost</label>
            <input id="swal-cost" type="number" class="swal2-input" placeholder="0.00">
          `,
          showCancelButton: true,
          confirmButtonColor: "#2ca078",
          confirmButtonText: "Create Order",
          preConfirm: () => {
            const sup = document.getElementById("swal-supplier").value;
            const desc = document.getElementById("swal-desc").value;
            const cost = document.getElementById("swal-cost").value;
            if (!sup || !desc || !cost) Swal.showValidationMessage("Please fill all fields");
            return { supplier_id: sup, description: desc, cost: cost };
          },
        });

        if (formValues) {
          const res = await fetch("../api/po.php", {
            method: "POST",
            body: JSON.stringify({ action: "create_direct_po", supplier_id: formValues.supplier_id, description: formValues.description, cost: formValues.cost }),
          });
          const data = await res.json();
          if (data.status === "success") {
            Swal.fire("Success", "Direct Order Created!", "success");
            loadPOs();
          } else {
            Swal.fire("Error", data.message, "error");
          }
        }
      }

      async function approveReq(id) {
        if (!confirm("Are you sure you want to approve this request?")) return;
        await fetch("../api/po.php", {
          method: "POST",
          body: JSON.stringify({ action: "approve_req", req_id: id }),
        });
        loadPending();
      }

      async function createPO(reqId) {
        const options = suppliers.map((s) => `<option value="${s.id}">${s.company_name}</option>`).join("");
        const { value: formValues } = await Swal.fire({
          title: "Generate Purchase Order",
          html: `
            <label style="display:block; text-align:left; margin-top:10px;">Select Supplier</label>
            <select id="swal-supplier" class="swal2-input">${options}</select>
            <label style="display:block; text-align:left; margin-top:10px;">Total Agreed Cost</label>
            <input id="swal-cost" type="number" class="swal2-input" placeholder="0.00">
          `,
          showCancelButton: true,
          confirmButtonColor: "#2ca078",
          confirmButtonText: "Generate PO",
          preConfirm: () => {
            return { supplier_id: document.getElementById("swal-supplier").value, cost: document.getElementById("swal-cost").value };
          },
        });

        if (formValues) {
          const res = await fetch("../api/po.php", {
            method: "POST",
            body: JSON.stringify({ action: "create_po", req_id: reqId, supplier_id: formValues.supplier_id, cost: formValues.cost }),
          });
          const data = await res.json();
          if (data.status === "success") {
            Swal.fire("Success", "PO Generated!", "success");
            loadPending();
            loadPOs();
          } else {
            Swal.fire("Error", data.message, "error");
          }
        }
      }

      async function receivePO(id) {
        const result = await Swal.fire({
          title: "Receive Items?",
          text: "This will add the items to your Warehouse Inventory.",
          icon: "question",
          showCancelButton: true,
          confirmButtonColor: "#3b82f6",
          confirmButtonText: "Yes, Receive Items",
        });

        if (result.isConfirmed) {
          const res = await fetch("../api/inventory.php", {
            method: "POST",
            body: JSON.stringify({ action: "receive_po", po_id: id }),
          });
          const data = await res.json();
          if (data.status === "success") {
            Swal.fire("Received!", "Items added to Inventory.", "success");
            loadPOs();
          } else {
            Swal.fire("Error", data.message || "Check API", "error");
          }
        }
      }

      fetchSuppliers();
      loadPending();
      loadPOs();
    </script>
  </body>
</html>
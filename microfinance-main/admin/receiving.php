<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
$page = 'receiving.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" /><title>5.3 Inbound Operations</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  </head>
  <body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
      <header class="page-header"><h1>Receiving Dock (Inbound)</h1></header>
      <div class="content-wrapper">
        <div class="content-card">
          <div class="card-header"><h2 class="card-title">Pending Shipments</h2><small>Click 'Receive' to move PO items into inventory.</small></div>
          <div class="card-body"><div class="data-table" id="receivingTable"><p style="text-align:center; padding:20px;">Checking for incoming shipments...</p></div></div>
        </div>
      </div>
    </main>
    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      async function loadIncoming() {
          const res = await fetch("../api/receiving.php");
          const data = await res.json();
          if (data.status === "success" && data.data.length > 0) {
              document.getElementById("receivingTable").innerHTML = data.data.map(po => `
                <div class="table-row">
                    <div class="client-info"><strong>PO #${po.id}</strong><br><small>Supplier: ${po.supplier_name}</small></div>
                    <div class="amount">${po.total_amount}</div>
                    <div style="text-align:right;">
                        <button onclick="receiveItems(${po.id}, '${po.supplier_name}', 10)" class="action-btn" style="background:var(--brand-green); color:white; padding:6px 12px; font-size:12px;">Mark as Received</button>
                    </div>
                </div>
              `).join("");
          } else { document.getElementById("receivingTable").innerHTML = '<p style="text-align:center; padding:20px;">No pending inbound orders.</p>'; }
      }
      async function receiveItems(id, name, qty) {
          const res = await fetch("../api/receiving.php", { method: "POST", body: JSON.stringify({ action: 'receive_items', po_id: id, item_name: 'PO Order Item', qty: qty }) });
          const data = await res.json();
          if (data.status === "success") { Swal.fire("Received", data.message, "success"); loadIncoming(); }
      }
      loadIncoming();
    </script>
  </body>
</html>
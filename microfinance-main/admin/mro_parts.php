<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
$page = 'mro_parts.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" /><title>4.3 Spare Parts & Supplies</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
  </head>
  <body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
      <header class="page-header"><h1>Spare Parts Inventory</h1></header>
      <div class="content-wrapper">
        <div class="content-card">
          <div class="card-header"><h2 class="card-title">Available Parts</h2><small>Items available for MRO work orders.</small></div>
          <div class="card-body"><div class="data-table" id="partsTable"></div></div>
        </div>
      </div>
    </main>
    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      async function loadParts() {
          const res = await fetch("../api/mro_parts.php");
          const data = await res.json();
          if (data.status === "success") {
              document.getElementById("partsTable").innerHTML = data.data.map(p => `
                <div class="table-row">
                    <div class="client-info"><strong>${p.item_name}</strong><br><small>Unit: ${p.unit}</small></div>
                    <div class="amount" style="font-weight:700; color:var(--brand-green);">${p.quantity} In Stock</div>
                </div>
              `).join("");
          }
      }
      loadParts();
    </script>
  </body>
</html>
<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
$page = 'outbound.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>5.4 Outbound Operations</title>
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
            <h1>5.4 Outbound Operations</h1>
          </div>
        </div>
        <div class="header-right">
          <button class="action-btn" onclick="openReleaseModal()" style="background: var(--brand-green); color: white">
            <i data-lucide="arrow-up-circle"></i> Release Materials
          </button>
        </div>
      </header>

      <div class="content-wrapper">
        <div class="content-card">
          <div class="card-header">
            <h2 class="card-title">Outbound Release Logs</h2>
            <small>History of all materials issued to Projects or MRO.</small>
          </div>
          <div class="card-body">
            <div class="data-table" id="outboundTable">
              <p style="text-align: center; padding: 20px">Loading logs...</p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      let stockItems = [];

      async function fetchStock() {
          const res = await fetch("../api/outbound.php?action=get_stock");
          const data = await res.json();
          if(data.status === 'success') stockItems = data.data;
      }

      async function loadOutboundLogs() {
        try {
          const res = await fetch("../api/outbound.php?action=get_outbound_logs");
          const data = await res.json();

          if (data.status === "success" && data.data.length > 0) {
            const html = data.data.map(log => `
                <div class="table-row">
                    <div class="client-info">
                        <strong>${log.item_name}</strong>
                        <br><small style="color:var(--text-tertiary);">${log.reason}</small>
                    </div>
                    <div>
                        <span style="font-size:12px; color:var(--text-secondary);">${log.created_at}</span>
                    </div>
                    <div style="text-align:right;">
                        <span style="color:#ef4444; font-weight:700; font-size:16px;">
                            ${log.change_amount}
                        </span>
                    </div>
                </div>
            `).join("");
            document.getElementById("outboundTable").innerHTML = html;
          } else {
            document.getElementById("outboundTable").innerHTML = '<p style="text-align:center; padding:20px; color:var(--text-tertiary);">No outbound materials yet.</p>';
          }
        } catch (e) { console.error(e); }
      }

      async function openReleaseModal() {
        const options = stockItems.map(i => `<option value="${i.id}">${i.item_name} (In Stock: ${i.quantity} ${i.unit})</option>`).join("");
        
        const { value: formValues } = await Swal.fire({
          title: "Release Materials",
          width: '500px',
          html: `
            <div style="text-align:left; padding-top: 10px;">
                <label style="color:var(--brand-green) !important; font-size:11px !important;">Select Material to Release</label>
                <select id="rel-item" class="swal2-select" style="margin: 8px 0 20px 0 !important; width:100%;">${options}</select>

                <label style="color:var(--brand-green) !important; font-size:11px !important;">Quantity</label>
                <input id="rel-qty" type="number" class="swal2-input" placeholder="0" style="margin-bottom: 20px !important;">

                <label style="color:var(--brand-green) !important; font-size:11px !important;">Destination (Project / MRO Ticket)</label>
                <input id="rel-dest" type="text" class="swal2-input" placeholder="e.g. Project Alpha Phase 1">
            </div>
          `,
          showCancelButton: true,
          confirmButtonColor: "#ef4444",
          confirmButtonText: "Issue Outbound",
          preConfirm: () => {
            const qty = document.getElementById("rel-qty").value;
            const dest = document.getElementById("rel-dest").value;
            if (!qty || qty <= 0) return Swal.showValidationMessage("Please enter a valid quantity.");
            if (!dest) return Swal.showValidationMessage("Please enter a destination.");
            
            return { 
                action: 'release_materials', 
                item_id: document.getElementById("rel-item").value,
                quantity: qty,
                destination: dest
            };
          },
        });

        if (formValues) {
            const res = await fetch("../api/outbound.php", { method: "POST", body: JSON.stringify(formValues) });
            const data = await res.json();
            if (data.status === "success") {
                Swal.fire("Released!", data.message, "success");
                fetchStock();
                loadOutboundLogs();
            } else { Swal.fire("Error", data.message, "error"); }
        }
      }

      fetchStock();
      loadOutboundLogs();
    </script>
  </body>
</html>
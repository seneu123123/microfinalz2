<?php
session_start();
$page = 'inventory';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Inventory Management</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../img/logo.png" />
    <style>
      .low-stock {
        color: #ef4444;
        font-weight: bold;
      }
      .good-stock {
        color: var(--brand-green);
        font-weight: bold;
      }
      .log-positive {
        color: var(--brand-green);
      }
      .log-negative {
        color: #ef4444;
      }
    </style>
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
            <h1>Inventory Management</h1>
          </div>
        </div>
        <div class="header-right">
          <div class="search-box">
            <i data-lucide="search"></i>
            <input
              type="search"
              id="invSearch"
              placeholder="Search items..."
              onkeyup="filterInventory()"
            />
          </div>
        </div>
      </header>

      <div class="content-wrapper">
        <div class="stats-grid">
          <div class="stat-card">
            <div
              class="stat-icon"
              style="background: rgba(59, 130, 246, 0.1); color: #3b82f6"
            >
              <i data-lucide="package"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Total Unique Items</span>
              <h3 class="stat-value" id="totalItems">0</h3>
            </div>
          </div>
          <div class="stat-card">
            <div
              class="stat-icon"
              style="
                background: rgba(44, 160, 120, 0.1);
                color: var(--brand-green);
              "
            >
              <i data-lucide="dollar-sign"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Total Inventory Value</span>
              <h3 class="stat-value" id="totalValue">$0.00</h3>
            </div>
          </div>
          <div class="stat-card">
            <div
              class="stat-icon"
              style="background: rgba(239, 68, 68, 0.1); color: #ef4444"
            >
              <i data-lucide="alert-triangle"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Low Stock Alerts</span>
              <h3 class="stat-value" id="lowStockCount">0</h3>
            </div>
          </div>
        </div>

        <div class="content-grid" style="grid-template-columns: 2fr 1fr">
          <div class="content-card">
            <div class="card-header">
              <h3 class="card-title">Current Stock Levels</h3>
            </div>
            <div class="card-body">
              <div class="data-table" id="invTable">
                <p style="padding: 20px; text-align: center">
                  Loading inventory...
                </p>
              </div>
            </div>
          </div>

          <div class="content-card">
            <div class="card-header">
              <h3 class="card-title">Recent Movement</h3>
            </div>
            <div class="card-body">
              <div class="data-table" id="logTable">
                <p style="padding: 20px; text-align: center; font-size: 13px">
                  Loading logs...
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      let inventoryData = [];

      // 1. Load Inventory & Calculate Stats
      async function loadInventory() {
        try {
          const res = await fetch("../api/inventory.php?action=get_inventory");
          const data = await res.json();

          if (data.status === "success") {
            inventoryData = data.data;
            renderTable(inventoryData);
            calculateStats(inventoryData);
          }
        } catch (e) {
          console.error(e);
        }
      }

      // 2. Load History Logs
      async function loadHistory() {
        try {
          const res = await fetch("../api/inventory.php?action=get_history");
          const data = await res.json();

          if (data.status === "success") {
            const html = data.data
              .map(
                (l) => `
                        <div class="table-row" style="grid-template-columns: 2fr 1fr;">
                            <div class="client-info">
                                <span class="client-name" style="font-size:13px;">${l.item_name}</span>
                                <span class="client-detail" style="font-size:11px;">${l.reason}</span>
                            </div>
                            <div style="text-align:right;">
                                <span class="${l.change_amount > 0 ? "log-positive" : "log-negative"}" style="font-weight:bold; font-size:13px;">
                                    ${l.change_amount > 0 ? "+" : ""}${l.change_amount}
                                </span>
                                <div style="font-size:10px; color:#888;">${new Date(l.created_at).toLocaleDateString()}</div>
                            </div>
                        </div>
                    `,
              )
              .join("");
            document.getElementById("logTable").innerHTML =
              html ||
              '<p style="padding:15px; text-align:center; color:#888;">No history yet.</p>';
          }
        } catch (e) {
          console.error(e);
        }
      }

      // Helper: Render Main Table
      function renderTable(data) {
        const html = data
          .map((i) => {
            const isLow = i.quantity < 10; // Low stock threshold
            return `
                <div class="table-row" style="grid-template-columns: 2fr 1fr 1fr auto;">
                    <div class="client-info">
                        <strong style="color:var(--text-primary);">${i.item_name}</strong>
                        <br><small style="color:var(--text-secondary);">${i.sku || "No SKU"}</small>
                    </div>
                    <div class="${isLow ? "low-stock" : "good-stock"}">
                        ${i.quantity} <span style="font-size:0.8em; color:var(--text-tertiary);">${i.unit}</span>
                    </div>
                    <div class="amount">$${(i.quantity * i.unit_price).toLocaleString()}</div>
                    <div>
                        <button onclick="adjustStock(${i.id}, '${i.item_name}')" class="btn-text" style="font-size:12px;">Adjust</button>
                    </div>
                </div>`;
          })
          .join("");
        document.getElementById("invTable").innerHTML =
          html ||
          '<p style="padding:20px; text-align:center;">Inventory is empty.</p>';
      }

      // Helper: Calculate Dashboard Stats
      function calculateStats(data) {
        document.getElementById("totalItems").innerText = data.length;

        const totalVal = data.reduce(
          (sum, item) => sum + item.quantity * item.unit_price,
          0,
        );
        document.getElementById("totalValue").innerText =
          "$" +
          totalVal.toLocaleString(undefined, { minimumFractionDigits: 2 });

        const lowStock = data.filter((i) => i.quantity < 10).length;
        document.getElementById("lowStockCount").innerText = lowStock;
      }

      // Helper: Filter Function
      function filterInventory() {
        const term = document.getElementById("invSearch").value.toLowerCase();
        const filtered = inventoryData.filter((i) =>
          i.item_name.toLowerCase().includes(term),
        );
        renderTable(filtered);
      }

      // Action: Adjust Stock Modal
      async function adjustStock(id, name) {
        const { value: formValues } = await Swal.fire({
          title: "Adjust Stock: " + name,
          html: `
                    <label style="display:block; text-align:left; font-size:13px; margin-top:10px;">Adjustment Amount (+/-)</label>
                    <input id="swal-adj" type="number" class="swal2-input" placeholder="-5 or 10">
                    <label style="display:block; text-align:left; font-size:13px; margin-top:10px;">Reason</label>
                    <input id="swal-reason" class="swal2-input" placeholder="e.g. Damaged, Found, Audit">
                `,
          showCancelButton: true,
          confirmButtonColor: "#2ca078",
          confirmButtonText: "Update Stock",
          preConfirm: () => {
            return {
              adj: document.getElementById("swal-adj").value,
              reason: document.getElementById("swal-reason").value,
            };
          },
        });

        if (formValues) {
          const res = await fetch("../api/inventory.php", {
            method: "POST",
            body: JSON.stringify({
              action: "adjust_stock",
              item_id: id,
              adjustment: formValues.adj,
              reason: formValues.reason,
            }),
          });
          const data = await res.json();
          if (data.status === "success") {
            Swal.fire("Updated", "Stock adjusted successfully.", "success");
            loadInventory();
            loadHistory();
          } else {
            Swal.fire("Error", data.message, "error");
          }
        }
      }

      // Init
      loadInventory();
      loadHistory();
    </script>
  </body>
</html>

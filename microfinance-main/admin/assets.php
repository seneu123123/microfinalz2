<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
$page = 'assets.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Asset Management</title>
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
            <h1>Asset Registry</h1>
          </div>
        </div>
        <div class="header-right">
          <button class="action-btn" onclick="openAssetModal()" style="background: var(--brand-green); color: white">
            <i data-lucide="monitor"></i> Register Asset
          </button>
        </div>
      </header>

      <div class="content-wrapper">
        <div class="content-card">
          <div class="card-header">
            <h2 class="card-title">Tracked Company Assets</h2>
            <small>Items linked from Warehousing or added manually.</small>
          </div>
          <div class="card-body">
            <div class="data-table" id="assetTable">
              <p style="text-align: center; padding: 20px">Loading assets...</p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      let inventoryItems = [];

      async function fetchInventory() {
          const res = await fetch("../api/assets.php?action=get_inventory");
          const data = await res.json();
          if(data.status === 'success') inventoryItems = data.data;
      }

      async function loadAssets() {
        try {
          const res = await fetch("../api/assets.php?action=get_assets");
          const data = await res.json();

          if (data.status === "success" && data.data.length > 0) {
            const html = data.data.map(a => {
                let statusColor = a.status === 'Active' ? 'approved' : (a.status === 'In Maintenance' ? 'pending' : 'rejected');
                return `
                <div class="table-row">
                    <div class="client-info">
                        <strong>${a.asset_name}</strong>
                        <br><small style="color:#888;">S/N: ${a.serial_number || 'N/A'}</small>
                    </div>
                    <div class="amount">Warranty: ${a.warranty_expiry || 'N/A'}</div>
                    <div>
                        <span class="badge-status ${statusColor}">${a.status}</span>
                    </div>
                </div>
            `}).join("");
            document.getElementById("assetTable").innerHTML = html;
          } else {
            document.getElementById("assetTable").innerHTML = '<p style="text-align:center; padding:20px; color:#888;">No assets registered yet.</p>';
          }
        } catch (e) { console.error(e); }
      }

      async function openAssetModal() {
        const options = inventoryItems.map(i => `<option value="${i.id}">${i.item_name} (Stock: ${i.quantity})</option>`).join("");
        
        const { value: formValues } = await Swal.fire({
          title: "Register New Asset",
          html: `
            <div style="text-align:left; background:#eff6ff; padding:10px; border-radius:6px; margin-bottom:15px; font-size:13px; color:#1e3a8a;">
                <i data-lucide="info" style="width:16px; height:16px; vertical-align:middle;"></i> Select an item from Inventory to convert it to a tracked asset, or enter a new name manually.
            </div>
            <label style="display:block; text-align:left; font-size:14px;">Link from Inventory (Optional)</label>
            <select id="swal-inv" class="swal2-input">
                <option value="">-- Do not link --</option>
                ${options}
            </select>
            <input id="swal-name" class="swal2-input" placeholder="Asset Name (e.g. Delivery Van)">
            <input id="swal-sn" class="swal2-input" placeholder="Serial / Plate Number">
            <label style="display:block; text-align:left; font-size:14px; margin-top:10px;">Warranty Expiry</label>
            <input id="swal-warranty" type="date" class="swal2-input">
          `,
          showCancelButton: true,
          confirmButtonColor: "#2ca078",
          confirmButtonText: "Register Asset",
          didOpen: () => { lucide.createIcons(); },
          preConfirm: () => {
            const name = document.getElementById("swal-name").value;
            const inv = document.getElementById("swal-inv").value;
            if (!name && !inv) return Swal.showValidationMessage("Please provide an asset name or select from inventory");
            
            // If they picked inventory but left name blank, use the inventory select text
            let finalName = name;
            if(!name && inv) {
                const sel = document.getElementById("swal-inv");
                finalName = sel.options[sel.selectedIndex].text.split(" (Stock")[0]; 
            }

            return { 
                action: 'register_asset',
                inventory_id: inv, 
                asset_name: finalName, 
                serial_number: document.getElementById("swal-sn").value,
                warranty: document.getElementById("swal-warranty").value
            };
          },
        });

        if (formValues) {
          const res = await fetch("../api/assets.php", {
            method: "POST",
            body: JSON.stringify(formValues),
          });
          const data = await res.json();
          if (data.status === "success") {
            Swal.fire("Success", data.message, "success");
            fetchInventory();
            loadAssets();
          } else {
            Swal.fire("Error", data.message, "error");
          }
        }
      }

      fetchInventory();
      loadAssets();
    </script>
  </body>
</html>
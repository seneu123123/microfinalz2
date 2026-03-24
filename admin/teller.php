<?php
session_start();
$page = 'teller.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Teller & Collections</title>
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
            <h1>Teller Operations & Collections</h1>
          </div>
        </div>
        <div class="header-right" style="display: flex; gap: 12px;">
          <button class="action-btn" onclick="remitDrawer()" style="background: var(--surface-hover); color: var(--text-primary); border: 1px solid var(--border-color);">
            <i data-lucide="lock"></i> End of Day Remit
          </button>
          <button class="action-btn" onclick="openPaymentModal()" style="background: var(--brand-green); color: white">
            <i data-lucide="banknote"></i> Receive Payment
          </button>
        </div>
      </header>

      <div class="content-wrapper">
        
        <div class="stats-grid" style="margin-bottom: 24px;">
          <div class="stat-card" style="border-left: 4px solid #3b82f6;">
            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
              <i data-lucide="inbox"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Counter 1 - Cash Drawer</span>
              <h3 class="stat-value" id="drawer-balance" style="font-size: 36px; color: #3b82f6;">Loading...</h3>
              <div class="stat-trend" style="color: var(--text-tertiary); margin-top: 4px;">Total physical cash collected today</div>
            </div>
          </div>
        </div>

        <div class="content-card">
          <div class="card-header">
            <h2 class="card-title">Recent Collections</h2>
            <small>Log of all incoming loan repayments.</small>
          </div>
          <div class="card-body">
            <div class="data-table" id="collectionsTable">
              <p style="text-align: center; padding: 20px">Loading collections...</p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      let activeLoans = [];

      async function fetchActiveLoans() {
          const res = await fetch("../api/teller.php?action=get_active_loans");
          const data = await res.json();
          if(data.status === 'success') activeLoans = data.data;
      }

      async function loadDrawer() {
        try {
          const res = await fetch("../api/teller.php?action=get_drawer");
          const data = await res.json();
          if (data.status === "success" && data.data) {
              const balance = parseFloat(data.data.current_balance);
              document.getElementById("drawer-balance").innerText = "$" + balance.toLocaleString(undefined, {minimumFractionDigits: 2});
          }
        } catch (e) { console.error(e); }
      }

      async function loadCollections() {
        try {
          const res = await fetch("../api/teller.php?action=get_collections");
          const data = await res.json();

          if (data.status === "success" && data.data.length > 0) {
            const html = data.data.map(c => {
                return `
                <div class="table-row">
                    <div class="client-info">
                        <strong>${c.full_name}</strong>
                        <br><small style="color:var(--text-tertiary);">Ref Loan #${c.loan_id} | ${c.teller_name}</small>
                    </div>
                    <div>
                        <span style="font-size:12px; color:var(--text-secondary);">${c.payment_date}</span>
                    </div>
                    <div style="text-align:right;">
                        <span style="color:var(--brand-green); font-weight:700; font-size:16px;">
                            +$${parseFloat(c.amount_paid).toLocaleString(undefined, {minimumFractionDigits: 2})}
                        </span>
                    </div>
                </div>
            `}).join("");
            document.getElementById("collectionsTable").innerHTML = html;
          } else {
            document.getElementById("collectionsTable").innerHTML = '<p style="text-align:center; padding:20px; color:var(--text-tertiary);">No collections recorded yet.</p>';
          }
        } catch (e) { console.error(e); }
      }

      async function openPaymentModal() {
        if(activeLoans.length === 0) return Swal.fire("Notice", "There are no active disbursed loans to collect payments for.", "info");

        const options = activeLoans.map(l => `<option value="${l.id}">${l.full_name} (Loan #${l.id})</option>`).join("");
        
        const { value: formValues } = await Swal.fire({
          title: "Process Repayment",
          width: '500px',
          html: `
            <div style="text-align:left; padding-top: 10px;">
                <label style="color:var(--brand-green) !important; font-size:11px !important;">Select Client / Active Loan</label>
                <select id="pay-loan" class="swal2-select" style="margin: 8px 0 20px 0 !important; width:100%;">${options}</select>

                <label style="color:var(--brand-green) !important; font-size:11px !important;">Payment Amount Received ($)</label>
                <input id="pay-amount" type="number" class="swal2-input" placeholder="0.00" style="font-size: 20px !important; font-weight: bold; color: var(--brand-green) !important;">
            </div>
          `,
          showCancelButton: true,
          confirmButtonColor: "#2ca078",
          confirmButtonText: "Accept Payment",
          preConfirm: () => {
            const amount = document.getElementById("pay-amount").value;
            if (!amount || amount <= 0) return Swal.showValidationMessage("Please enter a valid payment amount.");
            
            return { 
                action: 'process_payment', 
                loan_id: document.getElementById("pay-loan").value,
                amount: amount
            };
          },
        });

        if (formValues) {
            const res = await fetch("../api/teller.php", { method: "POST", body: JSON.stringify(formValues) });
            const data = await res.json();
            if (data.status === "success") {
                Swal.fire("Success!", data.message, "success");
                loadDrawer();
                loadCollections();
            } else { Swal.fire("Error", data.message, "error"); }
        }
      }

      async function remitDrawer() {
        const result = await Swal.fire({
          title: "End of Day Remittance?",
          text: "This will empty your teller drawer and securely transfer all physical cash to the Main HQ Vault.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3b82f6",
          cancelButtonColor: "#ef4444",
          confirmButtonText: "Yes, Remit to Vault"
        });

        if (result.isConfirmed) {
            const res = await fetch("../api/teller.php", { 
                method: "POST", 
                body: JSON.stringify({ action: 'remit_cash' }) 
            });
            const data = await res.json();
            
            if (data.status === "success") {
                Swal.fire("Remitted!", data.message, "success");
                loadDrawer(); // Drawer should drop back to $0.00
            } else { 
                Swal.fire("Error", data.message, "error"); 
            }
        }
      }

      fetchActiveLoans();
      loadDrawer();
      loadCollections();
    </script>
  </body>
</html>
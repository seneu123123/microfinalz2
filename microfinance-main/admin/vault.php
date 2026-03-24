<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
$page = 'vault.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Branch Operations - Vault</title>
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
            <h1>Branch Vault Operations</h1>
          </div>
        </div>
        <div class="header-right">
          <button class="action-btn" onclick="openTransactionModal()" style="background: var(--brand-green); color: white">
            <i data-lucide="arrow-left-right"></i> Manual Transfer
          </button>
        </div>
      </header>

      <div class="content-wrapper">
        
        <div class="stats-grid" style="margin-bottom: 24px;">
          <div class="stat-card" style="border-left: 4px solid var(--brand-green);">
            <div class="stat-icon" style="background: rgba(44, 160, 120, 0.1); color: var(--brand-green);">
              <i data-lucide="building-2"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Main HQ Vault Balance</span>
              <h3 class="stat-value" id="vault-balance" style="font-size: 36px; color: var(--brand-green);">Loading...</h3>
              <div class="stat-trend" style="color: var(--text-tertiary); margin-top: 4px;">Live tracking of all cash inflows and disbursements</div>
            </div>
          </div>
        </div>

        <div class="content-card">
          <div class="card-header">
            <h2 class="card-title">Vault Ledger & Transactions</h2>
            <small>Watch auto-disbursements trigger live when contracts are signed.</small>
          </div>
          <div class="card-body">
            <div class="data-table" id="ledgerTable">
              <p style="text-align: center; padding: 20px">Loading ledger...</p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();

      async function loadVault() {
        try {
          const res = await fetch("../api/vault.php?action=get_balance");
          const data = await res.json();
          if (data.status === "success" && data.data) {
              const balance = parseFloat(data.data.current_balance);
              document.getElementById("vault-balance").innerText = "$" + balance.toLocaleString(undefined, {minimumFractionDigits: 2});
          }
        } catch (e) { console.error(e); }
      }

      async function loadTransactions() {
        try {
          const res = await fetch("../api/vault.php?action=get_transactions");
          const data = await res.json();

          if (data.status === "success" && data.data.length > 0) {
            const html = data.data.map(t => {
                let isNegative = (t.transaction_type === 'Outflow' || t.transaction_type === 'Disbursement');
                let amountColor = isNegative ? '#ef4444' : 'var(--brand-green)';
                let sign = isNegative ? '-' : '+';
                
                let detailText = t.transaction_type;
                if(t.transaction_type === 'Disbursement' && t.full_name) {
                    detailText = `Loan Release: ${t.full_name} (Loan #${t.loan_id})`;
                }

                let icon = isNegative ? 'arrow-down-right' : 'arrow-up-right';

                return `
                <div class="table-row">
                    <div class="client-info">
                        <strong>${t.transaction_type}</strong>
                        <br><small style="color:var(--text-tertiary);">${detailText}</small>
                    </div>
                    <div>
                        <span style="font-size:12px; color:var(--text-secondary);">${t.transaction_date}</span>
                    </div>
                    <div style="text-align:right;">
                        <span style="color:${amountColor}; font-weight:700; font-size:16px;">
                            ${sign}$${parseFloat(t.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}
                        </span>
                    </div>
                </div>
            `}).join("");
            document.getElementById("ledgerTable").innerHTML = html;
          } else {
            document.getElementById("ledgerTable").innerHTML = '<p style="text-align:center; padding:20px; color:var(--text-tertiary);">No transactions logged yet.</p>';
          }
        } catch (e) { console.error(e); }
      }

      async function openTransactionModal() {
        const { value: formValues } = await Swal.fire({
          title: "Manual Vault Transfer",
          width: '500px',
          html: `
            <div style="text-align:left; padding-top: 10px;">
                <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); padding: 14px; border-radius: 8px; font-size: 13px; margin-bottom:20px;">
                    <strong>Note:</strong> Loan disbursements are handled automatically by the Document Control system. Use this only for external funding or branch expenses.
                </div>

                <label style="color:var(--brand-green) !important; font-size:11px !important;">Transaction Type</label>
                <select id="trans-type" class="swal2-select" style="margin: 8px 0 20px 0 !important; width:100%;">
                    <option value="Inflow">Cash Inflow (Deposit/Funding)</option>
                    <option value="Outflow">Cash Outflow (Expense/Transfer)</option>
                </select>

                <label style="color:var(--brand-green) !important; font-size:11px !important;">Amount ($)</label>
                <input id="trans-amount" type="number" class="swal2-input" placeholder="0.00" style="font-size: 20px !important; font-weight: bold;">
            </div>
          `,
          showCancelButton: true,
          confirmButtonColor: "#2ca078",
          confirmButtonText: "Execute Transfer",
          preConfirm: () => {
            const amount = document.getElementById("trans-amount").value;
            if (!amount || amount <= 0) return Swal.showValidationMessage("Please enter a valid amount.");
            
            return { 
                action: 'manual_transaction', 
                type: document.getElementById("trans-type").value,
                amount: amount
            };
          },
        });

        if (formValues) {
            const res = await fetch("../api/vault.php", { method: "POST", body: JSON.stringify(formValues) });
            const data = await res.json();
            if (data.status === "success") {
                Swal.fire("Success!", data.message, "success");
                loadVault();
                loadTransactions();
            } else { Swal.fire("Error", data.message, "error"); }
        }
      }

      loadVault();
      loadTransactions();
    </script>
  </body>
</html>
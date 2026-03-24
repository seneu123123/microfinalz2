<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
$page = 'loans.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Loan Management</title>
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
            <h1>Loan Management</h1>
          </div>
        </div>
        <div class="header-right">
          <button class="action-btn" onclick="openClientModal()" style="background: var(--surface-hover); color: var(--text-primary); border: 1px solid var(--border-color);">
            <i data-lucide="user-plus"></i> New Client
          </button>
          <button class="action-btn" onclick="openLoanModal()" style="background: var(--brand-green); color: white">
            <i data-lucide="file-text"></i> New Application
          </button>
        </div>
      </header>

      <div class="content-wrapper">
        <div class="content-card">
          <div class="card-header">
            <h2 class="card-title">Active Applications</h2>
            <small>Approve applications to auto-generate contracts in Document Control.</small>
          </div>
          <div class="card-body">
            <div class="data-table" id="loansTable">
              <p style="text-align: center; padding: 20px">Loading applications...</p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();
      let clients = [];

      async function fetchClients() {
          const res = await fetch("../api/loans.php?action=get_clients");
          const data = await res.json();
          if(data.status === 'success') clients = data.data;
      }

      async function loadLoans() {
        try {
          const res = await fetch("../api/loans.php?action=get_loans");
          const data = await res.json();

          if (data.status === "success" && data.data.length > 0) {
            const html = data.data.map(l => {
                let statusColor = 'pending';
                if(l.status === 'Approved') statusColor = 'ordered';
                if(l.status === 'Disbursed') statusColor = 'approved';
                if(l.status === 'Completed') statusColor = 'active';

                let actionBtn = '';
                if (l.status === 'Pending Review') {
                    actionBtn = `<button onclick="approveLoan(${l.id})" class="action-btn" style="background:var(--brand-green); color:white; padding:6px 12px; font-size:12px; height:auto;">Approve & Generate Contract</button>`;
                } else if (l.status === 'Approved') {
                    actionBtn = `<span style="font-size:12px; color:var(--text-secondary);">Waiting for Signature</span>`;
                }

                return `
                <div class="table-row">
                    <div class="client-info">
                        <strong>${l.full_name}</strong>
                        <br><small style="color:var(--text-tertiary);">Risk: ${l.risk_profile}</small>
                    </div>
                    <div class="amount">
                        $${parseFloat(l.principal_amount).toLocaleString()}<br>
                        <span style="font-size:12px; color:var(--text-secondary); font-weight:normal;">${l.term_months} Months @ ${l.interest_rate}%</span>
                    </div>
                    <div><span class="badge-status ${statusColor}">${l.status}</span></div>
                    <div style="text-align:right;">${actionBtn}</div>
                </div>
            `}).join("");
            document.getElementById("loansTable").innerHTML = html;
          } else {
            document.getElementById("loansTable").innerHTML = '<p style="text-align:center; padding:20px; color:var(--text-tertiary);">No loan applications found.</p>';
          }
        } catch (e) { console.error(e); }
      }

      async function openClientModal() {
        const { value: formValues } = await Swal.fire({
          title: "Register Client",
          html: `
            <div style="padding-top: 10px;">
                <label>Full Name</label>
                <input id="client-name" class="swal2-input" placeholder="e.g. Maria Santos">
                
                <div style="display: flex; gap: 16px;">
                    <div style="flex: 1;">
                        <label>Contact Number</label>
                        <input id="client-contact" class="swal2-input" placeholder="09XX-XXX-XXXX">
                    </div>
                    <div style="flex: 1;">
                        <label>Risk Profile</label>
                        <select id="client-risk" class="swal2-select" style="margin: 8px 0 20px 0 !important; width:100%;">
                            <option value="Low">Low Risk</option>
                            <option value="Medium" selected>Medium Risk</option>
                            <option value="High">High Risk</option>
                        </select>
                    </div>
                </div>
                
                <label>Home Address</label>
                <input id="client-address" class="swal2-input" placeholder="Full Address">
            </div>
          `,
          showCancelButton: true,
          confirmButtonColor: "#2ca078",
          confirmButtonText: "Register Client",
          preConfirm: () => {
            const name = document.getElementById("client-name").value;
            if (!name) return Swal.showValidationMessage("Client Name is required");
            return { 
                action: 'add_client', name: name, 
                contact: document.getElementById("client-contact").value,
                risk: document.getElementById("client-risk").value,
                address: document.getElementById("client-address").value
            };
          }
        });

        if (formValues) {
            const res = await fetch("../api/loans.php", { method: "POST", body: JSON.stringify(formValues) });
            const data = await res.json();
            if (data.status === "success") {
                Swal.fire("Success", data.message, "success");
                fetchClients();
            } else { Swal.fire("Error", data.message, "error"); }
        }
      }

      async function openLoanModal() {
        if(clients.length === 0) return Swal.fire("Notice", "Please register a client first.", "info");
        
        const options = clients.map(c => `<option value="${c.id}">${c.full_name} (${c.risk_profile} Risk)</option>`).join("");
        
        const { value: formValues } = await Swal.fire({
          title: "New Loan Application",
          html: `
            <div style="padding-top: 10px;">
                <label>Select Client</label>
                <select id="loan-client" class="swal2-select" style="margin: 8px 0 20px 0 !important; width:100%;">${options}</select>
                
                <label>Principal Amount ($)</label>
                <input id="loan-amount" type="number" class="swal2-input" placeholder="0.00">
                
                <div style="display: flex; gap: 16px;">
                    <div style="flex: 1;">
                        <label>Interest Rate (%)</label>
                        <input id="loan-interest" type="number" class="swal2-input" placeholder="e.g. 5.0">
                    </div>
                    <div style="flex: 1;">
                        <label>Term (Months)</label>
                        <input id="loan-terms" type="number" class="swal2-input" placeholder="e.g. 12">
                    </div>
                </div>
            </div>
          `,
          showCancelButton: true,
          confirmButtonColor: "#2ca078",
          confirmButtonText: "Submit Application",
          preConfirm: () => {
            const amt = document.getElementById("loan-amount").value;
            if (!amt) return Swal.showValidationMessage("Principal amount is required");
            return { 
                action: 'create_loan', 
                client_id: document.getElementById("loan-client").value,
                amount: amt,
                interest: document.getElementById("loan-interest").value || 0,
                terms: document.getElementById("loan-terms").value || 1
            };
          }
        });

        if (formValues) {
            const res = await fetch("../api/loans.php", { method: "POST", body: JSON.stringify(formValues) });
            const data = await res.json();
            if (data.status === "success") {
                Swal.fire("Success", data.message, "success");
                loadLoans();
            } else { Swal.fire("Error", data.message, "error"); }
        }
      }

      async function approveLoan(id) {
        const result = await Swal.fire({
            title: "Approve Loan?",
            text: "This will automatically generate a contract in the Document Control system.",
            icon: "info",
            showCancelButton: true,
            confirmButtonColor: "#2ca078",
            confirmButtonText: "Yes, Approve & Generate"
        });

        if (result.isConfirmed) {
            const res = await fetch("../api/loans.php", { 
                method: "POST", 
                body: JSON.stringify({ action: 'approve_loan', loan_id: id }) 
            });
            const data = await res.json();
            if (data.status === "success") {
                Swal.fire("Approved!", data.message, "success");
                loadLoans();
            } else { Swal.fire("Error", data.message, "error"); }
        }
      }

      fetchClients();
      loadLoans();
    </script>
  </body>
</html>
<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
$page = 'budget'; // For sidebar highlighting
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Budget Management</title>
  <link rel="stylesheet" href="../css/dashboard.css">
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="icon" type="image/png" href="../img/logo.png">
</head>
<body>
  
  <?php include '../includes/sidebar.php'; ?>

  <main class="main-content">
    <header class="page-header">
      <div class="header-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn"><i data-lucide="menu"></i></button>
        <div class="header-title"><h1>Budget Management & Disbursements</h1></div>
      </div>
    </header>

    <div class="content-wrapper">
      
      <div class="stats-grid" style="margin-bottom: 20px;">
          <div class="stat-card">
              <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i data-lucide="landmark"></i></div>
              <div class="stat-content">
                  <span class="stat-label">Total Master Budget</span>
                  <h3 class="stat-value" id="totalBudget">$0.00</h3>
              </div>
          </div>
          <div class="stat-card">
              <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i data-lucide="trending-down"></i></div>
              <div class="stat-content">
                  <span class="stat-label">Total Disbursed</span>
                  <h3 class="stat-value" id="allocatedBudget" style="color: #ef4444;">$0.00</h3>
              </div>
          </div>
          <div class="stat-card">
              <div class="stat-icon" style="background: rgba(44, 160, 120, 0.1); color: var(--brand-green);"><i data-lucide="wallet"></i></div>
              <div class="stat-content">
                  <span class="stat-label">Remaining Funds</span>
                  <h3 class="stat-value" id="remainingBudget" style="color: var(--brand-green);">$0.00</h3>
              </div>
          </div>
      </div>

      <div class="content-grid" style="grid-template-columns: 1fr 1fr;">
          
          <div class="content-card">
              <div class="card-header">
                  <h3 class="card-title">Pending Funding Requests</h3>
                  <small>Auto-synced from Procurement Purchase Orders</small>
              </div>
              <div class="card-body">
                  <div class="data-table" id="pendingTable">
                      <p style="text-align:center; padding:20px;">Loading requests...</p>
                  </div>
              </div>
          </div>

          <div class="content-card">
              <div class="card-header">
                  <h3 class="card-title">Disbursement Ledger</h3>
              </div>
              <div class="card-body">
                  <div class="data-table" id="disbursementTable">
                      <p style="text-align:center; padding:20px;">Loading ledger...</p>
                  </div>
              </div>
          </div>

      </div>
    </div>
  </main>

  <script src="../js/dashboard.js"></script>
  <script>
    lucide.createIcons();

    async function loadBudgetDashboard() {
        try {
            const res = await fetch('../api/budget.php?action=get_overview');
            const data = await res.json();
            
            if(data.status === 'success') {
                // 1. Update Cards
                const tb = parseFloat(data.budget.total_budget);
                const ab = parseFloat(data.budget.allocated_amount);
                
                document.getElementById('totalBudget').innerText = '$' + tb.toLocaleString(undefined, {minimumFractionDigits: 2});
                document.getElementById('allocatedBudget').innerText = '$' + ab.toLocaleString(undefined, {minimumFractionDigits: 2});
                document.getElementById('remainingBudget').innerText = '$' + (tb - ab).toLocaleString(undefined, {minimumFractionDigits: 2});

                // 2. Render Pending Requests
                const pTable = document.getElementById('pendingTable');
                if(data.pending.length === 0) {
                    pTable.innerHTML = '<p style="text-align:center; padding:20px; color:#888;">No pending requests from Procurement.</p>';
                } else {
                    pTable.innerHTML = data.pending.map(p => `
                        <div class="table-row" style="grid-template-columns: 2fr 1fr auto;">
                            <div class="client-info">
                                <span class="client-name">PO #${p.po_id} - ${p.company_name}</span>
                                <span class="client-detail">Ordered: ${p.created_at.split(' ')[0]}</span>
                            </div>
                            <div class="amount" style="color:#ef4444; font-weight:bold;">
                                $${parseFloat(p.total_cost).toLocaleString(undefined, {minimumFractionDigits: 2})}
                            </div>
                            <div style="text-align:right;">
                                <button onclick="releaseFunds(${p.po_id}, ${p.total_cost})" class="action-btn" style="background:#3b82f6; color:white; padding:6px 12px; font-size:12px;">Release Funds</button>
                            </div>
                        </div>
                    `).join('');
                }

                // 3. Render Disbursements
                const dTable = document.getElementById('disbursementTable');
                if(data.disbursements.length === 0) {
                    dTable.innerHTML = '<p style="text-align:center; padding:20px; color:#888;">No funds released yet.</p>';
                } else {
                    dTable.innerHTML = data.disbursements.map(d => `
                        <div class="table-row" style="grid-template-columns: 2fr 1fr;">
                            <div class="client-info">
                                <span class="client-name">Funded PO #${d.po_id || 'N/A'}</span>
                                <span class="client-detail">Released: ${d.release_date}</span>
                            </div>
                            <div style="text-align:right; color:var(--brand-green); font-weight:bold;">
                                -$${parseFloat(d.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}
                            </div>
                        </div>
                    `).join('');
                }
            }
        } catch(e) { console.error(e); }
    }

    async function releaseFunds(poId, amount) {
        Swal.fire({
            title: 'Release Funds?',
            text: \`This will deduct $\${amount.toLocaleString()} from the master budget for PO #\${poId}.\`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'Yes, Release Funds'
        }).then(async (result) => {
            if (result.isConfirmed) {
                const res = await fetch('../api/budget.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'release_funds', po_id: poId, amount: amount })
                });
                const data = await res.json();
                if(data.status === 'success') {
                    Swal.fire('Released!', data.message, 'success');
                    loadBudgetDashboard();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            }
        });
    }

    loadBudgetDashboard();
  </script>
</body>
</html>
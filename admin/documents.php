<?php
session_start();
$page = 'documents.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document Control</title>
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
            <h1>Document Control</h1>
          </div>
        </div>
        <div class="header-right">
            <span style="color: var(--text-tertiary); font-size: 13px;">
                <i data-lucide="info" style="width: 14px; height: 14px; vertical-align: middle;"></i> 
                Contracts are auto-generated via Loan Approvals
            </span>
        </div>
      </header>

      <div class="content-wrapper">
        <div class="content-card">
          <div class="card-header">
            <h2 class="card-title">Pending & Archived Documents</h2>
            <small>Digitally sign contracts to automatically trigger vault disbursements.</small>
          </div>
          <div class="card-body">
            <div class="data-table" id="docsTable">
              <p style="text-align: center; padding: 20px">Loading documents...</p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();

      async function loadDocuments() {
        try {
          const res = await fetch("../api/documents.php?action=get_documents");
          const data = await res.json();

          if (data.status === "success" && data.data.length > 0) {
            const html = data.data.map(d => {
                let statusColor = d.signature_status === 'Signed' ? 'approved' : 'pending';
                
                let actionBtn = '';
                if (d.signature_status === 'Pending Signature') {
                    actionBtn = `<button onclick="openSignModal(${d.id}, '${d.document_name}', ${d.principal_amount})" class="action-btn" style="background:var(--brand-green); color:white; padding:6px 12px; font-size:12px; height:auto;">
                                    <i data-lucide="pen-tool" style="width: 14px; height: 14px;"></i> E-Sign
                                 </button>`;
                } else {
                    actionBtn = `<button class="action-btn" style="background:var(--surface-hover); color:var(--brand-green); border: 1px solid rgba(44, 160, 120, 0.3); padding:6px 12px; font-size:12px; height:auto;">
                                    <i data-lucide="download" style="width: 14px; height: 14px;"></i> Download PDF
                                 </button>`;
                }

                let amountDisplay = d.principal_amount ? `$${parseFloat(d.principal_amount).toLocaleString()}` : 'N/A';

                return `
                <div class="table-row">
                    <div class="client-info">
                        <strong>${d.document_name}</strong>
                        <br><small style="color:var(--text-tertiary);">Ref ID: #${d.reference_id} | Type: ${d.reference_type}</small>
                    </div>
                    <div class="amount">
                        ${amountDisplay}<br>
                        <span style="font-size:12px; color:var(--text-secondary); font-weight:normal;">Linked Value</span>
                    </div>
                    <div><span class="badge-status ${statusColor}">${d.signature_status}</span></div>
                    <div style="text-align:right;">${actionBtn}</div>
                </div>
            `}).join("");
            document.getElementById("docsTable").innerHTML = html;
            lucide.createIcons(); // Re-initialize icons for dynamically injected buttons
          } else {
            document.getElementById("docsTable").innerHTML = '<p style="text-align:center; padding:20px; color:var(--text-tertiary);">No documents found. Approve a loan to generate one.</p>';
          }
        } catch (e) { console.error(e); }
      }

      async function openSignModal(docId, docName, amount) {
        const { value: formValues } = await Swal.fire({
          title: "Execute E-Signature",
          width: '500px',
          html: `
            <div style="text-align:left; padding-top: 10px;">
                
                <div style="margin-bottom: 20px;">
                    <div style="background: var(--surface-hover); padding: 14px; border-radius: 8px; border: 1px solid var(--border-color); font-size: 14px;">
                        <strong>Document:</strong> ${docName}<br>
                        <strong>Disbursement Amount:</strong> <span style="color:var(--brand-green); font-weight:bold;">$${parseFloat(amount).toLocaleString()}</span>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="color:var(--brand-green) !important; font-size:11px !important;">Digital Signature (Type Full Name)</label>
                    <input id="sign-name" class="swal2-input" placeholder="Client Name" style="font-family: 'Courier New', monospace; font-style: italic; font-size: 18px !important;">
                </div>
                
                <div style="background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); padding: 16px; border-radius: 10px;">
                    <label class="checkbox-wrapper" style="cursor:pointer;">
                        <input type="checkbox" id="sign-confirm">
                        <div style="display:flex; flex-direction:column; gap:4px;">
                            <strong style="color: #b45309; font-size: 13px;">I agree to the terms and authorize the vault release.</strong>
                            <span style="color: #d97706; font-size: 11px; line-height: 1.4;">This action is final and will instantly withdraw funds from Branch Operations.</span>
                        </div>
                    </label>
                </div>
            </div>
          `,
          showCancelButton: true,
          confirmButtonColor: "#2ca078",
          confirmButtonText: "Sign & Disburse",
          preConfirm: () => {
            const signature = document.getElementById("sign-name").value;
            const confirmed = document.getElementById("sign-confirm").checked;
            
            if (!signature) return Swal.showValidationMessage("Digital signature is required.");
            if (!confirmed) return Swal.showValidationMessage("You must check the authorization box.");
            
            return { action: 'sign_document', document_id: docId };
          },
        });

        if (formValues) {
            const res = await fetch("../api/documents.php", { method: "POST", body: JSON.stringify(formValues) });
            const data = await res.json();
            if (data.status === "success") {
                Swal.fire("Success!", data.message, "success");
                loadDocuments();
            } else { Swal.fire("Error", data.message, "error"); }
        }
      }

      loadDocuments();
    </script>
  </body>
</html>
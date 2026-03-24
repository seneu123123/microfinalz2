<?php
session_start();
$page = 'compliance.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>4.4 Compliance and Safety</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../img/logo.png" />
  </head>
  <body>
    <button class="theme-toggle" id="themeToggle"><i data-lucide="sun" class="sun-icon"></i><i data-lucide="moon" class="moon-icon"></i></button>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
      <header class="page-header">
        <div class="header-left"><h1>Compliance & Safety</h1></div>
        <div class="header-right" style="display: flex; gap: 12px;">
          <button class="action-btn" onclick="openIncidentModal()" style="background:#ef4444; color:white;"><i data-lucide="alert-octagon"></i> Report Incident</button>
          <button class="action-btn" onclick="openAuditModal()" style="background:var(--brand-green); color:white;"><i data-lucide="shield-check"></i> Log Safety Audit</button>
        </div>
      </header>

      <div class="content-wrapper">
        <div class="content-grid">
            
            <div class="content-card">
                <div class="card-header"><h2 class="card-title">Recent Safety Audits</h2></div>
                <div class="card-body">
                    <div class="data-table" id="auditTable"><p style="text-align:center; padding:20px;">Loading...</p></div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header"><h2 class="card-title">Incident Reports</h2></div>
                <div class="card-body">
                    <div class="data-table" id="incidentTable"><p style="text-align:center; padding:20px;">Loading...</p></div>
                </div>
            </div>

        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script>
      lucide.createIcons();

      async function loadData() {
          try {
              const auditRes = await fetch("../api/compliance.php?action=get_audits");
              const auditData = await auditRes.json();
              const incRes = await fetch("../api/compliance.php?action=get_incidents");
              const incData = await incRes.json();

              if(auditData.status === 'success') {
                  document.getElementById("auditTable").innerHTML = auditData.data.map(a => `
                    <div class="table-row">
                        <div class="client-info"><strong>${a.audit_type}</strong><br><small>Auditor: ${a.auditor_name}</small></div>
                        <div style="font-size:12px;">${a.audit_date}</div>
                        <div><span class="badge-status ${a.status === 'Passed' ? 'approved' : 'pending'}">${a.status}</span></div>
                    </div>
                  `).join("") || '<p style="padding:20px; text-align:center;">No audits recorded.</p>';
              }

              if(incData.status === 'success') {
                  document.getElementById("incidentTable").innerHTML = incData.data.map(i => {
                      let sevColor = i.severity === 'Critical' ? '#ef4444' : (i.severity === 'High' ? '#f59e0b' : '#3b82f6');
                      return `
                    <div class="table-row">
                        <div class="client-info"><strong>${i.incident_type}</strong><br><small>Loc: ${i.location}</small></div>
                        <div style="font-size:12px; color:${sevColor}; font-weight:bold;">${i.severity}</div>
                        <div><span class="badge-status ${i.status === 'Resolved' ? 'approved' : 'pending'}">${i.status}</span></div>
                    </div>
                  `}).join("") || '<p style="padding:20px; text-align:center;">No incidents reported.</p>';
              }
          } catch(e) { console.error(e); }
      }

      async function openAuditModal() {
        const { value: formValues } = await Swal.fire({
          title: "Log Safety Audit",
          html: `<label>Audit Type</label><input id="a-type" class="swal2-input" placeholder="e.g. Fire Safety">
                 <label>Auditor Name</label><input id="a-name" class="swal2-input" placeholder="Your Name">
                 <label>Date</label><input id="a-date" type="date" class="swal2-input">
                 <label>Status</label><select id="a-status" class="swal2-select"><option value="Passed">Passed</option><option value="Failed">Failed</option><option value="Needs Action">Needs Action</option></select>`,
          confirmButtonText: "Save Audit",
          preConfirm: () => { return { action: 'add_audit', type: document.getElementById('a-type').value, auditor: document.getElementById('a-name').value, date: document.getElementById('a-date').value, status: document.getElementById('a-status').value, remarks: '' }; }
        });
        if (formValues) {
            const res = await fetch("../api/compliance.php", { method: "POST", body: JSON.stringify(formValues) });
            const data = await res.json();
            if (data.status === "success") { Swal.fire("Success", data.message, "success"); loadData(); }
        }
      }

      async function openIncidentModal() {
        const { value: formValues } = await Swal.fire({
          title: "Report Safety Incident",
          html: `<label>Incident Type</label><input id="i-type" class="swal2-input" placeholder="e.g. Equipment Malfunction">
                 <label>Location</label><input id="i-loc" class="swal2-input" placeholder="e.g. Warehouse Zone B">
                 <label>Date</label><input id="i-date" type="date" class="swal2-input">
                 <label>Severity</label><select id="i-sev" class="swal2-select"><option value="Low">Low</option><option value="Medium">Medium</option><option value="High">High</option><option value="Critical">Critical</option></select>
                 <label>Description</label><textarea id="i-desc" class="swal2-textarea"></textarea>`,
          confirmButtonColor: "#ef4444", confirmButtonText: "Report Incident",
          preConfirm: () => { return { action: 'report_incident', type: document.getElementById('i-type').value, location: document.getElementById('i-loc').value, date: document.getElementById('i-date').value, severity: document.getElementById('i-sev').value, description: document.getElementById('i-desc').value }; }
        });
        if (formValues) {
            const res = await fetch("../api/compliance.php", { method: "POST", body: JSON.stringify(formValues) });
            const data = await res.json();
            if (data.status === "success") { Swal.fire("Reported", data.message, "success"); loadData(); }
        }
      }
      loadData();
    </script>
  </body>
</html>
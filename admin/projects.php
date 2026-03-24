<?php
session_start();
$page = 'projects'; // For sidebar highlighting
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Project Management</title>
  <link rel="stylesheet" href="../css/dashboard.css">
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="icon" type="image/png" href="../img/logo.png">
  <style>
      .bpa-btn { background: #3b82f6; color: white; padding: 6px 12px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
      .bpa-btn:hover { background: #2563eb; }
      .item-row { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px; margin-bottom: 10px; align-items: center; background: #f9f9f9; padding: 8px; border-radius: 6px; }
      .remove-btn { color: #ef4444; cursor: pointer; font-weight: bold; padding: 4px; }
  </style>
</head>
<body>
  
  <?php include '../includes/sidebar.php'; ?>

  <main class="main-content">
    <header class="page-header">
      <div class="header-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn"><i data-lucide="menu"></i></button>
        <div class="header-title"><h1>Project Management</h1></div>
      </div>
      <div class="header-right">
        <button class="action-btn" onclick="openCreateProjectModal()" style="background: var(--brand-green); color: white;">
          <i data-lucide="folder-plus"></i> New Project
        </button>
      </div>
    </header>

    <div class="content-wrapper">
      <div class="content-card">
        <div class="card-header">
          <h2 class="card-title">Project Portfolio</h2>
          <small>Manage projects and auto-draft material requisitions (BPA).</small>
        </div>
        <div class="card-body">
          <div class="data-table" id="projectTable">
            <p style="text-align:center; padding: 20px;">Loading projects...</p>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="../js/dashboard.js"></script>
  <script>
    lucide.createIcons();
    let bpaItemsList = [];

    // 1. Fetch and Display Projects
    async function loadProjects() {
        const table = document.getElementById('projectTable');
        try {
            const res = await fetch('../api/projects.php?action=get_projects');
            const data = await res.json();
            
            if(data.status === 'success') {
                if(!data.data || data.data.length === 0) {
                    table.innerHTML = '<p style="text-align:center; padding:20px; color:#888;">No projects found. Create one to start!</p>';
                    return;
                }

                const html = data.data.map(p => {
                    // Logic to show BPA button only if project is in Planning
                    let actionBtn = '';
                    if (p.status === 'Planning') {
                        actionBtn = `<button onclick="openBPAModal(${p.id}, '${p.project_name}')" class="bpa-btn">
                                        <i data-lucide="send" style="width:14px; height:14px;"></i> Auto-Draft Requisition
                                     </button>`;
                    } else {
                        actionBtn = `<span style="font-size:12px; color:#888;"><i data-lucide="check-circle" style="width:14px; height:14px; vertical-align:middle;"></i> Materials Requested</span>`;
                    }

                    return `
                    <div class="table-row" style="grid-template-columns: 2fr 1fr 1fr auto;">
                        <div class="client-info">
                            <div class="client-avatar" style="background: #e2e8f0; color: #333;">
                                <i data-lucide="folder"></i>
                            </div>
                            <div>
                                <span class="client-name">${p.project_name}</span>
                                <span class="client-detail">${p.description || 'No description'}</span>
                            </div>
                        </div>
                        <div class="amount">
                            <div>$${parseFloat(p.budget_limit).toLocaleString()}</div>
                            <small style="color:var(--text-tertiary);">Budget Limit</small>
                        </div>
                        <div>
                            <span class="badge-status ${p.status === 'Active' ? 'approved' : 'pending'}">${p.status}</span>
                        </div>
                        <div style="text-align: right;">${actionBtn}</div>
                    </div>
                `}).join('');
                table.innerHTML = html;
                lucide.createIcons();
            } else {
                table.innerHTML = `<p style="color:#ef4444; padding:20px;">DB Error: ${data.message}</p>`;
            }
        } catch(e) {
            table.innerHTML = `<p style="color:#ef4444; padding:20px;">Network Error: ${e.message}</p>`;
        }
    }

    // 2. Create Project Modal
    function openCreateProjectModal() {
        Swal.fire({
            title: 'Initialize New Project',
            html: `
                <input id="swal-name" class="swal2-input" placeholder="Project Name">
                <input id="swal-desc" class="swal2-input" placeholder="Project Description">
                <input id="swal-budget" type="number" class="swal2-input" placeholder="Estimated Budget Limit ($)">
            `,
            confirmButtonText: 'Create Project',
            confirmButtonColor: '#2ca078',
            showCancelButton: true,
            preConfirm: () => {
                const name = document.getElementById('swal-name').value;
                if(!name) Swal.showValidationMessage('Project name is required');
                return {
                    action: 'create_project',
                    project_name: name,
                    description: document.getElementById('swal-desc').value,
                    budget_limit: document.getElementById('swal-budget').value || 0
                }
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                const res = await fetch('../api/projects.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(result.value)
                });
                const data = await res.json();
                if(data.status === 'success') {
                    Swal.fire('Success', data.message, 'success');
                    loadProjects(); 
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            }
        });
    }

    // 3. BPA Auto-Draft Requisition Modal
    function openBPAModal(projectId, projectName) {
        bpaItemsList = []; 
        Swal.fire({
            title: 'Auto-Draft Requisition',
            text: `Request materials for ${projectName}. This will instantly send a Requisition to Procurement.`,
            width: '700px',
            html: `
                <div style="text-align:left; background:#eff6ff; padding:10px; border-radius:6px; margin-bottom:15px; font-size:13px; color:#1e3a8a;">
                    <i data-lucide="info" style="width:16px; height:16px; vertical-align:middle;"></i> BPA Automation: This form bypasses manual entry and injects data directly into the Procurement workflow.
                </div>
                <div style="text-align:left;">
                    <div id="bpa-item-container" style="max-height:150px; overflow-y:auto; margin-bottom:10px;"></div>
                    
                    <div style="display:flex; gap:10px; margin-top:10px;">
                        <input id="bpa-item" placeholder="Item Name" class="swal2-input" style="margin:0; flex:2;">
                        <input id="bpa-qty" type="number" placeholder="Qty" class="swal2-input" style="margin:0; flex:1;">
                        <input id="bpa-unit" placeholder="Unit (e.g., pcs)" class="swal2-input" style="margin:0; flex:1;">
                        <input id="bpa-cost" type="number" placeholder="Est. Cost" class="swal2-input" style="margin:0; flex:1;">
                        <button type="button" onclick="addBpaItem()" class="action-btn" style="padding:0 15px; background:var(--brand-green); color:white;">+</button>
                    </div>
                </div>
            `,
            confirmButtonText: 'Send to Procurement',
            confirmButtonColor: '#3b82f6',
            showCancelButton: true,
            didOpen: () => { lucide.createIcons(); renderBpaItems(); },
            preConfirm: () => {
                if(bpaItemsList.length === 0) return Swal.showValidationMessage('Please add at least one material to request.');
                return { 
                    action: 'auto_draft_req',
                    project_id: projectId,
                    project_name: projectName,
                    items: bpaItemsList 
                }
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                const res = await fetch('../api/projects.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(result.value)
                });
                const data = await res.json();
                if(data.status === 'success') {
                    Swal.fire('BPA Executed!', data.message, 'success');
                    loadProjects();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            }
        });
    }

    // BPA Item Management
    window.addBpaItem = function() {
        const name = document.getElementById('bpa-item').value;
        const qty = document.getElementById('bpa-qty').value;
        const unit = document.getElementById('bpa-unit').value || 'pcs';
        const cost = document.getElementById('bpa-cost').value || 0;
        
        if(!name || !qty) return;

        bpaItemsList.push({name, qty, unit, cost});
        renderBpaItems();
        
        document.getElementById('bpa-item').value = '';
        document.getElementById('bpa-qty').value = '';
        document.getElementById('bpa-cost').value = '';
        document.getElementById('bpa-item').focus();
    };

    function renderBpaItems() {
        const container = document.getElementById('bpa-item-container');
        if(bpaItemsList.length === 0) {
            container.innerHTML = '<span style="color:#aaa; font-size:12px;">No items added yet.</span>';
            return;
        }
        container.innerHTML = bpaItemsList.map((item, index) => `
            <div class="item-row">
                <span style="font-weight:500; font-size:14px;">${item.name}</span>
                <span style="font-size:14px;">${item.qty}</span>
                <span style="color:#666; font-size:12px;">${item.unit}</span>
                <span style="color:#666; font-size:12px;">$${item.cost}</span>
                <span class="remove-btn" onclick="bpaItemsList.splice(${index},1); renderBpaItems()">×</span>
            </div>
        `).join('');
    }

    loadProjects();
  </script>
</body>
</html>
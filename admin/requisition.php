<?php



session_start();



$page = 'requisition.php';



?>



<!DOCTYPE html>



<html lang="en">



<head>



  <meta charset="UTF-8">



  <meta name="viewport" content="width=device-width, initial-scale=1.0">



  <title>Purchase Requisitions Management</title>



  <link rel="stylesheet" href="../css/dashboard.css">



  <script src="https://unpkg.com/lucide@latest"></script>



  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



  <link rel="icon" type="image/png" href="../img/logo.png">



  <style>



    /* Requisition Management Styles matching procurement.php */



    .form-section {



      margin-bottom: 2rem;



      background: var(--bg-primary);



      border: 1px solid var(--border-color);



      border-radius: 8px;



      padding: 1.5rem;



      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);



    }







    .form-section h4 {



      margin: 0 0 1rem 0;



      color: var(--text-primary);



      font-size: 1rem;



      display: flex;



      align-items: center;



      gap: 0.5rem;



      padding-bottom: 0.75rem;



      border-bottom: 2px solid var(--border-color);



    }







    .form-grid {



      display: grid;



      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));



      gap: 1.5rem;



    }







    .form-group {



      display: flex;



      flex-direction: column;



    }







    .form-group label {



      margin-bottom: 0.5rem;



      color: var(--text-primary);



      font-weight: 500;



      font-size: 0.9rem;



    }







    .form-group input,



    .form-group select,



    .form-group textarea {



      padding: 0.75rem;



      border: 1px solid var(--border-color);



      border-radius: 6px;



      font-size: 0.9rem;



      font-family: inherit;



      background: var(--bg-primary);



      color: var(--text-primary);



      transition: border-color 0.2s;



    }







    .form-group input:focus,



    .form-group select:focus,



    .form-group textarea:focus {



      outline: none;



      border-color: var(--brand-green);



      box-shadow: 0 0 0 3px rgba(44, 160, 120, 0.1);



    }







    .form-group textarea {



      resize: vertical;



      min-height: 80px;



    }







    .button-group {



      display: flex;



      gap: 1rem;



      margin-top: 2rem;



    }







    .btn {



      padding: 0.75rem 1.5rem;



      border: none;



      border-radius: 6px;



      font-size: 0.9rem;



      font-weight: 500;



      cursor: pointer;



      transition: all 0.2s;



      display: flex;



      align-items: center;



      gap: 0.5rem;



    }







    .btn-primary {



      background: var(--brand-green);



      color: white;



    }







    .btn-primary:hover {



      background: #1d8659;



      transform: translateY(-2px);



      box-shadow: 0 4px 12px rgba(44, 160, 120, 0.3);



    }







    .btn-secondary {



      background: var(--bg-secondary);



      color: var(--text-primary);



      border: 1px solid var(--border-color);



    }







    .btn-secondary:hover {



      background: var(--border-color);



    }







    .requisition-table {



      width: 100%;



      border-collapse: collapse;



      margin-top: 1.5rem;



      font-size: 0.85rem;



      background: var(--bg-primary);



      border-radius: 8px;



      overflow: hidden;



      border: 1px solid var(--border-color);



      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);



    }







    .requisition-table thead {



      background: var(--bg-secondary);



      border-bottom: 2px solid var(--border-color);



    }







    .requisition-table th {



      padding: 0.75rem;



      text-align: left;



      font-weight: 600;



      color: var(--text-primary);



      white-space: nowrap;



    }







    .requisition-table td {



      padding: 0.75rem;



      border-bottom: 1px solid var(--border-color);



      color: var(--text-primary);



      vertical-align: middle;



    }







    .requisition-table tbody tr:hover {



      background: var(--bg-secondary);



    }







    .requisition-table tbody tr:last-child td {



      border-bottom: none;



    }







    .status-badge {



      display: inline-block;



      padding: 0.25rem 0.75rem;



      border-radius: 20px;



      font-size: 0.75rem;



      font-weight: 600;



      text-transform: uppercase;



    }







    .status-pending {



      background: rgba(255, 193, 7, 0.15);



      color: #f59e0b;



    }







    .status-approved {



      background: rgba(44, 160, 120, 0.15);



      color: var(--brand-green);



    }







    .status-po_created {



      background: rgba(59, 130, 246, 0.15);



      color: #3b82f6;



    }







    .alert {



      padding: 1rem;



      border-radius: 6px;



      margin-bottom: 1rem;



      display: none;



    }







    .alert.show {



      display: block;



    }







    .alert-success {



      background: rgba(44, 160, 120, 0.1);



      color: var(--brand-green);



      border: 1px solid var(--brand-green);



    }







    .alert-error {



      background: rgba(239, 68, 68, 0.1);



      color: #ef4444;



      border: 1px solid #ef4444;



    }







    .action-buttons {



      display: flex;



      gap: 0.5rem;



      align-items: center;



    }







    .btn-action {



      padding: 0.4rem 0.8rem;



      border: none;



      border-radius: 4px;



      font-size: 0.8rem;



      cursor: pointer;



      transition: all 0.2s;



      display: inline-flex;



      align-items: center;



      gap: 0.3rem;



    }







    .btn-edit {



      background: rgba(59, 130, 246, 0.1);



      color: #3b82f6;



      border: 1px solid #3b82f6;



    }







    .btn-edit:hover {



      background: #3b82f6;



      color: white;



    }







    .btn-success {



      background: rgba(44, 160, 120, 0.1);



      color: var(--brand-green);



      border: 1px solid var(--brand-green);



    }







    .btn-success:hover {



      background: var(--brand-green);



      color: white;



    }







    .btn-info {



      background: rgba(99, 102, 241, 0.1);



      color: #6366f1;



      border: 1px solid #6366f1;



    }







    .btn-info:hover {



      background: #6366f1;



      color: white;



    }







    .item-row {



      display: grid;



      grid-template-columns: 2fr 1fr 1fr auto;



      gap: 10px;



      margin-bottom: 10px;



      align-items: center;



      background: #f9f9f9;



      padding: 8px;



      border-radius: 6px;



    }







    .remove-btn {



      color: #ef4444;



      cursor: pointer;



      font-weight: bold;



      padding: 4px;



    }







    .remove-btn:hover { color: #dc2626; }



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



          <h1>Purchase Requisitions Management</h1>



          <p>Create and manage purchase requisitions</p>



        </div>



      </div>



      <div class="header-right">



        <div class="search-box">



          <i data-lucide="search"></i>



          <input type="search" placeholder="Search requisitions...">



        </div>



        <button class="icon-btn">



          <i data-lucide="bell"></i>



        </button>



        <button class="btn btn-primary" onclick="openReqModal()">



          <i data-lucide="plus-circle"></i> New Requisition



        </button>



      </div>



    </header>







    <div class="content-wrapper">



      <div class="content-grid">



        <div class="content-card" style="grid-column: 1 / -1;">



          <div class="card-header">



            <div>



              <h3 class="card-title">Requisition Management</h3>



              <p class="card-subtitle">Create and manage purchase requisitions</p>



            </div>



          </div>



          <div class="card-body">



            <div id="successAlert" class="alert alert-success">



              <i data-lucide="check-circle"></i> <span id="successMessage">Operation completed successfully!</span>



            </div>



            <div id="errorAlert" class="alert alert-error">



              <i data-lucide="alert-circle"></i> <span id="errorMessage">Error occurred</span>



            </div>







            <div class="form-section">



              <h4><i data-lucide="list"></i> My Requisitions</h4>



              <table class="requisition-table" id="reqTable">



                <thead>



                  <tr>



                    <th>Requisition ID</th>



                    <th>Purpose</th>



                    <th>Items</th>



                    <th>Request Date</th>



                    <th>Status</th>



                    <th>Actions</th>



                  </tr>



                </thead>



                <tbody id="reqTableBody">



                  <tr>



                    <td colspan="6" style="text-align: center; color: var(--text-secondary);">



                      Loading requisitions...



                    </td>



                  </tr>



                </tbody>



              </table>



            </div>



          </div>



        </div>



      </div>



    </div>



  </main>







  <script src="../js/dashboard.js"></script>



  <script>



    lucide.createIcons();



    let itemList = []; 



    let editItemList = [];







    // Requisition Management System



    class RequisitionManagement {



      constructor() {



        this.requisitions = [];



        this.apiUrl = '../api/requisition.php';



        this.loadRequisitions();



      }







      // Load requisitions



      async loadRequisitions() {



        try {



          console.log('Loading requisitions...');



          const response = await fetch(this.apiUrl, {



            method: 'GET',



            headers: {



              'Content-Type': 'application/json',



              'Accept': 'application/json'



            }



          });







          if (!response.ok) {



            throw new Error(`HTTP ${response.status}: ${response.statusText}`);



          }







          const result = await response.json();



          console.log('API Response:', result);







          if (result.status === 'success') {



            this.requisitions = result.data || [];



            console.log('Loaded requisitions:', this.requisitions.length);



          } else {



            this.showError('Failed to load requisitions: ' + result.message);



          }



          this.displayRequisitions();



        } catch (error) {



          console.error('Load requisitions error:', error);



          this.showError('Error loading requisitions: ' + error.message);



          this.displayRequisitions(); // Show empty state



        }



      }







      // Display requisitions in table



      displayRequisitions() {



        const tbody = document.getElementById('reqTableBody');







        if (this.requisitions.length === 0) {



          tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-secondary);">No requisitions found</td></tr>';



          return;



        }







        tbody.innerHTML = this.requisitions.map(req => {



          const itemsHtml = req.items && req.items.length > 0 



            ? req.items.map(item => `



                <div style="display: inline-block; margin: 2px 4px 2px 0; padding: 2px 6px; background: rgba(0,0,0,0.05); border-radius: 3px; font-size: 0.85rem;">



                  <strong>${item.name}</strong> (${item.quantity} ${item.unit})



                </div>



              `).join('')



            : '<span style="color: #999; font-style: italic;">No items</span>';



            



          return `



            <tr>



              <td><strong style="color: #2563eb;">REQ #${req.id}</strong></td>



              <td>



                <div style="font-weight: 500; color: var(--text-primary);">${req.remarks || "No remarks"}</div>



              </td>



              <td>



                <div style="max-width: 400px; overflow: hidden; text-overflow: ellipsis;">



                  ${itemsHtml}



                </div>



              </td>



              <td>



                <div style="color: #666; font-size: 0.9rem;">${req.request_date}</div>



              </td>



              <td>



                <span class="status-badge status-${req.status.toLowerCase().replace(' ', '_')}">



                  ${req.status}



                </span>



              </td>



              <td>



                <div class="action-buttons">



                  <button class="btn-action btn-edit" onclick="requisitionMgr.editRequisition(${req.id})" title="Edit Requisition">



                    <i data-lucide="edit"></i> Edit



                  </button>



                  <button class="btn-action btn-success" onclick="requisitionMgr.sendRequest(${req.id})" title="Send Request">



                    <i data-lucide="send"></i> Send



                  </button>



                  <button class="btn-action btn-info" onclick="requisitionMgr.viewDetails(${req.id})" title="View Details">



                    <i data-lucide="eye"></i> View



                  </button>



                </div>



              </td>



            </tr>



          `;



        }).join('');







        // Reinitialize Lucide icons



        lucide.createIcons();



      }







      // Show success message



      showSuccess(message) {



        const alert = document.getElementById('successAlert');



        document.getElementById('successMessage').textContent = message;



        alert.classList.add('show');



        setTimeout(() => alert.classList.remove('show'), 4000);



      }







      // Show error message



      showError(message) {



        const alert = document.getElementById('errorAlert');



        document.getElementById('errorMessage').textContent = message;



        alert.classList.add('show');



        setTimeout(() => alert.classList.remove('show'), 4000);



      }







      // Add requisition (using SweetAlert modal)



      async addRequisition(requisitionData) {



        try {



          const response = await fetch(this.apiUrl, {



            method: 'POST',



            headers: {



              'Content-Type': 'application/json'



            },



            body: JSON.stringify(requisitionData)



          });







          const result = await response.json();







          if (result.status === 'success') {



            this.showSuccess('Requisition created successfully');



            this.loadRequisitions();



            return true;



          } else {



            this.showError('Failed to create requisition: ' + result.message);



            return false;



          }



        } catch (error) {



          this.showError('Error creating requisition: ' + error.message);



          return false;



        }



      }







      // Edit requisition



      editRequisition(reqId) {



        const requisition = this.requisitions.find(r => r.id === reqId);



        if (!requisition) return;







        // For now, just show details. In a full implementation, this would open an edit modal



        this.viewDetails(reqId);



      }







      // Send request to vendor registration



      async sendRequest(reqId) {



        try {



          // Get requisition details first



          const response = await fetch(`${this.apiUrl}?id=${reqId}`);



          const data = await response.json();



          



          if (data.status === 'success' && data.data && data.data.length > 0) {



            const requisition = data.data[0];



            



            const result = await Swal.fire({



              title: 'Send Request to Vendor Registration',



              html: `



                <div style="text-align: left;">



                  <p><strong>Requisition Details:</strong></p>



                  <p><strong>ID:</strong> REQ #${requisition.id}</p>



                  <p><strong>Purpose:</strong> ${requisition.remarks || 'No purpose specified'}</p>



                  <p><strong>Items:</strong> ${requisition.items ? requisition.items.map(item => `${item.name} (${item.quantity} ${item.unit})`).join(', ') : 'No items'}</p>



                  <p><strong>Date:</strong> ${requisition.request_date}</p>



                  <hr style="margin: 10px 0;">



                  <p>This requisition will appear in the Purchase Requisitions area of Vendor Registration page.</p>



                </div>



              `,



              icon: 'info',



              confirmButtonText: 'Send Request',



              confirmButtonColor: '#2ca078',



              showCancelButton: true,



            });







            if (result.isConfirmed) {



              // Send to vendor-registration Purchase Requisitions



              try {



                const sendResponse = await fetch('../api/vendor_requisitions.php', {



                  method: 'POST',



                  headers: { 'Content-Type': 'application/json' },



                  body: JSON.stringify({



                    requisition_id: reqId,



                    vendor_id: 'VENDOR_REGISTRATION',



                    sent_to: 'vendor_registration'



                  })



                });







                const sendResult = await sendResponse.json();



                if (sendResult.status === 'success') {



                  this.showSuccess('Request sent to Vendor Registration Purchase Requisitions area!');



                  this.loadRequisitions();



                } else {



                  this.showError(sendResult.message || 'Failed to send request');



                }



              } catch (error) {



                this.showError('Failed to send request');



              }



            }



          }



        } catch (error) {



          this.showError('Failed to load requisition details');



        }



      }







      // View requisition details



      async viewDetails(reqId) {



        try {



          const response = await fetch(`${this.apiUrl}?id=${reqId}`);



          const data = await response.json();



          



          if (data.status === 'success' && data.data && data.data.length > 0) {



            const requisition = data.data[0];



            const itemsHtml = requisition.items && requisition.items.length > 0



              ? requisition.items.map(item => `



                  <div style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">



                    <strong>${item.name}</strong> - ${item.quantity} ${item.unit}



                  </div>



                `).join('')



              : '<p>No items found</p>';







            Swal.fire({



              title: `Requisition #${requisition.id} Details`,



              html: `



                <div style="text-align: left;">



                  <p><strong>Purpose:</strong> ${requisition.remarks || 'No purpose specified'}</p>



                  <p><strong>Date:</strong> ${requisition.request_date}</p>



                  <p><strong>Status:</strong> ${requisition.status}</p>



                  <p><strong>Items:</strong></p>



                  ${itemsHtml}



                </div>



              `,



              width: '600px',



              confirmButtonText: 'Close',



              confirmButtonColor: '#2ca078'



            });



          }



        } catch (error) {



          this.showError('Failed to load requisition details');



        }



      }



    }







    // Initialize requisition management



    let requisitionMgr;



    document.addEventListener('DOMContentLoaded', () => {



      requisitionMgr = new RequisitionManagement();



    });







    // Add Requisition Modal (using SweetAlert)



    function openReqModal() {



      itemList = []; 



      Swal.fire({



        title: "New Requisition",



        width: "700px",



        html: `



          <div style="text-align:left; margin-bottom:10px;">



              <label style="font-size:12px; font-weight:600; color:#666;">Purpose / Project Name</label>



              <input id="req-remarks" class="swal2-input" placeholder="e.g., Office Supplies Q1" style="margin: 5px 0 15px 0;">



              



              <h4 style="margin-top:20px; border-bottom:1px solid #eee; padding-bottom:5px;">Items</h4>



              <div id="item-container" style="max-height:150px; overflow-y:auto; margin-bottom:10px;"></div>



              



              <div style="display:flex; gap:10px; margin-top:10px;">



                  <input id="new-item" placeholder="Item Name" class="swal2-input" style="margin:0; flex:2;">



                  <input id="new-qty" type="number" placeholder="Qty" class="swal2-input" style="margin:0; flex:1;">



                  <input id="new-unit" placeholder="Unit" class="swal2-input" style="margin:0; flex:1;">



                  <button type="button" onclick="addItem()" class="btn btn-primary" style="padding:0 15px;">+</button>



              </div>



          </div>



        `,



        confirmButtonText: "Submit Request",



        confirmButtonColor: "#2ca078",



        showCancelButton: true,



        preConfirm: () => {



          if (itemList.length === 0) return Swal.showValidationMessage("Please add at least one item");



          return { remarks: document.getElementById("req-remarks").value, items: itemList };



        },



      }).then(async (result) => {



        if (result.isConfirmed && result.value) {



          const success = await requisitionMgr.addRequisition(result.value);



          if (success) {



            // Success message shown through requisitionMgr.showSuccess()



          }



        }



      });



    }







    // Add item function



    window.addItem = function () {



      const name = document.getElementById("new-item").value;



      const qty = document.getElementById("new-qty").value;



      const unit = document.getElementById("new-unit").value;



      if (!name || !qty) return;



      itemList.push({ name, qty, unit });



      renderItems();



      document.getElementById("new-item").value = "";



      document.getElementById("new-qty").value = "";



      document.getElementById("new-item").focus();



    };







    // Render items function



    function renderItems() {



      const container = document.getElementById("item-container");



      if (itemList.length === 0) {



        container.innerHTML = '<span style="color:#aaa; font-size:12px;">No items added yet.</span>';



        return;



      }



      container.innerHTML = itemList.map((item, index) => `



          <div class="item-row">



              <span style="font-weight:500;">${item.name}</span>



              <span>${item.qty}</span>



              <span style="color:#666; font-size:12px;">${item.unit}</span>



              <span class="remove-btn" onclick="itemList.splice(${index},1); renderItems()">×</span>



          </div>



      `).join("");



    }



  </script>



</body>



</html>
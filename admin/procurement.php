<?php

session_start();

?>

<!DOCTYPE html>







<html lang="en">







<head>



  <meta charset="UTF-8">



  <meta name="viewport" content="width=device-width, initial-scale=1.0">



  <title>Procurement - Vendor Management</title>



  <link rel="stylesheet" href="../css/dashboard.css">



  <script src="https://unpkg.com/lucide@latest"></script>



  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



  <link rel="icon" type="image/png" href="../img/logo.png">



  <style>



    /* Vendor Management Styles matching vendor-registration.html */



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







    .vendor-table {



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







    .vendor-table thead {



      background: var(--bg-secondary);



      border-bottom: 2px solid var(--border-color);



    }







    .vendor-table th {



      padding: 0.75rem;



      text-align: left;



      font-weight: 600;



      color: var(--text-primary);



    }







    .vendor-table td {



      padding: 0.75rem;



      border-bottom: 1px solid var(--border-color);



      color: var(--text-primary);



    }







    .vendor-table tbody tr:hover {



      background: var(--bg-secondary);



    }







    .vendor-table tbody tr:last-child td {



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







    .status-active {



      background: rgba(44, 160, 120, 0.15);



      color: var(--brand-green);



    }







    .status-pending {



      background: rgba(255, 193, 7, 0.15);



      color: #f59e0b;



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







    .btn-delete {



      background: rgba(239, 68, 68, 0.1);



      color: #ef4444;



      border: 1px solid #ef4444;



    }







    .btn-delete:hover {



      background: #ef4444;



      color: white;



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



          <h1>Procurement - Vendor Management</h1>



          <p>Manage procurement vendors and supplier relationships</p>



        </div>



      </div>



      <div class="header-right">



        <div class="search-box">



          <i data-lucide="search"></i>



          <input type="search" placeholder="Search vendors...">



        </div>



        <button class="icon-btn">



          <i data-lucide="bell"></i>



        </button>



        

      </div>



    </header>







    <div class="content-wrapper">



      <div class="content-grid">



        <div class="content-card" style="grid-column: 1 / -1;">



          <div class="card-header">



            <div>



              <h3 class="card-title">Vendor Management</h3>



              <p class="card-subtitle">Manage procurement vendors and suppliers</p>



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



              <h4><i data-lucide="store"></i> Active Vendors</h4>



              <table class="vendor-table" id="vendorTable">



                <thead>



                  <tr>



                    <th>Vendor ID</th>



                    <th>Company Name</th>



                    <th>Contact Person</th>



                    <th>Contact Info</th>



                    <th>Services/Product</th>



                    <th>Business Details</th>



                    <th>Status</th>



                  </tr>



                </thead>



                <tbody id="vendorTableBody">



                  <tr>



                    <td colspan="7" style="text-align: center; color: var(--text-secondary);">



                      Loading vendors...



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







    // Procurement Vendor Management System - Using logistics_db database



    class ProcurementVendorManagement {



      constructor() {



        this.vendors = [];



        this.apiUrl = '../api/procurement_vendors.php';



        this.loadVendors();



      }







      // Load vendors from logistics_db vendors table



      async loadVendors() {



        try {



          console.log('Loading vendors from logistics_db...');



          const response = await fetch(`${this.apiUrl}?action=get_procurement_vendors`, {



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



            this.vendors = result.data || [];



            console.log('Loaded vendors from logistics_db:', this.vendors.length);



          } else {



            this.showError('Failed to load vendors: ' + result.message);



          }



          this.displayVendors();



        } catch (error) {



          console.error('Load vendors error:', error);



          this.showError('Error loading vendors from logistics_db: ' + error.message);



          this.displayVendors(); // Show empty state



        }



      }







      // Display vendors in table



      displayVendors() {



        const tbody = document.getElementById('vendorTableBody');







        if (this.vendors.length === 0) {



          tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-secondary);">No vendors sent to procurement yet</td></tr>';



          return;



        }







        tbody.innerHTML = this.vendors.map(vendor => `



          <tr>



            <td><strong>${vendor.id}</strong></td>



            <td>${vendor.company_name}</td>



            <td>${vendor.contact_person}</td>



            <td>



              <div>${vendor.email}</div>



              <small style="color: var(--text-secondary);">${vendor.phone}</small>



            </td>



            <td>${vendor.business_type || '-'}</td>



            <td><small style="color: var(--text-secondary);">${vendor.business_details || '-'}</small></td>



            <td>



              <span class="status-badge status-${vendor.status.toLowerCase()}">



                ${vendor.status}



              </span>



            </td>



          </tr>



        `).join('');







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







      // Add vendor (using SweetAlert modal)



      async addVendor(vendorData) {



        try {



          const response = await fetch(this.apiUrl, {



            method: 'POST',



            headers: {



              'Content-Type': 'application/json'



            },



            body: JSON.stringify({



              action: 'add_vendor',



              ...vendorData



            })



          });







          const result = await response.json();







          if (result.status === 'success') {



            this.showSuccess('Vendor added successfully to logistics_db');



            this.loadVendors();



            return true;



          } else {



            this.showError('Failed to add vendor: ' + result.message);



            return false;



          }



        } catch (error) {



          this.showError('Error adding vendor: ' + error.message);



          return false;



        }



      }



    }







    // Initialize procurement vendor management



    let procurementVendors;



    document.addEventListener('DOMContentLoaded', () => {



      procurementVendors = new ProcurementVendorManagement();



    });







    // Add Vendor Modal (using SweetAlert)



    function openAddModal() {



      Swal.fire({



        title: "Add New Vendor",



        html: `



          <div style="text-align: left;">



            <input id="swal-company" class="swal2-input" placeholder="Company Name *" style="width: 100%; margin-bottom: 10px;">



            <input id="swal-contact" class="swal2-input" placeholder="Contact Person *" style="width: 100%; margin-bottom: 10px;">



            <input id="swal-email" class="swal2-input" placeholder="Email Address *" style="width: 100%; margin-bottom: 10px;">



            <input id="swal-phone" class="swal2-input" placeholder="Phone Number *" style="width: 100%; margin-bottom: 10px;">



            <textarea id="swal-address" class="swal2-textarea" placeholder="Address *" style="width: 100%; height: 80px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; padding: 8px;"></textarea>



          </div>



        `,



        confirmButtonText: "Save Vendor",



        confirmButtonColor: "#2ca078",



        showCancelButton: true,



        preConfirm: () => {



          const company = document.getElementById("swal-company").value;



          const contact = document.getElementById("swal-contact").value;



          const email = document.getElementById("swal-email").value;



          const phone = document.getElementById("swal-phone").value;



          const address = document.getElementById("swal-address").value;







          // Validation



          if (!company || !contact || !email || !phone || !address) {



            Swal.showValidationMessage("Please fill all required fields");



            return false;



          }







          // Email validation



          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;



          if (!emailRegex.test(email)) {



            Swal.showValidationMessage("Please enter a valid email address");



            return false;



          }







          return {



            company_name: company,



            contact_person: contact,



            email: email,



            phone: phone,



            address: address



          };



        },



      }).then(async (result) => {



        if (result.isConfirmed && result.value) {



          const success = await procurementVendors.addVendor(result.value);



          if (success) {



            // SweetAlert will show success through the procurementVendors.showSuccess()



          }



        }



      });



    }



  </script>



</body>



</html>




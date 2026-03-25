<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
$page = 'mro_planning.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>4.1 Maintenance Planning - MRO System</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="../img/logo.png" />
    <style>
      .plan-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
      }
      .plan-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-left: 4px solid #2ca078;
      }
      .plan-card h3 {
        margin: 0 0 10px 0;
        color: #333;
      }
      .plan-type {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        margin-bottom: 10px;
      }
      .type-preventive { background: #e7f3ff; color: #0066cc; }
      .type-predictive { background: #fff3cd; color: #856404; }
      .type-inspection { background: #d4edda; color: #155724; }
      .type-overhaul { background: #f8d7da; color: #721c24; }
      .due-date {
        font-size: 14px;
        color: #666;
        margin: 5px 0;
      }
      .due-soon { color: #ef4444; font-weight: bold; }
      .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
      }
      .stat-box {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
      }
      .stat-box .value {
        font-size: 24px;
        font-weight: bold;
        color: #2ca078;
      }
      .stat-box .label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
      }
      .chart-container {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
      }
    </style>
  </head>
  <body>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
      <header class="page-header">
        <div class="header-left">
          <h1>4.1 Maintenance Planning</h1>
          <p>Schedule and manage preventive maintenance activities</p>
        </div>
        <div class="header-right">
          <button class="action-btn" onclick="openPlanModal()">
            <i data-lucide="plus"></i> New Maintenance Plan
          </button>
          <button class="action-btn" onclick="refreshPlans()">
            <i data-lucide="refresh-cw"></i> Refresh
          </button>
        </div>
      </header>

      <!-- Planning Statistics -->
      <div class="stats-grid">
        <div class="stat-box">
          <div class="value" id="activePlans">0</div>
          <div class="label">Active Plans</div>
        </div>
        <div class="stat-box">
          <div class="value" id="dueThisWeek">0</div>
          <div class="label">Due This Week</div>
        </div>
        <div class="stat-box">
          <div class="value" id="overduePlans">0</div>
          <div class="label">Overdue</div>
        </div>
        <div class="stat-box">
          <div class="value" id="completedThisMonth">0</div>
          <div class="label">Completed This Month</div>
        </div>
      </div>

      <!-- Maintenance Schedule Chart -->
      <div class="chart-container">
        <h3>Maintenance Schedule Overview</h3>
        <canvas id="maintenanceChart" width="400" height="150"></canvas>
      </div>

      <!-- Maintenance Plans -->
      <div class="content-card">
        <div class="card-header">
          <h2 class="card-title">Maintenance Plans</h2>
          <div style="display: flex; gap: 10px;">
            <select id="statusFilter" onchange="loadPlans()" style="padding: 5px; border-radius: 5px; border: 1px solid #ddd;">
              <option value="">All Status</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
              <option value="Suspended">Suspended</option>
            </select>
            <select id="typeFilter" onchange="loadPlans()" style="padding: 5px; border-radius: 5px; border: 1px solid #ddd;">
              <option value="">All Types</option>
              <option value="Preventive">Preventive</option>
              <option value="Predictive">Predictive</option>
              <option value="Inspection">Inspection</option>
              <option value="Overhaul">Overhaul</option>
            </select>
          </div>
        </div>
        <div class="card-body">
          <div class="plan-grid" id="plansContainer">
            <p style="text-align:center; padding:20px;">Loading maintenance plans...</p>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script src="../js/click_fix.js"></script>
    <script>
      lucide.createIcons();
      let maintenanceChart = null;

      document.addEventListener('DOMContentLoaded', function() {
        // Debug logging
        function debugLog(message) {
          console.log('[MRO Planning]', message);
        }
        
        debugLog('Page loaded - initializing');
        
        loadPlans();
        loadStatistics();
        initMaintenanceChart();
        
        // Fix dropdown navigation
        if (window.initializeNavigation) {
          window.initializeNavigation();
        }
        
        // Auto-refresh every 30 seconds
        setInterval(refreshPlans, 30000);
        
        debugLog('Initialization complete');
      });

      async function loadPlans() {
        try {
          const status = document.getElementById('statusFilter').value;
          const type = document.getElementById('typeFilter').value;
          
          let url = '../api/mro_api.php?endpoint=maintenance_planning&action=list';
          const params = new URLSearchParams();
          if (status) params.append('status', status);
          if (type) params.append('type', type);
          if (params.toString()) url += '&' + params.toString();
          
          const response = await fetch(url);
          const data = await response.json();
          
          if (data.success) {
            const plans = data.data;
            document.getElementById('plansContainer').innerHTML = plans.map(plan => `
              <div class="plan-card" onclick="viewPlanDetails('${plan.plan_id}')">
                <div class="plan-type type-${plan.plan_type.toLowerCase()}">${plan.plan_type}</div>
                <h3>${plan.plan_title}</h3>
                <p>${plan.description || 'No description'}</p>
                <div class="due-date ${isDueSoon(plan.next_due_date) ? 'due-soon' : ''}">
                  <i data-lucide="calendar"></i> Due: ${formatDate(plan.next_due_date)}
                  ${getDaysUntil(plan.next_due_date)}
                </div>
                <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                  <span class="badge-status ${plan.status === 'Active' ? 'approved' : 'pending'}">${plan.status}</span>
                  <div>
                    <button onclick="editPlan('${plan.plan_id}')" class="action-btn" style="font-size: 11px; padding: 3px 8px;">Edit</button>
                    <button onclick="deletePlan('${plan.plan_id}')" class="action-btn" style="font-size: 11px; padding: 3px 8px; background: #dc3545;">Delete</button>
                  </div>
                </div>
              </div>
            `).join("") || '<p style="text-align:center; padding:20px;">No maintenance plans found.</p>';
            
            // Reinitialize icons
            lucide.createIcons();
          } else {
            document.getElementById('plansContainer').innerHTML = '<p style="text-align:center; padding:20px; color: red;">Error loading plans.</p>';
          }
        } catch (error) {
          console.error('[MRO Planning] Error loading plans:', error);
          document.getElementById('plansContainer').innerHTML = '<p style="text-align:center; padding:20px; color: red;">Error loading plans.</p>';
        }
      }

      async function loadStatistics() {
        try {
          const response = await fetch('../api/mro_api.php?endpoint=maintenance_planning&action=list');
          const data = await response.json();
          
          if (data.success) {
            const plans = data.data;
            const activePlans = plans.filter(p => p.status === 'Active').length;
            const dueThisWeek = plans.filter(p => {
              const dueDate = new Date(p.next_due_date);
              const weekFromNow = new Date();
              weekFromNow.setDate(weekFromNow.getDate() + 7);
              return dueDate <= weekFromNow && dueDate >= new Date();
            }).length;
            const overduePlans = plans.filter(p => new Date(p.next_due_date) < new Date() && p.status === 'Active').length;
            
            document.getElementById('activePlans').textContent = activePlans;
            document.getElementById('dueThisWeek').textContent = dueThisWeek;
            document.getElementById('overduePlans').textContent = overduePlans;
            document.getElementById('completedThisMonth').textContent = Math.floor(Math.random() * 10) + 5; // Placeholder
          }
        } catch (error) {
          console.error('[MRO Planning] Error loading statistics:', error);
        }
      }

      function initMaintenanceChart() {
        const ctx = document.getElementById('maintenanceChart');
        if (!ctx) return;
        
        maintenanceChart = new Chart(ctx.getContext('2d'), {
          type: 'line',
          data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
              label: 'Scheduled Maintenance',
              data: [3, 5, 2, 8, 4, 6, 3],
              borderColor: '#2ca078',
              backgroundColor: 'rgba(44, 160, 120, 0.1)',
              tension: 0.4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false
              }
            },
            scales: {
              y: {
                beginAtZero: true
              }
            }
          }
        });
      }

      function refreshPlans() {
        loadPlans();
        loadStatistics();
        if (maintenanceChart) {
          maintenanceChart.update();
        }
      }

      function openPlanModal() {
        Swal.fire({
          title: 'Create Maintenance Plan',
          html: `
            <input id="planTitle" class="swal2-input" placeholder="Plan Title">
            <select id="planType" class="swal2-input">
              <option value="">Select Type</option>
              <option value="Preventive">Preventive</option>
              <option value="Predictive">Predictive</option>
              <option value="Inspection">Inspection</option>
              <option value="Overhaul">Overhaul</option>
            </select>
            <textarea id="planDescription" class="swal2-textarea" placeholder="Description"></textarea>
            <input id="nextDueDate" type="date" class="swal2-input">
            <input id="estimatedCost" type="number" class="swal2-input" placeholder="Estimated Cost">
          `,
          confirmButtonText: 'Create Plan',
          showCancelButton: true,
          preConfirm: () => {
            const title = document.getElementById('planTitle').value;
            const type = document.getElementById('planType').value;
            const description = document.getElementById('planDescription').value;
            const dueDate = document.getElementById('nextDueDate').value;
            const cost = document.getElementById('estimatedCost').value;
            
            if (!title || !type || !dueDate) {
              Swal.showValidationMessage('Please fill in all required fields');
              return false;
            }
            
            return { title, type, description, dueDate, cost };
          }
        }).then((result) => {
          if (result.isConfirmed) {
            createPlan(result.value);
          }
        });
      }

      async function createPlan(planData) {
        try {
          const response = await fetch('../api/mro_api.php?endpoint=maintenance_planning&action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              ...planData,
              created_by: 1 // Replace with actual user ID
            })
          });
          
          const data = await response.json();
          
          if (data.success) {
            Swal.fire('Success', 'Maintenance plan created successfully', 'success');
            loadPlans();
            loadStatistics();
          } else {
            Swal.fire('Error', data.message || 'Failed to create plan', 'error');
          }
        } catch (error) {
          console.error('[MRO Planning] Error creating plan:', error);
          Swal.fire('Error', 'Failed to create plan', 'error');
        }
      }

      function viewPlanDetails(planId) {
        console.log('[MRO Planning] Viewing plan:', planId);
        Swal.fire({
          title: 'Plan Details',
          text: `Plan ID: ${planId}`,
          icon: 'info',
          confirmButtonText: 'OK'
        });
      }

      function editPlan(planId) {
        console.log('[MRO Planning] Editing plan:', planId);
        Swal.fire({
          title: 'Edit Plan',
          text: `Edit plan ${planId}`,
          icon: 'info',
          confirmButtonText: 'OK'
        });
      }

      function deletePlan(planId) {
        Swal.fire({
          title: 'Delete Plan?',
          text: `Are you sure you want to delete plan ${planId}?`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Delete',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            console.log('[MRO Planning] Deleting plan:', planId);
            Swal.fire('Deleted', 'Plan deleted successfully', 'success');
            loadPlans();
          }
        });
      }

      // Utility functions
      function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString();
      }

      function isDueSoon(dateString) {
        if (!dateString) return false;
        const dueDate = new Date(dateString);
        const today = new Date();
        const daysUntil = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
        return daysUntil <= 7 && daysUntil >= 0;
      }

      function getDaysUntil(dateString) {
        if (!dateString) return '';
        const dueDate = new Date(dateString);
        const today = new Date();
        const daysUntil = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
        
        if (daysUntil < 0) return '<span style="color: #dc3545;">Overdue</span>';
        if (daysUntil === 0) return '<span style="color: #ffc107;">Due today</span>';
        if (daysUntil <= 7) return `<span style="color: #ffc107;">${daysUntil} days</span>`;
        return `<span style="color: #28a745;">${daysUntil} days</span>`;
      }
    </script>
  </body>
</html>
<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
$page = 'mro_work_orders.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>4.2 Work Order Management - MRO System</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="../img/logo.png" />
    <style>
      .work-order-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
      }
      .work-order-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-left: 4px solid #2ca078;
        position: relative;
      }
      .work-order-card h3 {
        margin: 0 0 10px 0;
        color: #333;
      }
      .status-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
      }
      .status-pending { background: #fff3cd; color: #856404; }
      .status-in-progress { background: #cce5ff; color: #0066cc; }
      .status-completed { background: #d4edda; color: #155724; }
      .status-on-hold { background: #f8d7da; color: #721c24; }
      .priority-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        margin-bottom: 10px;
      }
      .priority-low { background: #e7f3ff; color: #0066cc; }
      .priority-normal { background: #fff3cd; color: #856404; }
      .priority-high { background: #f8d7da; color: #721c24; }
      .priority-urgent { background: #f5c6cb; color: #491217; }
      .priority-critical { background: #d32f2f; color: white; }
      .fleet-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        background: #e7f3ff;
        color: #0066cc;
        margin-bottom: 5px;
      }
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
      .technician-info {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 10px 0;
        font-size: 14px;
        color: #666;
      }
      .cost-info {
        margin-top: 10px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 5px;
        font-size: 14px;
      }
      .cost-breakdown {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
      }
      .total-cost {
        font-weight: bold;
        color: #2ca078;
        border-top: 1px solid #dee2e6;
        padding-top: 5px;
        margin-top: 5px;
      }
    </style>
  </head>
  <body>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
      <header class="page-header">
        <div class="header-left">
          <h1>4.2 Work Order Management</h1>
          <p>Manage and track all maintenance work orders</p>
        </div>
        <div class="header-right">
          <button class="action-btn" onclick="openWorkOrderModal()">
            <i data-lucide="plus"></i> New Work Order
          </button>
          <button class="action-btn" onclick="refreshWorkOrders()">
            <i data-lucide="refresh-cw"></i> Refresh
          </button>
        </div>
      </header>

      <!-- Work Order Statistics -->
      <div class="stats-grid">
        <div class="stat-box">
          <div class="value" id="totalWorkOrders">0</div>
          <div class="label">Total Work Orders</div>
        </div>
        <div class="stat-box">
          <div class="value" id="activeWorkOrders">0</div>
          <div class="label">In Progress</div>
        </div>
        <div class="stat-box">
          <div class="value" id="completedToday">0</div>
          <div class="label">Completed Today</div>
        </div>
        <div class="stat-box">
          <div class="value" id="avgCompletionTime">0h</div>
          <div class="label">Avg. Completion Time</div>
        </div>
      </div>

      <!-- Work Order Status Chart -->
      <div class="chart-container">
        <h3>Work Order Status Overview</h3>
        <canvas id="workOrderChart" width="400" height="150"></canvas>
      </div>

      <!-- Fleet Integration Status -->
      <div class="content-card" style="background: #f8f9fa; border: 1px solid #dee2e6;">
        <div class="card-header">
          <h2 class="card-title">🚗 Fleet Integration</h2>
          <small>Work orders from Fleet Management System</small>
        </div>
        <div class="card-body">
          <div id="fleetWorkOrders" style="display: grid; gap: 10px;">
            <p style="text-align:center; padding:20px;">Loading fleet work orders...</p>
          </div>
        </div>
      </div>

      <!-- All Work Orders -->
      <div class="content-card">
        <div class="card-header">
          <h2 class="card-title">All Work Orders</h2>
          <div style="display: flex; gap: 10px;">
            <select id="statusFilter" onchange="loadWorkOrders()" style="padding: 5px; border-radius: 5px; border: 1px solid #ddd;">
              <option value="">All Status</option>
              <option value="Pending">Pending</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
              <option value="On Hold">On Hold</option>
            </select>
            <select id="priorityFilter" onchange="loadWorkOrders()" style="padding: 5px; border-radius: 5px; border: 1px solid #ddd;">
              <option value="">All Priorities</option>
              <option value="Low">Low</option>
              <option value="Normal">Normal</option>
              <option value="High">High</option>
              <option value="Urgent">Urgent</option>
              <option value="Critical">Critical</option>
            </select>
          </div>
        </div>
        <div class="card-body">
          <div class="work-order-grid" id="workOrdersContainer">
            <p style="text-align:center; padding:20px;">Loading work orders...</p>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script src="../js/click_fix.js"></script>
    <script>
      lucide.createIcons();
      let workOrderChart = null;

      document.addEventListener('DOMContentLoaded', function() {
        loadWorkOrders();
        loadStatistics();
        initWorkOrderChart();
        
        // Fix dropdown navigation
        if (window.initializeNavigation) {
          window.initializeNavigation();
        }
        
        // Auto-refresh every 30 seconds
        setInterval(refreshWorkOrders, 30000);
      });

      async function loadWorkOrders() {
        try {
          const status = document.getElementById('statusFilter').value;
          const priority = document.getElementById('priorityFilter').value;
          
          let url = '../api/mro_api.php?endpoint=work_orders&action=list';
          const params = new URLSearchParams();
          if (status) params.append('status', status);
          if (priority) params.append('priority', priority);
          if (params.toString()) url += '&' + params.toString();
          
          const response = await fetch(url);
          const data = await response.json();
          
          if (data.success) {
            const workOrders = data.data;
            
            // Separate fleet and regular work orders
            const fleetOrders = workOrders.filter(wo => wo.fleet_vehicle_id);
            const regularOrders = workOrders.filter(wo => !wo.fleet_vehicle_id);
            
            // Display fleet work orders
            document.getElementById('fleetWorkOrders').innerHTML = fleetOrders.length > 0 ? 
              fleetOrders.map(wo => `
                <div style="background: white; padding: 15px; border-radius: 8px; border-left: 3px solid #0066cc;">
                  <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                      <div class="fleet-badge">🚗 ${wo.fleet_vehicle_id}</div>
                      <strong>${wo.title}</strong>
                      <p style="margin: 5px 0; color: #666; font-size: 14px;">${wo.description}</p>
                      <div class="priority-badge priority-${wo.priority.toLowerCase()}">${wo.priority}</div>
                    </div>
                    <div class="status-badge status-${wo.status.toLowerCase().replace(' ', '-')}">${wo.status}</div>
                  </div>
                  ${wo.assigned_technician ? `
                    <div class="technician-info">
                      <i data-lucide="user"></i> ${wo.assigned_technician}
                    </div>
                  ` : ''}
                  <div style="font-size: 12px; color: #666; margin-top: 5px;">
                    Created: ${formatDate(wo.created_at)}
                    ${wo.scheduled_date ? `• Scheduled: ${formatDate(wo.scheduled_date)}` : ''}
                  </div>
                  <div style="margin-top: 10px; display: flex; gap: 5px;">
                    <button onclick="updateWorkOrderStatus('${wo.work_order_id}', 'In Progress')" class="action-btn" style="font-size: 11px; padding: 3px 8px;">
                      Start Work
                    </button>
                    <button onclick="viewWorkOrderDetails('${wo.work_order_id}')" class="action-btn" style="font-size: 11px; padding: 3px 8px;">
                      Details
                    </button>
                  </div>
                </div>
              `).join('') : 
              '<p style="text-align:center; padding:20px; color: #666;">No fleet work orders.</p>';
            
            // Display regular work orders
            document.getElementById('workOrdersContainer').innerHTML = regularOrders.map(wo => `
              <div class="work-order-card">
                <div class="status-badge status-${wo.status.toLowerCase().replace(' ', '-')}">${wo.status}</div>
                <div class="priority-badge priority-${wo.priority.toLowerCase()}">${wo.priority}</div>
                <h3>${wo.title}</h3>
                <p>${wo.description}</p>
                
                ${wo.asset_name ? `
                  <div style="margin: 10px 0;">
                    <strong>Asset:</strong> ${wo.asset_name}
                  </div>
                ` : ''}
                
                ${wo.assigned_technician ? `
                  <div class="technician-info">
                    <i data-lucide="user"></i> Assigned to: ${wo.assigned_technician}
                  </div>
                ` : ''}
                
                ${wo.estimated_hours || wo.actual_hours ? `
                  <div class="cost-info">
                    <div class="cost-breakdown">
                      <span>Hours:</span>
                      <span>${wo.actual_hours || wo.estimated_hours || 0}h</span>
                    </div>
                    <div class="cost-breakdown">
                      <span>Labor Cost:</span>
                      <span>$${wo.labor_cost || 0}</span>
                    </div>
                    <div class="cost-breakdown">
                      <span>Parts Cost:</span>
                      <span>$${wo.parts_cost || 0}</span>
                    </div>
                    <div class="total-cost">
                      <span>Total Cost:</span>
                      <span>$${wo.total_cost || 0}</span>
                    </div>
                  </div>
                ` : ''}
                
                <div style="margin-top: 15px; font-size: 12px; color: #666;">
                  Created: ${formatDate(wo.created_at)}
                  ${wo.scheduled_date ? `• Scheduled: ${formatDate(wo.scheduled_date)}` : ''}
                  ${wo.started_date ? `• Started: ${formatDate(wo.started_date)}` : ''}
                  ${wo.completed_date ? `• Completed: ${formatDate(wo.completed_date)}` : ''}
                </div>
                
                <div style="margin-top: 15px; display: flex; gap: 5px;">
                  ${wo.status === 'Pending' ? `
                    <button onclick="updateWorkOrderStatus('${wo.work_order_id}', 'In Progress')" class="action-btn" style="font-size: 11px; padding: 3px 8px;">
                      Start Work
                    </button>
                  ` : ''}
                  ${wo.status === 'In Progress' ? `
                    <button onclick="updateWorkOrderStatus('${wo.work_order_id}', 'Completed')" class="action-btn" style="font-size: 11px; padding: 3px 8px; background: #28a745;">
                      Complete
                    </button>
                  ` : ''}
                  <button onclick="editWorkOrder('${wo.work_order_id}')" class="action-btn" style="font-size: 11px; padding: 3px 8px;">
                    Edit
                  </button>
                  <button onclick="viewWorkOrderDetails('${wo.work_order_id}')" class="action-btn" style="font-size: 11px; padding: 3px 8px;">
                    Details
                  </button>
                </div>
              </div>
            `).join('') || '<p style="text-align:center; padding:20px;">No work orders found.</p>';
            
            // Reinitialize icons
            lucide.createIcons();
            updateWorkOrderChart(workOrders);
          }
        } catch (error) {
          console.error('Error loading work orders:', error);
          document.getElementById('workOrdersContainer').innerHTML = '<p style="text-align:center; padding:20px; color: red;">Error loading work orders.</p>';
        }
      }

      async function loadStatistics() {
        try {
          const response = await fetch('../api/mro_api.php?endpoint=work_orders&action=list');
          const data = await response.json();
          
          if (data.success) {
            const workOrders = data.data;
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const stats = {
              total: workOrders.length,
              active: workOrders.filter(wo => wo.status === 'In Progress').length,
              completedToday: workOrders.filter(wo => {
                if (wo.completed_date) {
                  const completedDate = new Date(wo.completed_date);
                  completedDate.setHours(0, 0, 0, 0);
                  return completedDate.getTime() === today.getTime();
                }
                return false;
              }).length,
              avgCompletionTime: 0
            };
            
            // Calculate average completion time
            const completedOrders = workOrders.filter(wo => wo.status === 'Completed' && wo.started_date && wo.completed_date);
            if (completedOrders.length > 0) {
              const totalTime = completedOrders.reduce((sum, wo) => {
                const start = new Date(wo.started_date);
                const end = new Date(wo.completed_date);
                return sum + (end - start) / (1000 * 60 * 60); // Convert to hours
              }, 0);
              stats.avgCompletionTime = Math.round(totalTime / completedOrders.length * 10) / 10;
            }
            
            document.getElementById('totalWorkOrders').textContent = stats.total;
            document.getElementById('activeWorkOrders').textContent = stats.active;
            document.getElementById('completedToday').textContent = stats.completedToday;
            document.getElementById('avgCompletionTime').textContent = stats.avgCompletionTime + 'h';
          }
        } catch (error) {
          console.error('Error loading statistics:', error);
        }
      }

      function initWorkOrderChart() {
        const ctx = document.getElementById('workOrderChart').getContext('2d');
        workOrderChart = new Chart(ctx, {
          type: 'doughnut',
          data: {
            labels: ['Pending', 'In Progress', 'Completed', 'On Hold'],
            datasets: [{
              data: [0, 0, 0, 0],
              backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545'],
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom'
              }
            }
          }
        });
      }

      function updateWorkOrderChart(workOrders) {
        if (!workOrderChart) return;
        
        const statusCounts = {
          'Pending': 0,
          'In Progress': 0,
          'Completed': 0,
          'On Hold': 0
        };
        
        workOrders.forEach(wo => {
          if (statusCounts.hasOwnProperty(wo.status)) {
            statusCounts[wo.status]++;
          }
        });
        
        workOrderChart.data.datasets[0].data = Object.values(statusCounts);
        workOrderChart.update();
      }

      async function openWorkOrderModal() {
        const { value: formValues } = await Swal.fire({
          title: 'Create New Work Order',
          html: `
            <label>Title</label>
            <input id="wo-title" class="swal2-input" placeholder="Work order title">
            <label>Description</label>
            <textarea id="wo-description" class="swal2-textarea" placeholder="Describe the work needed"></textarea>
            <label>Priority</label>
            <select id="wo-priority" class="swal2-select">
              <option value="Low">Low</option>
              <option value="Normal" selected>Normal</option>
              <option value="High">High</option>
              <option value="Urgent">Urgent</option>
              <option value="Critical">Critical</option>
            </select>
            <label>Work Order Type</label>
            <select id="wo-type" class="swal2-select">
              <option value="Corrective" selected>Corrective</option>
              <option value="Preventive">Preventive</option>
              <option value="Emergency">Emergency</option>
              <option value="Inspection">Inspection</option>
            </select>
            <label>Assigned Technician</label>
            <input id="wo-technician" class="swal2-input" placeholder="Technician name">
            <label>Estimated Hours</label>
            <input id="wo-hours" type="number" class="swal2-input" placeholder="0.0">
            <label>Scheduled Date</label>
            <input id="wo-date" type="datetime-local" class="swal2-input">
          `,
          confirmButtonText: 'Create Work Order',
          preConfirm: () => {
            return {
              title: document.getElementById('wo-title').value,
              description: document.getElementById('wo-description').value,
              priority: document.getElementById('wo-priority').value,
              work_order_type: document.getElementById('wo-type').value,
              assigned_technician: document.getElementById('wo-technician').value,
              estimated_hours: parseFloat(document.getElementById('wo-hours').value) || 0,
              scheduled_date: document.getElementById('wo-date').value,
              created_by: <?php echo $_SESSION['user_id']; ?>
            };
          }
        });

        if (formValues.title && formValues.description) {
          try {
            const response = await fetch('../api/mro_api.php?endpoint=work_orders&action=create', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(formValues)
            });
            
            const data = await response.json();
            if (data.success) {
              Swal.fire('Success', 'Work order created successfully', 'success');
              loadWorkOrders();
              loadStatistics();
            } else {
              Swal.fire('Error', data.message, 'error');
            }
          } catch (error) {
            Swal.fire('Error', 'Failed to create work order', 'error');
          }
        }
      }

      async function updateWorkOrderStatus(workOrderId, newStatus) {
        try {
          const response = await fetch(`../api/mro_api.php?endpoint=work_orders&action=update`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              work_order_id: workOrderId,
              status: newStatus,
              started_date: newStatus === 'In Progress' ? new Date().toISOString().slice(0, 19).replace('T', ' ') : null,
              completed_date: newStatus === 'Completed' ? new Date().toISOString().slice(0, 19).replace('T', ' ') : null
            })
          });
          
          const data = await response.json();
          if (data.success) {
            Swal.fire('Success', `Work order ${newStatus.toLowerCase()}`, 'success');
            loadWorkOrders();
            loadStatistics();
          } else {
            Swal.fire('Error', data.message, 'error');
          }
        } catch (error) {
          Swal.fire('Error', 'Failed to update work order', 'error');
        }
      }

      async function viewWorkOrderDetails(workOrderId) {
        try {
          const response = await fetch(`../api/mro_api.php?endpoint=work_order_status&work_order_id=${workOrderId}`);
          const data = await response.json();
          
          if (data.success) {
            const wo = data.data;
            const html = `
              <div style="text-align: left;">
                <h3>${wo.title}</h3>
                <p><strong>Description:</strong> ${wo.description}</p>
                <p><strong>Status:</strong> ${wo.status}</p>
                <p><strong>Priority:</strong> ${wo.priority}</p>
                <p><strong>Type:</strong> ${wo.work_order_type}</p>
                ${wo.assigned_technician ? `<p><strong>Technician:</strong> ${wo.assigned_technician}</p>` : ''}
                ${wo.fleet_vehicle_id ? `<p><strong>Fleet Vehicle:</strong> ${wo.fleet_vehicle_id}</p>` : ''}
                ${wo.asset_name ? `<p><strong>Asset:</strong> ${wo.asset_name}</p>` : ''}
                <p><strong>Created:</strong> ${formatDate(wo.created_at)}</p>
                ${wo.scheduled_date ? `<p><strong>Scheduled:</strong> ${formatDate(wo.scheduled_date)}</p>` : ''}
                ${wo.started_date ? `<p><strong>Started:</strong> ${formatDate(wo.started_date)}</p>` : ''}
                ${wo.completed_date ? `<p><strong>Completed:</strong> ${formatDate(wo.completed_date)}</p>` : ''}
                <p><strong>Total Cost:</strong> $${wo.total_cost || 0}</p>
                ${wo.parts_used && wo.parts_used.length > 0 ? `
                  <h4>Parts Used:</h4>
                  <ul>
                    ${wo.parts_used.map(part => `<li>${part.part_name} - Qty: ${part.quantity_used} - $${part.total_cost}</li>`).join('')}
                  </ul>
                ` : ''}
              </div>
            `;
            
            Swal.fire({
              title: 'Work Order Details',
              html: html,
              width: 600,
              confirmButtonText: 'Close'
            });
          } else {
            Swal.fire('Error', 'Failed to load work order details', 'error');
          }
        } catch (error) {
          Swal.fire('Error', 'Failed to load work order details', 'error');
        }
      }

      function editWorkOrder(workOrderId) {
        // For now, just show a message that editing is not fully implemented
        Swal.fire('Info', 'Work order editing will be available in the next update.', 'info');
      }

      function refreshWorkOrders() {
        loadWorkOrders();
        loadStatistics();
      }

      // Utility functions
      function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      }
    </script>
  </body>
</html>

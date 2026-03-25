<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header("Location: ../login.php"); 
    exit(); 
}
$page = 'mro_dashboard.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>MRO System Dashboard - Logistics 1</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="../img/logo.png" />
    <style>
      .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
      }
      .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-left: 4px solid #2ca078;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
      }
      .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
      }
      .stat-card h3 {
        margin: 0 0 10px 0;
        color: #333;
      }
      .stat-card .value {
        font-size: 32px;
        font-weight: bold;
        color: #2ca078;
      }
      .stat-card .change {
        font-size: 12px;
        margin-top: 5px;
      }
      .change.positive { color: #22c55e; }
      .change.negative { color: #ef4444; }
      .fleet-integration {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
      }
      .integration-status {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
      }
      .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #22c55e;
        animation: pulse 2s infinite;
      }
      @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
      }
      .chart-container {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
      }
      .work-order-priority {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
      }
      .priority-low { background: #e7f3ff; color: #0066cc; }
      .priority-normal { background: #fff3cd; color: #856404; }
      .priority-high { background: #f8d7da; color: #721c24; }
      .priority-urgent { background: #f5c6cb; color: #491217; }
      .priority-critical { background: #d32f2f; color: white; }
      .technician-workload {
        display: flex;
        align-items: center;
        gap: 10px;
      }
      .workload-bar {
        flex: 1;
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
      }
      .workload-fill {
        height: 100%;
        background: #2ca078;
        transition: width 0.3s ease;
      }
      .debug-panel {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 20px;
        font-size: 12px;
      }
      .click-test {
        background: #007bff;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        margin: 5px;
      }
      .click-test:hover {
        background: #0056b3;
      }
    </style>
  </head>
  <body>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
      <header class="page-header">
        <div class="header-left">
          <h1>MRO System Dashboard</h1>
          <p>Logistics 1 - Maintenance, Repair & Operations</p>
        </div>
        <div class="header-right">
          <button class="action-btn" onclick="testClick('button1')">
            <i data-lucide="plus"></i> Test Click 1
          </button>
          <button class="action-btn" onclick="testClick('button2')">
            <i data-lucide="refresh-cw"></i> Test Click 2
          </button>
        </div>
      </header>

      <!-- Debug Panel -->
      <div class="debug-panel" id="debugPanel">
        <strong>🔧 Debug Panel:</strong> Click events will appear here
        <div id="debugLog" style="margin-top: 10px; max-height: 100px; overflow-y: auto;"></div>
      </div>

      <!-- Fleet Integration Status -->
      <div class="fleet-integration">
        <div class="integration-status">
          <div class="status-indicator"></div>
          <strong>Fleet Integration Active</strong>
          <span style="color: #666;">Connected to Logistics 2 Fleet System</span>
        </div>
        <div style="display: flex; gap: 20px; font-size: 14px;">
          <div>Last sync: <span id="lastSync">Just now</span></div>
          <div>Active requests: <span id="fleetRequests" style="color: #2ca078; font-weight: bold;">0</span></div>
          <div>API endpoint: <code style="background: #f1f3f4; padding: 2px 6px; border-radius: 3px;">/api/mro_api.php</code></div>
        </div>
      </div>

      <!-- Dashboard Stats -->
      <div class="dashboard-grid">
        <div class="stat-card" onclick="handleStatClick('activeWorkOrders')">
          <h3>Active Work Orders</h3>
          <div class="value" id="activeWorkOrders">0</div>
          <div class="change positive">+12% from last week</div>
        </div>
        <div class="stat-card" onclick="handleStatClick('pendingRequests')">
          <h3>Pending Requests</h3>
          <div class="value" id="pendingRequests">0</div>
          <div class="change negative">+5 from yesterday</div>
        </div>
        <div class="stat-card" onclick="handleStatClick('completedToday')">
          <h3>Completed Today</h3>
          <div class="value" id="completedToday">0</div>
          <div class="change positive">On track</div>
        </div>
        <div class="stat-card" onclick="handleStatClick('availableTechs')">
          <h3>Technicians Available</h3>
          <div class="value" id="availableTechs">0</div>
          <div class="change">3 busy, 1 on leave</div>
        </div>
      </div>

      <!-- Charts and Tables -->
      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        <!-- Work Orders Chart -->
        <div class="chart-container">
          <h3>Work Order Status Overview</h3>
          <canvas id="workOrderChart" width="400" height="200"></canvas>
        </div>

        <!-- Technician Workload -->
        <div class="chart-container">
          <h3>Technician Workload</h3>
          <div id="technicianWorkload"></div>
        </div>
      </div>

      <!-- Recent Work Orders Table -->
      <div class="content-card">
        <div class="card-header">
          <h2 class="card-title">Recent Work Orders</h2>
          <div style="display: flex; gap: 10px;">
            <select id="statusFilter" onchange="loadWorkOrders()" style="padding: 5px; border-radius: 5px; border: 1px solid #ddd;">
              <option value="">All Status</option>
              <option value="Pending">Pending</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
            </select>
          </div>
        </div>
        <div class="card-body">
          <div class="data-table" id="workOrdersTable">
            <p style="text-align:center; padding:20px;">Loading work orders...</p>
          </div>
        </div>
      </div>

      <!-- Fleet Requests -->
      <div class="content-card">
        <div class="card-header">
          <h2 class="card-title">Fleet Integration Requests</h2>
          <small>Maintenance requests from Fleet Management System</small>
        </div>
        <div class="card-body">
          <div class="data-table" id="fleetRequestsTable">
            <p style="text-align:center; padding:20px;">Loading fleet requests...</p>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script src="../js/click_fix.js"></script>
    <script src="../js/navigation_fix.js"></script>
    <script>
      // Debug logging function
      function debugLog(message) {
        const debugLog = document.getElementById('debugLog');
        if (debugLog) {
          const timestamp = new Date().toLocaleTimeString();
          debugLog.innerHTML += `<div>[${timestamp}] ${message}</div>`;
          debugLog.scrollTop = debugLog.scrollHeight;
        }
        console.log('[MRO Debug]', message);
      }

      // Test click function
      function testClick(buttonId) {
        debugLog(`Test button clicked: ${buttonId}`);
        Swal.fire({
          title: 'Click Test Successful!',
          text: `Button ${buttonId} is working correctly`,
          icon: 'success',
          timer: 2000,
          showConfirmButton: false
        });
      }

      // Handle stat card clicks
      function handleStatClick(statType) {
        debugLog(`Stat card clicked: ${statType}`);
        
        const messages = {
          'activeWorkOrders': 'Active Work Orders - Click to view detailed list',
          'pendingRequests': 'Pending Requests - Click to view and manage',
          'completedToday': 'Completed Today - Click to view completed items',
          'availableTechs': 'Available Technicians - Click to view technician list'
        };
        
        Swal.fire({
          title: 'Stat Card Clicked',
          text: messages[statType] || 'Unknown stat type',
          icon: 'info',
          confirmButtonText: 'OK'
        });
      }

      // Initialize dashboard with comprehensive error handling
      document.addEventListener('DOMContentLoaded', function() {
        debugLog('DOM Content Loaded - Initializing dashboard');
        
        try {
          // Initialize Lucide icons
          if (typeof lucide !== 'undefined') {
            lucide.createIcons();
            debugLog('Lucide icons initialized');
          } else {
            debugLog('ERROR: Lucide not loaded');
          }
          
          // Initialize navigation
          if (window.initializeNavigation) {
            window.initializeNavigation();
            debugLog('Navigation initialized');
          } else {
            debugLog('WARNING: Navigation initializer not found');
          }
          
          // Load dashboard data
          loadDashboardStats();
          loadWorkOrders();
          loadFleetRequests();
          loadTechnicianWorkload();
          initWorkOrderChart();
          
          debugLog('Dashboard initialization complete');
          
          // Auto-refresh every 30 seconds
          setInterval(() => {
            debugLog('Auto-refresh triggered');
            refreshDashboard();
          }, 30000);
          
        } catch (error) {
          debugLog('ERROR during initialization: ' + error.message);
          console.error('Dashboard initialization error:', error);
        }
      });

      // Global error handler
      window.addEventListener('error', function(event) {
        debugLog('JavaScript Error: ' + event.error.message);
        console.error('Global error:', event.error);
      });

      // Global unhandled promise rejection handler
      window.addEventListener('unhandledrejection', function(event) {
        debugLog('Unhandled Promise Rejection: ' + event.reason);
        console.error('Unhandled rejection:', event.reason);
      });

      let workOrderChart = null;

      // Enhanced API call function with comprehensive error handling
      async function safeApiCall(url, options = {}) {
        try {
          debugLog(`API Call: ${url}`);
          
          const response = await fetch(url, {
            headers: {
              'Content-Type': 'application/json',
              ...options.headers
            },
            ...options
          });
          
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
          }
          
          const data = await response.json();
          debugLog(`API Success: ${url} - ${data.success ? 'Success' : 'Failed'}`);
          
          if (!data.success) {
            throw new Error(data.message || 'API request failed');
          }
          
          return data;
        } catch (error) {
          debugLog(`API Error: ${url} - ${error.message}`);
          console.error('API call error:', error);
          
          // Show user-friendly error message
          Swal.fire({
            title: 'Connection Error',
            text: 'Unable to connect to the server. Please check your connection.',
            icon: 'error',
            confirmButtonText: 'OK'
          });
          
          throw error;
        }
      }

      async function loadDashboardStats() {
        try {
          debugLog('Loading dashboard statistics');
          
          const response = await safeApiCall('../api/mro_api.php?endpoint=work_orders&action=list');
          const workOrders = response.data || [];
          
          const active = workOrders.filter(wo => wo.status === 'In Progress').length;
          const pending = workOrders.filter(wo => wo.status === 'Pending').length;
          const completed = workOrders.filter(wo => wo.status === 'Completed' && isToday(wo.completed_date)).length;
          
          // Update DOM with error handling
          updateElement('activeWorkOrders', active);
          updateElement('pendingRequests', pending);
          updateElement('completedToday', completed);
          
          // Load technician count
          try {
            const techResponse = await safeApiCall('../api/mro_api.php?endpoint=technicians&action=list');
            const available = techResponse.data.filter(t => t.availability_status === 'Available').length;
            updateElement('availableTechs', available);
          } catch (techError) {
            debugLog('Technician load error: ' + techError.message);
            updateElement('availableTechs', '?');
          }
          
          debugLog('Dashboard statistics loaded successfully');
          
        } catch (error) {
          debugLog('Failed to load dashboard stats: ' + error.message);
          // Set default values
          updateElement('activeWorkOrders', '0');
          updateElement('pendingRequests', '0');
          updateElement('completedToday', '0');
          updateElement('availableTechs', '0');
        }
      }

      function updateElement(id, value) {
        const element = document.getElementById(id);
        if (element) {
          element.textContent = value;
        } else {
          debugLog(`WARNING: Element #${id} not found`);
        }
      }

      async function loadWorkOrders() {
        try {
          debugLog('Loading work orders');
          
          const status = document.getElementById('statusFilter')?.value || '';
          let url = '../api/mro_api.php?endpoint=work_orders&action=list';
          if (status) url += `&status=${status}`;
          
          const response = await safeApiCall(url);
          const workOrders = response.data || [];
          
          const container = document.getElementById('workOrdersTable');
          if (!container) {
            debugLog('ERROR: workOrdersTable element not found');
            return;
          }
          
          if (workOrders.length === 0) {
            container.innerHTML = '<p style="text-align:center; padding:20px;">No work orders found.</p>';
            return;
          }
          
          container.innerHTML = workOrders.map(wo => `
            <div class="table-row" onclick="handleWorkOrderClick('${wo.work_order_id}')">
              <div class="client-info">
                <strong>${wo.title}</strong><br>
                <small>${wo.description}</small>
                ${wo.fleet_vehicle_id ? `<br><small style="color: #2ca078;">🚗 Fleet: ${wo.fleet_vehicle_id}</small>` : ''}
              </div>
              <div>
                <span class="work-order-priority priority-${wo.priority.toLowerCase()}">${wo.priority}</span>
              </div>
              <div><span class="badge-status ${getStatusClass(wo.status)}">${wo.status}</span></div>
              <div style="text-align:right;">
                <small>${formatDate(wo.created_at)}</small><br>
                ${wo.assigned_technician ? `<small>👨‍🔧 ${wo.assigned_technician}</small>` : ''}
              </div>
            </div>
          `).join('');
          
          updateWorkOrderChart(workOrders);
          debugLog('Work orders loaded successfully');
          
        } catch (error) {
          debugLog('Failed to load work orders: ' + error.message);
          const container = document.getElementById('workOrdersTable');
          if (container) {
            container.innerHTML = '<p style="text-align:center; padding:20px; color: red;">Error loading work orders.</p>';
          }
        }
      }

      function handleWorkOrderClick(workOrderId) {
        debugLog(`Work order clicked: ${workOrderId}`);
        Swal.fire({
          title: 'Work Order Details',
          text: `Work Order ID: ${workOrderId}`,
          icon: 'info',
          confirmButtonText: 'View Details',
          showCancelButton: true,
          cancelButtonText: 'Close'
        }).then((result) => {
          if (result.isConfirmed) {
            // Navigate to work order details
            window.location.href = `mro_work_orders.php?wo=${workOrderId}`;
          }
        });
      }

      async function loadFleetRequests() {
        try {
          debugLog('Loading fleet requests');
          
          const response = await safeApiCall('../api/mro_api.php?endpoint=work_orders&action=list');
          const fleetRequests = response.data.filter(wo => wo.fleet_vehicle_id) || [];
          
          document.getElementById('fleetRequests').textContent = fleetRequests.length;
          
          const container = document.getElementById('fleetRequestsTable');
          if (!container) return;
          
          if (fleetRequests.length === 0) {
            container.innerHTML = '<p style="text-align:center; padding:20px;">No fleet requests.</p>';
            return;
          }
          
          container.innerHTML = fleetRequests.slice(0, 5).map(wo => `
            <div class="table-row" onclick="handleFleetRequestClick('${wo.work_order_id}')">
              <div class="client-info">
                <strong>🚗 ${wo.fleet_vehicle_id}</strong><br>
                <small>${wo.description}</small>
              </div>
              <div>
                <span class="work-order-priority priority-${wo.priority.toLowerCase()}">${wo.priority}</span>
              </div>
              <div><span class="badge-status ${getStatusClass(wo.status)}">${wo.status}</span></div>
              <div style="text-align:right;">
                <small>${formatDate(wo.created_at)}</small>
              </div>
            </div>
          `).join('');
          
          debugLog('Fleet requests loaded successfully');
          
        } catch (error) {
          debugLog('Failed to load fleet requests: ' + error.message);
        }
      }

      function handleFleetRequestClick(workOrderId) {
        debugLog(`Fleet request clicked: ${workOrderId}`);
        Swal.fire({
          title: 'Fleet Request',
          text: `Fleet Work Order: ${workOrderId}`,
          icon: 'info',
          confirmButtonText: 'Manage Request'
        });
      }

      async function loadTechnicianWorkload() {
        try {
          debugLog('Loading technician workload');
          
          const response = await safeApiCall('../api/mro_api.php?endpoint=technicians&action=workload');
          const workload = response.data || [];
          
          const container = document.getElementById('technicianWorkload');
          if (!container) return;
          
          if (workload.length === 0) {
            container.innerHTML = '<p style="text-align:center;">No technician data available</p>';
            return;
          }
          
          container.innerHTML = workload.slice(0, 5).map(tech => `
            <div style="margin-bottom: 15px;">
              <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <strong>${tech.name}</strong>
                <span style="font-size: 12px; color: #666;">${tech.active_work_orders} active</span>
              </div>
              <div class="technician-workload">
                <div class="workload-bar">
                  <div class="workload-fill" style="width: ${(tech.active_work_orders / tech.max_work_orders) * 100}%"></div>
                </div>
                <span style="font-size: 12px; color: #666;">${Math.round((tech.active_work_orders / tech.max_work_orders) * 100)}%</span>
              </div>
            </div>
          `).join('');
          
          debugLog('Technician workload loaded successfully');
          
        } catch (error) {
          debugLog('Failed to load technician workload: ' + error.message);
        }
      }

      function initWorkOrderChart() {
        try {
          debugLog('Initializing work order chart');
          
          const ctx = document.getElementById('workOrderChart');
          if (!ctx) {
            debugLog('ERROR: workOrderChart canvas not found');
            return;
          }
          
          workOrderChart = new Chart(ctx.getContext('2d'), {
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
          
          debugLog('Work order chart initialized');
          
        } catch (error) {
          debugLog('Failed to initialize chart: ' + error.message);
        }
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

      function refreshDashboard() {
        debugLog('Refreshing dashboard');
        document.getElementById('lastSync').textContent = 'Just now';
        loadDashboardStats();
        loadWorkOrders();
        loadFleetRequests();
        loadTechnicianWorkload();
      }

      // Utility functions
      function getStatusClass(status) {
        const statusClasses = {
          'Pending': 'pending',
          'In Progress': 'processing',
          'Completed': 'approved',
          'On Hold': 'pending',
          'Cancelled': 'rejected'
        };
        return statusClasses[status] || 'pending';
      }

      function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      }

      function isToday(dateString) {
        if (!dateString) return false;
        const date = new Date(dateString);
        const today = new Date();
        return date.toDateString() === today.toDateString();
      }
    </script>
  </body>
</html>

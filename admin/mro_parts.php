<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
$page = 'mro_parts.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>4.3 Spare Parts and Supplies - MRO System</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="../img/logo.png" />
    <style>
      .parts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
      }
      .part-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-left: 4px solid #2ca078;
      }
      .part-card h3 {
        margin: 0 0 10px 0;
        color: #333;
      }
      .stock-status {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        margin-bottom: 10px;
      }
      .stock-in-stock { background: #d4edda; color: #155724; }
      .stock-low { background: #fff3cd; color: #856404; }
      .stock-out { background: #f8d7da; color: #721c24; }
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
      .usage-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
      }
      .usage-table th,
      .usage-table td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
      }
      .usage-table th {
        background: #f8f9fa;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
      }
      .reorder-alert {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
      }
    </style>
  </head>
  <body>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
      <header class="page-header">
        <div class="header-left">
          <h1>4.3 Spare Parts and Supplies</h1>
          <p>Manage inventory and track parts usage for maintenance</p>
        </div>
        <div class="header-right">
          <button class="action-btn" onclick="openPartsUsageModal()">
            <i data-lucide="plus"></i> Record Usage
          </button>
          <button class="action-btn" onclick="refreshParts()">
            <i data-lucide="refresh-cw"></i> Refresh
          </button>
        </div>
      </header>

      <!-- Parts Statistics -->
      <div class="stats-grid">
        <div class="stat-box">
          <div class="value" id="totalParts">0</div>
          <div class="label">Total Parts</div>
        </div>
        <div class="stat-box">
          <div class="value" id="lowStock">0</div>
          <div class="label">Low Stock</div>
        </div>
        <div class="stat-box">
          <div class="value" id="outOfStock">0</div>
          <div class="label">Out of Stock</div>
        </div>
        <div class="stat-box">
          <div class="value" id="totalValue">$0</div>
          <div class="label">Total Value</div>
        </div>
      </div>

      <!-- Parts Usage Chart -->
      <div class="chart-container">
        <h3>Parts Usage Overview</h3>
        <canvas id="partsUsageChart" width="400" height="150"></canvas>
      </div>

      <!-- Parts Inventory -->
      <div class="content-card">
        <div class="card-header">
          <h2 class="card-title">Parts Inventory</h2>
          <div style="display: flex; gap: 10px;">
            <select id="stockFilter" onchange="loadInventory()" style="padding: 5px; border-radius: 5px; border: 1px solid #ddd;">
              <option value="">All Stock Levels</option>
              <option value="in-stock">In Stock</option>
              <option value="low">Low Stock</option>
              <option value="out">Out of Stock</option>
            </select>
            <input type="text" id="searchParts" placeholder="Search parts..." onkeyup="loadInventory()" style="padding: 5px; border-radius: 5px; border: 1px solid #ddd;">
          </div>
        </div>
        <div class="card-body">
          <div class="parts-grid" id="partsContainer">
            <p style="text-align:center; padding:20px;">Loading parts inventory...</p>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script src="../js/click_fix.js"></script>
    <script>
      lucide.createIcons();

      document.addEventListener('DOMContentLoaded', function() {
        loadInventory();
        loadStatistics();
        
        // Fix dropdown navigation
        if (window.initializeNavigation) {
          window.initializeNavigation();
        }
        
        // Auto-refresh every 30 seconds
        setInterval(refreshParts, 30000);
      });

      async function loadInventory() {
        try {
          const stockFilter = document.getElementById('stockFilter').value;
          const searchTerm = document.getElementById('searchParts').value;
          
          // Load from inventory system
          const response = await fetch('../api/inventory.php?action=list');
          const data = await response.json();
          
          if (data.success) {
            let inventory = data.data || [];
            
            // Apply filters
            if (searchTerm) {
              inventory = inventory.filter(item => 
                item.item_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                (item.sku && item.sku.toLowerCase().includes(searchTerm.toLowerCase()))
              );
            }
            
            if (stockFilter) {
              inventory = inventory.filter(item => {
                const quantity = parseInt(item.quantity) || 0;
                switch(stockFilter) {
                  case 'in-stock': return quantity > 10;
                  case 'low': return quantity > 0 && quantity <= 10;
                  case 'out': return quantity === 0;
                  default: return true;
                }
              });
            }
            
            // Calculate stock status
            inventory = inventory.map(item => {
              const quantity = parseInt(item.quantity) || 0;
              let stockStatus = 'in-stock';
              if (quantity === 0) stockStatus = 'out';
              else if (quantity <= 10) stockStatus = 'low';
              
              return { ...item, stockStatus };
            });
            
            document.getElementById('partsContainer').innerHTML = inventory.map(part => `
              <div class="part-card">
                <div class="stock-status stock-${part.stockStatus}">
                  ${part.stockStatus === 'out' ? '🔴 Out of Stock' : 
                    part.stockStatus === 'low' ? '🟡 Low Stock' : '🟢 In Stock'}
                </div>
                <h3>${part.item_name}</h3>
                ${part.sku ? `<p><strong>SKU:</strong> ${part.sku}</p>` : ''}
                <div style="margin: 10px 0;">
                  <strong>Quantity:</strong> ${part.quantity} ${part.unit || 'pcs'}
                </div>
                <div style="margin: 5px 0;">
                  <strong>Unit Price:</strong> $${part.unit_price || 0}
                </div>
                <div style="margin: 5px 0;">
                  <strong>Total Value:</strong> $${((part.quantity || 0) * (part.unit_price || 0)).toFixed(2)}
                </div>
                <div style="margin-top: 15px; display: flex; gap: 5px;">
                  <button onclick="recordUsage('${part.id}', '${part.item_name}', '${part.unit_price || 0}')" class="action-btn" style="font-size: 11px; padding: 3px 8px;">
                    Use Part
                  </button>
                  ${part.stockStatus !== 'in-stock' ? `
                    <button onclick="requestReorder('${part.id}', '${part.item_name}')" class="action-btn" style="font-size: 11px; padding: 3px 8px; background: #ffc107;">
                      Reorder
                    </button>
                  ` : ''}
                </div>
              </div>
            `).join('') || '<p style="text-align:center; padding:20px;">No parts found.</p>';
            
            // Reinitialize icons
            lucide.createIcons();
          }
        } catch (error) {
          console.error('[MRO Parts] Error loading inventory:', error);
          document.getElementById('partsContainer').innerHTML = '<p style="text-align:center; padding:20px; color: red;">Error loading inventory.</p>';
        }
      }

      async function loadStatistics() {
        try {
          const response = await fetch('../api/inventory.php?action=statistics');
          const data = await response.json();
          
          if (data.success) {
            const stats = data.data;
            document.getElementById('totalParts').textContent = stats.total_parts || 0;
            document.getElementById('lowStock').textContent = stats.low_stock || 0;
            document.getElementById('outOfStock').textContent = stats.out_of_stock || 0;
            document.getElementById('totalValue').textContent = '$' + (stats.total_value || 0).toFixed(2);
          }
        } catch (error) {
          console.error('[MRO Parts] Error loading statistics:', error);
        }
      }

      function initPartsUsageChart() {
        const ctx = document.getElementById('partsUsageChart');
        if (!ctx) return;
        
        new Chart(ctx.getContext('2d'), {
          type: 'bar',
          data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
              label: 'Parts Used',
              data: [12, 19, 8, 15, 22, 18, 14],
              backgroundColor: '#2ca078'
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

      function refreshParts() {
        loadInventory();
        loadStatistics();
      }

      function openPartsUsageModal() {
        Swal.fire({
          title: 'Record Parts Usage',
          html: `
            <select id="partSelect" class="swal2-input">
              <option value="">Select Part</option>
            </select>
            <input id="workOrder" class="swal2-input" placeholder="Work Order ID">
            <input id="quantity" type="number" class="swal2-input" placeholder="Quantity Used">
            <textarea id="notes" class="swal2-textarea" placeholder="Notes"></textarea>
          `,
          confirmButtonText: 'Record Usage',
          showCancelButton: true,
          preConfirm: () => {
            const part = document.getElementById('partSelect').value;
            const workOrder = document.getElementById('workOrder').value;
            const quantity = document.getElementById('quantity').value;
            
            if (!part || !workOrder || !quantity) {
              Swal.showValidationMessage('Please fill in all required fields');
              return false;
            }
            
            return { part, workOrder, quantity };
          }
        }).then((result) => {
          if (result.isConfirmed) {
            console.log('[MRO Parts] Recording usage:', result.value);
            Swal.fire('Success', 'Usage recorded successfully', 'success');
            loadInventory();
          }
        });
      }

      function recordUsage(partId, partName, unitPrice) {
        Swal.fire({
          title: 'Record Parts Usage',
          html: `
            <p style="margin:0 0 10px;"><strong>Part:</strong> ${partName || 'Selected Part'}</p>
            <input id="workOrderId" class="swal2-input" placeholder="Work Order ID">
            <input id="quantity" type="number" class="swal2-input" placeholder="Quantity Used" value="1">
            <textarea id="notes" class="swal2-textarea" placeholder="Usage notes"></textarea>
          `,
          confirmButtonText: 'Record Usage',
          showCancelButton: true,
          preConfirm: () => {
            const workOrderId = document.getElementById('workOrderId').value;
            const quantity = document.getElementById('quantity').value;
            
            if (!workOrderId || !quantity) {
              Swal.showValidationMessage('Please fill in all required fields');
              return false;
            }
            
            return { workOrderId, quantity, notes: document.getElementById('notes').value };
          }
        }).then(async (result) => {
          if (result.isConfirmed) {
            try {
              const response = await fetch('../api/mro_api.php?endpoint=parts_management&action=add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                  part_id: partId,
                  work_order_id: result.value.workOrderId,
                  quantity: result.value.quantity,
                  notes: result.value.notes
                })
              });
              const data = await response.json();
              if (data.success) {
                Swal.fire('Success', 'Parts usage recorded successfully', 'success');
                loadInventory();
                loadStatistics();
              } else {
                Swal.fire('Error', data.message || 'Failed to record usage', 'error');
              }
            } catch (error) {
              console.error('[MRO Parts] Error recording usage:', error);
              Swal.fire('Error', 'Failed to record usage', 'error');
            }
          }
        });
      }

      function requestReorder(partId, partName) {
        Swal.fire({
          title: 'Request Reorder',
          html: `
            <input id="reorderQuantity" type="number" class="swal2-input" placeholder="Reorder Quantity">
            <textarea id="reorderNotes" class="swal2-textarea" placeholder="Reorder notes"></textarea>
          `,
          confirmButtonText: 'Request Reorder',
          showCancelButton: true,
          preConfirm: () => {
            const quantity = document.getElementById('reorderQuantity').value;
            const notes = document.getElementById('reorderNotes').value;
            
            if (!quantity) {
              Swal.showValidationMessage('Please enter reorder quantity');
              return false;
            }
            
            return { quantity, notes };
          }
        }).then((result) => {
          if (result.isConfirmed) {
            console.log('[MRO Parts] Requesting reorder:', partId, result.value);
            Swal.fire('Success', 'Reorder requested successfully', 'success');
          }
        });
      }
    </script>
  </body>
</html>
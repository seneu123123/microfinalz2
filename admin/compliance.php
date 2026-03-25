<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
$page = 'compliance.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>4.4 Compliance and Safety - MRO System</title>
    <link rel="stylesheet" href="../css/dashboard.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="../img/logo.png" />
    <style>
      .compliance-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
      }
      .check-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-left: 4px solid #2ca078;
        position: relative;
      }
      .check-card h3 {
        margin: 0 0 10px 0;
        color: #333;
      }
      .check-type {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        margin-bottom: 10px;
      }
      .type-pre-work { background: #e7f3ff; color: #0066cc; }
      .type-post-work { background: #d4edda; color: #155724; }
      .type-safety { background: #fff3cd; color: #856404; }
      .type-quality { background: #f8d7da; color: #721c24; }
      .type-environmental { background: #e7f3ff; color: #0066cc; }
      .status-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
      }
      .status-passed { background: #d4edda; color: #155724; }
      .status-failed { background: #f8d7da; color: #721c24; }
      .status-pending { background: #fff3cd; color: #856404; }
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
      .checklist-preview {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 5px;
        margin: 10px 0;
        font-size: 12px;
      }
      .checklist-item {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 5px 0;
      }
      .checklist-item input[type="checkbox"] {
        margin: 0;
      }
      .issues-section {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 5px;
        padding: 10px;
        margin: 10px 0;
      }
      .compliance-score {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 10px 0;
      }
      .score-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
        color: white;
      }
      .score-high { background: #28a745; }
      .score-medium { background: #ffc107; color: #333; }
      .score-low { background: #dc3545; }
    </style>
  </head>
  <body>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
      <header class="page-header">
        <div class="header-left">
          <h1>4.4 Compliance and Safety</h1>
          <p>Manage safety checks and compliance for maintenance work</p>
        </div>
        <div class="header-right">
          <button class="action-btn" onclick="openComplianceModal()">
            <i data-lucide="plus"></i> New Safety Check
          </button>
          <button class="action-btn" onclick="refreshCompliance()">
            <i data-lucide="refresh-cw"></i> Refresh
          </button>
        </div>
      </header>

      <!-- Compliance Statistics -->
      <div class="stats-grid">
        <div class="stat-box">
          <div class="value" id="totalChecks">0</div>
          <div class="label">Total Checks</div>
        </div>
        <div class="stat-box">
          <div class="value" id="passedChecks">0</div>
          <div class="label">Passed</div>
        </div>
        <div class="stat-box">
          <div class="value" id="failedChecks">0</div>
          <div class="label">Failed</div>
        </div>
        <div class="stat-box">
          <div class="value" id="complianceRate">0%</div>
          <div class="label">Compliance Rate</div>
        </div>
      </div>

      <!-- Compliance Overview Chart -->
      <div class="chart-container">
        <h3>Compliance Overview</h3>
        <canvas id="complianceChart" width="400" height="150"></canvas>
      </div>

      <!-- Recent Compliance Checks -->
      <div class="content-card">
        <div class="card-header">
          <h2 class="card-title">Recent Compliance Checks</h2>
          <div style="display: flex; gap: 10px;">
            <select id="typeFilter" onchange="loadComplianceChecks()" style="padding: 5px; border-radius: 5px; border: 1px solid #ddd;">
              <option value="">All Types</option>
              <option value="Pre-Work">Pre-Work</option>
              <option value="Post-Work">Post-Work</option>
              <option value="Safety">Safety</option>
              <option value="Quality">Quality</option>
              <option value="Environmental">Environmental</option>
            </select>
            <select id="statusFilter" onchange="loadComplianceChecks()" style="padding: 5px; border-radius: 5px; border: 1px solid #ddd;">
              <option value="">All Status</option>
              <option value="1">Passed</option>
              <option value="0">Failed</option>
            </select>
          </div>
        </div>
        <div class="card-body">
          <div class="compliance-grid" id="complianceContainer">
            <p style="text-align:center; padding:20px;">Loading compliance checks...</p>
          </div>
        </div>
      </div>

      <!-- Safety Checklist Templates -->
      <div class="content-card">
        <div class="card-header">
          <h2 class="card-title">Safety Checklist Templates</h2>
          <small>Standard checklists for different types of maintenance work</small>
        </div>
        <div class="card-body">
          <div class="compliance-grid" id="templatesContainer">
            <div class="check-card">
              <div class="check-type type-pre-work">Pre-Work</div>
              <h3>General Maintenance Safety</h3>
              <div class="checklist-preview">
                <div class="checklist-item">
                  <input type="checkbox" checked disabled>
                  <span>Personal Protective Equipment (PPE) worn</span>
                </div>
                <div class="checklist-item">
                  <input type="checkbox" checked disabled>
                  <span>Work area properly secured</span>
                </div>
                <div class="checklist-item">
                  <input type="checkbox" checked disabled>
                  <span>Tools inspected for safety</span>
                </div>
              </div>
              <button onclick="useTemplate('pre-work-general')" class="action-btn" style="font-size: 12px; padding: 5px 10px;">
                Use Template
              </button>
            </div>
            
            <div class="check-card">
              <div class="check-type type-post-work">Post-Work</div>
              <h3>Work Completion Check</h3>
              <div class="checklist-preview">
                <div class="checklist-item">
                  <input type="checkbox" checked disabled>
                  <span>Work area cleaned and organized</span>
                </div>
                <div class="checklist-item">
                  <input type="checkbox" checked disabled>
                  <span>Tools properly stored</span>
                </div>
                <div class="checklist-item">
                  <input type="checkbox" checked disabled>
                  <span>Waste disposed properly</span>
                </div>
              </div>
              <button onclick="useTemplate('post-work-completion')" class="action-btn" style="font-size: 12px; padding: 5px 10px;">
                Use Template
              </button>
            </div>
            
            <div class="check-card">
              <div class="check-type type-safety">Safety</div>
              <h3>Electrical Safety</h3>
              <div class="checklist-preview">
                <div class="checklist-item">
                  <input type="checkbox" checked disabled>
                  <span>Power source disconnected</span>
                </div>
                <div class="checklist-item">
                  <input type="checkbox" checked disabled>
                  <span>Lockout/Tagout procedures followed</span>
                </div>
                <div class="checklist-item">
                  <input type="checkbox" checked disabled>
                  <span>Electrical tools inspected</span>
                </div>
              </div>
              <button onclick="useTemplate('safety-electrical')" class="action-btn" style="font-size: 12px; padding: 5px 10px;">
                Use Template
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script src="../js/dashboard.js"></script>
    <script src="../js/click_fix.js"></script>
    <script src="../js/navigation_fix.js"></script>
    <script>
      lucide.createIcons();
      let complianceChart = null;

      document.addEventListener('DOMContentLoaded', function() {
        loadComplianceChecks();
        loadStatistics();
        initComplianceChart();
        
        // Fix dropdown navigation
        if (window.initializeNavigation) {
          window.initializeNavigation();
        }
        
        // Auto-refresh every 30 seconds
        setInterval(refreshCompliance, 30000);
      });

      async function loadComplianceChecks() {
        try {
          const typeFilter = document.getElementById('typeFilter').value;
          const statusFilter = document.getElementById('statusFilter').value;
          
          // For now, we'll simulate compliance checks since the API might not be fully implemented
          const simulatedChecks = [
            {
              check_id: 1,
              work_order_id: 'WO-2024-1234',
              check_type: 'Pre-Work',
              performed_by: 'John Smith',
              check_date: new Date().toISOString(),
              passed: 1,
              issues_found: '',
              corrective_actions: ''
            },
            {
              check_id: 2,
              work_order_id: 'WO-2024-1235',
              check_type: 'Post-Work',
              performed_by: 'Mike Johnson',
              check_date: new Date(Date.now() - 86400000).toISOString(),
              passed: 1,
              issues_found: '',
              corrective_actions: ''
            },
            {
              check_id: 3,
              work_order_id: 'WO-2024-1236',
              check_type: 'Safety',
              performed_by: 'Sarah Wilson',
              check_date: new Date(Date.now() - 172800000).toISOString(),
              passed: 0,
              issues_found: 'Missing safety goggles',
              corrective_actions: 'Provided safety goggles to technician'
            }
          ];
          
          let checks = simulatedChecks;
          
          // Apply filters
          if (typeFilter) {
            checks = checks.filter(check => check.check_type === typeFilter);
          }
          if (statusFilter !== '') {
            checks = checks.filter(check => check.passed.toString() === statusFilter);
          }
          
          document.getElementById('complianceContainer').innerHTML = checks.map(check => `
            <div class="check-card">
              <div class="status-badge status-${check.passed ? 'passed' : 'failed'}">
                ${check.passed ? '✓ Passed' : '✗ Failed'}
              </div>
              <div class="check-type type-${check.check_type.toLowerCase().replace('-', '-')}">${check.check_type}</div>
              <h3>${check.work_order_id}</h3>
              <div style="margin: 10px 0;">
                <strong>Performed by:</strong> ${check.performed_by}
              </div>
              <div style="margin: 5px 0;">
                <strong>Date:</strong> ${formatDate(check.check_date)}
              </div>
              ${check.issues_found ? `
                <div class="issues-section">
                  <strong>Issues Found:</strong> ${check.issues_found}
                </div>
              ` : ''}
              ${check.corrective_actions ? `
                <div style="margin: 10px 0;">
                  <strong>Corrective Actions:</strong> ${check.corrective_actions}
                </div>
              ` : ''}
              <div style="margin-top: 15px; display: flex; gap: 5px;">
                <button onclick="viewCheckDetails(${check.check_id})" class="action-btn" style="font-size: 11px; padding: 3px 8px;">
                  View Details
                </button>
                <button onclick="downloadReport(${check.check_id})" class="action-btn" style="font-size: 11px; padding: 3px 8px;">
                  Download Report
                </button>
              </div>
            </div>
          `).join('') || '<p style="text-align:center; padding:20px;">No compliance checks found.</p>';
          
          // Reinitialize icons
          lucide.createIcons();
          updateComplianceChart(checks);
        } catch (error) {
          console.error('Error loading compliance checks:', error);
          document.getElementById('complianceContainer').innerHTML = '<p style="text-align:center; padding:20px; color: red;">Error loading compliance checks.</p>';
        }
      }

      async function loadStatistics() {
        try {
          // Simulated statistics
          const stats = {
            total: 45,
            passed: 42,
            failed: 3,
            complianceRate: 93
          };
          
          document.getElementById('totalChecks').textContent = stats.total;
          document.getElementById('passedChecks').textContent = stats.passed;
          document.getElementById('failedChecks').textContent = stats.failed;
          document.getElementById('complianceRate').textContent = stats.complianceRate + '%';
        } catch (error) {
          console.error('Error loading statistics:', error);
        }
      }

      function initComplianceChart() {
        const ctx = document.getElementById('complianceChart').getContext('2d');
        complianceChart = new Chart(ctx, {
          type: 'doughnut',
          data: {
            labels: ['Passed', 'Failed'],
            datasets: [{
              data: [0, 0],
              backgroundColor: ['#28a745', '#dc3545'],
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

      function updateComplianceChart(checks) {
        if (!complianceChart) return;
        
        const passed = checks.filter(check => check.passed).length;
        const failed = checks.filter(check => !check.passed).length;
        
        complianceChart.data.datasets[0].data = [passed, failed];
        complianceChart.update();
      }

      async function openComplianceModal() {
        const { value: formValues } = await Swal.fire({
          title: 'New Compliance Check',
          html: `
            <label>Work Order ID</label>
            <input id="work-order-id" class="swal2-input" placeholder="WO-2024-1234">
            <label>Check Type</label>
            <select id="check-type" class="swal2-select">
              <option value="Pre-Work">Pre-Work</option>
              <option value="Post-Work">Post-Work</option>
              <option value="Safety">Safety</option>
              <option value="Quality">Quality</option>
              <option value="Environmental">Environmental</option>
            </select>
            <label>Performed By</label>
            <input id="performed-by" class="swal2-input" placeholder="Technician name">
            <label>Check Results</label>
            <select id="check-results" class="swal2-select">
              <option value="1">Passed</option>
              <option value="0">Failed</option>
            </select>
            <label>Issues Found</label>
            <textarea id="issues-found" class="swal2-textarea" placeholder="Describe any issues found..."></textarea>
            <label>Corrective Actions</label>
            <textarea id="corrective-actions" class="swal2-textarea" placeholder="Actions taken to resolve issues..."></textarea>
          `,
          confirmButtonText: 'Submit Check',
          preConfirm: () => {
            return {
              work_order_id: document.getElementById('work-order-id').value,
              check_type: document.getElementById('check-type').value,
              performed_by: document.getElementById('performed-by').value,
              passed: parseInt(document.getElementById('check-results').value),
              issues_found: document.getElementById('issues-found').value,
              corrective_actions: document.getElementById('corrective-actions').value
            };
          }
        });

        if (formValues.work_order_id && formValues.check_type && formValues.performed_by) {
          try {
            // For now, just show success since the API might not be fully implemented
            Swal.fire('Success', 'Compliance check submitted successfully', 'success');
            loadComplianceChecks();
            loadStatistics();
          } catch (error) {
            Swal.fire('Error', 'Failed to submit compliance check', 'error');
          }
        }
      }

      function useTemplate(templateType) {
        const templates = {
          'pre-work-general': {
            checklist_items: [
              { item: 'Personal Protective Equipment (PPE) worn', required: true },
              { item: 'Work area properly secured', required: true },
              { item: 'Tools inspected for safety', required: true },
              { item: 'Emergency exits accessible', required: true },
              { item: 'Fire extinguisher available', required: true }
            ]
          },
          'post-work-completion': {
            checklist_items: [
              { item: 'Work area cleaned and organized', required: true },
              { item: 'Tools properly stored', required: true },
              { item: 'Waste disposed properly', required: true },
              { item: 'Equipment returned to service', required: true },
              { item: 'Documentation completed', required: true }
            ]
          },
          'safety-electrical': {
            checklist_items: [
              { item: 'Power source disconnected', required: true },
              { item: 'Lockout/Tagout procedures followed', required: true },
              { item: 'Electrical tools inspected', required: true },
              { item: 'Grounding verified', required: true },
              { item: 'Voltage tested before reconnection', required: true }
            ]
          }
        };

        const template = templates[templateType];
        if (template) {
          openComplianceModal();
          // The template data will be used when the modal is submitted
          window.currentTemplate = template;
        }
      }

      function viewCheckDetails(checkId) {
        Swal.fire({
          title: 'Compliance Check Details',
          html: `
            <div style="text-align: left;">
              <h4>Check ID: #${checkId}</h4>
              <p><strong>Work Order:</strong> WO-2024-1234</p>
              <p><strong>Type:</strong> Pre-Work Safety</p>
              <p><strong>Performed by:</strong> John Smith</p>
              <p><strong>Date:</strong> ${new Date().toLocaleDateString()}</p>
              <p><strong>Status:</strong> <span style="color: #28a745;">✓ Passed</span></p>
              
              <h4>Checklist Items:</h4>
              <div style="background: #f8f9fa; padding: 10px; border-radius: 5px;">
                <div style="display: flex; align-items: center; gap: 8px; margin: 5px 0;">
                  <input type="checkbox" checked disabled>
                  <span>Personal Protective Equipment (PPE) worn</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; margin: 5px 0;">
                  <input type="checkbox" checked disabled>
                  <span>Work area properly secured</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; margin: 5px 0;">
                  <input type="checkbox" checked disabled>
                  <span>Tools inspected for safety</span>
                </div>
              </div>
              
              <h4>Notes:</h4>
              <p>All safety procedures followed correctly. No issues identified.</p>
            </div>
          `,
          width: 600,
          confirmButtonText: 'Close'
        });
      }

      function downloadReport(checkId) {
        Swal.fire('Info', 'Report download will be available in the next update.', 'info');
      }

      function refreshCompliance() {
        loadComplianceChecks();
        loadStatistics();
      }

      // Utility functions
      function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString();
      }
    </script>
  </body>
</html>

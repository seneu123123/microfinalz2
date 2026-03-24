<?php
// modules/dashboard.php
// Login process removed - direct access allowed

require '../config/db.php';

// 2. Get Real Stats from Database
// Count Total Clients
$stmt = $pdo->query("SELECT COUNT(*) FROM clients");
$total_clients = $stmt->fetchColumn();

// Count Active Loans
$stmt = $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'Active'");
$active_loans = $stmt->fetchColumn();

// Sum Total Loan Portfolio
$stmt = $pdo->query("SELECT SUM(amount) FROM loans");
$total_portfolio = $stmt->fetchColumn();

$page = 'dashboard'; // Used by sidebar to highlight the active link
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Microfinance</title>
  <link rel="stylesheet" href="../css/dashboard.css">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
  
  <?php include '../includes/sidebar.php'; ?>

  <main class="main-content">
    <header class="page-header">
      <div class="header-left">
        <div class="header-title">
          <h1>Dashboard Overview</h1>
          <p>Welcome to Dashboard!</p>
        </div>
      </div>
    </header>

    <div class="content-wrapper">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(44, 160, 120, 0.1); color: var(--brand-green);">
            <i data-lucide="users"></i>
          </div>
          <div class="stat-content">
            <span class="stat-label">Total Clients</span>
            <h3 class="stat-value"><?php echo number_format($total_clients); ?></h3>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: var(--brand-yellow);">
            <i data-lucide="banknote"></i>
          </div>
          <div class="stat-content">
            <span class="stat-label">Active Loans</span>
            <h3 class="stat-value"><?php echo number_format($active_loans); ?></h3>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
            <i data-lucide="wallet"></i>
          </div>
          <div class="stat-content">
            <span class="stat-label">Total Portfolio</span>
            <h3 class="stat-value">$<?php echo number_format($total_portfolio, 2); ?></h3>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="../js/dashboard.js"></script>
  <script>
    // Initialize Lucide icons and set active page
    document.addEventListener('DOMContentLoaded', function() {
      // Create Lucide icons
      if (typeof lucide !== 'undefined') {
        lucide.createIcons();
      }
      
      // Set active page in sidebar
      const currentPage = 'dashboard.php';
      const navItems = document.querySelectorAll('.nav-item[data-page]');
      navItems.forEach(item => {
        if (item.getAttribute('data-page') === currentPage) {
          item.classList.add('active');
        }
      });
      
      // Sidebar functionality
      const sidebarToggle = document.getElementById('sidebarToggle');
      const sidebar = document.getElementById('sidebar');
      
      if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
          sidebar.classList.toggle('collapsed');
          localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
      }
      
      // Submenu functionality
      const navItemsWithSubmenu = document.querySelectorAll('.nav-item.has-submenu');
      navItemsWithSubmenu.forEach((item) => {
        item.addEventListener('click', (e) => {
          e.preventDefault();
          const module = item.getAttribute('data-module');
          const submenu = document.getElementById(`submenu-${module}`);
          
          if (submenu) {
            // Close other submenus
            document.querySelectorAll('.submenu').forEach((sub) => {
              if (sub !== submenu) {
                sub.classList.remove('active');
                sub.previousElementSibling?.classList.remove('active');
              }
            });
            
            // Toggle current submenu
            submenu.classList.toggle('active');
            item.classList.toggle('active');
          }
        });
      });
      
      // Prevent submenu links from toggling parent
      document.querySelectorAll('.submenu-item').forEach((item) => {
        item.addEventListener('click', (e) => {
          e.stopPropagation();
        });
      });
    });
  </script>
</body>
</html>
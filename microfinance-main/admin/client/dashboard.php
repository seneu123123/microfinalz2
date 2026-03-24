<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header("Location: ../../clientlogin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Client Dasboard</title>
  <link rel="stylesheet" href="../../css/clientdashboard.css?v=1.2">
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="icon" type="image/png" href="../../img/logo.png">
</head>
<body>


  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="logo-container">
        <div class="logo-wrapper">
          <img src="../../img/logo.png" alt="Logo" class="logo">
        </div>
        <div class="logo-text">
          <h2 class="app-name">Microfinance</h2>
          <span class="app-tagline">Client Portal</span>
        </div>
      </div>
      <button class="sidebar-toggle" id="sidebarToggle">
        <i data-lucide="panel-left-close"></i>
      </button>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section">
        <span class="nav-section-title">OVERVIEW</span>
        
        <a href="dashboard.php" class="nav-item active">
          <i data-lucide="layout-dashboard"></i>
          <span>Dashboard</span>
        </a>


        <div class="nav-item-group">
          <button class="nav-item has-submenu" data-module="loans">
            <div class="nav-item-content">
              <i data-lucide="hand-coins"></i>
              <span>My Loans</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
          </button>
          <div class="submenu" id="submenu-loans">
            <a href="#" class="submenu-item">
              <i data-lucide="plus-circle"></i>
              <span>New Application</span>
            </a>
            <a href="#" class="submenu-item">
              <i data-lucide="landmark"></i>
              <span>Active Loans</span>
            </a>
            <a href="#" class="submenu-item">
              <i data-lucide="calendar-range"></i>
              <span>Repayment Schedule</span>
            </a>
          </div>
        </div>


        <div class="nav-item-group">
          <button class="nav-item has-submenu" data-module="savings">
            <div class="nav-item-content">
              <i data-lucide="piggy-bank"></i>
              <span>My Savings</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
          </button>
          <div class="submenu" id="submenu-savings">
            <a href="#" class="submenu-item">
              <i data-lucide="wallet"></i>
              <span>Balance Overview</span>
            </a>
            <a href="#" class="submenu-item">
              <i data-lucide="history"></i>
              <span>Transaction History</span>
            </a>
          </div>
        </div>


        <div class="nav-item-group">
          <button class="nav-item has-submenu" data-module="payments">
            <div class="nav-item-content">
              <i data-lucide="credit-card"></i>
              <span>Payments</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
          </button>
          <div class="submenu" id="submenu-payments">
            <a href="#" class="submenu-item">
              <i data-lucide="alert-circle"></i>
              <span>Due Payments</span>
            </a>
            <a href="#" class="submenu-item">
              <i data-lucide="file-text"></i>
              <span>Payment History</span>
            </a>
          </div>
        </div>

        <a href="#" class="nav-item">
          <i data-lucide="message-square"></i>
          <span>Support</span>
        </a>
      </div>

      <div class="nav-section">
        <span class="nav-section-title">ACCOUNT</span>
        
        <a href="profile.php" class="nav-item">
          <i data-lucide="user"></i>
          <span>My Profile</span>
        </a>

        <a href="securitysetting.php" class="nav-item">
          <i data-lucide="shield-check"></i>
          <span>Settings</span>
        </a>
      </div>
    </nav>

    <div class="sidebar-footer">
      <div class="user-profile">
        <div class="user-avatar">
          <img src="../../img/profile.png" alt="User">
        </div>
        <div class="user-info">
          <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Client'); ?></span>
          <span class="user-role">Client Account</span>
        </div>
        <button class="user-menu-btn" id="userMenuBtn">
          <i data-lucide="more-vertical"></i>
        </button>
        <div class="user-menu-dropdown" id="userMenuDropdown">
          <div class="umd-header">
            <div class="umd-avatar" id="umdAvatar"></div>
            <div class="umd-info">
              <span class="umd-signed">Signed in as</span>
              <span class="umd-name" id="umdName"></span>
              <span class="umd-role" id="umdRole"></span>
            </div>
          </div>
          <div class="umd-divider"></div>
          <a href="profile.php" class="umd-item"><i data-lucide="user-round"></i><span>Profile</span></a>
          <div class="umd-divider"></div>
          <a href="../../clientlogin.php" class="umd-item umd-item-danger umd-sign-out"><i data-lucide="log-out"></i><span>Sign Out</span></a>
        </div>
      </div>
    </div>
  </aside>


  <main class="main-content">
    <header class="page-header">
      <div class="header-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
          <i data-lucide="menu"></i>
        </button>
        <div class="header-title">
          <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Valued Client'); ?></h1>
          <p>Microfinance Service Portal • Last login: Today at <?php echo date('H:i'); ?></p>
        </div>
      </div>
      <div class="header-right">
        <div class="search-box">
          <i data-lucide="search"></i>
          <input type="search" placeholder="Search services...">
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <i data-lucide="sun" class="sun-icon"></i>
          <i data-lucide="moon" class="moon-icon"></i>
        </button>
        <button class="icon-btn">
          <i data-lucide="bell"></i>
          <span class="badge">2</span>
        </button>
      </div>
    </header>

    <div class="content-wrapper">
    
    </div>
  </main>
  <script src="../../js/sidebar-active.js"></script>
  <script src="../../js/clientdashboard.js"></script>
  <script>
    lucide.createIcons();
  </script>
  <script src="../../js/user-menu.js"></script>
</body>
</html>


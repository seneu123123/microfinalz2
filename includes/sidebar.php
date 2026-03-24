<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="logo-container">
      <div class="logo-wrapper">
        <img src="../img/logo.png" alt="Logo" class="logo" />
      </div>
      <div class="logo-text">
        <h2 class="app-name">Microfinance</h2>
        <span class="app-tagline">32005</span>
      </div>
    </div>
    <button class="sidebar-toggle" id="sidebarToggle">
      <i data-lucide="panel-left-close"></i>
    </button>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">
      <span class="nav-section-title">MAIN MENU</span>
      
      <a href="dashboard.php" class="nav-item" data-page="dashboard.php">
        <i data-lucide="layout-dashboard"></i>
        <span>Dashboard</span>
      </a>

      <div class="nav-item-group">
        <button class="nav-item has-submenu" data-module="procurement">
          <div class="nav-item-content"><i data-lucide="shopping-cart"></i><span>Procurement</span></div>
          <i data-lucide="chevron-down" class="submenu-icon"></i>
        </button>
        <div class="submenu" id="submenu-procurement">
          <a href="procurement.php" class="submenu-item"><i data-lucide="users"></i><span>Supplier Management</span></a>
          <a href="requisition.php" class="submenu-item"><i data-lucide="file-plus"></i><span>Purchase Requisition</span></a>
          <a href="purchase_orders.php" class="submenu-item"><i data-lucide="shopping-bag"></i><span>Purchase Order Management</span></a>
          <a href="procurement_report.php" class="submenu-item"><i data-lucide="bar-chart-2"></i><span>Procurement Reporting</span></a>
        </div>
      </div>

      <div class="nav-item-group">
        <button class="nav-item has-submenu" data-module="project">
          <div class="nav-item-content"><i data-lucide="folder-kanban"></i><span>Project Management</span></div>
          <i data-lucide="chevron-down" class="submenu-icon"></i>
        </button>
        <div class="submenu" id="submenu-project">
          <a href="projects.php" class="submenu-item"><i data-lucide="layout-template"></i><span>Project Planning</span></a>
          <a href="tasks.php" class="submenu-item"><i data-lucide="check-square"></i><span>Task Management</span></a>
          <a href="budget.php" class="submenu-item"><i data-lucide="landmark"></i><span>Budget and Timeline</span></a>
          <a href="project_report.php" class="submenu-item"><i data-lucide="bar-chart"></i><span>Reporting</span></a>
        </div>
      </div>

      <div class="nav-item-group">
        <button class="nav-item has-submenu" data-module="asset">
          <div class="nav-item-content"><i data-lucide="monitor"></i><span>Asset Management</span></div>
          <i data-lucide="chevron-down" class="submenu-icon"></i>
        </button>
        <div class="submenu" id="submenu-asset">
          <a href="assets.php" class="submenu-item"><i data-lucide="database"></i><span>Asset Registry</span></a>
          <a href="monitoring.php" class="submenu-item"><i data-lucide="activity"></i><span>Asset Monitoring</span></a>
          <a href="mro.php" class="submenu-item"><i data-lucide="tool"></i><span>Maintenance and Repair</span></a>
          <a href="disposal.php" class="submenu-item"><i data-lucide="trash-2"></i><span>Asset Disposal</span></a>
        </div>
      </div>

      <div class="nav-item-group">
        <button class="nav-item has-submenu" data-module="mro">
          <div class="nav-item-content"><i data-lucide="wrench"></i><span>MRO</span></div>
          <i data-lucide="chevron-down" class="submenu-icon"></i>
        </button>
        <div class="submenu" id="submenu-mro">
          <a href="mro_planning.php" class="submenu-item"><i data-lucide="calendar"></i><span>Maintenance Planning</span></a>
          <a href="mro.php" class="submenu-item"><i data-lucide="clipboard-list"></i><span>Work Order Management</span></a>
          <a href="mro_parts.php" class="submenu-item"><i data-lucide="settings"></i><span>Spare Parts and Supplies</span></a>
          <a href="compliance.php" class="submenu-item"><i data-lucide="shield-check"></i><span>Compliance and Safety</span></a>
        </div>
      </div>

      <div class="nav-item-group">
        <button class="nav-item has-submenu" data-module="warehousing">
          <div class="nav-item-content"><i data-lucide="package"></i><span>Warehousing</span></div>
          <i data-lucide="chevron-down" class="submenu-icon"></i>
        </button>
        <div class="submenu" id="submenu-warehousing">
          <a href="inventory.php" class="submenu-item"><i data-lucide="layers"></i><span>Inventory Management</span></a>
          <a href="storage.php" class="submenu-item"><i data-lucide="box"></i><span>Storage Management</span></a>
          <a href="receiving.php" class="submenu-item"><i data-lucide="arrow-down-circle"></i><span>Inbound Operations</span></a>
          <a href="outbound.php" class="submenu-item"><i data-lucide="arrow-up-circle"></i><span>Outbound Operations</span></a>
          <a href="warehouse_report.php" class="submenu-item"><i data-lucide="pie-chart"></i><span>Reporting</span></a>
        </div>
      </div>
    </div>

    <div class="nav-section" style="margin-top: 25px;">
      <span class="nav-section-title">FINANCIAL LOGISTICS</span>
      <a href="loans.php" class="nav-item"><i data-lucide="wallet"></i><span>Loan Management</span></a>
      <a href="documents.php" class="nav-item"><i data-lucide="file-signature"></i><span>Document Control</span></a>
      <a href="vault.php" class="nav-item"><i data-lucide="building-2"></i><span>Branch Operations</span></a>
      <a href="teller.php" class="nav-item"><i data-lucide="inbox"></i><span>Teller & Collections</span></a>
    </div>

    <div class="nav-section" style="margin-top: 25px;">
      <span class="nav-section-title">ACCOUNT</span>
      
      <button id="themeToggle" class="nav-item" style="background: none; border: none; width: 100%; text-align: left; cursor: pointer;">
        <i data-lucide="moon"></i><span>Toggle Theme</span>
      </button>

      <button onclick="logout()" class="nav-item" style="background: none; border: none; width: 100%; text-align: left; cursor: pointer;">
        <i data-lucide="log-out"></i><span>Logout</span>
      </button>
    </div>
  </nav>
</aside>
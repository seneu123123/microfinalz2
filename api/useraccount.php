<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'administrator') {
    header('Location: ../../login.php');
    exit;
}

require_once '../../config/config.php';

// Fetch all users with their creation date (based on earliest role assignment)
$usersSql = "SELECT ua.AccountID, ua.Username, ua.Email, ua.AccountStatus, ua.IsVerified,
             MIN(uar.AssignedAt) as CreatedAt, e.EmployeeCode
             FROM useraccounts ua
             LEFT JOIN useraccountroles uar ON ua.AccountID = uar.AccountID
             LEFT JOIN employee e ON ua.EmployeeID = e.EmployeeID
             GROUP BY ua.AccountID
             ORDER BY ua.AccountID ASC";
$usersResult = $conn->query($usersSql);
$users = [];
if ($usersResult) {
    while ($row = $usersResult->fetch_assoc()) {
        $users[] = $row;
    }
}

// Fetch all roles for dropdown
$rolesSql = "SELECT RoleID, RoleName FROM roles ORDER BY RoleName ASC";
$rolesResult = $conn->query($rolesSql);
$roles = [];
if ($rolesResult) {
    while ($row = $rolesResult->fetch_assoc()) {
        $roles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Management - Microfinance</title>
  <link rel="stylesheet" href="../../css/useraccount.css?v=1.4">
  <link rel="stylesheet" href="../../css/sidebar-fix.css?v=1.1">
  <script src="https://unpkg.com/lucide@0.474.0/dist/umd/lucide.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="icon" type="image/png" href="../../img/logo.png">
</head>
<body>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="logo-container">
        <div class="logo-wrapper">
          <img src="../../img/logo.png" alt="Logo" class="logo">
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
        
        <a href="dashboard.php" class="nav-item">
          <i data-lucide="layout-dashboard"></i>
          <span>Dashboard</span>
        </a>

        <div class="nav-item-group active">
          <button class="nav-item has-submenu" data-module="hr">
            <div class="nav-item-content">
              <i data-lucide="users"></i>
              <span>Account Management</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
          </button>
          <div class="submenu" id="submenu-hr">
            <a href="useraccount.php" class="submenu-item active">
              <i data-lucide="user-plus"></i>
              <span>User Accounts</span>
            </a>
            <a href="rolespermission.php" class="submenu-item">
              <i data-lucide="contact-round"></i>
              <span>Roles & Permissions</span>
            </a>
            <a href="securitysetting.php" class="submenu-item">
              <i data-lucide="user-cog"></i>
              <span>Security Settings</span>
            </a>
            <a href="auditlogs.php" class="submenu-item">
              <i data-lucide="book-user"></i>
              <span>Audit Logs</span>
            </a>
          </div>
        </div>

        <div class="nav-item-group">
          <button class="nav-item has-submenu" data-module="finance">
            <div class="nav-item-content">
              <i data-lucide="banknote"></i>
              <span>Finance</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
          </button>
          <div class="submenu" id="submenu-finance">
            <a href="#" class="submenu-item">
              <i data-lucide="receipt"></i>
              <span>Accounting</span>
            </a>
            <a href="#" class="submenu-item">
              <i data-lucide="file-text"></i>
              <span>Invoicing</span>
            </a>
            <a href="#" class="submenu-item">
              <i data-lucide="pie-chart"></i>
              <span>Budget Planning</span>
            </a>
          </div>
        </div>

        <div class="nav-item-group">
          <button class="nav-item has-submenu" data-module="loans">
            <div class="nav-item-content">
              <i data-lucide="hand-coins"></i>
              <span>Loan Management</span>
            </div>
            <i data-lucide="chevron-down" class="submenu-icon"></i>
          </button>
          <div class="submenu" id="submenu-loans">
            <a href="#" class="submenu-item">
              <i data-lucide="file-plus"></i>
              <span>Applications</span>
            </a>
            <a href="#" class="submenu-item">
              <i data-lucide="check-circle"></i>
              <span>Approvals</span>
            </a>
            <a href="#" class="submenu-item">
              <i data-lucide="calendar-clock"></i>
              <span>Disbursements</span>
            </a>
            <a href="#" class="submenu-item">
              <i data-lucide="coins"></i>
              <span>Collections</span>
            </a>
          </div>
        </div>

        <a href="#" class="nav-item">
          <i data-lucide="users-round"></i>
          <span>Clients</span>
        </a>

        <a href="#" class="nav-item">
          <i data-lucide="file-bar-chart"></i>
          <span>Reports</span>
        </a>
      </div>

      <div class="nav-section">
        <span class="nav-section-title">SETTINGS</span>
        
        <a href="#" class="nav-item">
          <i data-lucide="settings"></i>
          <span>Configuration</span>
        </a>

        <a href="#" class="nav-item">
          <i data-lucide="shield"></i>
          <span>Security</span>
        </a>
      </div>
    </nav>

    <div class="sidebar-footer">
      <div class="user-profile">
        <div class="user-avatar">
          <img src="../../img/profile.png" alt="User">
        </div>
        <div class="user-info">
          <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
          <span class="user-role"><?php echo htmlspecialchars($_SESSION['user_role']); ?></span>
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
          <a href="../../login.php" class="umd-item umd-item-danger umd-sign-out"><i data-lucide="log-out"></i><span>Sign Out</span></a>
        </div>
      </div>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <header class="page-header">
      <div class="header-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
          <i data-lucide="menu"></i>
        </button>
        <div class="header-title">
          <h1>Account Management</h1>
          <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>! Manage user accounts and permissions.</p>
        </div>
      </div>
      <div class="header-right">
        <div class="search-box">
          <i data-lucide="search"></i>
          <input type="search" placeholder="Search...">
        </div>
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <i data-lucide="sun" class="sun-icon"></i>
          <i data-lucide="moon" class="moon-icon"></i>
        </button>
        <button class="icon-btn">
          <i data-lucide="bell"></i>
        </button>
      </div>
    </header>

    <div class="content-wrapper">

      <!-- Stats Bar -->
      <?php
        $totalUsers    = count($users);
        $activeUsers   = count(array_filter($users, fn($u) => $u['AccountStatus'] === 'Active'));
        $inactiveUsers = $totalUsers - $activeUsers;
        $unverified    = count(array_filter($users, fn($u) => !$u['IsVerified']));
      ?>
      <div class="ua-stats">
        <div class="ua-stat-card">
          <div class="ua-stat-icon blue"><i data-lucide="users"></i></div>
          <div class="ua-stat-info">
            <span class="ua-stat-value"><?php echo $totalUsers; ?></span>
            <span class="ua-stat-label">Total Accounts</span>
          </div>
        </div>
        <div class="ua-stat-card">
          <div class="ua-stat-icon green"><i data-lucide="user-check"></i></div>
          <div class="ua-stat-info">
            <span class="ua-stat-value"><?php echo $activeUsers; ?></span>
            <span class="ua-stat-label">Active</span>
          </div>
        </div>
        <div class="ua-stat-card">
          <div class="ua-stat-icon amber"><i data-lucide="user-minus"></i></div>
          <div class="ua-stat-info">
            <span class="ua-stat-value"><?php echo $inactiveUsers; ?></span>
            <span class="ua-stat-label">Inactive</span>
          </div>
        </div>
        <div class="ua-stat-card">
          <div class="ua-stat-icon red"><i data-lucide="shield-off"></i></div>
          <div class="ua-stat-info">
            <span class="ua-stat-value"><?php echo $unverified; ?></span>
            <span class="ua-stat-label">Unverified</span>
          </div>
        </div>
      </div>

      <!-- Table Card -->
      <section class="users-panel">
        <div class="panel-header">
          <div class="panel-header-left">
            <div class="panel-header-icon"><i data-lucide="user-cog"></i></div>
            <div class="panel-header-titles">
              <h2>User Accounts</h2>
              <div class="panel-header-sub"><?php echo $totalUsers; ?> accounts registered</div>
            </div>
          </div>
          <div class="panel-actions">
            <div class="panel-search">
              <i data-lucide="search"></i>
              <input type="search" id="tableSearch" placeholder="Search accounts…">
            </div>
            <button id="addUserBtn" class="btn btn-primary">
              <i data-lucide="user-plus"></i> Add Account
            </button>
          </div>
        </div>

        <div class="panel-body">
          <div class="table-responsive">
            <table id="usersTable" class="users-table">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>Verified</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $user):
                  $initials = strtoupper(substr($user['Username'], 0, 1)) . (strlen($user['Username']) > 1 ? strtoupper(substr($user['Username'], 1, 1)) : '');
                ?>
                <tr>
                  <td>
                    <div class="ua-user-cell">
                      <div class="ua-user-avatar"><?php echo htmlspecialchars($initials); ?></div>
                      <div>
                        <div class="ua-user-name"><?php echo htmlspecialchars($user['Username']); ?></div>
                        <?php if (!empty($user['EmployeeCode'])): ?>
                        <div class="ua-user-id"><?php echo htmlspecialchars($user['EmployeeCode']); ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($user['Email']); ?></td>
                  <td>
                    <span class="badge badge-<?php echo strtolower($user['AccountStatus']); ?>">
                      <?php echo htmlspecialchars($user['AccountStatus']); ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge badge-<?php echo $user['IsVerified'] ? 'verified' : 'unverified'; ?>">
                      <?php echo $user['IsVerified'] ? 'Verified' : 'Unverified'; ?>
                    </span>
                  </td>
                  <td><?php echo date('M d, Y', strtotime($user['CreatedAt'] ?? 'now')); ?></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn btn-sm btn-edit" data-account-id="<?php echo $user['AccountID']; ?>" data-username="<?php echo htmlspecialchars($user['Username']); ?>">
                        <i data-lucide="edit-2"></i> Edit
                      </button>
                      <button class="btn btn-sm btn-delete" data-account-id="<?php echo $user['AccountID']; ?>" data-username="<?php echo htmlspecialchars($user['Username']); ?>">
                        <i data-lucide="trash-2"></i> Delete
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- Add Account Modal -->
      <div id="addUserModal" class="modal" aria-hidden="true">
        <div class="modal-dialog">

          <!-- Gradient hero header -->
          <div class="modal-hero">
            <div class="modal-hero-inner">
              <div class="modal-hero-icon"><i data-lucide="user-plus"></i></div>
              <div class="modal-hero-text">
                <h3 id="modalTitle">Add New Account</h3>
                <p>Fill in the details below to create a user account.</p>
              </div>
              <button class="close-modal" id="closeModalBtn" title="Close">&times;</button>
            </div>
          </div>

          <div class="modal-body">
            <form id="createUserForm">
              <input type="hidden" id="accountId" name="account_id" value="">

              <div class="form-row">
                <label for="username">Username <span class="required">*</span></label>
                <input id="username" name="username" type="text" placeholder="Enter username" required />
              </div>

              <div class="form-row">
                <label for="email">Email Address <span class="required">*</span></label>
                <input id="email" name="email" type="email" placeholder="Enter email address" required />
              </div>

              <div class="form-row">
                <label for="password">Password <span class="required">*</span></label>
                <div class="password-wrapper">
                  <input id="password" name="password" type="password" placeholder="Enter password" required />
                  <button type="button" class="btn-toggle-pwd" onclick="togglePassword('password')">
                    <i data-lucide="eye" class="eye-icon"></i>
                  </button>
                </div>
              </div>

              <div class="form-row">
                <label for="confirmPassword">Confirm Password <span class="required">*</span></label>
                <div class="password-wrapper">
                  <input id="confirmPassword" name="confirm_password" type="password" placeholder="Confirm password" required />
                  <button type="button" class="btn-toggle-pwd" onclick="togglePassword('confirmPassword')">
                    <i data-lucide="eye" class="eye-icon"></i>
                  </button>
                </div>
              </div>

              <div class="form-row">
                <label for="roles">Assign Roles <span class="required">*</span></label>
                <select id="roles" name="roles[]" multiple size="4" required>
                  <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['RoleID']; ?>">
                      <?php echo htmlspecialchars($role['RoleName']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="hint">Hold Ctrl/Cmd to select multiple roles.</small>
              </div>

              <div class="form-row">
                <label for="accountStatus">Account Status <span class="required">*</span></label>
                <select id="accountStatus" name="account_status" required>
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                </select>
              </div>
            </form>
          </div>

          <!-- Sticky footer -->
          <div class="form-actions">
            <button type="button" id="cancelCreate" class="btn-modal-cancel">Cancel</button>
            <button type="submit" form="createUserForm" class="btn-modal-submit">
              <i data-lucide="save"></i> <span id="submitBtnLabel">Create Account</span>
            </button>
          </div>

        </div>
      </div>

    </div>
  </main>
  <script src="../../js/sidebar-active.js"></script>
  <script src="../../js/useraccount.js?v=<?php echo time(); ?>"></script>
  <script>
    // Initialize icons safely
    if (window.lucide) {
      window.lucide.createIcons();
    }

    // Robust Fallback: Verify if openAddAccountModal exists, if not, define it
    if (typeof window.openAddAccountModal !== 'function') {
      window.openAddAccountModal = function() {
        console.log("Fallback modal opener triggered");
        const modal = document.getElementById("addUserModal");
        const form = document.getElementById("createUserForm");
        
        if (modal) {
          modal.style.display = "flex";
          modal.classList.add("show");
          modal.setAttribute("aria-hidden", "false");
          
          if (form) {
            form.reset();
            document.getElementById("accountId").value = "";
          }
          
          // Reset header and button text for 'Add' mode
          const title = document.getElementById("modalTitle");
          const lbl   = document.getElementById("submitBtnLabel");
          if (title) title.textContent = "Add New Account";
          if (lbl)   lbl.textContent   = "Create Account";
        } else {
          alert("Error: Modal element not found!");
        }
      };
    }

    // Attach explicit click listener to button as backup
    document.addEventListener('DOMContentLoaded', function() {
      const btn = document.getElementById("addUserBtn");
      if (btn) {
        btn.onclick = function(e) {
          e.preventDefault();
          if (window.openAddAccountModal) {
            window.openAddAccountModal();
          } else {
            console.error("openAddAccountModal is still not defined");
          }
        };
      }
    });

    // Table search filtering
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.getElementById('tableSearch');
      if (searchInput) {
        searchInput.addEventListener('input', function() {
          const q = this.value.toLowerCase();
          document.querySelectorAll('#usersTable tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
          });
        });
      }
    });

    // Handle close buttons for fallback
    document.addEventListener('click', function(e) {
      if (e.target && (e.target.id === 'closeModalBtn' || e.target.id === 'cancelCreate' || e.target.classList.contains('close-modal'))) {
        const modal = document.getElementById("addUserModal");
        if (modal) {
          modal.style.display = "none";
          modal.classList.remove("show");
        }
      }
      // Click outside
      if (e.target && e.target.id === 'addUserModal') {
        e.target.style.display = "none";
        e.target.classList.remove("show");
      }
    });
  </script>
  
  <script src="../../js/user-menu.js"></script>
</body>
</html>


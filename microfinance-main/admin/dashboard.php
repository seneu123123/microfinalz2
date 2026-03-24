<?php
// modules/dashboard.php
session_start();

// 1. Security: If user is not logged in, kick them back to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

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
          <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
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
  <script>lucide.createIcons();</script>
</body>
</html>
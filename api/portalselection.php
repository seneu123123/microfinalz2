<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Portal — Microfinance</title>
    <link rel="stylesheet" href="css/portalselection.css">
    <link rel="icon" type="image/png" href="img/logo.png">
</head>
<body>
    <!-- Theme Toggle -->
    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sun-icon">
            <circle cx="12" cy="12" r="5"></circle>
            <line x1="12" y1="1" x2="12" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="23"></line>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
            <line x1="1" y1="12" x2="3" y2="12"></line>
            <line x1="21" y1="12" x2="23" y2="12"></line>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
        </svg>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="moon-icon">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
        </svg>
    </button>

    <div class="portal-container">
        <!-- Logo -->
        <a href="index.php" class="portal-logo">
            <img src="img/logo.png" alt="Microfinance Logo">
            <span class="portal-logo-text">Microfinance</span>
        </a>

        <!-- Header -->
        <div class="portal-header">
            <h1>Select Your Portal</h1>
            <p>Choose the portal that matches your role to continue</p>
        </div>

        <!-- Portal Cards -->
        <div class="portal-cards">
            <!-- Client Portal -->
            <a href="clientlogin.php" class="portal-card client-card">
                <div class="portal-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <span class="portal-card-title">Client Portal</span>
                <span class="portal-card-desc">Access your loan accounts, payments, and financial overview</span>
                <div class="portal-card-arrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </div>
            </a>

            <!-- Employee Portal -->
            <a href="login.php" class="portal-card employee-card">
                <div class="portal-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                </div>
                <span class="portal-card-title">Employee Portal</span>
                <span class="portal-card-desc">Staff and management access for system administration</span>
                <div class="portal-card-arrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </div>
            </a>
        </div>

        <!-- Back link -->
        <div class="portal-back">
            <a href="index.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Home
            </a>
        </div>
    </div>

    <script>
        const themeToggle = document.getElementById('themeToggle');
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') document.body.classList.add('dark-mode');

        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        });
    </script>
</body>
</html>
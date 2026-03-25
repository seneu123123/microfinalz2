/**
 * Admin Dashboard Module
 * Handles dashboard stats, charts, notifications, and real-time updates
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('[AdminDashboard] Initializing admin dashboard');
    initAdminDashboard();
});

function initAdminDashboard() {
    // Load dashboard statistics
    loadDashboardStats();

    // Initialize sidebar toggle
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });

        // Restore sidebar state
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            sidebar.classList.add('collapsed');
        }
    }

    // Initialize submenu navigation
    initSubmenus();

    // Initialize theme toggle
    initThemeToggle();

    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Auto-refresh stats every 60 seconds
    setInterval(loadDashboardStats, 60000);
}

/**
 * Initialize submenu toggle functionality
 */
function initSubmenus() {
    var navItems = document.querySelectorAll('.nav-item.has-submenu');

    navItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var module = item.getAttribute('data-module');
            var submenu = document.getElementById('submenu-' + module);

            if (!submenu) return;

            // Close other submenus
            document.querySelectorAll('.submenu').forEach(function(sub) {
                if (sub !== submenu) {
                    sub.classList.remove('active');
                    var otherItem = sub.previousElementSibling;
                    if (otherItem && otherItem.classList.contains('has-submenu')) {
                        otherItem.classList.remove('active');
                    }
                }
            });

            // Toggle current submenu
            submenu.classList.toggle('active');
            item.classList.toggle('active');
        });
    });

    // Prevent submenu links from toggling parent
    document.querySelectorAll('.submenu-item').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
}

/**
 * Initialize theme toggle
 */
function initThemeToggle() {
    var themeToggle = document.getElementById('themeToggle');
    if (!themeToggle) return;

    // Restore saved theme
    var savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);

    themeToggle.addEventListener('click', function() {
        var current = document.documentElement.getAttribute('data-theme') || 'light';
        var next = current === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
    });
}

/**
 * Load dashboard statistics from API
 */
async function loadDashboardStats() {
    try {
        var response = await fetch('../api/reporting.php?action=dashboard_stats');
        var data = await response.json();

        if (data.success && data.data) {
            updateStatCard('totalClients', data.data.total_clients);
            updateStatCard('activeLoans', data.data.active_loans);
            updateStatCard('totalPortfolio', data.data.total_portfolio, true);
            updateStatCard('pendingApprovals', data.data.pending_approvals);
        }
    } catch (error) {
        console.error('[AdminDashboard] Error loading stats:', error);
    }
}

/**
 * Update a stat card value
 */
function updateStatCard(id, value, isCurrency) {
    var el = document.getElementById(id);
    if (!el) return;

    if (isCurrency) {
        el.textContent = '$' + Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
    } else {
        el.textContent = Number(value || 0).toLocaleString();
    }
}

/**
 * Logout function
 */
function logout() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Logout',
            text: 'Are you sure you want to logout?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2ca078',
            confirmButtonText: 'Yes, logout'
        }).then(function(result) {
            if (result.isConfirmed) {
                performLogout();
            }
        });
    } else {
        if (confirm('Are you sure you want to logout?')) {
            performLogout();
        }
    }
}

function performLogout() {
    fetch('../api/auth.php?action=logout', { method: 'POST' })
        .then(function() {
            sessionStorage.clear();
            localStorage.removeItem('session');
            window.location.href = '../login.html';
        })
        .catch(function() {
            window.location.href = '../login.html';
        });
}
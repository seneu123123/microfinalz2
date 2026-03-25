/**
 * Client Dashboard Module
 * Handles client-side dashboard functionality, sidebar, and data loading
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('[ClientDashboard] Initializing client dashboard');
    initClientDashboard();
});

function initClientDashboard() {
    // Initialize sidebar toggle
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    var mobileMenuBtn = document.getElementById('mobileMenuBtn');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });

        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            sidebar.classList.add('collapsed');
        }
    }

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
        });
    }

    // Initialize submenu navigation
    var navItems = document.querySelectorAll('.nav-item.has-submenu');
    navItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var module = item.getAttribute('data-module');
            var submenu = document.getElementById('submenu-' + module);
            if (!submenu) return;

            document.querySelectorAll('.submenu').forEach(function(sub) {
                if (sub !== submenu) {
                    sub.classList.remove('active');
                    var otherItem = sub.previousElementSibling;
                    if (otherItem && otherItem.classList.contains('has-submenu')) {
                        otherItem.classList.remove('active');
                    }
                }
            });

            submenu.classList.toggle('active');
            item.classList.toggle('active');
        });
    });

    document.querySelectorAll('.submenu-item').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    // Initialize theme toggle
    var themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        var savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);

        themeToggle.addEventListener('click', function() {
            var current = document.documentElement.getAttribute('data-theme') || 'light';
            var next = current === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
        });
    }

    // Load client data
    loadClientOverview();

    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

/**
 * Load client dashboard overview data
 */
async function loadClientOverview() {
    try {
        var response = await fetch('../../api/loans.php?action=client_summary');
        var data = await response.json();

        if (data.success && data.data) {
            var stats = data.data;
            updateEl('activeLoansCount', stats.active_loans || 0);
            updateEl('totalBalance', formatCurrency(stats.total_balance || 0));
            updateEl('nextPayment', formatCurrency(stats.next_payment || 0));
            updateEl('savingsBalance', formatCurrency(stats.savings_balance || 0));
        }
    } catch (error) {
        console.error('[ClientDashboard] Error loading overview:', error);
    }
}

function updateEl(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = value;
}

function formatCurrency(amount) {
    return '$' + Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2 });
}
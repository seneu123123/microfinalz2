/**
 * Sidebar Active State Module
 * Highlights the current page in the sidebar navigation
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('[Sidebar] Active state module loaded');
    initSidebarActive();
});

function initSidebarActive() {
    // Get the current page filename
    var path = window.location.pathname;
    var currentPage = path.substring(path.lastIndexOf('/') + 1) || 'dashboard.php';

    // Highlight active nav item
    var navItems = document.querySelectorAll('.nav-item[href], a.nav-item');
    navItems.forEach(function(item) {
        var href = item.getAttribute('href');
        if (!href) return;

        var linkPage = href.substring(href.lastIndexOf('/') + 1);

        if (linkPage === currentPage) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });

    // Highlight active submenu item and auto-open its parent
    var submenuItems = document.querySelectorAll('.submenu-item');
    submenuItems.forEach(function(item) {
        var href = item.getAttribute('href');
        if (!href) return;

        var linkPage = href.substring(href.lastIndexOf('/') + 1);

        if (linkPage === currentPage) {
            item.classList.add('active');

            // Auto-open the parent submenu
            var submenu = item.closest('.submenu');
            if (submenu) {
                submenu.classList.add('active');
                var parentButton = submenu.previousElementSibling;
                if (parentButton && parentButton.classList.contains('has-submenu')) {
                    parentButton.classList.add('active');
                }
            }
        }
    });
}
/**
 * User Menu Module
 * Handles the user profile dropdown menu in the sidebar footer
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('[UserMenu] User menu module loaded');
    initUserMenu();
});

function initUserMenu() {
    var menuBtn = document.getElementById('userMenuBtn');
    var menuDropdown = document.getElementById('userMenuDropdown');

    if (!menuBtn || !menuDropdown) return;

    // Toggle dropdown on button click
    menuBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        menuDropdown.classList.toggle('show');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!menuBtn.contains(e.target) && !menuDropdown.contains(e.target)) {
            menuDropdown.classList.remove('show');
        }
    });

    // Close dropdown on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            menuDropdown.classList.remove('show');
        }
    });

    // Populate user info in dropdown header
    populateUserMenuInfo();
}

/**
 * Populate user name/role in the dropdown header
 */
function populateUserMenuInfo() {
    var userName = document.querySelector('.user-name');
    var umdName = document.getElementById('umdName');
    var umdRole = document.getElementById('umdRole');
    var umdAvatar = document.getElementById('umdAvatar');

    if (userName && umdName) {
        umdName.textContent = userName.textContent;
    }

    var userRole = document.querySelector('.user-role');
    if (userRole && umdRole) {
        umdRole.textContent = userRole.textContent;
    }

    // Set avatar initials
    if (umdAvatar && userName) {
        var name = userName.textContent.trim();
        var initials = name.split(' ').map(function(w) { return w.charAt(0); }).join('').substring(0, 2).toUpperCase();
        umdAvatar.textContent = initials;
        umdAvatar.style.cssText = 'width:40px;height:40px;border-radius:50%;background:#2ca078;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:14px;';
    }
}
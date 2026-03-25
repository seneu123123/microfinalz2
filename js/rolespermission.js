/**
 * Roles and Permissions Management System
 * Handles user roles, permissions, and access control for MRO System
 */

// Role definitions
const ROLES = {
    ADMIN: 'admin',
    ADMIN2: 'admin2', 
    VENDOR_USER: 'vendor_user'
};

// Permission definitions
const PERMISSIONS = {
    // Dashboard permissions
    VIEW_DASHBOARD: 'view_dashboard',
    
    // Work Order permissions
    VIEW_WORK_ORDERS: 'view_work_orders',
    CREATE_WORK_ORDERS: 'create_work_orders',
    EDIT_WORK_ORDERS: 'edit_work_orders',
    DELETE_WORK_ORDERS: 'delete_work_orders',
    ASSIGN_WORK_ORDERS: 'assign_work_orders',
    
    // Maintenance Planning permissions
    VIEW_MAINTENANCE_PLANS: 'view_maintenance_plans',
    CREATE_MAINTENANCE_PLANS: 'create_maintenance_plans',
    EDIT_MAINTENANCE_PLANS: 'edit_maintenance_plans',
    DELETE_MAINTENANCE_PLANS: 'delete_maintenance_plans',
    
    // Parts Management permissions
    VIEW_PARTS: 'view_parts',
    MANAGE_INVENTORY: 'manage_inventory',
    RECORD_PARTS_USAGE: 'record_parts_usage',
    REORDER_PARTS: 'reorder_parts',
    
    // Compliance permissions
    VIEW_COMPLIANCE: 'view_compliance',
    SUBMIT_COMPLIANCE_CHECKS: 'submit_compliance_checks',
    APPROVE_COMPLIANCE: 'approve_compliance',
    
    // User Management permissions
    VIEW_USERS: 'view_users',
    MANAGE_USERS: 'manage_users',
    ASSIGN_ROLES: 'assign_roles',
    
    // Reports permissions
    VIEW_REPORTS: 'view_reports',
    GENERATE_REPORTS: 'generate_reports',
    EXPORT_REPORTS: 'export_reports'
};

// Role permissions mapping
const ROLE_PERMISSIONS = {
    [ROLES.ADMIN]: [
        // Admin has all permissions
        ...Object.values(PERMISSIONS)
    ],
    
    [ROLES.ADMIN2]: [
        // Admin2 has most permissions except user management
        PERMISSIONS.VIEW_DASHBOARD,
        PERMISSIONS.VIEW_WORK_ORDERS,
        PERMISSIONS.CREATE_WORK_ORDERS,
        PERMISSIONS.EDIT_WORK_ORDERS,
        PERMISSIONS.ASSIGN_WORK_ORDERS,
        PERMISSIONS.VIEW_MAINTENANCE_PLANS,
        PERMISSIONS.CREATE_MAINTENANCE_PLANS,
        PERMISSIONS.EDIT_MAINTENANCE_PLANS,
        PERMISSIONS.VIEW_PARTS,
        PERMISSIONS.RECORD_PARTS_USAGE,
        PERMISSIONS.REORDER_PARTS,
        PERMISSIONS.VIEW_COMPLIANCE,
        PERMISSIONS.SUBMIT_COMPLIANCE_CHECKS,
        PERMISSIONS.VIEW_REPORTS,
        PERMISSIONS.GENERATE_REPORTS,
        PERMISSIONS.EXPORT_REPORTS
    ],
    
    [ROLES.VENDOR_USER]: [
        // Vendor users have limited permissions
        PERMISSIONS.VIEW_DASHBOARD,
        PERMISSIONS.VIEW_WORK_ORDERS,
        PERMISSIONS.EDIT_WORK_ORDERS, // Can edit assigned work orders
        PERMISSIONS.VIEW_MAINTENANCE_PLANS,
        PERMISSIONS.VIEW_PARTS,
        PERMISSIONS.RECORD_PARTS_USAGE,
        PERMISSIONS.VIEW_COMPLIANCE,
        PERMISSIONS.SUBMIT_COMPLIANCE_CHECKS,
        PERMISSIONS.VIEW_REPORTS
    ]
};

// Current user session
let currentUser = null;
let userPermissions = [];

/**
 * Initialize roles and permissions system
 */
function initializeRolesPermissions() {
    // Get current user from session
    fetchCurrentUserInfo();
    
    // Apply permissions to UI elements
    applyPermissionsToUI();
    
    console.log('[ROLES] Roles and permissions system initialized');
}

/**
 * Fetch current user information
 */
async function fetchCurrentUserInfo() {
    try {
        const response = await fetch('../api/user.php?action=current_user');
        const data = await response.json();
        
        if (data.success) {
            currentUser = data.user;
            userPermissions = ROLE_PERMISSIONS[currentUser.role] || [];
            
            console.log('[ROLES] Current user:', currentUser.name, 'Role:', currentUser.role);
            console.log('[ROLES] Permissions:', userPermissions);
        } else {
            console.error('[ROLES] Failed to fetch user info');
        }
    } catch (error) {
        console.error('[ROLES] Error fetching user info:', error);
    }
}

/**
 * Check if user has specific permission
 * @param {string} permission - Permission to check
 * @returns {boolean} - True if user has permission
 */
function hasPermission(permission) {
    return userPermissions.includes(permission);
}

/**
 * Check if user has any of the specified permissions
 * @param {string[]} permissions - Array of permissions to check
 * @returns {boolean} - True if user has any of the permissions
 */
function hasAnyPermission(permissions) {
    return permissions.some(permission => userPermissions.includes(permission));
}

/**
 * Check if user has all specified permissions
 * @param {string[]} permissions - Array of permissions to check
 * @returns {boolean} - True if user has all permissions
 */
function hasAllPermissions(permissions) {
    return permissions.every(permission => userPermissions.includes(permission));
}

/**
 * Apply permissions to UI elements
 */
function applyPermissionsToUI() {
    // Hide/show elements based on permissions
    const permissionElements = document.querySelectorAll('[data-permission]');
    
    permissionElements.forEach(element => {
        const requiredPermission = element.getAttribute('data-permission');
        
        if (!hasPermission(requiredPermission)) {
            // Hide element
            element.style.display = 'none';
            
            // Add visual indicator for debugging (remove in production)
            if (window.location.hostname === 'localhost') {
                element.style.border = '2px dashed red';
                element.title = `Permission required: ${requiredPermission}`;
            }
        }
    });
    
    // Disable buttons based on permissions
    const permissionButtons = document.querySelectorAll('[data-permission-button]');
    
    permissionButtons.forEach(button => {
        const requiredPermission = button.getAttribute('data-permission-button');
        
        if (!hasPermission(requiredPermission)) {
            button.disabled = true;
            button.title = `Permission required: ${requiredPermission}`;
            
            // Add visual indicator
            button.classList.add('permission-disabled');
        }
    });
    
    console.log('[ROLES] Permissions applied to UI elements');
}

/**
 * Show permission denied message
 * @param {string} action - Action that was denied
 */
function showPermissionDenied(action) {
    Swal.fire({
        title: 'Access Denied',
        text: `You don't have permission to ${action}. Please contact your administrator.`,
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#dc3545'
    });
}

/**
 * Permission wrapper for functions
 * @param {string} permission - Required permission
 * @param {Function} callback - Function to execute if permission granted
 * @param {string} action - Action description for error message
 */
function requirePermission(permission, callback, action) {
    return function(...args) {
        if (hasPermission(permission)) {
            return callback.apply(this, args);
        } else {
            showPermissionDenied(action || 'perform this action');
            return false;
        }
    };
}

/**
 * Get user role display name
 * @param {string} role - Role code
 * @returns {string} - Display name
 */
function getRoleDisplayName(role) {
    const roleNames = {
        [ROLES.ADMIN]: 'Administrator',
        [ROLES.ADMIN2]: 'Manager',
        [ROLES.VENDOR_USER]: 'Vendor User'
    };
    
    return roleNames[role] || 'Unknown';
}

/**
 * Check if user can access specific page
 * @param {string} page - Page identifier
 * @returns {boolean} - True if user can access page
 */
function canAccessPage(page) {
    const pagePermissions = {
        'dashboard': PERMISSIONS.VIEW_DASHBOARD,
        'mro_dashboard': PERMISSIONS.VIEW_DASHBOARD,
        'mro_planning': PERMISSIONS.VIEW_MAINTENANCE_PLANS,
        'mro_work_orders': PERMISSIONS.VIEW_WORK_ORDERS,
        'mro_parts': PERMISSIONS.VIEW_PARTS,
        'compliance': PERMISSIONS.VIEW_COMPLIANCE,
        'reports': PERMISSIONS.VIEW_REPORTS,
        'users': PERMISSIONS.VIEW_USERS
    };
    
    const requiredPermission = pagePermissions[page];
    if (!requiredPermission) {
        return true; // No specific permission required
    }
    
    return hasPermission(requiredPermission);
}

/**
 * Filter menu items based on permissions
 */
function filterMenuItems() {
    const menuItems = document.querySelectorAll('.nav-item[data-page]');
    
    menuItems.forEach(item => {
        const page = item.getAttribute('data-page');
        
        if (!canAccessPage(page)) {
            item.style.display = 'none';
        }
    });
}

/**
 * Initialize role-based UI when DOM is ready
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize roles and permissions
    initializeRolesPermissions();
    
    // Filter menu items
    setTimeout(() => {
        filterMenuItems();
    }, 500);
});

/**
 * Export functions for global use
 */
window.RolesPermissions = {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    requirePermission,
    canAccessPage,
    showPermissionDenied,
    getRoleDisplayName,
    ROLES,
    PERMISSIONS
};

console.log('[ROLES] Roles and permissions module loaded');
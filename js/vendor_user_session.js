// Session Validation for Vendor User Dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in and has vendor_user role
    const session = getSession();
    
    if (!session) {
        // No session found, redirect to login
        window.location.href = '../login.html';
        return;
    }
    
    // Validate session with server
    fetch('../api/auth.php?action=validate&token=' + session.token)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                // Session invalid, clear and redirect
                clearSession();
                Swal.fire({
                    icon: 'error',
                    title: 'Session Expired',
                    text: 'Your session has expired. Please login again.',
                    confirmButtonColor: '#2ca078',
                    confirmButtonText: 'Go to Login'
                }).then(() => {
                    window.location.href = '../login.html';
                });
                return;
            }
            
            // Check if user has vendor_user role
            if (data.data.user.role !== 'vendor_user') {
                clearSession();
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: 'You do not have permission to access this page.',
                    confirmButtonColor: '#2ca078',
                    confirmButtonText: 'Go to Login'
                }).then(() => {
                    window.location.href = '../login.html';
                });
                return;
            }
            
            // Session valid and user is vendor_user, show welcome message
            console.log('Vendor user logged in:', data.data.user.name);
            
            // Update user info in sidebar if available
            const userNameElement = document.querySelector('.user-name');
            const userRoleElement = document.querySelector('.user-role');
            
            if (userNameElement) {
                userNameElement.textContent = data.data.user.name;
            }
            
            if (userRoleElement) {
                userRoleElement.textContent = 'Vendor User';
            }
        })
        .catch(error => {
            console.error('Session validation error:', error);
            clearSession();
            window.location.href = '../login.html';
        });
});

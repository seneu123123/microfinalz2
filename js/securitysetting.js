/**
 * Security Settings Module
 * Handles password changes, two-factor authentication, and session management
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('[Security] Security settings module loaded');
    initSecuritySettings();
});

function initSecuritySettings() {
    // Initialize password change form
    const passwordForm = document.getElementById('passwordChangeForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', handlePasswordChange);
    }

    // Initialize 2FA toggle
    const twoFactorToggle = document.getElementById('twoFactorToggle');
    if (twoFactorToggle) {
        twoFactorToggle.addEventListener('change', handleTwoFactorToggle);
    }

    // Initialize session management
    loadActiveSessions();

    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

/**
 * Handle password change submission
 */
async function handlePasswordChange(e) {
    e.preventDefault();

    const currentPassword = document.getElementById('currentPassword');
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');

    if (!currentPassword || !newPassword || !confirmPassword) return;

    if (newPassword.value !== confirmPassword.value) {
        Swal.fire({
            icon: 'error',
            title: 'Password Mismatch',
            text: 'New password and confirm password do not match.',
            confirmButtonColor: '#2ca078'
        });
        return;
    }

    if (newPassword.value.length < 8) {
        Swal.fire({
            icon: 'error',
            title: 'Weak Password',
            text: 'Password must be at least 8 characters long.',
            confirmButtonColor: '#2ca078'
        });
        return;
    }

    try {
        const response = await fetch('../api/useraccount.php?action=change_password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                current_password: currentPassword.value,
                new_password: newPassword.value
            })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Password Changed',
                text: 'Your password has been updated successfully.',
                confirmButtonColor: '#2ca078'
            });
            e.target.reset();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to change password.',
                confirmButtonColor: '#2ca078'
            });
        }
    } catch (error) {
        console.error('[Security] Password change error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred. Please try again.',
            confirmButtonColor: '#2ca078'
        });
    }
}

/**
 * Handle two-factor authentication toggle
 */
async function handleTwoFactorToggle(e) {
    const enabled = e.target.checked;
    const action = enabled ? 'enable' : 'disable';

    const result = await Swal.fire({
        title: (enabled ? 'Enable' : 'Disable') + ' Two-Factor Authentication?',
        text: enabled
            ? 'This adds an extra layer of security to your account.'
            : 'Disabling 2FA will make your account less secure.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2ca078',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, ' + action + ' it'
    });

    if (!result.isConfirmed) {
        e.target.checked = !enabled;
        return;
    }

    try {
        const response = await fetch('../api/useraccount.php?action=toggle_2fa', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ enabled: enabled })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '2FA ' + (enabled ? 'Enabled' : 'Disabled'),
                text: data.message || 'Two-factor authentication has been ' + action + 'd.',
                confirmButtonColor: '#2ca078'
            });
        } else {
            e.target.checked = !enabled;
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to ' + action + ' 2FA.',
                confirmButtonColor: '#2ca078'
            });
        }
    } catch (error) {
        console.error('[Security] 2FA toggle error:', error);
        e.target.checked = !enabled;
    }
}

/**
 * Load active sessions
 */
async function loadActiveSessions() {
    var container = document.getElementById('activeSessionsList');
    if (!container) return;

    try {
        var response = await fetch('../api/useraccount.php?action=active_sessions');
        var data = await response.json();

        if (data.success && data.data) {
            container.innerHTML = data.data.map(function(session) {
                var endBtn = session.current
                    ? '<span style="color:#2ca078; font-weight:bold;">Current</span>'
                    : '<button onclick="terminateSession(\'' + session.id + '\')" class="action-btn" style="font-size:11px; padding:3px 8px; background:#dc3545;">End</button>';
                return '<div class="session-item" style="display:flex; justify-content:space-between; align-items:center; padding:12px; border-bottom:1px solid #eee;">' +
                    '<div><strong>' + (session.device || 'Unknown Device') + '</strong>' +
                    '<div style="font-size:12px; color:#666;">IP: ' + (session.ip || 'N/A') + ' &bull; ' + (session.last_active || 'N/A') + '</div></div>' +
                    endBtn + '</div>';
            }).join('');
        } else {
            container.innerHTML = '<p style="padding:15px; color:#666;">No active sessions found.</p>';
        }
    } catch (error) {
        console.error('[Security] Session load error:', error);
        container.innerHTML = '<p style="padding:15px; color:#666;">Unable to load sessions.</p>';
    }
}

/**
 * Terminate a specific session
 */
async function terminateSession(sessionId) {
    var result = await Swal.fire({
        title: 'End Session?',
        text: 'This will log out the device associated with this session.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'End Session'
    });

    if (!result.isConfirmed) return;

    try {
        var response = await fetch('../api/useraccount.php?action=terminate_session', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session_id: sessionId })
        });

        var data = await response.json();

        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Session Ended', confirmButtonColor: '#2ca078' });
            loadActiveSessions();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to end session.', confirmButtonColor: '#2ca078' });
        }
    } catch (error) {
        console.error('[Security] Session termination error:', error);
    }
}

/**
 * Password strength checker
 */
function checkPasswordStrength(password) {
    var score = 0;
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;

    var levels = ['Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];
    var colors = ['#dc3545', '#fd7e14', '#ffc107', '#28a745', '#20c997'];
    var index = Math.min(score, levels.length - 1);

    return { score: score, label: levels[index], color: colors[index] };
}

// Update password strength indicator in real-time
document.addEventListener('input', function(e) {
    if (e.target.id === 'newPassword') {
        var indicator = document.getElementById('passwordStrength');
        if (indicator) {
            var strength = checkPasswordStrength(e.target.value);
            indicator.textContent = strength.label;
            indicator.style.color = strength.color;
        }
    }
});
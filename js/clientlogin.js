/**
 * Client Login Module
 * Handles client portal authentication and session management
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('[ClientLogin] Client login module loaded');
    initClientLogin();
});

function initClientLogin() {
    // Initialize login form
    var loginForm = document.getElementById('clientLoginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleClientLogin);
    }

    // Initialize password visibility toggle
    var togglePassword = document.getElementById('togglePassword');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            var passwordInput = document.getElementById('password');
            if (passwordInput) {
                var type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('i').setAttribute('data-lucide', type === 'password' ? 'eye' : 'eye-off');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        });
    }

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

    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

/**
 * Handle client login form submission
 */
async function handleClientLogin(e) {
    e.preventDefault();

    var email = document.getElementById('email');
    var password = document.getElementById('password');
    var submitBtn = e.target.querySelector('button[type="submit"]');

    if (!email || !password) return;

    if (!email.value.trim() || !password.value.trim()) {
        showError('Please fill in all fields.');
        return;
    }

    // Disable submit button
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Signing in...';
    }

    try {
        var response = await fetch('../api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email: email.value.trim(),
                password: password.value,
                portal: 'client'
            })
        });

        var data = await response.json();

        if (data.success) {
            // Store session data
            if (data.token) {
                localStorage.setItem('session', JSON.stringify({
                    token: data.token,
                    user: data.user
                }));
            }

            showSuccess('Login successful! Redirecting...');

            setTimeout(function() {
                window.location.href = data.redirect || 'admin/client/dashboard.php';
            }, 1000);
        } else if (data.otp_required) {
            // Handle OTP requirement
            showOTPForm(email.value.trim());
        } else {
            showError(data.message || 'Invalid email or password.');
        }
    } catch (error) {
        console.error('[ClientLogin] Login error:', error);
        showError('Unable to connect to the server. Please try again.');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Sign In';
        }
    }
}

/**
 * Show OTP verification form
 */
function showOTPForm(email) {
    if (typeof Swal === 'undefined') return;

    Swal.fire({
        title: 'Verify Your Identity',
        html: '<p>We sent a verification code to your email.</p>' +
              '<input id="otpInput" class="swal2-input" placeholder="Enter OTP code" maxlength="6" style="text-align:center; letter-spacing:8px; font-size:20px;">',
        confirmButtonText: 'Verify',
        confirmButtonColor: '#2ca078',
        showCancelButton: true,
        allowOutsideClick: false,
        preConfirm: function() {
            var otp = document.getElementById('otpInput').value;
            if (!otp || otp.length < 4) {
                Swal.showValidationMessage('Please enter a valid OTP code.');
                return false;
            }
            return otp;
        }
    }).then(function(result) {
        if (result.isConfirmed) {
            verifyOTP(email, result.value);
        }
    });
}

/**
 * Verify OTP code
 */
async function verifyOTP(email, otp) {
    try {
        var response = await fetch('../api/verify_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, otp: otp })
        });

        var data = await response.json();

        if (data.success) {
            showSuccess('Verification successful! Redirecting...');
            setTimeout(function() {
                window.location.href = data.redirect || 'admin/client/dashboard.php';
            }, 1000);
        } else {
            showError(data.message || 'Invalid OTP code. Please try again.');
        }
    } catch (error) {
        console.error('[ClientLogin] OTP verification error:', error);
        showError('Verification failed. Please try again.');
    }
}

/**
 * Show error message
 */
function showError(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            confirmButtonColor: '#2ca078'
        });
    } else {
        alert(message);
    }
}

/**
 * Show success message
 */
function showSuccess(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: message,
            confirmButtonColor: '#2ca078',
            timer: 2000,
            showConfirmButton: false
        });
    }
}
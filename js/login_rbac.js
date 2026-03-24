// RBAC Authentication System
lucide.createIcons();

// Theme Toggle
const themeToggle = document.getElementById("themeToggle");
const body = document.body;

// Check for saved theme preference
const savedTheme = localStorage.getItem("theme");
if (savedTheme === "dark") {
    body.classList.add("dark-mode");
}

themeToggle.addEventListener("click", () => {
    body.classList.toggle("dark-mode");
    const isDark = body.classList.contains("dark-mode");
    localStorage.setItem("theme", isDark ? "dark" : "light");
});

// Form Switching
function switchForm(formType) {
    const loginForm = document.getElementById("loginForm");
    const registerForm = document.getElementById("registerForm");

    if (formType === "register") {
        loginForm.classList.remove("active");
        registerForm.classList.add("active");
    } else {
        registerForm.classList.remove("active");
        loginForm.classList.add("active");
    }

    // Reinitialize Lucide icons after form switch
    setTimeout(() => {
        window.lucide.createIcons();
    }, 100);
}

// Password Toggle
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.parentElement.querySelector(".toggle-password");
    const icon = button.querySelector(".eye-icon");

    if (input.type === "password") {
        input.type = "text";
        icon.setAttribute("data-lucide", "eye-off");
    } else {
        input.type = "password";
        icon.setAttribute("data-lucide", "eye");
    }

    window.lucide.createIcons();
}

function getPasswordStrength(password) {
    let score = 0;
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;

    let label = "Very weak";
    let cssClass = "weak";
    let width = "20%";
    if (score >= 4) {
        label = "Strong";
        cssClass = "strong";
        width = "80%";
    }
    if (score === 5) {
        label = "Very strong";
        cssClass = "very-strong";
        width = "100%";
    } else if (score === 3) {
        label = "Medium";
        cssClass = "medium";
        width = "60%";
    } else if (score === 2) {
        label = "Weak";
        cssClass = "weak";
        width = "40%";
    }

    return { score, label, cssClass, width };
}

function updatePasswordStrengthUI(password) {
    const bar = document.getElementById("passwordStrengthBar");
    const labelEl = document.getElementById("passwordStrengthLabel");
    if (!bar || !labelEl) return;

    if (!password) {
        bar.style.width = "0%";
        bar.style.backgroundColor = "#e5e7eb";
        labelEl.textContent = "Password strength: not evaluated yet";
        labelEl.className = "password-strength-label";
        return;
    }

    const { score, label, cssClass, width } = getPasswordStrength(password);
    bar.style.width = width;
    if (score <= 2) {
        bar.style.backgroundColor = "#ef4444";
    } else if (score === 3) {
        bar.style.backgroundColor = "#f59e0b";
    } else {
        bar.style.backgroundColor = "#22c55e";
    }

    labelEl.textContent = "Password strength: " + label;
    labelEl.className = "password-strength-label " + cssClass;
}

// Session Management
function setSession(token, user) {
    localStorage.setItem('session_token', token);
    localStorage.setItem('user', JSON.stringify(user));
}

function getSession() {
    const token = localStorage.getItem('session_token');
    const user = localStorage.getItem('user');

    if (token && user) {
        return {
            token: token,
            user: JSON.parse(user)
        };
    }
    return null;
}

function clearSession() {
    localStorage.removeItem('session_token');
    localStorage.removeItem('user');
}

function validateSession() {
    const session = getSession();

    if (!session) {
        return false;
    }

    // Validate session with server
    fetch('../api/auth.php?action=validate&token=' + session.token)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                clearSession();
                window.location.href = '../login.html';
            }
        })
        .catch(error => {
            console.error('Session validation error:', error);
            clearSession();
            window.location.href = '../login.html';
        });

    return true;
}

// Enhanced Login Handler with RBAC
function handleLogin(e) {
    e.preventDefault();

    const email = document.getElementById("loginEmail").value;
    const password = document.getElementById("loginPassword").value;

    console.log("Login:", { email, password });

    // Validation
    if (!email || !password) {
        Swal.fire({
            icon: "error",
            title: "Validation Error",
            text: "Please fill in all fields",
            confirmButtonColor: "#2ca078"
        });
        return;
    }

    // Show loading
    console.log("Signing in...");
    document.querySelector('button[type="submit"]').disabled = true;
    document.querySelector('button[type="submit"]').innerHTML = '<span>Signing in...</span>';

    // Authenticate with API
    fetch('../api/auth.php?action=login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            email: email,
            password: password
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Set session
                setSession(data.data.session_token, data.data.user);

                console.log(`Login successful! Redirecting to ${data.data.redirect}`);
                window.location.href = data.data.redirect;
            } else {
                // Restore button state
                const submitButton = document.querySelector('button[type="submit"]');
                submitButton.disabled = false;
                submitButton.innerHTML = '<span>Sign in</span><i data-lucide="arrow-right" class="btn-icon"></i>';
                window.lucide.createIcons();

                Swal.fire({
                    icon: "error",
                    title: "Login failed",
                    text: data.message || "Invalid email or password",
                    confirmButtonColor: "#2ca078"
                });
            }
        })
        .catch(error => {
            console.error('Login error:', error);

            // Restore button state
            const submitButton = document.querySelector('button[type="submit"]');
            submitButton.disabled = false;
            submitButton.innerHTML = '<span>Sign in</span><i data-lucide="arrow-right" class="btn-icon"></i>';
            window.lucide.createIcons();

            Swal.fire({
                icon: "error",
                title: "Login error",
                text: "An error occurred during login. Please try again.",
                confirmButtonColor: "#2ca078"
            });
        });
}

// Enhanced Register Handler
function handleRegister(e) {
    e.preventDefault();

    const name = document.getElementById("registerName").value;
    const email = document.getElementById("registerEmail").value;
    const password = document.getElementById("registerPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    const acceptTerms = document.getElementById("acceptTerms").checked;

    // Validation
    const strength = getPasswordStrength(password);
    if (strength.score < 3) {
        Swal.fire({
            icon: "error",
            title: "Weak password",
            text: "Password is too weak (" + strength.label + "). Use upper, lower, number and symbol.",
            confirmButtonColor: "#2ca078"
        });
        return;
    }

    if (password !== confirmPassword) {
        Swal.fire({
            icon: "error",
            title: "Passwords do not match",
            text: "Please make sure both password fields are identical.",
            confirmButtonColor: "#2ca078"
        });
        return;
    }

    if (password.length < 8) {
        Swal.fire({
            icon: "error",
            title: "Password too short",
            text: "Password must be at least 8 characters long.",
            confirmButtonColor: "#2ca078"
        });
        return;
    }

    if (!acceptTerms) {
        Swal.fire({
            icon: "error",
            title: "Accept the terms",
            text: "Please accept the Terms and Privacy Policy to continue.",
            confirmButtonColor: "#2ca078"
        });
        return;
    }

    // Show loading
    Swal.fire({
        title: "Creating account...",
        text: "Please wait while we create your account",
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Register with API
    fetch('../api/auth.php?action=register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            name: name,
            email: email,
            password: password
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: "success",
                    title: "Account created!",
                    text: data.message || "Your account has been created successfully. Please wait for admin approval.",
                    confirmButtonColor: "#2ca078",
                    confirmButtonText: "Go to login"
                }).then(() => {
                    switchForm('login');
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Registration failed",
                    text: data.message || "An error occurred during registration",
                    confirmButtonColor: "#2ca078"
                });
            }
        })
        .catch(error => {
            console.error('Registration error:', error);
            Swal.fire({
                icon: "error",
                title: "Registration error",
                text: "An error occurred during registration. Please try again.",
                confirmButtonColor: "#2ca078"
            });
        });
}

// Create Admin User
function createAdminUser() {
    Swal.fire({
        title: 'Create Admin User',
        html: `
            <div style="text-align: left;">
                <input id="adminName" class="swal2-input" placeholder="Admin Name" style="width: 100%; margin: 10px 0;">
                <input id="adminEmail" class="swal2-input" placeholder="Admin Email" style="width: 100%; margin: 10px 0;">
                <input id="adminPassword" class="swal2-input" type="password" placeholder="Admin Password" style="width: 100%; margin: 10px 0;">
                <input id="adminKey" class="swal2-input" placeholder="Admin Creation Key" style="width: 100%; margin: 10px 0;">
            </div>
        `,
        confirmButtonText: 'Create Admin',
        showCancelButton: true,
        confirmButtonColor: '#2ca078',
        preConfirm: () => {
            const name = document.getElementById('adminName').value;
            const email = document.getElementById('adminEmail').value;
            const password = document.getElementById('adminPassword').value;
            const adminKey = document.getElementById('adminKey').value;

            if (!name || !email || !password || !adminKey) {
                Swal.showValidationMessage('Please fill all fields');
                return false;
            }

            return { name, email, password, adminKey };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { name, email, password, adminKey } = result.value;

            // Create admin user
            fetch('../api/auth.php?action=create_admin', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    password: password,
                    admin_key: adminKey
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Admin Created!',
                            text: 'Admin user has been created successfully.',
                            confirmButtonColor: '#2ca078'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Creation Failed',
                            text: data.message || 'Failed to create admin user.',
                            confirmButtonColor: '#2ca078'
                        });
                    }
                })
                .catch(error => {
                    console.error('Admin creation error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Creation Error',
                        text: 'An error occurred while creating admin user.',
                        confirmButtonColor: '#2ca078'
                    });
                });
        }
    });
}

// OTP Functionality (simplified for demo)
let otpTimerInterval;

function generateOtpInputs() {
    const otpInputsContainer = document.getElementById('otpInputs');
    if (!otpInputsContainer) return;

    otpInputsContainer.innerHTML = '';
    for (let i = 0; i < 6; i++) {
        const input = document.createElement('input');
        input.type = 'text';
        input.maxLength = 1;
        input.className = 'otp-input';
        input.dataset.index = i;
        input.inputMode = 'numeric';
        input.pattern = '\d*';

        input.addEventListener('input', (e) => {
            const value = e.target.value;
            if (value.length === 1) {
                e.target.classList.add('filled');
                const nextInput = e.target.nextElementSibling;
                if (nextInput && nextInput.classList.contains('otp-input')) {
                    nextInput.focus();
                }
            } else if (value.length === 0) {
                e.target.classList.remove('filled');
            }
        });

        otpInputsContainer.appendChild(input);
    }

    const firstInput = otpInputsContainer.querySelector('.otp-input');
    if (firstInput) firstInput.focus();
}

// Initialize
document.addEventListener("DOMContentLoaded", () => {
    window.lucide.createIcons();

    // Check if user is already logged in
    const session = getSession();
    if (session) {
        validateSession();
    }

    // Initialize password strength
    const registerPasswordInput = document.getElementById("registerPassword");
    if (registerPasswordInput) {
        registerPasswordInput.addEventListener("input", (e) => {
            updatePasswordStrengthUI(e.target.value);
        });
        updatePasswordStrengthUI(registerPasswordInput.value || "");
    }

    // Add admin creation button (hidden feature)
    document.addEventListener('keydown', (e) => {
        // Ctrl+Shift+A to open admin creation
        if (e.ctrlKey && e.shiftKey && e.key === 'A') {
            e.preventDefault();
            createAdminUser();
        }
    });
});

// Add admin creation hint to login form footer
document.addEventListener('DOMContentLoaded', () => {
    const loginFooter = document.querySelector('#loginForm .form-footer');
    if (loginFooter) {
        const hintText = document.createElement('p');
        hintText.className = 'footer-text';
        hintText.style.fontSize = '0.8rem';
        hintText.style.color = '#666';
        hintText.style.marginTop = '10px';
        loginFooter.appendChild(hintText);
    }
});

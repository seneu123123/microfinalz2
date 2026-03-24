// HARDCODED PA TO
lucide.createIcons();
// Theme Toggle
const themeToggle = document.getElementById("themeToggle")
const body = document.body

// Check for saved theme preference
const savedTheme = localStorage.getItem("theme")
if (savedTheme === "dark") {
body.classList.add("dark-mode")
}

themeToggle.addEventListener("click", () => {
body.classList.toggle("dark-mode")
const isDark = body.classList.contains("dark-mode")
localStorage.setItem("theme", isDark ? "dark" : "light")
})

// Form Switching
function switchForm(formType) {
const loginForm = document.getElementById("loginForm")
const registerForm = document.getElementById("registerForm")

if (formType === "register") {
loginForm.classList.remove("active")
registerForm.classList.add("active")
} else {
registerForm.classList.remove("active")
loginForm.classList.add("active")
}

// Reinitialize Lucide icons after form switch
setTimeout(() => {fix 
window.lucide.createIcons()
}, 100)
}

// Password Toggle
function togglePassword(inputId) {
const input = document.getElementById(inputId)
const button = input.parentElement.querySelector(".toggle-password")
const icon = button.querySelector(".eye-icon")

if (input.type === "password") {
input.type = "text"
icon.setAttribute("data-lucide", "eye-off")
} else {
input.type = "password"
icon.setAttribute("data-lucide", "eye")
}

window.lucide.createIcons()
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

const DEMO_OTP_CODE = "123456";

function handleLogin(e) {
    e.preventDefault()

    const email = document.getElementById("loginEmail").value
    const password = document.getElementById("loginPassword").value

    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]')
    const originalText = submitBtn.innerHTML
    submitBtn.disabled = true
    submitBtn.innerHTML = '<i data-lucide="loader" class="animate-spin"></i> Signing in...'
    window.lucide.createIcons()

    // Call login API
    const formData = new FormData()
    formData.append('email', email)
    formData.append('password', password)

    fetch('api/login.php?action=login', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Store user data for OTP verification
            sessionStorage.setItem('pendingUser', JSON.stringify(data.data.user))
            
            // Show OTP popup
            showOtpPopup()
            
            Swal.fire({
                icon: 'success',
                title: 'Login Successful',
                text: data.message,
                confirmButtonColor: '#2ca078',
                timer: 3000,
                timerProgressBar: true
            })
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: data.message,
                confirmButtonColor: '#2ca078'
            })
        }
    })
    .catch(error => {
        console.error('Login error:', error)
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred during login. Please try again.',
            confirmButtonColor: '#2ca078'
        })
    })
    .finally(() => {
        // Restore button state
        submitBtn.disabled = false
        submitBtn.innerHTML = originalText
        window.lucide.createIcons()
    })
}

// Register Form Handler
function handleRegister(e) {
e.preventDefault()

const name = document.getElementById("registerName").value
const email = document.getElementById("registerEmail").value
const password = document.getElementById("registerPassword").value
const confirmPassword = document.getElementById("confirmPassword").value
const acceptTerms = document.getElementById("acceptTerms").checked
const otpCode = document.getElementById("otpCode").value.trim()

// Validation
const strength = getPasswordStrength(password)
if (strength.score < 3) {
Swal.fire({
icon: "error",
title: "Weak password",
text: "Password is too weak (" + strength.label + "). Use upper, lower, number and symbol.",
confirmButtonColor: "#2ca078"
})
return
}

if (password !== confirmPassword) {
Swal.fire({
icon: "error",
title: "Passwords do not match",
text: "Please make sure both password fields are identical.",
confirmButtonColor: "#2ca078"
})
return
}

if (password.length < 8) {
Swal.fire({
icon: "error",
title: "Password too short",
text: "Password must be at least 8 characters long.",
confirmButtonColor: "#2ca078"
})
return
}

if (!acceptTerms) {
Swal.fire({
icon: "error",
title: "Accept the terms",
text: "Please accept the Terms and Privacy Policy to continue.",
confirmButtonColor: "#2ca078"
})
return
}

if (otpCode !== DEMO_OTP_CODE) {
Swal.fire({
icon: "error",
title: "Invalid OTP",
text: "For this demo UI, please use the OTP code 123456.",
confirmButtonColor: "#2ca078"
})
return
}

console.log("Register:", { name, email, password })

// FAKE SUCCESSFUL
Swal.fire({
icon: "success",
title: "Account created (demo)",
html: "Name: <strong>" + name + "</strong><br>Email: <strong>" + email + "</strong>",
confirmButtonColor: "#2ca078",
confirmButtonText: "Continue to dashboard"
}).then(() => {
window.location.href = "admin/dashboard.php"
})
}

// OTP Functionality
let otpTimerInterval;

// Generate OTP inputs
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
        
        // Handle input
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
        
        // Handle backspace
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && e.target.value === '') {
                const prevInput = e.target.previousElementSibling;
                if (prevInput && prevInput.classList.contains('otp-input')) {
                    prevInput.focus();
                }
            }
        });
        
        // Prevent non-numeric input
        input.addEventListener('keypress', (e) => {
            if (!/\d/.test(e.key)) {
                e.preventDefault();
            }
        });
        
        otpInputsContainer.appendChild(input);
    }
    
    // Focus first input
    const firstInput = otpInputsContainer.querySelector('.otp-input');
    if (firstInput) firstInput.focus();
}

// Show OTP popup
function showOtpPopup() {
    const otpOverlay = document.getElementById('otpOverlay');
    if (!otpOverlay) return;
    
    otpOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    generateOtpInputs();
    startOtpTimer();
}

// Hide OTP popup
function hideOtpPopup() {
    const otpOverlay = document.getElementById('otpOverlay');
    if (!otpOverlay) return;
    
    otpOverlay.classList.remove('active');
    document.body.style.overflow = '';
    
    if (otpTimerInterval) {
        clearInterval(otpTimerInterval);
    }
}

// Start OTP timer
function startOtpTimer() {
    const otpTimer = document.getElementById('otpTimer');
    const resendOtp = document.getElementById('resendOtp');
    if (!otpTimer || !resendOtp) return;
    
    if (otpTimerInterval) {
        clearInterval(otpTimerInterval);
    }
    
    let timeLeft = 120;
    otpTimer.textContent = `(02:${timeLeft < 10 ? '0' + timeLeft : timeLeft})`;
    resendOtp.style.display = 'none';
    
    otpTimerInterval = setInterval(() => {
        timeLeft--;
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        otpTimer.textContent = `(${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')})`;
        
        if (timeLeft <= 0) {
            clearInterval(otpTimerInterval);
            otpTimer.textContent = '';
            resendOtp.style.display = 'inline';
        }
    }, 1000);
}

// Initialize OTP functionality
function initOtp() {
    const otpOverlay = document.getElementById('otpOverlay');
    const verifyEmailBtn = document.getElementById('verifyEmailBtn');
    const closeOtpPopup = document.getElementById('closeOtpPopup');
    const otpForm = document.getElementById('otpForm');
    const resendOtp = document.getElementById('resendOtp');
    const registerEmail = document.getElementById('registerEmail');
    
    // Show OTP section when email is filled
    if (registerEmail) {
        registerEmail.addEventListener('blur', function() {
            const otpSection = document.getElementById('otpSection');
            if (this.value && this.value.includes('@') && otpSection) {
                otpSection.style.display = 'block';
            }
        });
    }
    
    // Verify Email Button
    if (verifyEmailBtn) {
        verifyEmailBtn.addEventListener('click', () => {
            const email = document.getElementById('registerEmail').value;
            if (!email) {
                Swal.fire({
                    icon: 'error',
                    title: 'Email Required',
                    text: 'Please enter your email address first',
                    confirmButtonColor: '#2ca078'
                });
                return;
            }
            
            // Show loading state
            verifyEmailBtn.disabled = true;
            verifyEmailBtn.innerHTML = '<i data-lucide="loader" class="animate-spin"></i> Sending OTP...';
            window.lucide.createIcons();
            
            // Simulate API call
            setTimeout(() => {
                verifyEmailBtn.disabled = false;
                verifyEmailBtn.innerHTML = '<i data-lucide="check"></i> Verify Email';
                window.lucide.createIcons();
                showOtpPopup();
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'OTP Sent',
                    text: `A 6-digit code has been sent to ${email}`,
                    confirmButtonColor: '#2ca078',
                    timer: 3000,
                    timerProgressBar: true
                });
            }, 1000);
        });
    }
    
    // Close OTP Popup
    if (closeOtpPopup) {
        closeOtpPopup.addEventListener('click', hideOtpPopup);
    }
    
    // OTP Form Submission
    if (otpForm) {
        otpForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Get OTP code
            const otpInputsContainer = document.getElementById('otpInputs');
            if (!otpInputsContainer) return;
            
            const inputs = otpInputsContainer.querySelectorAll('.otp-input');
            let otpCode = '';
            let isValid = true;
            
            inputs.forEach(input => {
                if (input.value === '') {
                    isValid = false;
                    input.classList.add('error');
                } else {
                    input.classList.remove('error');
                    otpCode += input.value;
                }
            });
            
            if (!isValid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid OTP',
                    text: 'Please fill in all digits',
                    confirmButtonColor: '#2ca078'
                });
                return;
            }
            
            // Get pending user data
            const pendingUser = JSON.parse(sessionStorage.getItem('pendingUser') || '{}');
            
            if (!pendingUser.id) {
                Swal.fire({
                    icon: 'error',
                    title: 'Session Error',
                    text: 'Please login again',
                    confirmButtonColor: '#2ca078'
                });
                hideOtpPopup();
                return;
            }
            
            // Show loading state
            const verifyBtn = document.getElementById('verifyOtpBtn');
            const originalText = verifyBtn.innerHTML;
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<i data-lucide="loader" class="animate-spin"></i> Verifying...';
            window.lucide.createIcons();
            
            // Verify OTP with API
            const formData = new FormData();
            formData.append('user_id', pendingUser.id);
            formData.append('otp', otpCode);
            
            fetch('api/login.php?action=verify_otp', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear pending user data
                    sessionStorage.removeItem('pendingUser');
                    
                    // Hide OTP popup
                    hideOtpPopup();
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Successful',
                        text: 'Welcome back, ' + data.data.user.name + '!',
                        confirmButtonColor: '#2ca078',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        // Redirect to dashboard
                        window.location.href = data.data.redirect_url;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid OTP',
                        text: data.message,
                        confirmButtonColor: '#2ca078'
                    });
                }
            })
            .catch(error => {
                console.error('OTP verification error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred during verification. Please try again.',
                    confirmButtonColor: '#2ca078'
                });
            })
            .finally(() => {
                // Restore button state
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = originalText;
                window.lucide.createIcons();
            });
        });
    }
    
    // Resend OTP
    if (resendOtp) {
        resendOtp.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Get pending user data
            const pendingUser = JSON.parse(sessionStorage.getItem('pendingUser') || '{}');
            
            if (!pendingUser.email) {
                Swal.fire({
                    icon: 'error',
                    title: 'Session Error',
                    text: 'Please login again',
                    confirmButtonColor: '#2ca078'
                });
                hideOtpPopup();
                return;
            }
            
            // Show loading state
            resendOtp.style.display = 'none';
            const otpTimer = document.getElementById('otpTimer');
            if (otpTimer) {
                otpTimer.textContent = 'Sending...';
            }

            // Resend OTP with API
            const formData = new FormData();
            formData.append('email', pendingUser.email);
            
            fetch('api/login.php?action=resend_otp', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    generateOtpInputs();
                    startOtpTimer();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'New OTP Sent',
                        text: data.message,
                        confirmButtonColor: '#2ca078',
                        timer: 3000,
                        timerProgressBar: true
                    });
                } else {
                    // Show error and restore resend button
                    Swal.fire({
                        icon: 'error',
                        title: 'Resend Failed',
                        text: data.message,
                        confirmButtonColor: '#2ca078'
                    });
                    
                    resendOtp.style.display = 'inline';
                    if (otpTimer) {
                        otpTimer.textContent = '';
                    }
                }
            })
            .catch(error => {
                console.error('Resend OTP error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while resending OTP. Please try again.',
                    confirmButtonColor: '#2ca078'
                });
                
                resendOtp.style.display = 'inline';
                if (otpTimer) {
                    otpTimer.textContent = '';
                }
            });
        });
    }
}

document.addEventListener("DOMContentLoaded", () => {
    window.lucide.createIcons();
    initOtp();

    const registerPasswordInput = document.getElementById("registerPassword")
if (registerPasswordInput) {
registerPasswordInput.addEventListener("input", (e) => {
updatePasswordStrengthUI(e.target.value)
})
updatePasswordStrengthUI(registerPasswordInput.value || "")
}

const sendOtpButton = document.getElementById("sendOtpButton")
if (sendOtpButton) {
sendOtpButton.addEventListener("click", () => {
Swal.fire({
icon: "info",
title: "Demo OTP sent (UI only)",
text: "Use code: " + DEMO_OTP_CODE,
confirmButtonColor: "#2ca078"
})
})
}
})

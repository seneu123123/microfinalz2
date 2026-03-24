// Fallback Authentication System (works without web server)
// This provides a temporary solution when Apache isn't running

// Fallback user data (matches database setup)
const fallbackUsers = [
    // Admin users
    {
        email: 'admin@logistics.com',
        password: 'Admin123!@#',
        name: 'System Administrator',
        role: 'admin',
        status: 'active'
    },
    {
        email: 'finance@microfinance.com',
        password: 'Finance123!@#',
        name: 'Finance Manager',
        role: 'admin',
        status: 'active'
    },
    {
        email: 'operations@company.admin',
        password: 'Operations123!@#',
        name: 'Operations Manager',
        role: 'admin',
        status: 'active'
    },
    // Vendor users
    {
        email: 'vendor@supplier.com',
        password: 'Vendor123!@#',
        name: 'Test Vendor User',
        role: 'vendor_user',
        status: 'active'
    },
    {
        email: 'partner@partner.com',
        password: 'Partner123!@#',
        name: 'Partner User',
        role: 'vendor_user',
        status: 'active'
    },
    {
        email: 'supplier@vendor.com',
        password: 'Supplier123!@#',
        name: 'Supplier User',
        role: 'vendor_user',
        status: 'active'
    }
];

// Fallback role detection
function fallbackDetectRole(email) {
    const domain = email.split('@')[1].toLowerCase();

    const adminDomains = ['admin.com', 'microfinance.com', 'logistics.com', 'company.admin'];
    const vendorDomains = ['vendor.com', 'supplier.com', 'partner.com'];

    if (adminDomains.some(adminDomain => domain.includes(adminDomain))) {
        return 'admin';
    } else if (vendorDomains.some(vendorDomain => domain.includes(vendorDomain))) {
        return 'vendor_user';
    } else {
        return 'vendor_user';
    }
}

// Fallback authentication function
function fallbackAuthenticate(email, password) {
    const user = fallbackUsers.find(u => u.email === email && u.password === password);

    if (user && user.status === 'active') {
        return {
            success: true,
            data: {
                user: {
                    id: 1,
                    name: user.name,
                    email: user.email,
                    role: user.role
                },
                session_token: 'fallback-token-' + Date.now(),
                redirect: user.role === 'admin' ? 'admin/dashboard.php' : 'vendor_user/dashboard_user.html'
            },
            message: 'Login successful'
        };
    }

    return {
        success: false,
        message: 'Invalid email or password'
    };
}

// Enhanced login handler with fallback
function handleLoginWithFallback(e) {
    e.preventDefault();

    const email = document.getElementById("loginEmail").value;
    const password = document.getElementById("loginPassword").value;

    console.log("Login attempt:", { email, password });

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

    // Try API first, then fallback
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
        .then(response => {
            if (!response.ok) {
                throw new Error('API not accessible');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // API worked - set session and redirect
                setSession(data.data.session_token, data.data.user);

                Swal.fire({
                    icon: "success",
                    title: "Login successful!",
                    text: `Welcome back, ${data.data.user.name}! Your role: ${data.data.user.role}`,
                    confirmButtonColor: "#2ca078",
                    confirmButtonText: "Continue to dashboard"
                }).then(() => {
                    window.location.href = data.data.redirect;
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Login failed",
                    text: data.message || "Invalid email or password",
                    confirmButtonColor: "#2ca078"
                });
            }
        })
        .catch(error => {
            console.log('API not accessible, using fallback authentication');

            // Use fallback authentication
            const result = fallbackAuthenticate(email, password);

            if (result.success) {
                // Set session with fallback token
                setSession(result.data.session_token, result.data.user);

                // Redirect directly to dashboard (no popup)
                console.log(`Login successful! Redirecting to ${result.data.redirect}`);
                window.location.href = result.data.redirect;
            } else {
                // Restore button state
                const submitButton = document.querySelector('button[type="submit"]');
                submitButton.disabled = false;
                submitButton.innerHTML = '<span>Sign in</span><i data-lucide="arrow-right" class="btn-icon"></i>';
                window.lucide.createIcons();

                Swal.fire({
                    icon: "error",
                    title: "Login failed",
                    text: result.message,
                    confirmButtonColor: "#2ca078"
                });
            }
        });
}

// Replace the original handleLogin function
function handleLogin(e) {
    handleLoginWithFallback(e);
}

console.log('Fallback authentication system loaded');

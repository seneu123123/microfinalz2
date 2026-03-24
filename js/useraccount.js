/**
 * User Account Management - v1.0
 * Handles modal, theme toggle, sidebar, and account actions (Edit/Delete)
 * Last Updated: February 8, 2026
 */

// Fallback for SweetAlert2 when blocked by browser tracking prevention
if (typeof window.Swal === 'undefined') {
    window.Swal = {
        fire: (opts) => {
            alert((opts.title || '') + '\n' + (opts.text || ''));
            return Promise.resolve({ isConfirmed: true });
        },
        showLoading: () => { },
        close: () => { }
    };
}

// Fallback for Lucide icons when blocked by browser tracking prevention
if (typeof window.lucide === 'undefined') {
    window.lucide = { createIcons: () => { } };
}

function initUserAccount() {
    const body = document.body;
    const lucide = window.lucide;

    // =====================
    // 1. THEME TOGGLE
    // =====================
    const themeToggle = document.getElementById("themeToggle");
    if (themeToggle) {
        const savedTheme = localStorage.getItem("theme");
        if (savedTheme === "dark") body.classList.add("dark-mode");

        themeToggle.addEventListener("click", () => {
            body.classList.toggle("dark-mode");
            localStorage.setItem("theme", body.classList.contains("dark-mode") ? "dark" : "light");
        });
    }

    // =====================
    // 2. SIDEBAR TOGGLE
    // =====================
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.getElementById("sidebar");
    const mobileMenuBtn = document.getElementById("mobileMenuBtn");

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
            localStorage.setItem("sidebarCollapsed", sidebar.classList.contains("collapsed"));
        });

        if (localStorage.getItem("sidebarCollapsed") === "true") {
            sidebar.classList.add("collapsed");
        }
    }

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener("click", () => sidebar.classList.toggle("mobile-open"));
    }

    // =====================
    // 3. SUBMENU TOGGLE
    // =====================
    document.querySelectorAll(".nav-item.has-submenu").forEach((item) => {
        item.addEventListener("click", () => {
            const module = item.getAttribute("data-module");
            const submenu = document.getElementById(`submenu-${module}`);
            if (submenu) {
                submenu.classList.toggle("active");
                item.classList.toggle("active");
            }
        });
    });

    // =====================
    // 4. MODAL MANAGEMENT
    // =====================
    const modal = document.getElementById("addUserModal");
    const addUserBtn = document.getElementById("addUserBtn");
    const closeModalBtn = document.getElementById("closeModalBtn");
    const cancelCreate = document.getElementById("cancelCreate");
    const createUserForm = document.getElementById("createUserForm");

    // Helper to open modal
    const openModal = (shouldReset = true) => {
        if (!modal || !createUserForm) {
            console.error("Modal or form not found");
            return;
        }
        if (shouldReset) {
            createUserForm.reset();
            document.getElementById("accountId").value = ""; // Ensure ID is cleared on reset
        }
        modal.style.display = "flex"; // Keep inline style for reliability
        modal.classList.add("show");
        modal.setAttribute("aria-hidden", "false");
        console.log("Modal opened");
    };

    // Helper to close modal
    const closeModal = () => {
        if (!modal || !createUserForm) return;
        modal.style.display = "none";
        modal.classList.remove("show");
        modal.setAttribute("aria-hidden", "true");
        createUserForm.reset();
        document.getElementById("accountId").value = "";
    };

    // Add button click
    if (addUserBtn) {
        addUserBtn.addEventListener("click", openModal);
    }

    // Global fallback for inline onclick
    window.openAddAccountModal = openModal;

    // Close buttons
    if (closeModalBtn) closeModalBtn.addEventListener("click", closeModal);
    if (cancelCreate) cancelCreate.addEventListener("click", closeModal);

    // Close when clicking outside modal
    if (modal) {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) closeModal();
        });
    }

    // =====================
    // 5. FORM SUBMISSION
    // =====================
    if (createUserForm) {
        createUserForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            const username = document.getElementById("username").value.trim();
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirmPassword").value;
            const rolesSelect = document.getElementById("roles");
            const accountStatus = document.getElementById("accountStatus").value;

            const accountId = document.getElementById("accountId").value;
            const isEdit = !!accountId;
            console.log("Form Submit: AccountID:", accountId, "isEdit:", isEdit);

            // Validate passwords match (only if password is provided or it's a new account)
            if ((!isEdit || password) && password !== confirmPassword) {
                await Swal.fire({
                    icon: "error",
                    title: "Password Mismatch",
                    text: "Passwords do not match",
                    confirmButtonColor: "#2ca078"
                });
                return;
            }

            // Validate roles selected
            const roles = Array.from(rolesSelect.selectedOptions).map(option => option.value);
            if (roles.length === 0) {
                await Swal.fire({
                    icon: "error",
                    title: "Roles Required",
                    text: "Please select at least one role",
                    confirmButtonColor: "#2ca078"
                });
                return;
            }

            // Show loading
            Swal.fire({
                title: isEdit ? "Updating Account..." : "Creating Account...",
                text: "Please wait",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                // Use URLSearchParams to avoid multipart boundaries issues
                const params = new URLSearchParams();

                const actionType = isEdit ? "update_account" : "add_account";
                params.append("action", actionType);
                console.log("Submitting action:", actionType, "AccountID:", accountId);

                if (isEdit) {
                    params.append("account_id", accountId);
                }

                params.append("username", username);
                params.append("email", email);
                if (password) { // Only send password if provided
                    params.append("password", password);
                    params.append("confirm_password", confirmPassword);
                }
                params.append("account_status", accountStatus);
                roles.forEach(roleId => {
                    params.append("roles[]", roleId);
                });

                console.log("Submitting with URLSearchParams:", params.toString());

                const response = await fetch("account_action.php", {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params
                });

                const result = await response.json();

                if (result.success) {
                    closeModal(); // Close modal first
                    await Swal.fire({
                        icon: "success",
                        title: isEdit ? "Account Updated" : "Account Created",
                        text: isEdit ? "Account has been updated successfully" : "New account has been created successfully",
                        confirmButtonColor: "#2ca078"
                    });
                    location.reload();
                } else {
                    await Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: result.message || "Failed to create account",
                        confirmButtonColor: "#2ca078"
                    });
                }
            } catch (error) {
                console.error("Form submission error:", error);
                await Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Something went wrong. Please try again.",
                    confirmButtonColor: "#2ca078"
                });
            }
        });
    }

    // =====================
    // 6. PASSWORD TOGGLE
    // =====================
    window.togglePassword = function (inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        const parent = input.parentElement;
        const icon = parent?.querySelector(".eye-icon");

        if (input.type === "password") {
            input.type = "text";
            if (icon) icon.setAttribute("data-lucide", "eye-off");
        } else {
            input.type = "password";
            if (icon) icon.setAttribute("data-lucide", "eye");
        }

        lucide.createIcons();
    };

    // =====================
    // 7. EDIT & DELETE
    // =====================
    window.editUser = async function (accountId) {
        try {
            const response = await fetch(`account_action.php?action=get_account&account_id=${accountId}`);
            const result = await response.json();

            if (result.success) {
                const data = result.data;

                // Populate form
                document.getElementById("accountId").value = data.AccountID;
                document.getElementById("username").value = data.Username;
                document.getElementById("email").value = data.Email;
                document.getElementById("accountStatus").value = data.AccountStatus;

                // Handle roles
                const rolesSelect = document.getElementById("roles");
                Array.from(rolesSelect.options).forEach(option => {
                    option.selected = data.Roles.includes(parseInt(option.value));
                });

                // Update UI for Edit mode
                const modalTitle = document.getElementById('modalTitle');
                const submitLabel = document.getElementById('submitBtnLabel');
                if (modalTitle) modalTitle.textContent = 'Edit Account';
                if (submitLabel) submitLabel.textContent = 'Update Account';

                // Password fields are optional during edit
                document.getElementById("password").required = false;
                document.getElementById("confirmPassword").required = false;

                openModal(false);
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: result.message || "Failed to fetch account details",
                    confirmButtonColor: "#2ca078"
                });
            }
        } catch (error) {
            console.error("Error fetching account:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "An error occurred while fetching account details",
                confirmButtonColor: "#2ca078"
            });
        }
    };

    // Update openModal to reset to Add mode
    const openAddModal = () => {
        document.getElementById("accountId").value = "";
        const modalTitle = document.getElementById('modalTitle');
        const submitLabel = document.getElementById('submitBtnLabel');
        if (modalTitle) modalTitle.textContent = 'Add New Account';
        if (submitLabel) submitLabel.textContent = 'Create Account';
        document.getElementById("password").required = true;
        document.getElementById("confirmPassword").required = true;
        openModal(true);
    };

    // Override the click handler for add button
    if (addUserBtn) {
        // Clone node to strip all existing event listeners (including the one from line 115)
        const newBtn = addUserBtn.cloneNode(true);
        addUserBtn.parentNode.replaceChild(newBtn, addUserBtn);
        newBtn.addEventListener("click", openAddModal);
    }

    async function performDelete(id, username) {
        const confirmed = await Swal.fire({
            icon: "warning",
            title: "Delete Account",
            text: `Are you sure you want to delete the account "${username}"? This action cannot be undone.`,
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Delete",
            cancelButtonText: "Cancel"
        });

        if (!confirmed.isConfirmed) return;

        try {
            const formData = new FormData();
            formData.append("action", "delete_account");
            formData.append("account_id", id);

            const response = await fetch("account_action.php", {
                method: "POST",
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                await Swal.fire({
                    icon: "success",
                    title: "Account Deleted",
                    text: "Account has been deleted successfully",
                    confirmButtonColor: "#2ca078"
                });
                location.reload();
            } else {
                await Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: result.message || "Failed to delete account",
                    confirmButtonColor: "#2ca078"
                });
            }
        } catch (error) {
            console.error("Delete error:", error);
            await Swal.fire({
                icon: "error",
                title: "Error",
                text: "Something went wrong. Please try again.",
                confirmButtonColor: "#2ca078"
            });
        }
    }

    // Table delegated click handler
    const usersTable = document.getElementById("usersTable");
    if (usersTable) {
        usersTable.addEventListener("click", (e) => {
            const editBtn = e.target.closest(".btn-edit");
            if (editBtn) {
                const id = editBtn.getAttribute("data-account-id");
                editUser(parseInt(id, 10));
                return;
            }

            const delBtn = e.target.closest(".btn-delete");
            if (delBtn) {
                const id = delBtn.getAttribute("data-account-id");
                const username = delBtn.getAttribute("data-username");
                performDelete(id, username);
                return;
            }
        });
    }

    // Initialize icons
    lucide.createIcons();
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUserAccount);
} else {
    initUserAccount();
}
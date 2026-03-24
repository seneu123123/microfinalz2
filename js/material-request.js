// Material Request Management
class MaterialRequestManager {
    constructor() {
        this.requests = [];
        this.vendors = [];
        this.currentRequest = null;
        this.isEditing = false;
        this.init();
    }

    async init() {
        await this.loadVendors();
        await this.loadRequests();
        this.setupEventListeners();
        this.calculateTotals();
    }

    async loadVendors() {
        try {
            const response = await fetch('./api/material-request.php?action=vendors');
            const result = await response.json();

            if (result.success) {
                this.vendors = result.data;
                this.populateVendorSelect();
            } else {
                console.error('Failed to load vendors:', result.message);
            }
        } catch (error) {
            console.error('Error loading vendors:', error);
        }
    }

    async loadRequests() {
        try {
            const response = await fetch('./api/material-request.php?action=list');
            const result = await response.json();

            if (result.success) {
                this.requests = result.data;
                this.renderRequestsTable();
            } else {
                console.error('Failed to load requests:', result.message);
            }
        } catch (error) {
            console.error('Error loading requests:', error);
        }
    }

    populateVendorSelect() {
        const vendorSelect = document.getElementById('vendorSelect');
        vendorSelect.innerHTML = '<option value="">Select Vendor</option>';

        this.vendors.forEach(vendor => {
            const option = document.createElement('option');
            option.value = vendor.vendor_id;
            option.textContent = `${vendor.vendor_id} - ${vendor.vendor_name}`;
            vendorSelect.appendChild(option);
        });
    }

    setupEventListeners() {
        // Form submission
        document.getElementById('materialRequestForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveRequest();
        });

        // Vendor selection change
        document.getElementById('vendorSelect').addEventListener('change', (e) => {
            const selectedVendor = this.vendors.find(v => v.vendor_id == e.target.value);
            if (selectedVendor) {
                document.getElementById('vendorName').value = selectedVendor.vendor_name;
                document.getElementById('vendorAddress').value = selectedVendor.address || '';
            }
        });

        // Calculate totals on input change
        ['quantity', 'price'].forEach(field => {
            document.getElementById(field).addEventListener('input', () => {
                this.calculateTotals();
            });
        });

        // Clear form button
        document.getElementById('clearForm').addEventListener('click', () => {
            this.clearForm();
        });
    }

    calculateTotals() {
        const quantity = parseFloat(document.getElementById('quantity').value) || 0;
        const price = parseFloat(document.getElementById('price').value) || 0;
        const total = quantity * price;

        document.getElementById('totalAmount').value = total.toFixed(2);
    }

    async saveRequest() {
        const formData = this.getFormData();

        if (!this.validateForm(formData)) {
            return;
        }

        try {
            const action = this.isEditing ? 'update' : 'create';
            const url = `./api/material-request.php?action=${action}`;

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('success', this.isEditing ? 'Material request updated successfully!' : 'Material request created successfully!');
                this.clearForm();
                await this.loadRequests();
            } else {
                this.showAlert('error', result.message || 'Failed to save material request');
            }
        } catch (error) {
            console.error('Error saving request:', error);
            this.showAlert('error', 'An error occurred while saving the request');
        }
    }

    getFormData() {
        return {
            id: document.getElementById('requestId').value,
            vendor_id: document.getElementById('vendorSelect').value,
            vendor_name: document.getElementById('vendorName').value,
            material_name: document.getElementById('materialName').value,
            quantity: document.getElementById('quantity').value,
            unit: document.getElementById('unit').value,
            price: document.getElementById('price').value,
            total_amount: document.getElementById('totalAmount').value,
            address: document.getElementById('vendorAddress').value,
            additional_info: document.getElementById('additionalInfo').value,
            status: document.getElementById('requestStatus').value
        };
    }

    validateForm(data) {
        const required = ['vendor_id', 'vendor_name', 'material_name', 'quantity', 'unit', 'price'];

        for (const field of required) {
            if (!data[field] || data[field].trim() === '') {
                this.showAlert('error', `${this.getFieldLabel(field)} is required`);
                return false;
            }
        }

        if (parseFloat(data.quantity) <= 0) {
            this.showAlert('error', 'Quantity must be greater than 0');
            return false;
        }

        if (parseFloat(data.price) <= 0) {
            this.showAlert('error', 'Price must be greater than 0');
            return false;
        }

        return true;
    }

    getFieldLabel(field) {
        const labels = {
            vendor_id: 'Vendor',
            vendor_name: 'Vendor Name',
            material_name: 'Material Name',
            quantity: 'Quantity',
            unit: 'Unit',
            price: 'Price'
        };
        return labels[field] || field;
    }

    clearForm() {
        document.getElementById('materialRequestForm').reset();
        document.getElementById('requestId').value = '';
        document.getElementById('totalAmount').value = '0.00';
        this.isEditing = false;
        this.updateFormTitle();
    }

    updateFormTitle() {
        const title = document.getElementById('formTitle');
        title.textContent = this.isEditing ? 'Edit Material Request' : 'Create Material Request';
    }

    showAlert(type, message) {
        // Remove existing alerts
        const existingAlert = document.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;

        const form = document.getElementById('materialRequestForm');
        form.insertBefore(alert, form.firstChild);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }

    renderRequestsTable() {
        const tbody = document.getElementById('requestsTableBody');
        tbody.innerHTML = '';

        if (this.requests.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No material requests found</td></tr>';
            return;
        }

        this.requests.forEach(request => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${request.id}</td>
                <td>${request.vendor_company_name || request.vendor_name}</td>
                <td>${request.material_name}</td>
                <td>${request.quantity} ${request.unit}</td>
                <td>$${parseFloat(request.price).toFixed(2)}</td>
                <td>$${parseFloat(request.total_amount).toFixed(2)}</td>
                <td><span class="status status-${request.status.toLowerCase()}">${request.status}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="materialRequestManager.editRequest(${request.id})">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="materialRequestManager.deleteRequest(${request.id})">Delete</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    async editRequest(id) {
        try {
            const response = await fetch(`./api/material-request.php?action=get&id=${id}`);
            const result = await response.json();

            if (result.success) {
                this.currentRequest = result.data;
                this.populateFormForEdit();
                this.isEditing = true;
                this.updateFormTitle();
            } else {
                this.showAlert('error', 'Failed to load material request');
            }
        } catch (error) {
            console.error('Error loading request:', error);
        }
    }

    populateFormForEdit() {
        if (!this.currentRequest) return;

        document.getElementById('requestId').value = this.currentRequest.id;
        document.getElementById('vendorSelect').value = this.currentRequest.vendor_id;
        document.getElementById('vendorName').value = this.currentRequest.vendor_name;
        document.getElementById('materialName').value = this.currentRequest.material_name;
        document.getElementById('quantity').value = this.currentRequest.quantity;
        document.getElementById('unit').value = this.currentRequest.unit;
        document.getElementById('price').value = this.currentRequest.price;
        document.getElementById('totalAmount').value = this.currentRequest.total_amount;
        document.getElementById('vendorAddress').value = this.currentRequest.address || '';
        document.getElementById('additionalInfo').value = this.currentRequest.additional_info || '';
        document.getElementById('requestStatus').value = this.currentRequest.status || 'Pending';

        this.calculateTotals();
    }

    async deleteRequest(id) {
        if (!confirm('Are you sure you want to delete this material request?')) {
            return;
        }

        try {
            const response = await fetch('./api/material-request.php?action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('success', 'Material request deleted successfully!');
                await this.loadRequests();
            } else {
                this.showAlert('error', result.message || 'Failed to delete material request');
            }
        } catch (error) {
            console.error('Error deleting request:', error);
            this.showAlert('error', 'An error occurred while deleting the request');
        }
    }
}

// Initialize the material request manager
let materialRequestManager;
document.addEventListener('DOMContentLoaded', () => {
    materialRequestManager = new MaterialRequestManager();
});

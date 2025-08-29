// Main Application JavaScript

document.addEventListener('DOMContentLoaded', function() {
    
    // Helper function to preserve customer selection during form operations
    function preserveCustomerSelection(operation) {
        const customerIdInput = document.getElementById('customer_id');
        const selectedCustomerDiv = document.getElementById('selected_customer');
        const savedCustomerId = customerIdInput ? customerIdInput.value : null;
        const savedCustomerHtml = selectedCustomerDiv ? selectedCustomerDiv.innerHTML : null;
        
        const result = operation();
        
        // Restore customer selection if it was cleared
        if (savedCustomerId && customerIdInput && customerIdInput.value !== savedCustomerId) {
            customerIdInput.value = savedCustomerId;
        }
        if (savedCustomerHtml && selectedCustomerDiv && selectedCustomerDiv.innerHTML !== savedCustomerHtml) {
            selectedCustomerDiv.innerHTML = savedCustomerHtml;
        }
        
        return result;
    }
    
    // Highlight active navigation item
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
    
    // Auto-hide alerts after 5 seconds (excluding permanent alerts)
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent):not(#selected_customer .alert)');
    alerts.forEach(alert => {
        // Don't auto-hide if it's inside the customer selection area
        if (!alert.closest('#selected_customer')) {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        }
    });
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.btn-delete, [data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // Format currency inputs
    const currencyInputs = document.querySelectorAll('.currency-input');
    currencyInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(2);
            }
        });
    });
    
    // Phone number formatting
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = value.slice(0, 3) + '-' + value.slice(3);
                } else {
                    value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
                }
            }
            e.target.value = value;
        });
    });
    
    // Table row click to view details
    const clickableRows = document.querySelectorAll('.table-clickable tbody tr');
    clickableRows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't navigate if clicking on a button or link
            if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'A') {
                const url = this.dataset.href;
                if (url) {
                    window.location.href = url;
                }
            }
        });
    });
    
    // Toggle sidebar on mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    }
    
    // Form validation feedback - Skip order forms as they have custom validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        // Skip if this is the order form (it has its own validation in orders/form.php)
        if (form.action && form.action.includes('/orders/')) {
            return;
        }
        
        form.addEventListener('submit', function(event) {
            preserveCustomerSelection(() => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    });
    
    // Due date validation
    const dueDateInput = document.getElementById('due_date');
    const receivedDateInput = document.getElementById('date_received');
    
    if (dueDateInput) {
        dueDateInput.addEventListener('change', function() {
            preserveCustomerSelection(() => {
                if (receivedDateInput && this.value && receivedDateInput.value) {
                    if (new Date(this.value) < new Date(receivedDateInput.value)) {
                        this.setCustomValidity('Due date must be after received date');
                    } else {
                        this.setCustomValidity('');
                    }
                }
            });
        });
    }
    
    // Print functionality
    window.printOrder = function(orderId) {
        const printWindow = window.open(`/azteamcrm/orders/${orderId}/print`, '_blank');
        printWindow.onload = function() {
            printWindow.print();
        };
    };
    
    // Search functionality with debounce
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.toLowerCase();
            
            searchTimeout = setTimeout(() => {
                const rows = document.querySelectorAll('.searchable-table tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(query)) {
                        row.classList.remove('d-none');
                    } else {
                        row.classList.add('d-none');
                    }
                });
            }, 300);
        });
    }
});
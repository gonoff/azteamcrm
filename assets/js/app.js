// Main Application JavaScript

document.addEventListener('DOMContentLoaded', function() {
    
    // Highlight active navigation item
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
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
    
    // Form validation feedback
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Due date validation
    const dueDateInput = document.getElementById('due_date');
    const receivedDateInput = document.getElementById('date_received');
    
    if (dueDateInput && receivedDateInput) {
        dueDateInput.addEventListener('change', function() {
            if (this.value && receivedDateInput.value) {
                if (new Date(this.value) < new Date(receivedDateInput.value)) {
                    this.setCustomValidity('Due date must be after received date');
                } else {
                    this.setCustomValidity('');
                }
            }
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
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            }, 300);
        });
    }
});
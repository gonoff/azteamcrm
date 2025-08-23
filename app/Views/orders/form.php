<?php 
// Store old input and errors before clearing them
$old_input = $_SESSION['old_input'] ?? [];
$errors_from_session = $_SESSION['errors'] ?? $errors ?? [];
$errors = $errors_from_session;

// Clear session data after capturing the values
unset($_SESSION['old_input'], $_SESSION['errors']);

include dirname(__DIR__) . '/layouts/header.php'; 
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><?= $title ?></h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <form method="POST" action="<?= $order ? "/azteamcrm/orders/{$order->order_id}/update" : "/azteamcrm/orders/store" ?>" class="needs-validation" novalidate autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="customer_search" class="form-label">Customer <span class="text-danger">*</span></label>
                            
                            <!-- Hidden input to store selected customer ID -->
                            <input type="hidden" 
                                   id="customer_id" 
                                   name="customer_id" 
                                   value="<?= htmlspecialchars($selectedCustomerId ?? '') ?>"
                                   data-required="true">
                            
                            <!-- Search input for customer selection -->
                            <div class="position-relative">
                                <input type="text" 
                                       class="form-control <?= isset($errors['customer_id']) ? 'is-invalid' : '' ?>" 
                                       id="customer_search" 
                                       placeholder="Type to search customers by name, company, or phone..."
                                       autocomplete="off"
                                       style="<?= (isset($selected_customer) && $selected_customer) || (isset($displayCustomer) && $displayCustomer) ? 'display: none;' : '' ?>">
                                
                                <!-- Loading spinner -->
                                <div class="position-absolute top-50 end-0 translate-middle-y me-3 d-none" id="search_spinner">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Searching...</span>
                                    </div>
                                </div>
                                
                                <!-- Search results dropdown -->
                                <div class="dropdown-menu w-100 shadow" id="customer_results" style="max-height: 300px; overflow-y: auto;">
                                    <!-- Results will be populated here -->
                                </div>
                            </div>
                            
                            <!-- Selected customer display -->
                            <div id="selected_customer" class="mt-2">
                                <?php 
                                // Simplified logic - trust what the controller provides
                                $displayCustomer = null;
                                $selectedCustomerId = null;
                                
                                // Priority 1: Customer passed from controller (URL param or session)
                                if (isset($selected_customer) && $selected_customer) {
                                    $displayCustomer = $selected_customer;
                                    $selectedCustomerId = $selected_customer->customer_id;
                                } 
                                // Priority 2: Customer from old input (validation failure)
                                elseif (!empty($old_input['customer_id'])) {
                                    $selectedCustomerId = $old_input['customer_id'];
                                    // Find the customer in the list
                                    foreach ($customers as $customer) {
                                        if ($customer->customer_id == $selectedCustomerId) {
                                            $displayCustomer = $customer;
                                            break;
                                        }
                                    }
                                }
                                // Priority 3: Existing order customer
                                elseif ($order && $order->customer_id) {
                                    $selectedCustomerId = $order->customer_id;
                                    foreach ($customers as $customer) {
                                        if ($customer->customer_id == $selectedCustomerId) {
                                            $displayCustomer = $customer;
                                            break;
                                        }
                                    }
                                }
                                ?>
                                <!-- Debug: Customer ID = <?= htmlspecialchars($selectedCustomerId ?? 'null') ?> -->
                                <?php
                                if ($displayCustomer): 
                                ?>
                                    <div class="alert alert-info alert-permanent d-flex justify-content-between align-items-center py-2" data-customer-id="<?= $displayCustomer->customer_id ?>">
                                        <div>
                                            <strong><?= htmlspecialchars($displayCustomer->full_name) ?></strong>
                                            <?php if ($displayCustomer->company_name): ?>
                                                <br><small><?= htmlspecialchars($displayCustomer->company_name) ?></small>
                                            <?php endif; ?>
                                            <?php if ($displayCustomer->phone_number): ?>
                                                <br><small><?= $displayCustomer->formatPhoneNumber() ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearCustomerSelection()">
                                            <i class="bi bi-x"></i> Change
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (isset($errors['customer_id'])): ?>
                                <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['customer_id']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <?php 
                            // Determine return URL based on whether we're creating or editing an order
                            $returnPath = $order ? '/azteamcrm/orders/' . $order->order_id . '/edit' : '/azteamcrm/orders/create';
                            ?>
                            <a href="/azteamcrm/customers/create?return_url=<?= urlencode($returnPath) ?>" class="btn btn-outline-primary">
                                <i class="bi bi-plus-circle"></i> New Customer
                            </a>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="date_due" class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control <?= isset($errors['date_due']) ? 'is-invalid' : '' ?>" 
                                   id="date_due" 
                                   name="date_due" 
                                   value="<?= $old_input['date_due'] ?? $order->date_due ?? '' ?>" 
                                   min="<?= date('Y-m-d') ?>"
                                   required>
                            <?php if (isset($errors['date_due'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['date_due']) ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Must be today or later</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="order_status" class="form-label">Order Status</label>
                            <select class="form-select" id="order_status" name="order_status">
                                <?php $selectedStatus = $old_input['order_status'] ?? $order->order_status ?? 'pending'; ?>
                                <option value="pending" <?= $selectedStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="in_production" <?= $selectedStatus === 'in_production' ? 'selected' : '' ?>>In Production</option>
                                <option value="completed" <?= $selectedStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $selectedStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="payment_status" class="form-label">Payment Status</label>
                            <select class="form-select" id="payment_status" name="payment_status">
                                <?php $selectedPayment = $old_input['payment_status'] ?? $order->payment_status ?? 'unpaid'; ?>
                                <option value="unpaid" <?= $selectedPayment === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                <option value="partial" <?= $selectedPayment === 'partial' ? 'selected' : '' ?>>Partial</option>
                                <option value="paid" <?= $selectedPayment === 'paid' ? 'selected' : '' ?>>Paid</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="order_total" class="form-label">Order Total</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" 
                                       class="form-control" 
                                       id="order_total" 
                                       value="<?= number_format($order->order_total ?? 0.00, 2) ?>" 
                                       readonly
                                       style="background-color: #f8f9fa;">
                            </div>
                            <small class="text-muted">
                                <?php if ($order): ?>
                                    Total is automatically calculated from order items
                                <?php else: ?>
                                    Add items to the order to calculate the total
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> <strong>Note:</strong><br>
                                Orders are automatically marked as <span class="badge bg-danger">RUSH</span> when due within 7 days.
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="order_notes" class="form-label">Order Notes</label>
                        <textarea class="form-control" 
                                  id="order_notes" 
                                  name="order_notes" 
                                  rows="4"
                                  placeholder="Enter any special instructions or notes about this order..."><?= htmlspecialchars($old_input['order_notes'] ?? $order->order_notes ?? '') ?></textarea>
                        <small class="text-muted">Optional: Add any relevant information about the order</small>
                    </div>
                    
                    <?php if ($order): ?>
                        <div class="alert alert-info">
                            <strong>Order Information:</strong><br>
                            Created: <?= date('F d, Y g:i A', strtotime($order->date_created)) ?><br>
                            Order ID: #<?= $order->order_id ?><br>
                            Outstanding Balance: $<?= number_format($order->getOutstandingBalance(), 2) ?><br>
                            Payment Status: 
                            <?php if ($order->payment_status === 'paid'): ?>
                                <span class="badge bg-success">Paid</span>
                            <?php elseif ($order->payment_status === 'partial'): ?>
                                <span class="badge bg-warning">Partial</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Unpaid</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between">
                        <a href="/azteamcrm/orders" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Orders
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> <?= $order ? 'Update Order' : 'Create Order' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Customer search functionality
let searchTimeout;
let currentFocus = -1;

function initCustomerSearch() {
    const searchInput = document.getElementById('customer_search');
    const resultsDropdown = document.getElementById('customer_results');
    const spinner = document.getElementById('search_spinner');
    const customerIdInput = document.getElementById('customer_id');
    const selectedCustomerDiv = document.getElementById('selected_customer');
    
    // Check if customer is already selected on page load (PHP has already set visibility)
    if (customerIdInput.value && selectedCustomerDiv.querySelector('.alert')) {
        console.log('Customer pre-selected with ID:', customerIdInput.value);
        // Ensure search input stays hidden
        searchInput.style.display = 'none';
    }
    
    // No observers needed - hidden input is the source of truth
    
    // Handle search input
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            resultsDropdown.classList.remove('show');
            return;
        }
        
        // Show spinner
        spinner.classList.remove('d-none');
        
        // Debounce search
        searchTimeout = setTimeout(() => {
            searchCustomers(query);
        }, 300);
    });
    
    // Handle keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        const items = resultsDropdown.querySelectorAll('.dropdown-item');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            currentFocus++;
            addActive(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentFocus--;
            addActive(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentFocus > -1 && items[currentFocus]) {
                items[currentFocus].click();
            }
        } else if (e.key === 'Escape') {
            resultsDropdown.classList.remove('show');
            currentFocus = -1;
        }
    });
    
    // Click outside to close dropdown
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsDropdown.contains(e.target)) {
            resultsDropdown.classList.remove('show');
        }
    });
}

function searchCustomers(query) {
    const resultsDropdown = document.getElementById('customer_results');
    const spinner = document.getElementById('search_spinner');
    
    fetch('/azteamcrm/customers/search', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `csrf_token=<?= $csrf_token ?>&query=${encodeURIComponent(query)}`
    })
    .then(response => response.json())
    .then(data => {
        spinner.classList.add('d-none');
        
        if (data.success && data.results.length > 0) {
            displayResults(data.results);
        } else if (data.results.length === 0) {
            resultsDropdown.innerHTML = `
                <div class="dropdown-item-text text-muted">
                    <i class="bi bi-info-circle"></i> No customers found
                </div>
            `;
            resultsDropdown.classList.add('show');
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        spinner.classList.add('d-none');
        resultsDropdown.innerHTML = `
            <div class="dropdown-item-text text-danger">
                <i class="bi bi-exclamation-triangle"></i> Search failed. Please try again.
            </div>
        `;
        resultsDropdown.classList.add('show');
    });
}

function displayResults(results) {
    const resultsDropdown = document.getElementById('customer_results');
    currentFocus = -1;
    
    let html = '';
    results.forEach(customer => {
        html += `
            <a href="#" class="dropdown-item py-2" onclick="selectCustomer(${customer.customer_id}, '${escapeHtml(customer.full_name)}', '${escapeHtml(customer.company_name || '')}', '${escapeHtml(customer.phone_number || '')}'); return false;">
                <div>
                    <strong>${escapeHtml(customer.full_name)}</strong>
                    ${customer.company_name ? `<br><small class="text-muted">${escapeHtml(customer.company_name)}</small>` : ''}
                    ${customer.phone_number ? `<br><small class="text-muted">${escapeHtml(customer.phone_number)}</small>` : ''}
                </div>
            </a>
        `;
    });
    
    resultsDropdown.innerHTML = html;
    resultsDropdown.classList.add('show');
}

function selectCustomer(customerId, fullName, companyName, phoneNumber) {
    const searchInput = document.getElementById('customer_search');
    const customerIdInput = document.getElementById('customer_id');
    const selectedCustomerDiv = document.getElementById('selected_customer');
    const resultsDropdown = document.getElementById('customer_results');
    
    // Set the customer ID - this is the most important part
    customerIdInput.value = customerId;
    console.log('Customer selected, setting hidden input to:', customerId);
    console.log('Hidden input value after setting:', customerIdInput.value);
    
    // Hide search input and show selected customer
    searchInput.style.display = 'none';
    searchInput.value = '';
    resultsDropdown.classList.remove('show');
    
    // Display selected customer
    let customerHtml = `
        <div class="alert alert-info alert-permanent d-flex justify-content-between align-items-center py-2" data-customer-id="${customerId}">
            <div>
                <strong>${escapeHtml(fullName)}</strong>
    `;
    
    if (companyName) {
        customerHtml += `<br><small>${escapeHtml(companyName)}</small>`;
    }
    if (phoneNumber) {
        customerHtml += `<br><small>${escapeHtml(phoneNumber)}</small>`;
    }
    
    customerHtml += `
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearCustomerSelection()">
                <i class="bi bi-x"></i> Change
            </button>
        </div>
    `;
    
    selectedCustomerDiv.innerHTML = customerHtml;
    
    // Hidden input value is already set, no need for localStorage
    
    // Remove any validation errors
    const invalidFeedback = document.querySelector('.invalid-feedback.d-block');
    if (invalidFeedback) {
        invalidFeedback.classList.remove('d-block');
    }
    
    // Remove customer error alert if it exists
    const customerError = document.querySelector('.customer-error-alert');
    if (customerError) {
        customerError.remove();
    }
}

function clearCustomerSelection() {
    const searchInput = document.getElementById('customer_search');
    const customerIdInput = document.getElementById('customer_id');
    const selectedCustomerDiv = document.getElementById('selected_customer');
    
    console.log('Clearing customer selection');
    
    // Clear selection
    customerIdInput.value = '';
    selectedCustomerDiv.innerHTML = '';
    
    // No localStorage to clear
    
    // Show search input again
    searchInput.style.display = 'block';
    searchInput.value = '';
    searchInput.focus();
}

function addActive(items) {
    if (!items) return false;
    removeActive(items);
    
    if (currentFocus >= items.length) currentFocus = 0;
    if (currentFocus < 0) currentFocus = items.length - 1;
    
    if (items[currentFocus]) {
        items[currentFocus].classList.add('active');
    }
}

function removeActive(items) {
    for (let item of items) {
        item.classList.remove('active');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    // Log initial state for debugging
    const customerIdInput = document.getElementById('customer_id');
    const selectedCustomerDiv = document.getElementById('selected_customer');
    console.log('Page loaded - Customer ID input:', customerIdInput);
    console.log('Page loaded - Customer ID value:', customerIdInput ? customerIdInput.value : 'input not found');
    console.log('Page loaded - Customer ID value type:', customerIdInput ? typeof customerIdInput.value : 'N/A');
    console.log('Page loaded - Customer ID value length:', customerIdInput ? customerIdInput.value.length : 'N/A');
    console.log('Page loaded - Selected customer div has content:', selectedCustomerDiv.innerHTML.trim() !== '');
    
    // JavaScript Bridge: If customer is displayed but hidden input is empty, fix it
    const customerAlert = selectedCustomerDiv.querySelector('[data-customer-id]');
    if (customerAlert && customerAlert.dataset.customerId) {
        if (!customerIdInput.value || customerIdInput.value === '') {
            console.log('JavaScript Bridge: Setting customer ID from display:', customerAlert.dataset.customerId);
            customerIdInput.value = customerAlert.dataset.customerId;
            
            // Also hide the search input if it's visible
            const searchInput = document.getElementById('customer_search');
            if (searchInput) {
                searchInput.style.display = 'none';
            }
        }
    }
    
    // Initialize customer search
    initCustomerSearch();
    
    // Handle form submission and validation
    const orderForm = document.querySelector('form.needs-validation');
    if (orderForm) {
        // Override default validation behavior to preserve customer
        orderForm.addEventListener('submit', function(e) {
            // Get fresh DOM references for validation
            const customerIdInput = document.getElementById('customer_id');
            const selectedCustomerDiv = document.getElementById('selected_customer');
            
            console.log('Form submission - Customer ID input element:', customerIdInput);
            console.log('Form submission - Customer ID value:', customerIdInput ? customerIdInput.value : 'input not found');
            
            // Custom validation for customer selection
            if (!customerIdInput || !customerIdInput.value || customerIdInput.value === '') {
                e.preventDefault();
                e.stopPropagation();
                
                // Show error message for missing customer
                const customerError = document.createElement('div');
                customerError.className = 'alert alert-danger mt-2';
                customerError.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Please select a customer before submitting the order.';
                
                // Remove any existing error
                const existingError = document.querySelector('.customer-error-alert');
                if (existingError) {
                    existingError.remove();
                }
                
                // Add error after customer search div
                customerError.classList.add('customer-error-alert');
                const customerSearchDiv = document.getElementById('customer_search').parentElement;
                customerSearchDiv.parentElement.appendChild(customerError);
                
                // Focus on customer search
                const searchInput = document.getElementById('customer_search');
                if (searchInput.style.display !== 'none') {
                    searchInput.focus();
                }
                
                console.log('Form submission blocked: No customer selected');
                return false;
            }
            
            // Check other form validation
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                console.log('Form validation failed (non-customer fields)');
                return false;
            }
            
            // Form is valid and has customer - allow submission
            console.log('Form valid, submitting with customer ID:', customerIdInput.value);
            // Form will submit and page will reload
        }, false);
    }
    
    // Session data already cleared at the top of the file
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
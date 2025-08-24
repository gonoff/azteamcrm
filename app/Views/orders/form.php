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
                    
                    <?php 
                    // Determine selected customer FIRST so button visibility works correctly
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
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="customer_search" class="form-label">Customer <span class="text-danger">*</span></label>
                            
                            <!-- Hidden input to store selected customer ID -->
                            <input type="hidden" 
                                   id="customer_id" 
                                   name="customer_id" 
                                   value="<?= htmlspecialchars($selectedCustomerId ?? '') ?>"
                                   data-required="true">
                            
                            <!-- Search input with button group -->
                            <div class="input-group">
                                <div class="position-relative flex-grow-1">
                                <input type="text" 
                                       class="form-control <?= isset($errors['customer_id']) ? 'is-invalid' : '' ?> <?= $displayCustomer ? 'd-none' : '' ?>" 
                                       id="customer_search" 
                                       placeholder="Type to search customers by name, company, or phone..."
                                       autocomplete="off">
                                
                                <!-- Loading spinner -->
                                <div class="position-absolute top-50 end-0 translate-middle-y me-3 d-none" id="search_spinner">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Searching...</span>
                                    </div>
                                </div>
                                
                                </div>
                                
                                <?php 
                                // Determine return URL based on whether we're creating or editing an order
                                $returnPath = $order ? '/azteamcrm/orders/' . $order->order_id . '/edit' : '/azteamcrm/orders/create';
                                ?>
                                <a href="/azteamcrm/customers/create?return_url=<?= urlencode($returnPath) ?>" 
                                   class="btn btn-primary <?= $displayCustomer ? 'd-none' : '' ?>" 
                                   id="new_customer_btn">
                                    <i class="bi bi-plus-circle"></i> New Customer
                                </a>
                            </div>
                            
                            <!-- Search results dropdown -->
                            <div class="dropdown-menu w-100 shadow dropdown-scrollable" id="customer_results">
                                <!-- Results will be populated here -->
                            </div>
                            
                            <!-- Selected customer display -->
                            <div id="selected_customer" class="mt-2">
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
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="date_due" class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control <?= isset($errors['date_due']) ? 'is-invalid' : '' ?>" 
                                   id="date_due" 
                                   name="date_due" 
                                   value="<?= $old_input['date_due'] ?? $order->date_due ?? date('Y-m-d', strtotime('+2 weeks')) ?>" 
                                   min="<?= date('Y-m-d') ?>"
                                   required>
                            <?php if (isset($errors['date_due'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['date_due']) ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Defaults to 2 weeks from today. Must be today or later</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Order Status</label>
                            <?php if (isset($order->order_id)): ?>
                                <div class="form-control-plaintext">
                                    <?= $order->getOrderStatusBadge() ?>
                                    <small class="text-muted d-block mt-1">
                                        <i class="bi bi-info-circle"></i> Automatically determined by item statuses
                                    </small>
                                </div>
                            <?php else: ?>
                                <div class="form-control-plaintext">
                                    <span class="badge badge-warning">Pending</span>
                                    <small class="text-muted d-block mt-1">
                                        <i class="bi bi-info-circle"></i> Will update automatically based on items
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($order): ?>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Payment Status</label>
                            <div class="form-control-plaintext">
                                <?= $order->getPaymentStatusBadge() ?>
                                <small class="text-muted d-block mt-1">
                                    <i class="bi bi-info-circle"></i> Updated via payment recording
                                </small>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Payment Status</label>
                            <div class="form-control-plaintext">
                                <span class="badge badge-danger">Unpaid</span>
                                <small class="text-muted d-block mt-1">
                                    <i class="bi bi-info-circle"></i> New orders start as unpaid
                                </small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($order): ?>
                    <!-- Order Total - Only show for existing orders -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="order_total" class="form-label">Order Total</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" 
                                       class="form-control form-control-readonly" 
                                       id="order_total" 
                                       value="<?= number_format($order->order_total, 2) ?>" 
                                       readonly>
                            </div>
                            <small class="text-muted">Total is automatically calculated from order items</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> <strong>Note:</strong><br>
                                Orders are automatically marked as <span class="badge badge-danger">RUSH</span> when due within 7 days.
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- New Order Info -->
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> <strong>Creating New Order</strong><br>
                                After creating the order, you can add items to calculate totals and apply tax settings.
                                Orders are automatically marked as <span class="badge badge-danger">RUSH</span> when due within 7 days.
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($order): ?>
                    <!-- Tax Section - Only show for existing orders -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Tax Settings</h6>
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               id="apply_ct_tax" 
                                               name="apply_ct_tax" 
                                               value="1"
                                               <?= ($old_input['apply_ct_tax'] ?? $order->apply_ct_tax ?? 0) ? 'checked' : '' ?>
                                               onchange="calculateTax()">
                                        <label class="form-check-label" for="apply_ct_tax">
                                            Apply Connecticut Tax (6.35%)
                                        </label>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">Tax Amount:</small>
                                        <strong id="tax_display">$<?= number_format($order->tax_amount ?? 0.00, 2) ?></strong>
                                    </div>
                                    <input type="hidden" id="tax_amount" name="tax_amount" value="<?= $order->tax_amount ?? 0.00 ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <?php if ($order->order_total > 0): ?>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Order Summary</h6>
                                    <table class="table table-sm mb-0">
                                        <tr>
                                            <td>Subtotal:</td>
                                            <td class="text-end">$<span id="summary_subtotal"><?= number_format($order->order_total, 2) ?></span></td>
                                        </tr>
                                        <tr>
                                            <td>Tax:</td>
                                            <td class="text-end">$<span id="summary_tax"><?= number_format($order->tax_amount, 2) ?></span></td>
                                        </tr>
                                        <tr class="fw-bold">
                                            <td>Total:</td>
                                            <td class="text-end">$<span id="summary_total"><?= number_format($order->getTotalAmount(), 2) ?></span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-info-circle"></i> <strong>Add Order Items</strong><br>
                                Tax will be calculated after adding items to this order.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
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
                        <div class="card mb-3" style="background-color: var(--bg-subtle);">
                            <div class="card-body">
                                <h6 class="card-title">Financial Summary</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <small class="text-muted">Subtotal</small>
                                        <h5>$<?= number_format($order->order_total, 2) ?></h5>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Total Amount</small>
                                        <h5>$<?= number_format($order->getTotalAmount(), 2) ?></h5>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Amount Paid</small>
                                        <h5 class="text-success">$<?= number_format($order->amount_paid, 2) ?></h5>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Balance Due</small>
                                        <h5 class="<?= $order->getBalanceDue() > 0 ? 'text-danger' : 'text-success' ?>">
                                            $<?= number_format($order->getBalanceDue(), 2) ?>
                                        </h5>
                                    </div>
                                </div>
                                <hr>
                                <small class="text-muted">
                                    Created: <?= date('F d, Y g:i A', strtotime($order->date_created)) ?> | 
                                    Order ID: #<?= $order->order_id ?>
                                </small>
                            </div>
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
        searchInput.classList.add('d-none');
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
    const newCustomerBtn = document.getElementById('new_customer_btn');
    
    // Set the customer ID - this is the most important part
    customerIdInput.value = customerId;
    console.log('Customer selected, setting hidden input to:', customerId);
    console.log('Hidden input value after setting:', customerIdInput.value);
    
    // Hide search input and new customer button, show selected customer
    searchInput.classList.add('d-none');
    searchInput.value = '';
    if (newCustomerBtn) {
        newCustomerBtn.classList.add('d-none');
    }
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
    const newCustomerBtn = document.getElementById('new_customer_btn');
    
    console.log('Clearing customer selection');
    
    // Clear selection
    customerIdInput.value = '';
    selectedCustomerDiv.innerHTML = '';
    
    // No localStorage to clear
    
    // Show search input and new customer button again
    searchInput.classList.remove('d-none');
    searchInput.value = '';
    searchInput.focus();
    if (newCustomerBtn) {
        newCustomerBtn.classList.remove('d-none');
    }
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

// Tax calculation function
function calculateTax() {
    const checkbox = document.getElementById('apply_ct_tax');
    const taxDisplay = document.getElementById('tax_display');
    const taxAmountInput = document.getElementById('tax_amount');
    const orderTotalInput = document.getElementById('order_total');
    
    // Get the subtotal (order total)
    const subtotalText = orderTotalInput.value.replace(/,/g, '');
    const subtotal = parseFloat(subtotalText) || 0;
    
    let taxAmount = 0;
    if (checkbox.checked) {
        // Calculate 6.35% tax
        taxAmount = Math.round(subtotal * 0.0635 * 100) / 100;
    }
    
    // Update display
    taxDisplay.textContent = '$' + taxAmount.toFixed(2);
    taxAmountInput.value = taxAmount.toFixed(2);
    
    // Update summary if it exists
    const summaryTax = document.getElementById('summary_tax');
    const summaryTotal = document.getElementById('summary_total');
    
    if (summaryTax) {
        summaryTax.textContent = taxAmount.toFixed(2);
    }
    
    if (summaryTotal) {
        const total = subtotal + taxAmount;
        summaryTotal.textContent = total.toFixed(2);
    }
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
                searchInput.classList.add('d-none');
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
                if (!searchInput.classList.contains('d-none')) {
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

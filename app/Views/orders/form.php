<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

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
                
                <form method="POST" action="<?= $order ? "/azteamcrm/orders/{$order->order_id}/update" : "/azteamcrm/orders/store" ?>" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="customer_search" class="form-label">Customer <span class="text-danger">*</span></label>
                            
                            <!-- Hidden input to store selected customer ID -->
                            <input type="hidden" 
                                   id="customer_id" 
                                   name="customer_id" 
                                   value="<?= $_SESSION['old_input']['customer_id'] ?? $order->customer_id ?? '' ?>"
                                   required>
                            
                            <!-- Search input for customer selection -->
                            <div class="position-relative">
                                <input type="text" 
                                       class="form-control <?= isset($errors['customer_id']) ? 'is-invalid' : '' ?>" 
                                       id="customer_search" 
                                       placeholder="Type to search customers by name, company, or phone..."
                                       autocomplete="off">
                                
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
                                // Check if we have a pre-selected customer
                                $selectedCustomerId = null;
                                $selectedCustomer = null;
                                
                                if (isset($selected_customer_id)) {
                                    $selectedCustomerId = $selected_customer_id;
                                } elseif ($_SESSION['old_input']['customer_id'] ?? $order->customer_id ?? '') {
                                    $selectedCustomerId = $_SESSION['old_input']['customer_id'] ?? $order->customer_id ?? '';
                                }
                                
                                if ($selectedCustomerId) {
                                    // Find the selected customer from the list
                                    foreach ($customers as $customer) {
                                        if ($customer->customer_id == $selectedCustomerId) {
                                            $selectedCustomer = $customer;
                                            break;
                                        }
                                    }
                                }
                                
                                if ($selectedCustomer): 
                                ?>
                                    <div class="alert alert-info d-flex justify-content-between align-items-center py-2">
                                        <div>
                                            <strong><?= htmlspecialchars($selectedCustomer->full_name) ?></strong>
                                            <?php if ($selectedCustomer->company_name): ?>
                                                <br><small><?= htmlspecialchars($selectedCustomer->company_name) ?></small>
                                            <?php endif; ?>
                                            <?php if ($selectedCustomer->phone_number): ?>
                                                <br><small><?= $selectedCustomer->formatPhoneNumber() ?></small>
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
                                   value="<?= $_SESSION['old_input']['date_due'] ?? $order->date_due ?? '' ?>" 
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
                                <?php $selectedStatus = $_SESSION['old_input']['order_status'] ?? $order->order_status ?? 'pending'; ?>
                                <option value="pending" <?= $selectedStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="in_production" <?= $selectedStatus === 'in_production' ? 'selected' : '' ?>>In Production</option>
                                <option value="completed" <?= $selectedStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $selectedStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="payment_status" class="form-label">Payment Status</label>
                            <select class="form-select" id="payment_status" name="payment_status">
                                <?php $selectedPayment = $_SESSION['old_input']['payment_status'] ?? $order->payment_status ?? 'unpaid'; ?>
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
                                  placeholder="Enter any special instructions or notes about this order..."><?= htmlspecialchars($_SESSION['old_input']['order_notes'] ?? $order->order_notes ?? '') ?></textarea>
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
    
    // Check if customer is already selected on page load
    if (customerIdInput.value && selectedCustomerDiv.querySelector('.alert')) {
        searchInput.style.display = 'none';
    }
    
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
    
    // Set the customer ID
    customerIdInput.value = customerId;
    
    // Hide search input and show selected customer
    searchInput.style.display = 'none';
    searchInput.value = '';
    resultsDropdown.classList.remove('show');
    
    // Display selected customer
    let customerHtml = `
        <div class="alert alert-info d-flex justify-content-between align-items-center py-2">
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
    
    // Remove invalid feedback if it exists
    const invalidFeedback = document.querySelector('.invalid-feedback.d-block');
    if (invalidFeedback) {
        invalidFeedback.classList.remove('d-block');
    }
}

function clearCustomerSelection() {
    const searchInput = document.getElementById('customer_search');
    const customerIdInput = document.getElementById('customer_id');
    const selectedCustomerDiv = document.getElementById('selected_customer');
    
    // Clear selection
    customerIdInput.value = '';
    selectedCustomerDiv.innerHTML = '';
    
    // Show search input again
    searchInput.style.display = 'block';
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
    // Initialize customer search
    initCustomerSearch();
    
    // Clear old input from session
    <?php unset($_SESSION['old_input'], $_SESSION['errors']); ?>
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Order #<?= $order->order_id ?></h1>
    <div>
        <a href="/azteamcrm/orders" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
        <a href="/azteamcrm/orders/<?= $order->order_id ?>/edit" class="btn btn-secondary">
            <i class="bi bi-pencil"></i> Edit Order
        </a>
        
        <?php if (!in_array($order->order_status, ['cancelled', 'completed'])): ?>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                <i class="bi bi-x-circle"></i> Cancel Order
            </button>
        <?php endif; ?>
        
        <?php if ($_SESSION['user_role'] === 'administrator'): ?>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteOrderModal">
                <i class="bi bi-trash"></i> Delete Order
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <!-- Order Details Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Order Details</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Customer:</strong><br>
                        <?php if ($customer): ?>
                            <a href="/azteamcrm/customers/<?= $customer->customer_id ?>" class="text-decoration-none">
                                <?= htmlspecialchars($customer->full_name) ?>
                                <?php if ($customer->company_name): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($customer->company_name) ?></small>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Customer not found</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Phone:</strong><br>
                        <?= $customer ? $customer->formatPhoneNumber() : 'N/A' ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Order Created:</strong><br>
                        <?= date('F d, Y', strtotime($order->date_created)) ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Due Date:</strong><br>
                        <?= date('F d, Y', strtotime($order->date_due)) ?>
                        <?php if ($order->isOverdue() && $order->payment_status !== 'paid'): ?>
                            <span class="badge badge-danger ms-2">Overdue</span>
                        <?php elseif ($order->isDueSoon() && $order->payment_status !== 'paid'): ?>
                            <span class="badge badge-warning ms-2">Due Soon</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Order Status:</strong><br>
                        <?= $order->getOrderStatusBadge() ?>
                        <?php if ($order->isRushOrder()): ?>
                            <span class="badge badge-danger ms-1">RUSH</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Captured By:</strong><br>
                        <?= htmlspecialchars($user ? $user->full_name : 'Unknown') ?>
                    </div>
                </div>
                
                <?php if ($order->order_notes): ?>
                <div class="row">
                    <div class="col-12">
                        <strong>Order Notes:</strong><br>
                        <div class="alert alert-light">
                            <?= nl2br(htmlspecialchars($order->order_notes)) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-12">
                        <small class="text-muted">
                            Order ID: #<?= $order->order_id ?> | 
                            Created: <?= date('F d, Y g:i A', strtotime($order->date_created)) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Items Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Order Items</h5>
                <div>
                    <a href="/azteamcrm/orders/<?= $order->order_id ?>/order-items" class="btn btn-sm btn-info">
                        <i class="bi bi-list"></i> View All
                    </a>
                    <a href="/azteamcrm/orders/<?= $order->order_id ?>/order-items/create" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus"></i> Add Item
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($orderItems)): ?>
                    <p class="text-muted text-center">No order items added yet.</p>
                <?php else: ?>
                    <div class="order-items-container">
                        <table class="table table-sm order-items-table" id="orderItemsTable">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Size</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>Method</th>
                                    <th>Supplier Status</th>
                                    <th>Item Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                <tr data-item-id="<?= $item->order_item_id ?>">
                                    <td>
                                        <span class="editable-field" 
                                              data-field="product_description" 
                                              data-value="<?= htmlspecialchars($item->product_description) ?>">
                                            <?= htmlspecialchars($item->product_description) ?>
                                        </span>
                                    </td>
                                    <td><?= $item->getSizeLabel() ?></td>
                                    <td>
                                        <span class="editable-field" 
                                              data-field="quantity" 
                                              data-value="<?= $item->quantity ?>">
                                            <?= $item->quantity ?>
                                        </span>
                                    </td>
                                    <td>
                                        $<span class="editable-field" 
                                               data-field="unit_price" 
                                               data-value="<?= $item->unit_price ?>">
                                            <?= number_format($item->unit_price, 2) ?>
                                        </span>
                                    </td>
                                    <td class="item-total">
                                        $<?= number_format($item->total_price, 2) ?>
                                    </td>
                                    <td><?= $item->getCustomMethodLabel() ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm dropdown-toggle p-0 border-0" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown">
                                                <?= $item->getSupplierStatusBadge() ?>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="awaiting_order">Awaiting Order</a></li>
                                                <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="order_made">Order Made</a></li>
                                                <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="order_arrived">Order Arrived</a></li>
                                                <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="order_delivered">Order Delivered</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm dropdown-toggle p-0 border-0" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown">
                                                <?= $item->getStatusBadge() ?>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item update-item-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="pending">Pending</a></li>
                                                <li><a class="dropdown-item update-item-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="in_production">In Production</a></li>
                                                <li><a class="dropdown-item update-item-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="completed">Completed</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="/azteamcrm/order-items/<?= $item->order_item_id ?>/edit" class="btn btn-sm btn-outline-secondary" title="Full Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Financial Summary Card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Order Summary</h5>
            </div>
            <div class="card-body">
                <!-- Order Totals Section -->
                <div class="mb-3">
                    <table class="table table-sm">
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-end">$<?= number_format($order->order_total, 2) ?></td>
                        </tr>
                        <tr>
                            <td>Discount:</td>
                            <td class="text-end text-danger">-$<?= number_format($order->discount_amount, 2) ?></td>
                        </tr>
                        <tr>
                            <td>
                                <?php if ($order->apply_ct_tax): ?>
                                    CT Tax (6.35%):
                                <?php else: ?>
                                    Tax:
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                +$<?= number_format($order->tax_amount, 2) ?>
                                <?php if ($order->apply_ct_tax): ?>
                                    <span class="badge badge-info ms-1" style="font-size: 0.7rem;">CT</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Shipping:</td>
                            <td class="text-end">+$<?= number_format($order->shipping_amount, 2) ?></td>
                        </tr>
                        <tr class="table-primary">
                            <th>Total Amount:</th>
                            <th class="text-end">$<?= number_format($order->getTotalAmount(), 2) ?></th>
                        </tr>
                    </table>
                </div>
                
                <hr>
                
                <!-- Payment Section -->
                <div class="mb-3">
                    <table class="table table-sm">
                        <tr>
                            <td>Amount Paid:</td>
                            <td class="text-end text-success">$<?= number_format($order->amount_paid, 2) ?></td>
                        </tr>
                        <tr class="<?= $order->getBalanceDue() > 0 ? 'table-danger' : 'table-success' ?>">
                            <th>Balance Due:</th>
                            <th class="text-end">$<?= number_format($order->getBalanceDue(), 2) ?></th>
                        </tr>
                        <tr>
                            <td>Status:</td>
                            <td class="text-end">
                                <?php if ($order->payment_status === 'paid'): ?>
                                    <span class="badge badge-success">Paid</span>
                                <?php elseif ($order->payment_status === 'partial'): ?>
                                    <span class="badge badge-warning">Partial</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Unpaid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <hr>
                
                <!-- Add Payment Form -->
                <?php if ($order->getBalanceDue() > 0): ?>
                <form method="POST" action="/azteamcrm/orders/<?= $order->order_id ?>/process-payment">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <h6>Record Payment</h6>
                    
                    <div class="mb-2">
                        <label class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="payment_amount" 
                                   step="0.01" min="0.01" max="<?= $order->getBalanceDue() ?>"
                                   value="<?= $order->getBalanceDue() ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Method</label>
                        <select class="form-select form-select-sm" name="payment_method">
                            <option value="">Select Method</option>
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control form-control-sm" name="payment_notes" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-cash"></i> Record Payment
                    </button>
                </form>
                <?php else: ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> Order Fully Paid
                </div>
                <?php endif; ?>
                
                <!-- Payment History -->
                <?php 
                $payments = $order->getPaymentHistory();
                if (!empty($payments)): 
                ?>
                <hr>
                <h6>Payment History</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($payment->payment_date)) ?></td>
                                <td>$<?= number_format($payment->payment_amount, 2) ?></td>
                                <td><?= htmlspecialchars($payment->payment_method ?: 'N/A') ?></td>
                                <td><?= htmlspecialchars($payment->recorded_by_name) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Production Status Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Production Status</h5>
            </div>
            <div class="card-body">
                <?php 
                $totalItems = count($orderItems);
                $completedItems = 0;
                foreach ($orderItems as $item) {
                    if ($item->order_item_status === 'completed') {
                        $completedItems++;
                    }
                }
                $completionPercentage = $totalItems > 0 ? ($completedItems / $totalItems) * 100 : 0;
                ?>
                
                <div class="mb-3">
                    <strong>Progress:</strong>
                    <div class="progress mt-2">
                        <div class="progress-bar <?= $completionPercentage == 100 ? 'progress-bar-success' : '' ?>" 
                             style="width: <?= $completionPercentage ?>%">
                            <?= round($completionPercentage) ?>%
                        </div>
                    </div>
                    <small class="text-muted"><?= $completedItems ?> of <?= $totalItems ?> items completed</small>
                </div>
                
                <?php if ($completionPercentage == 100 && $order->payment_status === 'paid'): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> Order Complete!
                    </div>
                <?php elseif ($order->isOverdue()): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> Order is overdue!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Order Modal -->
<?php if ($_SESSION['user_role'] === 'administrator'): ?>
<div class="modal fade" id="deleteOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this order for <strong><?= htmlspecialchars($customer ? $customer->full_name : 'Unknown Customer') ?></strong>?</p>
                <p class="text-danger">This action cannot be undone and will also delete all associated order items.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="/azteamcrm/orders/<?= $order->order_id ?>/delete" class="form-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <button type="submit" class="btn btn-danger">Delete Order</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide paid amount field based on payment status
    const paymentStatusSelect = document.getElementById('payment_status');
    const paidAmountGroup = document.getElementById('paidAmountGroup');
    
    if (paymentStatusSelect && paidAmountGroup) {
        paymentStatusSelect.addEventListener('change', function() {
            if (this.value === 'partial') {
                paidAmountGroup.classList.remove('d-none');
            } else {
                paidAmountGroup.classList.add('d-none');
            }
        });
        
        // Trigger change event on page load to set initial state
        if (paymentStatusSelect.value === 'partial') {
            paidAmountGroup.classList.remove('d-none');
        }
    }
    
    // Inline editing functionality for order items
    const editableFields = document.querySelectorAll('.editable-field');
    
    editableFields.forEach(field => {
        field.addEventListener('click', function() {
            if (this.querySelector('input')) return; // Already editing
            
            const currentValue = this.getAttribute('data-value');
            const fieldName = this.getAttribute('data-field');
            const isPrice = fieldName === 'unit_price';
            
            // Create input element
            const input = document.createElement('input');
            input.type = isPrice ? 'number' : 'text';
            if (isPrice) {
                input.step = '0.01';
                input.min = '0';
            }
            input.value = currentValue;
            input.className = 'form-control form-control-sm';
            input.style.width = fieldName === 'product_description' ? '200px' : '80px';
            
            // Replace span content with input
            this.innerHTML = '';
            this.appendChild(input);
            input.focus();
            input.select();
            
            // Handle save on blur or enter
            const saveField = () => {
                const newValue = input.value;
                const itemId = this.closest('tr').getAttribute('data-item-id');
                
                // Update the display
                if (isPrice) {
                    this.innerHTML = parseFloat(newValue).toFixed(2);
                } else {
                    this.innerHTML = newValue;
                }
                this.setAttribute('data-value', newValue);
                
                // Send update to server
                fetch('/azteamcrm/order-items/' + itemId + '/update-inline', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'csrf_token=<?= $csrf_token ?>&field=' + fieldName + '&value=' + newValue
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update total if quantity or price changed
                        if (fieldName === 'quantity' || fieldName === 'unit_price') {
                            const row = document.querySelector('tr[data-item-id="' + itemId + '"]');
                            const qty = parseFloat(row.querySelector('[data-field="quantity"]').getAttribute('data-value'));
                            const price = parseFloat(row.querySelector('[data-field="unit_price"]').getAttribute('data-value'));
                            const totalCell = row.querySelector('.item-total');
                            totalCell.innerHTML = '$' + (qty * price).toFixed(2);
                            
                            // Update order total in financial summary if provided
                            if (data.new_total) {
                                location.reload(); // Reload to update all totals
                            }
                        }
                    } else {
                        alert('Failed to update field: ' + (data.message || 'Unknown error'));
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update field');
                    location.reload();
                });
            };
            
            input.addEventListener('blur', saveField);
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveField();
                }
            });
        });
    });
    
    // Direct event attachment for status updates - proven to work
    // Handle item status updates
    document.querySelectorAll('.update-item-status').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent Bootstrap interference
            
            const itemId = this.dataset.id;
            const status = this.dataset.status;
            
            // Add debugging
            console.log('Updating item status:', itemId, status);
            
            // Close the dropdown manually
            const dropdownToggle = this.closest('.dropdown').querySelector('.dropdown-toggle');
            const dropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
            if (dropdown) dropdown.hide();
            
            // Call the update function
            updateItemStatus(itemId, status);
        });
    });
    
    // Handle supplier status updates
    document.querySelectorAll('.update-supplier-status').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent Bootstrap interference
            
            const itemId = this.dataset.id;
            const status = this.dataset.status;
            
            // Add debugging
            console.log('Updating supplier status:', itemId, status);
            
            // Close the dropdown manually
            const dropdownToggle = this.closest('.dropdown').querySelector('.dropdown-toggle');
            const dropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
            if (dropdown) dropdown.hide();
            
            // Call the update function
            updateSupplierStatus(itemId, status);
        });
    });
    
    // Simple function for item status updates
    function updateItemStatus(itemId, newStatus) {
        console.log('Sending item status update request for item:', itemId, 'to status:', newStatus);
        
        fetch('/azteamcrm/order-items/' + itemId + '/update-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'csrf_token=<?= $csrf_token ?>&status_type=order_item_status&status=' + newStatus
        })
        .then(response => response.json())
        .then(data => {
            console.log('Item status update response:', data);
            if (data.success) {
                // Simple reload after successful update
                console.log('Status updated successfully, reloading page...');
                setTimeout(() => location.reload(), 500);
            } else {
                alert('Failed to update status: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error updating item status:', error);
            alert('Failed to update status: ' + error.message);
        });
    }
    
    // Simple function for supplier status updates
    function updateSupplierStatus(itemId, newStatus) {
        console.log('Sending supplier status update request for item:', itemId, 'to status:', newStatus);
        
        fetch('/azteamcrm/order-items/' + itemId + '/update-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'csrf_token=<?= $csrf_token ?>&status_type=supplier_status&status=' + newStatus
        })
        .then(response => response.json())
        .then(data => {
            console.log('Supplier status update response:', data);
            if (data.success) {
                // Simple reload after successful update
                console.log('Status updated successfully, reloading page...');
                setTimeout(() => location.reload(), 500);
            } else {
                alert('Failed to update supplier status: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error updating supplier status:', error);
            alert('Failed to update supplier status: ' + error.message);
        });
    }
    
    // Simple Bootstrap dropdown configuration for better positioning
    document.querySelectorAll('.dropdown').forEach(dropdown => {
        dropdown.setAttribute('data-bs-boundary', 'viewport');
        dropdown.setAttribute('data-bs-flip', 'true');
    });
});
</script>

<!-- Cancel Order Modal -->
<?php if (!in_array($order->order_status, ['cancelled', 'completed'])): ?>
<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this order for <strong><?= htmlspecialchars($customer ? $customer->full_name : 'Unknown Customer') ?></strong>?</p>
                <p class="text-warning"><i class="bi bi-exclamation-triangle"></i> This will mark the order as cancelled. The order items will remain unchanged.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <form action="/azteamcrm/orders/<?= $order->order_id ?>/cancel" method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> Cancel Order
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>

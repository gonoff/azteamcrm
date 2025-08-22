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
                            <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                            <select class="form-select <?= isset($errors['customer_id']) ? 'is-invalid' : '' ?>" 
                                    id="customer_id" 
                                    name="customer_id" 
                                    required>
                                <option value="">Select a customer...</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer->customer_id ?>" 
                                            <?= ($_SESSION['old_input']['customer_id'] ?? $order->customer_id ?? '') == $customer->customer_id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($customer->full_name) ?>
                                        <?php if ($customer->company_name): ?>
                                            (<?= htmlspecialchars($customer->company_name) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['customer_id'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['customer_id']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <a href="/azteamcrm/customers/create" class="btn btn-outline-primary">
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
                            <label for="order_total" class="form-label">Order Total <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       class="form-control <?= isset($errors['order_total']) ? 'is-invalid' : '' ?>" 
                                       id="order_total" 
                                       name="order_total" 
                                       value="<?= $_SESSION['old_input']['order_total'] ?? $order->order_total ?? '' ?>" 
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00"
                                       required>
                                <?php if (isset($errors['order_total'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['order_total']) ?></div>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">Enter the total amount to charge</small>
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
                            Outstanding Balance: $<?= number_format($order->outstanding_balance, 2) ?><br>
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
document.addEventListener('DOMContentLoaded', function() {
    // Calculate if order is rush based on due date
    const dueDateInput = document.getElementById('due_date');
    const rushOrderCheckbox = document.getElementById('is_rush_order');
    
    dueDateInput.addEventListener('change', function() {
        const dueDate = new Date(this.value);
        const today = new Date();
        const diffTime = Math.abs(dueDate - today);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        // Auto-check rush order if due within 7 days
        if (diffDays <= 7 && !rushOrderCheckbox.checked) {
            rushOrderCheckbox.checked = true;
            // Show alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-warning alert-dismissible fade show mt-2';
            alert.innerHTML = `
                <i class="bi bi-exclamation-triangle"></i> 
                This order has been automatically marked as RUSH (due within 7 days).
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            dueDateInput.parentElement.appendChild(alert);
        }
    });
    
    // Format phone number as user types
    const phoneInput = document.getElementById('client_phone');
    phoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 0) {
            if (value.length <= 3) {
                value = `(${value}`;
            } else if (value.length <= 6) {
                value = `(${value.slice(0, 3)}) ${value.slice(3)}`;
            } else {
                value = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
            }
        }
        e.target.value = value;
    });
    
    // Clear old input from session
    <?php unset($_SESSION['old_input'], $_SESSION['errors']); ?>
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
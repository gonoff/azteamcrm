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
                
                <form method="POST" action="<?= $order ? "/azteamcrm/orders/{$order->id}/update" : "/azteamcrm/orders/store" ?>" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="client_name" class="form-label">Client Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?= isset($errors['client_name']) ? 'is-invalid' : '' ?>" 
                                   id="client_name" 
                                   name="client_name" 
                                   value="<?= htmlspecialchars($_SESSION['old_input']['client_name'] ?? $order->client_name ?? '') ?>" 
                                   required>
                            <?php if (isset($errors['client_name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['client_name']) ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 3 characters</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="client_phone" class="form-label">Client Phone <span class="text-danger">*</span></label>
                            <input type="tel" 
                                   class="form-control <?= isset($errors['client_phone']) ? 'is-invalid' : '' ?>" 
                                   id="client_phone" 
                                   name="client_phone" 
                                   value="<?= htmlspecialchars($_SESSION['old_input']['client_phone'] ?? $order->client_phone ?? '') ?>" 
                                   placeholder="(555) 123-4567"
                                   required>
                            <?php if (isset($errors['client_phone'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['client_phone']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_received" class="form-label">Date Received <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control <?= isset($errors['date_received']) ? 'is-invalid' : '' ?>" 
                                   id="date_received" 
                                   name="date_received" 
                                   value="<?= $_SESSION['old_input']['date_received'] ?? $order->date_received ?? date('Y-m-d') ?>" 
                                   required>
                            <?php if (isset($errors['date_received'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['date_received']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control <?= isset($errors['due_date']) ? 'is-invalid' : '' ?>" 
                                   id="due_date" 
                                   name="due_date" 
                                   value="<?= $_SESSION['old_input']['due_date'] ?? $order->due_date ?? '' ?>" 
                                   min="<?= date('Y-m-d') ?>"
                                   required>
                            <?php if (isset($errors['due_date'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['due_date']) ?></div>
                            <?php endif; ?>
                            <small class="text-muted">Must be today or later</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="total_value" class="form-label">Total Value <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       class="form-control <?= isset($errors['total_value']) ? 'is-invalid' : '' ?>" 
                                       id="total_value" 
                                       name="total_value" 
                                       value="<?= $_SESSION['old_input']['total_value'] ?? $order->total_value ?? '' ?>" 
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00"
                                       required>
                                <?php if (isset($errors['total_value'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['total_value']) ?></div>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">Enter the total amount to charge</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Order Type</label>
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_rush_order" 
                                       name="is_rush_order"
                                       value="1"
                                       <?= ($_SESSION['old_input']['is_rush_order'] ?? $order->is_rush_order ?? false) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_rush_order">
                                    <span class="badge bg-danger">RUSH ORDER</span> - Mark this order as rush
                                </label>
                            </div>
                            <small class="text-muted">Rush orders have priority in production</small>
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
                            Created: <?= date('F d, Y g:i A', strtotime($order->created_at)) ?><br>
                            Last Updated: <?= date('F d, Y g:i A', strtotime($order->updated_at)) ?><br>
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
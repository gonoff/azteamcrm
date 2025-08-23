<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Order #<?= $order->order_id ?></h1>
    <div>
        <a href="/azteamcrm/orders" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
        <a href="/azteamcrm/orders/<?= $order->order_id ?>/edit" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Edit Order
        </a>
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
                            <span class="badge bg-danger ms-2">Overdue</span>
                        <?php elseif ($order->isDueSoon() && $order->payment_status !== 'paid'): ?>
                            <span class="badge bg-warning ms-2">Due Soon</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Order Status:</strong><br>
                        <?= $order->getOrderStatusBadge() ?>
                        <?php if ($order->isRushOrder()): ?>
                            <span class="badge bg-danger ms-1">RUSH</span>
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
                    <a href="/azteamcrm/orders/<?= $order->order_id ?>/order-items/create" class="btn btn-sm btn-success">
                        <i class="bi bi-plus"></i> Add Item
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($orderItems)): ?>
                    <p class="text-muted text-center">No order items added yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Size</th>
                                    <th>Quantity</th>
                                    <th>Method</th>
                                    <th>Supplier Status</th>
                                    <th>Item Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item->product_description) ?></td>
                                    <td><?= $item->getSizeLabel() ?></td>
                                    <td><?= $item->quantity ?></td>
                                    <td><?= $item->getCustomMethodLabel() ?></td>
                                    <td>
                                        <?= $item->getSupplierStatusBadge() ?>
                                    </td>
                                    <td>
                                        <?= $item->getStatusBadge() ?>
                                    </td>
                                    <td>
                                        <a href="/azteamcrm/order-items/<?= $item->order_item_id ?>/edit" class="btn btn-sm btn-outline-primary">
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
                <h5 class="mb-0">Financial Summary</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Total Value:</strong>
                    <h3 class="text-primary">$<?= number_format($order->order_total, 2) ?></h3>
                </div>
                
                <!-- Outstanding balance removed in new schema -->
                
                <div class="mb-3">
                    <strong>Payment Status:</strong><br>
                    <?php if ($order->payment_status === 'paid'): ?>
                        <span class="badge bg-success fs-6">PAID</span>
                    <?php elseif ($order->payment_status === 'partial'): ?>
                        <span class="badge bg-warning fs-6">PARTIAL PAYMENT</span>
                    <?php else: ?>
                        <span class="badge bg-danger fs-6">UNPAID</span>
                    <?php endif; ?>
                </div>
                
                <hr>
                
                <!-- Update Payment Form -->
                <form method="POST" action="/azteamcrm/orders/<?= $order->order_id ?>/update-status">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="mb-3">
                        <label for="payment_status" class="form-label">Update Payment Status</label>
                        <select class="form-select" id="payment_status" name="payment_status" required>
                            <option value="">Select Status</option>
                            <option value="unpaid" <?= $order->payment_status === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                            <option value="partial" <?= $order->payment_status === 'partial' ? 'selected' : '' ?>>Partial Payment</option>
                            <option value="paid" <?= $order->payment_status === 'paid' ? 'selected' : '' ?>>Paid in Full</option>
                        </select>
                    </div>
                    
                    <div class="mb-3 d-none" id="paidAmountGroup">
                        <label for="paid_amount" class="form-label">Amount Paid</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" 
                                   class="form-control" 
                                   id="paid_amount" 
                                   name="paid_amount" 
                                   step="0.01"
                                   min="0"
                                   max="<?= $order->order_total ?>"
                                   value="0">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-cash"></i> Update Payment
                    </button>
                </form>
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
                        <div class="progress-bar <?= $completionPercentage == 100 ? 'bg-success' : 'bg-primary' ?>" 
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
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
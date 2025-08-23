<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Order Items - Order #<?= $order->order_id ?></h1>
    <div>
        <a href="/azteamcrm/orders/<?= $order->order_id ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Order
        </a>
        <a href="/azteamcrm/orders/<?= $order->order_id ?>/order-items/create" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Add Order Item
        </a>
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

<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <strong>Customer:</strong> 
                <?php if ($customer): ?>
                    <a href="/azteamcrm/customers/<?= $customer->customer_id ?>" class="text-decoration-none">
                        <?= htmlspecialchars($customer->full_name) ?>
                        <?php if ($customer->company_name): ?>
                            (<?= htmlspecialchars($customer->company_name) ?>)
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    N/A
                <?php endif; ?>
                <br>
                <strong>Phone:</strong> <?= $customer ? $customer->formatPhoneNumber() : 'N/A' ?>
            </div>
            <div class="col-md-6">
                <strong>Due Date:</strong> <?= date('F d, Y', strtotime($order->date_due)) ?><br>
                <strong>Order Total:</strong> $<?= number_format($order->order_total, 2) ?>
            </div>
        </div>
    </div>
</div>

<?php if (empty($orderItems)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> No order items have been added to this order yet.
        <a href="/azteamcrm/orders/<?= $order->order_id ?>/order-items/create">Add the first item</a>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Item Status</th>
                            <th>Supplier Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td>#<?= $item->order_item_id ?></td>
                            <td>
                                <?= htmlspecialchars($item->product_description) ?>
                                <?php if ($item->custom_method): ?>
                                    <br><small class="text-muted">Method: <?= $item->getCustomMethodLabel() ?></small>
                                <?php endif; ?>
                                <?php if ($item->note_item): ?>
                                    <br><small class="text-muted"><i class="bi bi-sticky"></i> Has notes</small>
                                <?php endif; ?>
                            </td>
                            <td><?= $item->getProductTypeLabel() ?></td>
                            <td><?= $item->getSizeLabel() ?></td>
                            <td><?= $item->quantity ?></td>
                            <td>$<?= number_format($item->unit_price, 2) ?></td>
                            <td>$<?= number_format($item->total_price ?? ($item->quantity * $item->unit_price), 2) ?></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle p-0 border-0" type="button" data-bs-toggle="dropdown">
                                        <?= $item->getStatusBadge() ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><h6 class="dropdown-header">Update Item Status</h6></li>
                                        <li><a class="dropdown-item update-item-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="pending">
                                            <span class="badge bg-warning">Pending</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-item-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="in_production">
                                            <span class="badge bg-info">In Production</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-item-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="completed">
                                            <span class="badge bg-success">Completed</span>
                                        </a></li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle p-0 border-0" type="button" data-bs-toggle="dropdown">
                                        <?= $item->getSupplierStatusBadge() ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><h6 class="dropdown-header">Update Supplier Status</h6></li>
                                        <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="awaiting_order">
                                            <span class="badge bg-secondary">Awaiting Order</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="order_made">
                                            <span class="badge bg-info">Order Made</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="order_arrived">
                                            <span class="badge bg-primary">Order Arrived</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="order_delivered">
                                            <span class="badge bg-success">Order Delivered</span>
                                        </a></li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <a href="/azteamcrm/order-items/<?= $item->order_item_id ?>/edit" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-item" data-id="<?= $item->order_item_id ?>" data-desc="<?= htmlspecialchars($item->product_description) ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary -->
            <div class="mt-3 p-3 bg-light rounded">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Total Items:</strong> <?= count($orderItems) ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Total Quantity:</strong> 
                        <?php 
                        $totalQty = array_sum(array_map(function($item) { return $item->quantity; }, $orderItems));
                        echo $totalQty;
                        ?>
                    </div>
                    <div class="col-md-4">
                        <?php 
                        $completed = 0;
                        foreach ($orderItems as $item) {
                            if ($item->order_item_status === 'completed') {
                                $completed++;
                            }
                        }
                        $percentage = count($orderItems) > 0 ? ($completed / count($orderItems)) * 100 : 0;
                        ?>
                        <strong>Completed:</strong> <?= $completed ?> / <?= count($orderItems) ?> (<?= round($percentage) ?>%)
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this order item?</p>
                <p class="mb-0"><strong id="deleteItemDesc"></strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Form (hidden) -->
<form id="statusUpdateForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <input type="hidden" name="status_type" id="statusType">
    <input type="hidden" name="status_value" id="statusValue">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete buttons
    document.querySelectorAll('.delete-item').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.id;
            const itemDesc = this.dataset.desc;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            
            document.getElementById('deleteItemDesc').textContent = itemDesc;
            document.getElementById('deleteForm').action = '/azteamcrm/order-items/' + itemId + '/delete';
            
            modal.show();
        });
    });
    
    // Handle supplier status updates
    document.querySelectorAll('.update-supplier-status').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = this.dataset.id;
            const status = this.dataset.status;
            const dropdownButton = this.closest('.dropdown').querySelector('button[data-bs-toggle="dropdown"]');
            
            // Send AJAX request
            fetch(`/azteamcrm/order-items/${itemId}/update-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `csrf_token=<?= $csrf_token ?>&status_type=supplier_status&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the badge directly without reload
                    if (dropdownButton && data.badge) {
                        dropdownButton.innerHTML = data.badge;
                    }
                    // Show success message
                    showAlert('success', data.message || 'Supplier status updated successfully');
                } else {
                    showAlert('danger', data.message || 'Failed to update supplier status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while updating status');
            });
        });
    });
    
    // Handle item status updates
    document.querySelectorAll('.update-item-status').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = this.dataset.id;
            const status = this.dataset.status;
            const dropdownButton = this.closest('.dropdown').querySelector('button[data-bs-toggle="dropdown"]');
            
            // Send AJAX request
            fetch(`/azteamcrm/order-items/${itemId}/update-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `csrf_token=<?= $csrf_token ?>&status_type=order_item_status&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the badge directly without reload
                    if (dropdownButton && data.badge) {
                        dropdownButton.innerHTML = data.badge;
                    }
                    // Show success message
                    showAlert('success', data.message || 'Item status updated successfully');
                    
                    // If status changed to completed, update the progress counter
                    if (status === 'completed') {
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    showAlert('danger', data.message || 'Failed to update item status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while updating status');
            });
        });
    });
    
    // Helper function to show alerts
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        const alertContainer = document.querySelector('.alert');
        if (alertContainer) {
            alertContainer.remove();
        }
        document.querySelector('h1').insertAdjacentHTML('afterend', alertHtml);
    }
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
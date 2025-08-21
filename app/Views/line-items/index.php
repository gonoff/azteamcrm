<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Line Items - Order #<?= $order->id ?></h1>
    <div>
        <a href="/azteamcrm/orders/<?= $order->id ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Order
        </a>
        <a href="/azteamcrm/orders/<?= $order->id ?>/line-items/create" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Add Line Item
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
                <strong>Client:</strong> <?= htmlspecialchars($order->client_name) ?><br>
                <strong>Phone:</strong> <?= htmlspecialchars($order->client_phone) ?>
            </div>
            <div class="col-md-6">
                <strong>Due Date:</strong> <?= date('F d, Y', strtotime($order->due_date)) ?><br>
                <strong>Total Value:</strong> $<?= number_format($order->total_value, 2) ?>
            </div>
        </div>
    </div>
</div>

<?php if (empty($lineItems)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> No line items have been added to this order yet.
        <a href="/azteamcrm/orders/<?= $order->id ?>/line-items/create">Add the first item</a>
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
                            <th>Method</th>
                            <th>Areas</th>
                            <th>Supplier Status</th>
                            <th>Completion Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lineItems as $item): ?>
                        <tr>
                            <td>#<?= $item->id ?></td>
                            <td>
                                <?= htmlspecialchars($item->product_description) ?>
                                <?php if ($item->color_specification): ?>
                                    <br><small class="text-muted">Color: <?= htmlspecialchars($item->color_specification) ?></small>
                                <?php endif; ?>
                                <?php if ($item->line_item_notes): ?>
                                    <br><small class="text-muted"><i class="bi bi-sticky"></i> Has notes</small>
                                <?php endif; ?>
                            </td>
                            <td><?= $item->getProductTypeLabel() ?></td>
                            <td><?= $item->getSizeLabel() ?></td>
                            <td><?= $item->quantity ?></td>
                            <td><?= $item->getCustomizationMethodLabel() ?></td>
                            <td>
                                <?php 
                                $areas = $item->getCustomizationAreasArray();
                                echo ucwords(implode(', ', $areas));
                                ?>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle p-0 border-0" type="button" data-bs-toggle="dropdown">
                                        <?= $item->getSupplierStatusBadge() ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><h6 class="dropdown-header">Update Supplier Status</h6></li>
                                        <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->id ?>" data-status="awaiting_to_order">
                                            <span class="badge bg-secondary">Awaiting to Order</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->id ?>" data-status="order_made">
                                            <span class="badge bg-info">Order Made</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->id ?>" data-status="order_arrived">
                                            <span class="badge bg-primary">Order Arrived</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->id ?>" data-status="order_delivered">
                                            <span class="badge bg-success">Order Delivered</span>
                                        </a></li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle p-0 border-0" type="button" data-bs-toggle="dropdown">
                                        <?= $item->getCompletionStatusBadge() ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><h6 class="dropdown-header">Update Completion Status</h6></li>
                                        <li><a class="dropdown-item update-completion-status" href="#" data-id="<?= $item->id ?>" data-status="waiting_approval">
                                            <span class="badge bg-warning">Waiting Approval</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-completion-status" href="#" data-id="<?= $item->id ?>" data-status="artwork_approved">
                                            <span class="badge bg-info">Artwork Approved</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-completion-status" href="#" data-id="<?= $item->id ?>" data-status="material_prepared">
                                            <span class="badge bg-primary">Material Prepared</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-completion-status" href="#" data-id="<?= $item->id ?>" data-status="work_completed">
                                            <span class="badge bg-success">Work Completed</span>
                                        </a></li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <a href="/azteamcrm/line-items/<?= $item->id ?>/edit" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-item" data-id="<?= $item->id ?>" data-desc="<?= htmlspecialchars($item->product_description) ?>">
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
                        <strong>Total Items:</strong> <?= count($lineItems) ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Total Quantity:</strong> 
                        <?php 
                        $totalQty = array_sum(array_map(function($item) { return $item->quantity; }, $lineItems));
                        echo $totalQty;
                        ?>
                    </div>
                    <div class="col-md-4">
                        <?php 
                        $completed = 0;
                        foreach ($lineItems as $item) {
                            if ($item->supplier_status === 'order_delivered' && $item->completion_status === 'work_completed') {
                                $completed++;
                            }
                        }
                        $percentage = count($lineItems) > 0 ? ($completed / count($lineItems)) * 100 : 0;
                        ?>
                        <strong>Completed:</strong> <?= $completed ?> / <?= count($lineItems) ?> (<?= round($percentage) ?>%)
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
                <p>Are you sure you want to delete this line item?</p>
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
            document.getElementById('deleteForm').action = '/azteamcrm/line-items/' + itemId + '/delete';
            
            modal.show();
        });
    });
    
    // Handle supplier status updates
    document.querySelectorAll('.update-supplier-status').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = this.dataset.id;
            const status = this.dataset.status;
            
            // Create and submit form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/azteamcrm/line-items/' + itemId + '/update-status';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= $csrf_token ?>';
            form.appendChild(csrfInput);
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'supplier_status';
            statusInput.value = status;
            form.appendChild(statusInput);
            
            document.body.appendChild(form);
            form.submit();
        });
    });
    
    // Handle completion status updates
    document.querySelectorAll('.update-completion-status').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = this.dataset.id;
            const status = this.dataset.status;
            
            // Create and submit form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/azteamcrm/line-items/' + itemId + '/update-status';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= $csrf_token ?>';
            form.appendChild(csrfInput);
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'completion_status';
            statusInput.value = status;
            form.appendChild(statusInput);
            
            document.body.appendChild(form);
            form.submit();
        });
    });
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Today's Production Schedule</h1>
    <div>
        <a href="/azteamcrm/production" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="bi bi-calendar-day"></i> <strong>Today is <?= date('l, F j, Y') ?></strong>
            <br>Showing items due today, tomorrow, and items that should be started for upcoming orders.
        </div>
    </div>
</div>

<?php if (empty($todayItems)): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i> No items scheduled for production today.
    </div>
<?php else: ?>
    <?php 
    // Group items by priority
    $dueToday = [];
    $dueTomorrow = [];
    $startToday = [];
    
    foreach ($todayItems as $item) {
        if ($item->priority === 'due_today') {
            $dueToday[] = $item;
        } elseif ($item->priority === 'due_tomorrow') {
            $dueTomorrow[] = $item;
        } else {
            $startToday[] = $item;
        }
    }
    ?>
    
    <?php if (!empty($dueToday)): ?>
    <div class="card mb-3 border-danger">
        <div class="card-header card-header-danger">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Due Today (<?= count($dueToday) ?> items)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dueToday as $item): ?>
                        <tr>
                            <td>
                                <a href="/azteamcrm/orders/<?= $item->order_id ?>" target="_blank">#<?= $item->order_id ?></a>
                            </td>
                            <td><?= htmlspecialchars($item->customer_name) ?></td>
                            <td>
                                <?= htmlspecialchars($item->product_description) ?>
                                <br><small class="text-muted"><?= $item->getCustomMethodLabel() ?> - <?= $item->getSizeLabel() ?></small>
                            </td>
                            <td><?= $item->quantity ?></td>
                            <td><?= $item->getStatusBadge() ?></td>
                            <td>
                                <?php if ($item->order_item_status === 'pending'): ?>
                                <button class="btn btn-sm btn-primary quick-start" data-id="<?= $item->order_item_id ?>">
                                    <i class="bi bi-play-fill"></i> Start
                                </button>
                                <?php elseif ($item->order_item_status === 'in_production'): ?>
                                <button class="btn btn-sm btn-success quick-complete" data-id="<?= $item->order_item_id ?>">
                                    <i class="bi bi-check"></i> Complete
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($dueTomorrow)): ?>
    <div class="card mb-3 border-warning">
        <div class="card-header card-header-warning">
            <h5 class="mb-0"><i class="bi bi-clock"></i> Due Tomorrow (<?= count($dueTomorrow) ?> items)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dueTomorrow as $item): ?>
                        <tr>
                            <td>
                                <a href="/azteamcrm/orders/<?= $item->order_id ?>" target="_blank">#<?= $item->order_id ?></a>
                            </td>
                            <td><?= htmlspecialchars($item->customer_name) ?></td>
                            <td>
                                <?= htmlspecialchars($item->product_description) ?>
                                <br><small class="text-muted"><?= $item->getCustomMethodLabel() ?> - <?= $item->getSizeLabel() ?></small>
                            </td>
                            <td><?= $item->quantity ?></td>
                            <td><?= $item->getStatusBadge() ?></td>
                            <td>
                                <?php if ($item->order_item_status === 'pending'): ?>
                                <button class="btn btn-sm btn-primary quick-start" data-id="<?= $item->order_item_id ?>">
                                    <i class="bi bi-play-fill"></i> Start
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($startToday)): ?>
    <div class="card mb-3">
        <div class="card-header card-header-info">
            <h5 class="mb-0"><i class="bi bi-calendar-plus"></i> Consider Starting Today (<?= count($startToday) ?> items)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($startToday as $item): ?>
                        <tr>
                            <td>
                                <a href="/azteamcrm/orders/<?= $item->order_id ?>" target="_blank">#<?= $item->order_id ?></a>
                            </td>
                            <td><?= htmlspecialchars($item->customer_name) ?></td>
                            <td>
                                <?= htmlspecialchars($item->product_description) ?>
                                <br><small class="text-muted"><?= $item->getCustomMethodLabel() ?> - <?= $item->getSizeLabel() ?></small>
                            </td>
                            <td><?= $item->quantity ?></td>
                            <td><?= date('M d', strtotime($item->date_due)) ?></td>
                            <td>
                                <?php if ($item->order_item_status === 'pending'): ?>
                                <button class="btn btn-sm btn-outline-info quick-start" data-id="<?= $item->order_item_id ?>">
                                    <i class="bi bi-play-fill"></i> Start
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quick start production
    document.querySelectorAll('.quick-start').forEach(button => {
        button.addEventListener('click', function() {
            updateItemStatus(this.dataset.id, 'in_production', this);
        });
    });
    
    // Quick complete
    document.querySelectorAll('.quick-complete').forEach(button => {
        button.addEventListener('click', function() {
            updateItemStatus(this.dataset.id, 'completed', this);
        });
    });
    
    function updateItemStatus(itemId, status, button) {
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
                if (status === 'completed') {
                    // Remove the row
                    button.closest('tr').remove();
                } else {
                    // Update button
                    button.innerHTML = '<i class="bi bi-check"></i> Started';
                    button.disabled = true;
                    button.classList.remove('btn-primary', 'btn-info');
                    button.classList.add('btn-success');
                }
            } else {
                showAlert('danger', 'Failed to update status: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Network error: Unable to update status. Please check your connection and try again.');
        });
    }
    
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Remove existing alerts
        document.querySelectorAll('.alert').forEach(alert => {
            if (!alert.id) alert.remove();
        });
        
        // Add new alert
        document.querySelector('h1').insertAdjacentHTML('afterend', alertHtml);
    }
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
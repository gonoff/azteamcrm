<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Pending Production Items</h1>
    <div>
        <a href="/azteamcrm/production" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php if (empty($pendingItems)): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i> No pending items! All items are either in production or completed.
    </div>
<?php else: ?>
    <div class="alert alert-info mb-3">
        <i class="bi bi-info-circle"></i> Showing <strong><?= count($pendingItems) ?></strong> items waiting to start production.
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Size</th>
                            <th>Quantity</th>
                            <th>Method</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingItems as $item): ?>
                        <?php 
                        $isOverdue = strtotime($item->date_due) < strtotime('today');
                        $isDueSoon = strtotime($item->date_due) <= strtotime('+3 days');
                        $rowClass = $isOverdue ? 'table-danger' : ($isDueSoon ? 'table-warning' : '');
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td>
                                <a href="/azteamcrm/orders/<?= $item->order_id ?>" target="_blank">
                                    #<?= $item->order_id ?>
                                </a>
                            </td>
                            <td>
                                <?= htmlspecialchars($item->customer_name) ?>
                                <?php if ($item->company_name): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($item->company_name) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($item->product_description) ?>
                                <?php if ($item->note_item): ?>
                                    <i class="bi bi-sticky text-warning" title="Has notes"></i>
                                <?php endif; ?>
                            </td>
                            <td><?= $item->getSizeLabel() ?></td>
                            <td><?= $item->quantity ?></td>
                            <td><?= $item->getCustomMethodLabel() ?></td>
                            <td>
                                <?= date('M d, Y', strtotime($item->date_due)) ?>
                                <?php if ($isOverdue): ?>
                                    <span class="badge bg-danger">OVERDUE</span>
                                <?php elseif ($isDueSoon): ?>
                                    <span class="badge bg-warning">DUE SOON</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info start-production" 
                                        data-id="<?= $item->order_item_id ?>">
                                    <i class="bi bi-play-fill"></i> Start Production
                                </button>
                                <a href="/azteamcrm/order-items/<?= $item->order_item_id ?>/edit" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.start-production').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.id;
            
            fetch(`/azteamcrm/order-items/${itemId}/update-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `csrf_token=<?= $csrf_token ?>&status_type=order_item_status&status=in_production`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the row from the table
                    this.closest('tr').remove();
                    
                    // Check if table is now empty
                    if (document.querySelector('tbody').children.length === 0) {
                        location.reload();
                    }
                } else {
                    alert('Failed to update status: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating status');
            });
        });
    });
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
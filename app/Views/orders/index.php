<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Order Management</h1>
    <a href="/azteamcrm/orders/create" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> New Order
    </a>
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

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search orders by client name, phone, or order number...">
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover searchable-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Client</th>
                        <th>Phone</th>
                        <th>Date Received</th>
                        <th>Due Date</th>
                        <th>Total</th>
                        <th>Outstanding</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No orders found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <strong>#<?= $order->id ?></strong>
                                    <?php if ($order->is_rush_order): ?>
                                        <span class="badge bg-danger ms-1">RUSH</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($order->client_name) ?></td>
                                <td><?= htmlspecialchars($order->client_phone) ?></td>
                                <td><?= date('M d, Y', strtotime($order->date_received)) ?></td>
                                <td>
                                    <?= date('M d, Y', strtotime($order->due_date)) ?>
                                    <?php if ($order->isOverdue() && $order->payment_status !== 'paid'): ?>
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    <?php elseif ($order->isDueSoon() && $order->payment_status !== 'paid'): ?>
                                        <span class="badge bg-warning ms-1">Due Soon</span>
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format($order->total_value, 2) ?></td>
                                <td>
                                    <?php if ($order->outstanding_balance > 0): ?>
                                        <span class="text-danger">$<?= number_format($order->outstanding_balance, 2) ?></span>
                                    <?php else: ?>
                                        <span class="text-success">$0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($order->payment_status === 'paid'): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php elseif ($order->payment_status === 'partial'): ?>
                                        <span class="badge bg-warning">Partial</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="/azteamcrm/orders/<?= $order->id ?>" 
                                           class="btn btn-outline-primary" 
                                           title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="/azteamcrm/orders/<?= $order->id ?>/edit" 
                                           class="btn btn-outline-secondary" 
                                           title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <?php if ($_SESSION['user_role'] === 'administrator'): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-danger delete-order-btn" 
                                                    data-order-id="<?= $order->id ?>"
                                                    data-client="<?= htmlspecialchars($order->client_name) ?>"
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Order Modal -->
<div class="modal fade" id="deleteOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the order for <strong id="deleteClientName"></strong>?</p>
                <p class="text-danger">This action cannot be undone and will also delete all associated line items.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteOrderForm" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <button type="submit" class="btn btn-danger">Delete Order</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const table = document.querySelector('.searchable-table');
    const rows = table.querySelectorAll('tbody tr');
    
    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
    
    // Delete order functionality
    <?php if ($_SESSION['user_role'] === 'administrator'): ?>
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteOrderModal'));
    
    document.querySelectorAll('.delete-order-btn').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            const clientName = this.dataset.client;
            
            document.getElementById('deleteClientName').textContent = clientName;
            document.getElementById('deleteOrderForm').action = `/azteamcrm/orders/${orderId}/delete`;
            
            deleteModal.show();
        });
    });
    <?php endif; ?>
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Customer Details</h2>
        <div>
            <a href="/azteamcrm/customers" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Customers
            </a>
            <a href="/azteamcrm/customers/<?= $customer->customer_id ?>/edit" class="btn btn-secondary">
                <i class="bi bi-pencil"></i> Edit Customer
            </a>
            <?php if ($_SESSION['user_role'] === 'administrator' && $customer->getTotalOrders() == 0): ?>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteCustomerModal">
                    <i class="bi bi-trash"></i> Delete Customer
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
        <!-- Customer Information Card -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Full Name:</strong><br>
                        <?= htmlspecialchars($customer->full_name) ?>
                    </div>
                    
                    <?php if ($customer->company_name): ?>
                    <div class="mb-3">
                        <strong>Company:</strong><br>
                        <?= htmlspecialchars($customer->company_name) ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <strong>Address:</strong><br>
                        <?= htmlspecialchars($customer->address_line_1) ?><br>
                        <?php if ($customer->address_line_2): ?>
                            <?= htmlspecialchars($customer->address_line_2) ?><br>
                        <?php endif; ?>
                        <?= htmlspecialchars($customer->city . ', ' . $customer->state . ' ' . $customer->zip_code) ?>
                    </div>
                    
                    <?php if ($customer->phone_number): ?>
                    <div class="mb-3">
                        <strong>Phone:</strong><br>
                        <?= $customer->formatPhoneNumber() ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($customer->email): ?>
                    <div class="mb-3">
                        <strong>Email:</strong><br>
                        <a href="mailto:<?= htmlspecialchars($customer->email) ?>">
                            <?= htmlspecialchars($customer->email) ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <strong>Status:</strong><br>
                        <?= $customer->getStatusBadge() ?>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Customer Since:</strong><br>
                        <?= date('F d, Y', strtotime($customer->date_created)) ?>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h3 class="text-primary"><?= $customer->getTotalOrders() ?></h3>
                            <small class="text-muted">Total Orders</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h3 class="text-success">$<?= number_format($customer->getTotalRevenue(), 2) ?></h3>
                            <small class="text-muted">Total Revenue</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Section -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Order History</h5>
                    <a href="/azteamcrm/orders/create?customer_id=<?= $customer->customer_id ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus"></i> New Order
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i> No orders found for this customer.
                            <a href="/azteamcrm/orders/create?customer_id=<?= $customer->customer_id ?>" class="alert-link">Create the first order</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Due Date</th>
                                        <th>Total</th>
                                        <th>Order Status</th>
                                        <th>Payment Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <a href="/azteamcrm/orders/<?= $order->order_id ?>" class="text-decoration-none">
                                                #<?= $order->order_id ?>
                                            </a>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($order->date_created)) ?></td>
                                        <td>
                                            <?= date('M d, Y', strtotime($order->date_due)) ?>
                                            <?= $order->getUrgencyBadge() ?>
                                        </td>
                                        <td>$<?= number_format($order->order_total, 2) ?></td>
                                        <td><?= $order->getOrderStatusBadge() ?></td>
                                        <td><?= $order->getStatusBadge() ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="/azteamcrm/orders/<?= $order->order_id ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="/azteamcrm/orders/<?= $order->order_id ?>/edit" 
                                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </div>
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
    </div>
</div>

<!-- Delete Customer Modal -->
<?php if ($_SESSION['user_role'] === 'administrator' && $customer->getTotalOrders() == 0): ?>
<div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-labelledby="deleteCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCustomerModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this customer?</p>
                <p class="text-danger">
                    <strong>Customer:</strong> <?= htmlspecialchars($customer->full_name) ?>
                    <?php if ($customer->company_name): ?>
                        (<?= htmlspecialchars($customer->company_name) ?>)
                    <?php endif; ?>
                </p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="/azteamcrm/customers/<?= $customer->customer_id ?>/delete" class="form-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Delete Customer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
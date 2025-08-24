<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<h1 class="h2">Dashboard</h1>

<!-- Statistics Cards -->
<div class="row mt-4">
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-muted">Total Orders</h5>
                <h2 class="card-text"><?= number_format($stats['total_orders']) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-muted">Pending Orders</h5>
                <h2 class="card-text text-warning"><?= number_format($stats['pending_orders']) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-muted">Rush Orders</h5>
                <h2 class="card-text text-danger"><?= number_format($stats['rush_orders']) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-muted">Outstanding Balance</h5>
                <h2 class="card-text">$<?= number_format($stats['outstanding_balance'], 2) ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-muted">Total Revenue</h5>
                <h2 class="card-text text-success">$<?= number_format($stats['total_revenue'], 2) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-muted">Due Today</h5>
                <h2 class="card-text"><?= number_format($stats['orders_due_today']) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-muted">Overdue</h5>
                <h2 class="card-text text-danger"><?= number_format($stats['orders_overdue']) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-muted">In Production</h5>
                <h2 class="card-text text-info"><?= number_format($stats['items_in_production']) ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Urgent Orders -->
<?php if (!empty($urgentOrders)): ?>
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header card-header-danger">
                <h5 class="mb-0">Urgent Orders</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Client</th>
                                <th>Due Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($urgentOrders as $order): ?>
                            <tr>
                                <td>#<?= $order->order_id ?></td>
                                <td><?= htmlspecialchars($order->full_name ?? $order->company_name ?? 'N/A') ?></td>
                                <td><?= date('M d, Y', strtotime($order->date_due)) ?></td>
                                <td>$<?= number_format($order->order_total, 2) ?></td>
                                <td>
                                    <?= $order->getUrgencyBadge() ?>
                                    <?= $order->getStatusBadge() ?>
                                </td>
                                <td>
                                    <a href="/azteamcrm/orders/<?= $order->order_id ?>" class="btn btn-sm btn-secondary">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Orders -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Orders</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentOrders)): ?>
                    <p class="text-muted">No orders found.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Client</th>
                                <th>Phone</th>
                                <th>Date Received</th>
                                <th>Due Date</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?= $order->order_id ?></td>
                                <td><?= htmlspecialchars($order->customer ? $order->customer->full_name : 'N/A') ?></td>
                                <td><?= $order->customer ? $order->customer->formatPhoneNumber() : 'N/A' ?></td>
                                <td><?= date('M d', strtotime($order->date_created)) ?></td>
                                <td><?= date('M d', strtotime($order->date_due)) ?></td>
                                <td>$<?= number_format($order->order_total, 2) ?></td>
                                <td><?= $order->getStatusBadge() ?></td>
                                <td class="table-actions">
                                    <a href="/azteamcrm/orders/<?= $order->order_id ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="/azteamcrm/orders/<?= $order->order_id ?>/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="/azteamcrm/orders/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> New Order
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
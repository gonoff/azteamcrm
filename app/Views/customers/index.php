<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Customer Management</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="/azteamcrm/customers/create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Customer
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="customersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Phone</th>
                            <th>City, State</th>
                            <th>Status</th>
                            <th>Total Orders</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?= $customer->customer_id ?></td>
                            <td><?= htmlspecialchars($customer->full_name) ?></td>
                            <td><?= htmlspecialchars($customer->company_name ?: '-') ?></td>
                            <td><?= $customer->formatPhoneNumber() ?></td>
                            <td><?= htmlspecialchars($customer->city . ', ' . $customer->state) ?></td>
                            <td><?= $customer->getStatusBadge() ?></td>
                            <td>
                                <span class="badge bg-info"><?= $customer->getTotalOrders() ?></span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="/azteamcrm/customers/<?= $customer->customer_id ?>" 
                                       class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="/azteamcrm/customers/<?= $customer->customer_id ?>/edit" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($_SESSION['user_role'] === 'administrator' && $customer->getTotalOrders() == 0): ?>
                                    <form method="POST" action="/azteamcrm/customers/<?= $customer->customer_id ?>/delete" 
                                          style="display: inline;" 
                                          onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#customersTable').DataTable({
        order: [[1, 'asc']],
        pageLength: 25
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
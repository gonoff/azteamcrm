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

    <!-- Search Bar -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="/azteamcrm/customers" class="row g-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               value="<?= htmlspecialchars($search_term ?? '') ?>"
                               placeholder="Search customers by name, company, phone, or email...">
                        <button class="btn btn-primary" type="submit">Search</button>
                        <?php if (!empty($search_term)): ?>
                        <a href="/azteamcrm/customers" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Info -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <?= $pagination_info ?? '' ?>
        <?php if (!empty($search_term)): ?>
        <small class="text-muted">Search: "<?= htmlspecialchars($search_term) ?>"</small>
        <?php endif; ?>
    </div>

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
                                <span class="badge badge-info"><?= $customer->getTotalOrders() ?></span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="/azteamcrm/customers/<?= $customer->customer_id ?>" 
                                       class="btn btn-sm btn-secondary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="/azteamcrm/customers/<?= $customer->customer_id ?>/edit" 
                                       class="btn btn-sm btn-secondary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($_SESSION['user_role'] === 'administrator' && $customer->getTotalOrders() == 0): ?>
                                    <form method="POST" action="/azteamcrm/customers/<?= $customer->customer_id ?>/delete" 
                                          class="form-inline" 
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

    <!-- Pagination Controls -->
    <?php if (isset($pagination_html)): ?>
    <div class="d-flex justify-content-between align-items-center mt-4">
        <?= $pagination_info ?? '' ?>
        <?= $pagination_html ?? '' ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
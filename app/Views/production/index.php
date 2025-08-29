<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Production Dashboard</h1>
    <div>
        <a href="/azteamcrm/production/materials" class="btn btn-outline-primary">
            <i class="bi bi-list-check"></i> Materials Report
        </a>
        <a href="/azteamcrm/production/today" class="btn btn-outline-info">
            <i class="bi bi-calendar-day"></i> Today's Schedule
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-2 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title text-muted small">Pending</h5>
                <h3 class="card-text text-warning"><?= number_format($stats['total_pending']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title text-muted small">In Production</h5>
                <h3 class="card-text text-info"><?= number_format($stats['in_production']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title text-muted small">Completed Today</h5>
                <h3 class="card-text text-success"><?= number_format($stats['completed_today']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title text-muted small">Rush Items</h5>
                <h3 class="card-text text-danger"><?= number_format($stats['rush_items']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title text-muted small">Overdue</h5>
                <h3 class="card-text text-danger"><?= number_format($stats['overdue_items']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title text-muted small">Awaiting Supplier</h5>
                <h3 class="card-text text-secondary"><?= number_format($stats['awaiting_supplier']) ?></h3>
            </div>
        </div>
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

<!-- Filter Tabs -->
<ul class="nav nav-tabs mb-3" id="productionTabs">
    <li class="nav-item">
        <a class="nav-link active" href="#" data-filter="all">
            All Active <span class="badge badge-secondary"><?= count($productionItems) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" data-filter="pending">
            Pending <span class="badge badge-warning"><?= $stats['total_pending'] ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" data-filter="in_production">
            In Production <span class="badge badge-info"><?= $stats['in_production'] ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" data-filter="overdue">
            Overdue <span class="badge badge-danger"><?= $stats['overdue_items'] ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" data-filter="due_today">
            Due Today <span class="badge badge-warning"><?= count($itemsDueToday) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#" data-filter="rush">
            Rush Orders <span class="badge badge-danger"><?= $stats['rush_items'] ?></span>
        </a>
    </li>
</ul>

<!-- Bulk Actions Bar -->
<div id="bulkActionsBar" class="bulk-actions-bar mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <strong><span id="selectedCount">0</span> items selected</strong>
        </div>
        <div>
            <button class="btn btn-sm btn-primary" onclick="bulkUpdateStatus('in_production')">
                <i class="bi bi-play-fill"></i> Mark In Production
            </button>
            <button class="btn btn-sm btn-success" onclick="bulkUpdateStatus('completed')">
                <i class="bi bi-check-circle"></i> Mark Completed
            </button>
            <div class="dropdown d-inline">
                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-truck"></i> Update Supplier Status
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="bulkUpdateSupplierStatus('awaiting_order'); return false;">
                        <span class="badge badge-secondary">Awaiting Order</span>
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkUpdateSupplierStatus('order_made'); return false;">
                        <span class="badge badge-info">Order Made</span>
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkUpdateSupplierStatus('order_arrived'); return false;">
                        <span class="badge badge-primary">Order Arrived</span>
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkUpdateSupplierStatus('order_delivered'); return false;">
                        <span class="badge badge-success">Order Delivered</span>
                    </a></li>
                </ul>
            </div>
            <button class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                <i class="bi bi-x"></i> Clear Selection
            </button>
        </div>
    </div>
</div>

<!-- Search Bar -->
<div class="mb-3">
    <form method="GET" action="/azteamcrm/production" class="d-flex">
        <input type="text" name="search" class="form-control me-2" 
               placeholder="Search by product type, description, or notes..." 
               value="<?= htmlspecialchars($search_term) ?>">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-search"></i> Search
        </button>
        <?php if (!empty($search_term)): ?>
        <a href="/azteamcrm/production" class="btn btn-outline-secondary ms-2">
            <i class="bi bi-x"></i> Clear
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Production Items Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($productionItems)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No active production items at this time.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="productionTable">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Size</th>
                            <th>Qty</th>
                            <th>Method</th>
                            <th>Due Date</th>
                            <th>Item Status</th>
                            <th>Supplier</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productionItems as $item): ?>
                        <tr class="<?= $item->getRowClass() ?>" 
                            data-status="<?= $item->order_item_status ?>"
                            data-urgency="<?= $item->urgency_level ?>"
                            data-search="<?= strtolower($item->order_id . ' ' . $item->customer_name . ' ' . $item->product_description) ?>">
                            <td>
                                <input type="checkbox" class="form-check-input item-checkbox" value="<?= $item->order_item_id ?>">
                            </td>
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
                            <td>
                                <small><?= $item->getCustomMethodLabel() ?></small>
                                <?php if ($item->custom_area && $item->custom_area !== 'N/A'): ?>
                                    <br><small class="text-muted"><?= $item->getCustomAreaLabel() ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= date('M d', strtotime($item->date_due)) ?>
                                <?= $item->getUrgencyBadge() ?>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle p-0 border-0" type="button" data-bs-toggle="dropdown">
                                        <span class="status-badge" data-item-id="<?= $item->order_item_id ?>">
                                            <?= $item->getStatusBadge() ?>
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><h6 class="dropdown-header">Update Status</h6></li>
                                        <li><a class="dropdown-item update-item-status" href="#" 
                                               data-id="<?= $item->order_item_id ?>" 
                                               data-status="pending">
                                            <span class="badge badge-warning">Pending</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-item-status" href="#" 
                                               data-id="<?= $item->order_item_id ?>" 
                                               data-status="in_production">
                                            <span class="badge badge-info">In Production</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-item-status" href="#" 
                                               data-id="<?= $item->order_item_id ?>" 
                                               data-status="completed">
                                            <span class="badge badge-success">Completed</span>
                                        </a></li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle p-0 border-0" type="button" data-bs-toggle="dropdown">
                                        <span class="supplier-badge" data-item-id="<?= $item->order_item_id ?>">
                                            <?= $item->getSupplierStatusBadge() ?>
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><h6 class="dropdown-header">Supplier Status</h6></li>
                                        <li><a class="dropdown-item update-supplier-status" href="#" 
                                               data-id="<?= $item->order_item_id ?>" 
                                               data-status="awaiting_order">
                                            <span class="badge badge-secondary">Awaiting Order</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-supplier-status" href="#" 
                                               data-id="<?= $item->order_item_id ?>" 
                                               data-status="order_made">
                                            <span class="badge badge-info">Order Made</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-supplier-status" href="#" 
                                               data-id="<?= $item->order_item_id ?>" 
                                               data-status="order_arrived">
                                            <span class="badge badge-primary">Arrived</span>
                                        </a></li>
                                        <li><a class="dropdown-item update-supplier-status" href="#" 
                                               data-id="<?= $item->order_item_id ?>" 
                                               data-status="order_delivered">
                                            <span class="badge badge-success">Delivered</span>
                                        </a></li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <a href="/azteamcrm/order-items/<?= $item->order_item_id ?>/edit" 
                                   class="btn btn-sm btn-outline-primary" 
                                   title="Edit">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab filtering
    const tabs = document.querySelectorAll('#productionTabs .nav-link');
    const rows = document.querySelectorAll('#productionTable tbody tr');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active tab
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            
            rows.forEach(row => {
                if (filter === 'all') {
                    row.classList.remove('d-none');
                } else if (filter === 'pending' || filter === 'in_production') {
                    if (row.dataset.status === filter) {
                        row.classList.remove('d-none');
                    } else {
                        row.classList.add('d-none');
                    }
                } else {
                    if (row.dataset.urgency === filter) {
                        row.classList.remove('d-none');
                    } else {
                        row.classList.add('d-none');
                    }
                }
            });
        });
    });
    
    // Note: Search functionality now handled server-side via GET form
    
    // Checkbox selection
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => {
            if (!cb.closest('tr').classList.contains('d-none')) {
                cb.checked = this.checked;
            }
        });
        updateBulkActionsBar();
    });
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkActionsBar);
    });
    
    function updateBulkActionsBar() {
        const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
        
        // Always update the count, never hide the bar
        selectedCount.textContent = checkedCount;
        
        // Update select all checkbox
        selectAll.checked = checkedCount === checkboxes.length && checkedCount > 0;
    }
    
    // Individual status updates
    document.querySelectorAll('.update-item-status').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = this.dataset.id;
            const status = this.dataset.status;
            
            // Validate required data
            if (!itemId || !status) {
                showAlert('danger', 'Invalid item or status data. Please refresh the page and try again.');
                return;
            }
            
            fetch(`/azteamcrm/order-items/${itemId}/update-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `csrf_token=<?= $csrf_token ?>&status_type=order_item_status&status=${status}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update the badge
                    const badge = document.querySelector(`.status-badge[data-item-id="${itemId}"]`);
                    if (!badge) {
                        showAlert('warning', 'Status updated but display may be inconsistent. Please refresh if needed.');
                        return;
                    }
                    
                    if (data.badge) {
                        badge.innerHTML = data.badge;
                    }
                    
                    // Update row data attribute
                    const row = badge.closest('tr');
                    if (row) {
                        row.dataset.status = status;
                    }
                    
                    // Show success message
                    showAlert('success', 'Status updated successfully');
                    
                    // If item is completed, remove from active view
                    if (status === 'completed') {
                        const row = badge.closest('tr');
                        if (row) {
                            row.remove();
                            
                            // Update item count in info display
                            const tbody = document.querySelector('#productionTable tbody');
                            if (tbody && tbody.children.length === 0) {
                                const tableContainer = document.querySelector('.table-responsive');
                                tableContainer.innerHTML = `
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle"></i> All items completed! Great work.
                                    </div>
                                `;
                            }
                        }
                    }
                } else {
                    showAlert('danger', data.message || 'Failed to update status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                let errorMessage = 'Network error: Unable to update status.';
                if (error.message.includes('HTTP error')) {
                    errorMessage = `Server error: ${error.message}. Please try again or contact support.`;
                }
                showAlert('danger', errorMessage);
            });
        });
    });
    
    // Supplier status updates
    document.querySelectorAll('.update-supplier-status').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = this.dataset.id;
            const status = this.dataset.status;
            
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
                    // Update the badge
                    const badge = document.querySelector(`.supplier-badge[data-item-id="${itemId}"]`);
                    if (badge && data.badge) {
                        badge.innerHTML = data.badge;
                    }
                    
                    showAlert('success', 'Supplier status updated successfully');
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
});

// Bulk update functions
function bulkUpdateStatus(status) {
    const selectedIds = Array.from(document.querySelectorAll('.item-checkbox:checked'))
        .map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        showAlert('warning', 'No items selected. Please select items to update.');
        return;
    }
    
    // Validate status parameter
    const validStatuses = ['pending', 'in_production', 'completed'];
    if (!status || !validStatuses.includes(status)) {
        showAlert('danger', 'Invalid status selected. Please try again.');
        return;
    }
    
    if (!confirm(`Update ${selectedIds.length} items to ${status.replace('_', ' ')}?`)) {
        return;
    }
    
    const formData = new URLSearchParams();
    formData.append('csrf_token', '<?= $csrf_token ?>');
    formData.append('status_type', 'order_item_status');
    formData.append('status_value', status);
    selectedIds.forEach(id => formData.append('item_ids[]', id));
    
    fetch('/azteamcrm/production/bulk-update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            
            // Update UI for each selected item
            selectedIds.forEach(id => {
                const checkbox = document.querySelector(`.item-checkbox[value="${id}"]`);
                if (checkbox) {
                    const row = checkbox.closest('tr');
                    if (status === 'completed') {
                        // Remove completed items from view
                        row.remove();
                    } else {
                        // Update status badge and row data
                        row.dataset.status = status;
                        const statusBadge = row.querySelector('.status-badge');
                        if (statusBadge) {
                            // Update badge appearance based on new status
                            const badgeClass = status === 'in_production' ? 'badge-info' : 'badge-warning';
                            const badgeText = status.replace('_', ' ').toUpperCase();
                            statusBadge.innerHTML = `<span class="badge ${badgeClass}">${badgeText}</span>`;
                        }
                    }
                }
            });
            
            // Clear selection
            clearSelection();
            
            // Check if table is empty
            const tbody = document.querySelector('#productionTable tbody');
            if (tbody && tbody.children.length === 0) {
                const tableContainer = document.querySelector('.table-responsive');
                tableContainer.innerHTML = `
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> All items completed! Great work.
                    </div>
                `;
            }
        } else {
            showAlert('danger', data.message || 'Failed to update items');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        let errorMessage = 'Network error: Unable to update items.';
        if (error.message.includes('HTTP error')) {
            errorMessage = `Server error: ${error.message}. Please try again or contact support.`;
        }
        showAlert('danger', errorMessage);
    });
}

function bulkUpdateSupplierStatus(status) {
    const selectedIds = Array.from(document.querySelectorAll('.item-checkbox:checked'))
        .map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        showAlert('warning', 'No items selected. Please select items to update.');
        return;
    }
    
    // Validate supplier status parameter
    const validSupplierStatuses = ['awaiting_order', 'order_made', 'order_arrived', 'order_delivered'];
    if (!status || !validSupplierStatuses.includes(status)) {
        showAlert('danger', 'Invalid supplier status selected. Please try again.');
        return;
    }
    
    if (!confirm(`Update supplier status for ${selectedIds.length} items to ${status.replace(/_/g, ' ')}?`)) {
        return;
    }
    
    const formData = new URLSearchParams();
    formData.append('csrf_token', '<?= $csrf_token ?>');
    formData.append('status_type', 'supplier_status');
    formData.append('status_value', status);
    selectedIds.forEach(id => formData.append('item_ids[]', id));
    
    fetch('/azteamcrm/production/bulk-update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            
            // Update supplier status badges for each selected item
            selectedIds.forEach(id => {
                const checkbox = document.querySelector(`.item-checkbox[value="${id}"]`);
                if (checkbox) {
                    const row = checkbox.closest('tr');
                    const supplierBadge = row.querySelector('.supplier-badge');
                    if (supplierBadge) {
                        // Update supplier badge appearance based on new status
                        const badgeClass = status === 'order_delivered' ? 'badge-success' : 
                                         status === 'order_arrived' ? 'badge-info' : 
                                         status === 'order_made' ? 'badge-warning' : 'badge-secondary';
                        const badgeText = status.replace(/_/g, ' ').toUpperCase();
                        supplierBadge.innerHTML = `<span class="badge ${badgeClass}">${badgeText}</span>`;
                    }
                }
            });
            
            // Clear selection
            clearSelection();
        } else {
            showAlert('danger', data.message || 'Failed to update supplier status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        let errorMessage = 'Network error: Unable to update supplier status.';
        if (error.message.includes('HTTP error')) {
            errorMessage = `Server error: ${error.message}. Please try again or contact support.`;
        }
        showAlert('danger', errorMessage);
    });
}

function clearSelection() {
    document.querySelectorAll('.item-checkbox:checked').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    // Update the count to show 0 items selected
    document.getElementById('selectedCount').textContent = '0';
}

function showAlert(type, message) {
    // Validate input
    if (!type || !message) {
        console.error('showAlert called with invalid parameters:', { type, message });
        return;
    }
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="bi ${getAlertIcon(type)}"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove existing temporary alerts (keep permanent ones)
    document.querySelectorAll('.alert').forEach(alert => {
        if (!alert.id && !alert.classList.contains('alert-info')) {
            alert.remove();
        }
    });
    
    // Add new alert
    const headerElement = document.querySelector('h1');
    if (headerElement) {
        headerElement.insertAdjacentHTML('afterend', alertHtml);
        
        // Auto-dismiss success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                const successAlert = document.querySelector('.alert-success');
                if (successAlert) {
                    successAlert.remove();
                }
            }, 5000);
        }
    } else {
        console.error('Could not find header element to insert alert');
    }
}

function getAlertIcon(type) {
    const icons = {
        'success': 'bi-check-circle',
        'danger': 'bi-exclamation-triangle',
        'warning': 'bi-exclamation-circle',
        'info': 'bi-info-circle'
    };
    return icons[type] || 'bi-info-circle';
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
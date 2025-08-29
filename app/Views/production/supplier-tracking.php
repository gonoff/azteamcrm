<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Supplier Tracking</h1>
    <div>
        <a href="/azteamcrm/production" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Production
        </a>
    </div>
</div>

<!-- Tab Navigation -->
<ul class="nav nav-tabs mb-3" id="orderTabs">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'active' ? 'active' : '' ?>" 
           href="/azteamcrm/production/supplier-tracking?tab=active">
            <i class="bi bi-clock"></i> Active Orders
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'completed' ? 'active' : '' ?>" 
           href="/azteamcrm/production/supplier-tracking?tab=completed">
            <i class="bi bi-check-circle"></i> Completed Orders
        </a>
    </li>
</ul>

<!-- Sorting Controls -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center">
        <label for="sortSelect" class="me-2 mb-0"><strong>Sort by:</strong></label>
        <select class="form-select" id="sortSelect" style="width: auto; min-width: 200px;">
            <option value="urgency" <?= $currentSort === 'urgency' ? 'selected' : '' ?>>
                <?= $activeTab === 'active' ? 'Urgency (Default)' : 'Most Recent Due Date' ?>
            </option>
            <option value="due_date_asc" <?= $currentSort === 'due_date_asc' ? 'selected' : '' ?>>Due Date (Earliest First)</option>
            <option value="due_date_desc" <?= $currentSort === 'due_date_desc' ? 'selected' : '' ?>>Due Date (Latest First)</option>
            <option value="order_date_asc" <?= $currentSort === 'order_date_asc' ? 'selected' : '' ?>>Order Date (Oldest First)</option>
            <option value="order_date_desc" <?= $currentSort === 'order_date_desc' ? 'selected' : '' ?>>Order Date (Newest First)</option>
            <option value="customer_name" <?= $currentSort === 'customer_name' ? 'selected' : '' ?>>Customer Name (A-Z)</option>
        </select>
    </div>
    <div class="text-muted">
        <small><i class="bi bi-sort-alpha-down"></i> Orders grouped with their items</small>
    </div>
</div>

<?php if (empty($orders)): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i> 
        <?php if ($activeTab === 'completed'): ?>
            No completed orders found.
        <?php else: ?>
            No active orders! All orders are completed.
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info mb-3">
        <i class="bi bi-info-circle"></i> 
        Showing <strong><?= count($orders) ?></strong> <?= $activeTab ?> orders with 
        <strong><?= array_sum(array_map(function($order) { return count($order['items']); }, $orders)) ?></strong> items.
        <?php if ($activeTab === 'active'): ?>
            <span class="ms-3"><small>Auto-refreshes every 30 seconds</small></span>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 supplier-tracking-table">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 80px;">Size</th>
                            <th style="width: 60px;">Qty</th>
                            <th style="width: 200px;">Description</th>
                            <th style="width: 100px;">Method</th>
                            <th style="width: 120px;">Production Status</th>
                            <th style="width: 120px;">Supplier Status</th>
                            <th style="width: 80px;">Material Prep</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $orderIndex => $order): ?>
                            
                            <!-- ORDER HEADER ROW -->
                            <tr class="order-header-row <?= $order['urgency_level'] === 'overdue' ? 'bg-danger-subtle' : ($order['urgency_level'] === 'rush' ? 'bg-warning-subtle' : '') ?>">
                                <td colspan="7" class="order-header-cell">
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <div class="d-flex align-items-center">
                                            <strong class="me-3">
                                                <a href="/azteamcrm/orders/<?= $order['order_id'] ?>" target="_blank" class="text-decoration-none">
                                                    <i class="bi bi-box-seam"></i> Order #<?= $order['order_id'] ?>
                                                </a>
                                            </strong>
                                            <span class="me-3">
                                                <i class="bi bi-calendar3"></i> <?= date('M d, Y', strtotime($order['order_date'])) ?>
                                            </span>
                                            <span class="me-3">
                                                <i class="bi bi-clock"></i> Due: <?= date('M d, Y', strtotime($order['date_due'])) ?>
                                            </span>
                                            <span class="me-3">
                                                <i class="bi bi-person"></i> <?= htmlspecialchars($order['customer_name']) ?>
                                                <?php if ($order['company_name']): ?>
                                                    <small class="text-muted">(<?= htmlspecialchars($order['company_name']) ?>)</small>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <?php if ($order['urgency_level'] === 'overdue'): ?>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-exclamation-triangle-fill"></i> OVERDUE
                                                </span>
                                            <?php elseif ($order['urgency_level'] === 'rush'): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-lightning-fill"></i> RUSH ORDER
                                                </span>
                                            <?php elseif ($order['urgency_level'] === 'completed'): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle-fill"></i> COMPLETED
                                                </span>
                                            <?php endif; ?>
                                            <small class="text-muted ms-2"><?= count($order['items']) ?> items</small>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- ORDER ITEMS ROWS -->
                            <?php foreach ($order['items'] as $item): ?>
                                <tr class="order-item-row" 
                                    data-order-id="<?= $order['order_id'] ?>"
                                    data-item-id="<?= $item->order_item_id ?>">
                                    
                                    <!-- Size -->
                                    <td><?= $item->getSizeLabel() ?></td>
                                    
                                    <!-- Quantity -->
                                    <td><span class="badge bg-light text-dark"><?= $item->quantity ?></span></td>
                                    
                                    <!-- Description -->
                                    <td>
                                        <?= htmlspecialchars($item->product_description) ?>
                                        <?php if ($item->note_item): ?>
                                            <i class="bi bi-sticky text-warning ms-1" title="<?= htmlspecialchars($item->note_item) ?>"></i>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Method -->
                                    <td><?= $item->getCustomMethodLabel() ?></td>
                                    
                                    <!-- Production Status Dropdown -->
                                    <td>
                                        <?php if ($activeTab === 'completed'): ?>
                                            <?= $item->getStatusBadge() ?>
                                        <?php else: ?>
                                            <select class="form-select form-select-sm status-dropdown" 
                                                    data-item-id="<?= $item->order_item_id ?>" 
                                                    data-status-type="order_item_status"
                                                    data-current-status="<?= $item->order_item_status ?>">
                                                <option value="pending" <?= $item->order_item_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="in_production" <?= $item->order_item_status === 'in_production' ? 'selected' : '' ?>>In Production</option>
                                                <option value="completed" <?= $item->order_item_status === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Supplier Status Dropdown -->
                                    <td>
                                        <?php if ($activeTab === 'completed'): ?>
                                            <?= $item->getSupplierStatusBadge() ?>
                                        <?php else: ?>
                                            <select class="form-select form-select-sm status-dropdown supplier-status-dropdown" 
                                                    data-item-id="<?= $item->order_item_id ?>" 
                                                    data-status-type="supplier_status"
                                                    data-current-status="<?= $item->supplier_status ?>"
                                                    data-current-value="<?= $item->supplier_status ?>">
                                                <option value="" class="option-not-set">Not Set</option>
                                                <option value="awaiting_order" class="option-waiting" <?= $item->supplier_status === 'awaiting_order' ? 'selected' : '' ?>>Waiting</option>
                                                <option value="order_made" class="option-order-made" <?= $item->supplier_status === 'order_made' ? 'selected' : '' ?>>Order Made</option>
                                                <option value="order_arrived" class="option-order-arrived" <?= $item->supplier_status === 'order_arrived' ? 'selected' : '' ?>>Order Arrived</option>
                                                <option value="order_delivered" class="option-order-delivered" <?= $item->supplier_status === 'order_delivered' ? 'selected' : '' ?>>Order Delivered</option>
                                            </select>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Material Prepared Checkbox -->
                                    <td class="text-center">
                                        <?php if ($activeTab === 'completed'): ?>
                                            <?= $item->material_prepared ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>' ?>
                                        <?php else: ?>
                                            <input type="checkbox" 
                                                   class="form-check-input material-checkbox" 
                                                   data-item-id="<?= $item->order_item_id ?>"
                                                   <?= $item->material_prepared ? 'checked' : '' ?>>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <!-- Spacing between orders -->
                            <?php if ($orderIndex < count($orders) - 1): ?>
                                <tr class="order-separator">
                                    <td colspan="7" style="height: 8px; border: none; background: transparent;"></td>
                                </tr>
                            <?php endif; ?>
                            
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($activeTab === 'active'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let refreshTimeout;
    let isUpdating = false;
    
    // Auto-refresh every 30 seconds (only for active tab)
    function scheduleRefresh() {
        refreshTimeout = setTimeout(() => {
            if (!isUpdating) {
                window.location.reload();
            } else {
                // If we're updating something, try again in 5 seconds
                scheduleRefresh();
            }
        }, <?= $refreshInterval ?>);
    }
    
    // Start auto-refresh
    scheduleRefresh();
    
    // Handle sort dropdown changes
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const currentUrl = new URL(window.location);
            const newSort = this.value;
            
            // Update the sort parameter in URL
            currentUrl.searchParams.set('sort', newSort);
            
            // Navigate to new URL with sort parameter
            window.location.href = currentUrl.toString();
        });
    }
    
    // Function to update supplier status dropdown styling
    function updateSupplierDropdownStyling(dropdown) {
        const value = dropdown.value;
        const classList = dropdown.classList;
        
        // Remove all status classes
        classList.remove('status-not-set', 'status-waiting', 'status-order-made', 'status-order-arrived', 'status-order-delivered');
        
        // Add appropriate class based on value
        switch(value) {
            case '':
                classList.add('status-not-set');
                break;
            case 'awaiting_order':
                classList.add('status-waiting');
                break;
            case 'order_made':
                classList.add('status-order-made');
                break;
            case 'order_arrived':
                classList.add('status-order-arrived');
                break;
            case 'order_delivered':
                classList.add('status-order-delivered');
                break;
        }
    }
    
    // Initialize supplier dropdown styling on page load
    document.querySelectorAll('.supplier-status-dropdown').forEach(dropdown => {
        updateSupplierDropdownStyling(dropdown);
        
        // Update styling when dropdown changes
        dropdown.addEventListener('change', function() {
            updateSupplierDropdownStyling(this);
        });
    });
    
    // Handle status dropdown changes
    document.querySelectorAll('.status-dropdown').forEach(dropdown => {
        dropdown.addEventListener('change', function() {
            const itemId = this.dataset.itemId;
            const statusType = this.dataset.statusType;
            const newStatus = this.value;
            const currentStatus = this.dataset.currentStatus;
            
            if (newStatus === currentStatus) {
                return; // No change
            }
            
            isUpdating = true;
            this.disabled = true;
            
            fetch(`/azteamcrm/order-items/${itemId}/update-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `csrf_token=<?= $csrf_token ?>&status_type=${statusType}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.dataset.currentStatus = newStatus;
                    
                    // Update supplier dropdown styling if it's a supplier status change
                    if (statusType === 'supplier_status' && this.classList.contains('supplier-status-dropdown')) {
                        updateSupplierDropdownStyling(this);
                    }
                    
                    // Visual feedback
                    this.classList.add('border-success');
                    setTimeout(() => {
                        this.classList.remove('border-success');
                    }, 2000);
                } else {
                    // Revert the dropdown
                    this.value = currentStatus;
                    // Update supplier dropdown styling on revert
                    if (statusType === 'supplier_status' && this.classList.contains('supplier-status-dropdown')) {
                        updateSupplierDropdownStyling(this);
                    }
                    showAlert('danger', 'Failed to update status: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.value = currentStatus;
                // Update supplier dropdown styling on error revert
                if (statusType === 'supplier_status' && this.classList.contains('supplier-status-dropdown')) {
                    updateSupplierDropdownStyling(this);
                }
                showAlert('danger', 'Network error: Unable to update status');
            })
            .finally(() => {
                this.disabled = false;
                isUpdating = false;
            });
        });
    });
    
    // Handle material preparation checkbox changes
    document.querySelectorAll('.material-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const itemId = this.dataset.itemId;
            const prepared = this.checked ? '1' : '0';
            
            isUpdating = true;
            this.disabled = true;
            
            fetch(`/azteamcrm/production/update-material-prepared`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `csrf_token=<?= $csrf_token ?>&item_id=${itemId}&prepared=${prepared}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Visual feedback
                    this.parentElement.classList.add('bg-success-subtle');
                    setTimeout(() => {
                        this.parentElement.classList.remove('bg-success-subtle');
                    }, 2000);
                } else {
                    // Revert the checkbox
                    this.checked = !this.checked;
                    showAlert('danger', 'Failed to update material preparation: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !this.checked;
                showAlert('danger', 'Network error: Unable to update material preparation');
            })
            .finally(() => {
                this.disabled = false;
                isUpdating = false;
            });
        });
    });
    
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Remove existing alerts
        document.querySelectorAll('.alert:not(.alert-info):not(.alert-success)').forEach(alert => {
            alert.remove();
        });
        
        // Add new alert
        document.querySelector('h1').insertAdjacentHTML('afterend', alertHtml);
    }
    
    // Pause auto-refresh when user is interacting with dropdowns or checkboxes
    document.addEventListener('focusin', function(e) {
        if (e.target.matches('.status-dropdown, .material-checkbox')) {
            clearTimeout(refreshTimeout);
            isUpdating = true;
        }
    });
    
    document.addEventListener('focusout', function(e) {
        if (e.target.matches('.status-dropdown, .material-checkbox')) {
            isUpdating = false;
            scheduleRefresh();
        }
    });
});
</script>
<?php endif; ?>

<?php if ($activeTab === 'completed'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle sort dropdown changes for completed tab
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const currentUrl = new URL(window.location);
            const newSort = this.value;
            
            // Update the sort parameter in URL
            currentUrl.searchParams.set('sort', newSort);
            
            // Navigate to new URL with sort parameter
            window.location.href = currentUrl.toString();
        });
    }
});
</script>
<?php endif; ?>

<style>
/* Excel-like table styling */
.supplier-tracking-table {
    font-size: 0.875rem;
    border-collapse: separate;
    border-spacing: 0;
}

.supplier-tracking-table th {
    white-space: nowrap;
    font-weight: 600;
    border: 1px solid #dee2e6;
    background-color: #212529 !important;
    color: white;
    text-align: center;
    padding: 8px 12px;
}

.supplier-tracking-table td {
    vertical-align: middle;
    border: 1px solid #dee2e6;
    padding: 6px 12px;
}

/* Order header styling */
.order-header-row {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-left: 4px solid #007bff;
}

.order-header-cell {
    font-weight: 600;
    padding: 12px !important;
    border: 2px solid #007bff !important;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
}

/* Order item rows */
.order-item-row {
    background-color: #ffffff;
}

.order-item-row:hover {
    background-color: rgba(0, 123, 255, 0.05) !important;
}

/* Order separator */
.order-separator {
    background: transparent !important;
}

.order-separator td {
    border: none !important;
    padding: 0 !important;
}

/* Status controls */
.status-dropdown {
    min-width: 110px;
    font-size: 0.8rem;
}

.material-checkbox {
    transform: scale(1.3);
}

/* Tab styling */
.nav-tabs .nav-link {
    color: #495057;
    border: 1px solid transparent;
}

.nav-tabs .nav-link.active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.nav-tabs .nav-link:hover:not(.active) {
    background-color: #e9ecef;
    border-color: #dee2e6;
}

/* Rush order backgrounds */
.bg-danger-subtle {
    background-color: rgba(220, 53, 69, 0.1) !important;
}

.bg-warning-subtle {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

/* Professional borders */
.supplier-tracking-table {
    border: 2px solid #dee2e6;
}

.supplier-tracking-table thead th:first-child {
    border-left: 2px solid #dee2e6;
}

.supplier-tracking-table thead th:last-child {
    border-right: 2px solid #dee2e6;
}

/* Supplier Status Dropdown Styling */
.supplier-status-dropdown {
    font-weight: 500;
}

/* Dynamic dropdown background based on selected value */
.supplier-status-dropdown.status-not-set {
    background-color: #f8f9fa;
    color: #6c757d;
}

.supplier-status-dropdown.status-waiting {
    background-color: #6c757d;
    color: white;
}

.supplier-status-dropdown.status-order-made {
    background-color: #198754;
    color: white;
}

.supplier-status-dropdown.status-order-arrived {
    background-color: #dc3545;
    color: white;
}

.supplier-status-dropdown.status-order-delivered {
    background-color: #0d6efd;
    color: white;
}

/* Option styling (limited browser support but helps where available) */
.option-not-set {
    background-color: #f8f9fa !important;
    color: #6c757d !important;
}

.option-waiting {
    background-color: #6c757d !important;
    color: white !important;
}

.option-order-made {
    background-color: #198754 !important;
    color: white !important;
}

.option-order-arrived {
    background-color: #dc3545 !important;
    color: white !important;
}

.option-order-delivered {
    background-color: #0d6efd !important;
    color: white !important;
}
</style>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
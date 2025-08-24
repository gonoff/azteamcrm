<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Order #<?= $order->order_id ?></h1>
    <div>
        <a href="/azteamcrm/orders" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
        <a href="/azteamcrm/orders/<?= $order->order_id ?>/edit" class="btn btn-secondary">
            <i class="bi bi-pencil"></i> Edit Order
        </a>
        
        <?php if ($order->getBalanceDue() > 0): ?>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                <i class="bi bi-credit-card-2-front"></i> Record Payment
            </button>
        <?php endif; ?>
        
        <?php if (!in_array($order->order_status, ['cancelled', 'completed'])): ?>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                <i class="bi bi-x-circle"></i> Cancel Order
            </button>
        <?php endif; ?>
        
        <?php if ($_SESSION['user_role'] === 'administrator'): ?>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteOrderModal">
                <i class="bi bi-trash"></i> Delete Order
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

<!-- Top Section: Order Details and Production Status Side by Side -->
<div class="row mb-4">
    <div class="col-lg-7">
        <!-- Order Details Card -->
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Order Details</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Customer:</strong><br>
                        <?php if ($customer): ?>
                            <a href="/azteamcrm/customers/<?= $customer->customer_id ?>" class="text-decoration-none">
                                <?= htmlspecialchars($customer->full_name) ?>
                                <?php if ($customer->company_name): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($customer->company_name) ?></small>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Customer not found</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Phone:</strong><br>
                        <?= $customer ? $customer->formatPhoneNumber() : 'N/A' ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Order Created:</strong><br>
                        <?= date('F d, Y', strtotime($order->date_created)) ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Due Date:</strong><br>
                        <?= date('F d, Y', strtotime($order->date_due)) ?>
                        <?php if ($order->isOverdue() && $order->payment_status !== 'paid'): ?>
                            <span class="badge badge-danger ms-2">Overdue</span>
                        <?php elseif ($order->isDueSoon() && $order->payment_status !== 'paid'): ?>
                            <span class="badge badge-warning ms-2">Due Soon</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Order Status:</strong><br>
                        <?= $order->getOrderStatusBadge() ?>
                        <?php if ($order->isRushOrder()): ?>
                            <span class="badge badge-danger ms-1">RUSH</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Captured By:</strong><br>
                        <?= htmlspecialchars($user ? $user->full_name : 'Unknown') ?>
                    </div>
                </div>
                
                <?php if ($order->order_notes): ?>
                <div class="row">
                    <div class="col-12">
                        <strong>Order Notes:</strong><br>
                        <div class="alert alert-light">
                            <?= nl2br(htmlspecialchars($order->order_notes)) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-12">
                        <small class="text-muted">
                            Order ID: #<?= $order->order_id ?> | 
                            Created: <?= date('F d, Y g:i A', strtotime($order->date_created)) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-5">
        <!-- Production Status Card -->
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Production Status</h5>
            </div>
            <div class="card-body">
                <?php 
                $totalItems = count($orderItems);
                $completedItems = 0;
                $inProductionItems = 0;
                $pendingItems = 0;
                
                foreach ($orderItems as $item) {
                    if ($item->order_item_status === 'completed') {
                        $completedItems++;
                    } elseif ($item->order_item_status === 'in_production') {
                        $inProductionItems++;
                    } elseif ($item->order_item_status === 'pending') {
                        $pendingItems++;
                    }
                }
                $completionPercentage = $totalItems > 0 ? ($completedItems / $totalItems) * 100 : 0;
                ?>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <strong>Overall Progress:</strong>
                        <strong><?= round($completionPercentage) ?>%</strong>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-success" 
                             style="width: <?= $completionPercentage ?>%">
                            <?= $completedItems ?> completed
                        </div>
                        <?php if ($inProductionItems > 0): ?>
                        <div class="progress-bar bg-info" 
                             style="width: <?= $totalItems > 0 ? ($inProductionItems / $totalItems) * 100 : 0 ?>%">
                            <?= $inProductionItems ?> in production
                        </div>
                        <?php endif; ?>
                        <?php if ($pendingItems > 0): ?>
                        <div class="progress-bar bg-warning" 
                             style="width: <?= $totalItems > 0 ? ($pendingItems / $totalItems) * 100 : 0 ?>%">
                            <?= $pendingItems ?> pending
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Status Breakdown -->
                <div class="row text-center">
                    <div class="col-4">
                        <div class="text-muted small">Pending</div>
                        <div class="h5"><?= $pendingItems ?></div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">In Production</div>
                        <div class="h5"><?= $inProductionItems ?></div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Completed</div>
                        <div class="h5"><?= $completedItems ?></div>
                    </div>
                </div>
                
                <?php if ($completionPercentage == 100 && $order->payment_status === 'paid'): ?>
                    <hr>
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle"></i> Order Complete!
                    </div>
                <?php elseif ($order->isOverdue()): ?>
                    <hr>
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-exclamation-triangle"></i> Order is overdue!
                    </div>
                <?php elseif ($totalItems == 0): ?>
                    <hr>
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-info-circle"></i> No items added yet
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Middle Section: Order Items Full Width -->
<div class="row mb-4">
    <div class="col-12">
        <!-- Order Items Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Order Items</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createOrderItemModal">
                    <i class="bi bi-plus"></i> Add Item
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($orderItems)): ?>
                    <p class="text-muted text-center">No order items added yet.</p>
                <?php else: ?>
                    <div class="order-items-container">
                        <table class="table table-sm order-items-table" id="orderItemsTable">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Size</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>Method</th>
                                    <th>Supplier Status</th>
                                    <th>Item Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                <tr data-item-id="<?= $item->order_item_id ?>">
                                    <td>
                                        <span class="editable-field" 
                                              data-field="product_description" 
                                              data-value="<?= htmlspecialchars($item->product_description) ?>">
                                            <?= htmlspecialchars($item->product_description) ?>
                                        </span>
                                    </td>
                                    <td><?= $item->getSizeLabel() ?></td>
                                    <td>
                                        <span class="editable-field" 
                                              data-field="quantity" 
                                              data-value="<?= $item->quantity ?>">
                                            <?= $item->quantity ?>
                                        </span>
                                    </td>
                                    <td>
                                        $<span class="editable-field" 
                                               data-field="unit_price" 
                                               data-value="<?= $item->unit_price ?>">
                                            <?= number_format($item->unit_price, 2) ?>
                                        </span>
                                    </td>
                                    <td class="item-total">
                                        $<?= number_format($item->total_price, 2) ?>
                                    </td>
                                    <td><?= $item->getCustomMethodLabel() ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm dropdown-toggle p-0 border-0" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown">
                                                <?= $item->getSupplierStatusBadge() ?>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="awaiting_order">Awaiting Order</a></li>
                                                <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="order_made">Order Made</a></li>
                                                <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="order_arrived">Order Arrived</a></li>
                                                <li><a class="dropdown-item update-supplier-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="order_delivered">Order Delivered</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm dropdown-toggle p-0 border-0" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown">
                                                <?= $item->getStatusBadge() ?>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item update-item-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="pending">Pending</a></li>
                                                <li><a class="dropdown-item update-item-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="in_production">In Production</a></li>
                                                <li><a class="dropdown-item update-item-status" href="#" data-id="<?= $item->order_item_id ?>" data-status="completed">Completed</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary edit-item-modal" 
                                                title="Edit Item"
                                                data-item-id="<?= $item->order_item_id ?>"
                                                data-product-description="<?= htmlspecialchars($item->product_description) ?>"
                                                data-product-type="<?= $item->product_type ?>"
                                                data-product-size="<?= $item->product_size ?>"
                                                data-quantity="<?= $item->quantity ?>"
                                                data-unit-price="<?= $item->unit_price ?>"
                                                data-custom-method="<?= $item->custom_method ?>"
                                                data-custom-area="<?= htmlspecialchars($item->custom_area ?? '') ?>"
                                                data-order-item-status="<?= $item->order_item_status ?>"
                                                data-supplier-status="<?= $item->supplier_status ?>"
                                                data-note-item="<?= htmlspecialchars($item->note_item ?? '') ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
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

<!-- Bottom Section: Order Summary Full Width -->
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Order Summary</h5>
            </div>
            <div class="card-body">
                <!-- Order Totals Section -->
                <div class="mb-3">
                    <table class="table table-sm">
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-end">$<?= number_format($order->order_total, 2) ?></td>
                        </tr>
                        <tr>
                            <td>Discount:</td>
                            <td class="text-end text-danger">-$<?= number_format($order->discount_amount, 2) ?></td>
                        </tr>
                        <tr>
                            <td>
                                <?php if ($order->apply_ct_tax): ?>
                                    CT Tax (6.35%):
                                <?php else: ?>
                                    Tax:
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                +$<?= number_format($order->tax_amount, 2) ?>
                                <?php if ($order->apply_ct_tax): ?>
                                    <span class="badge badge-info ms-1" style="font-size: 0.7rem;">CT</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Shipping:</td>
                            <td class="text-end">+$<?= number_format($order->shipping_amount, 2) ?></td>
                        </tr>
                        <tr class="table-primary">
                            <th>Total Amount:</th>
                            <th class="text-end">$<?= number_format($order->getTotalAmount(), 2) ?></th>
                        </tr>
                    </table>
                </div>
                
                <hr>
                
                <!-- Payment Section -->
                <div class="mb-3">
                    <table class="table table-sm">
                        <tr>
                            <td>Amount Paid:</td>
                            <td class="text-end text-success">$<?= number_format($order->amount_paid, 2) ?></td>
                        </tr>
                        <tr class="<?= $order->getBalanceDue() > 0 ? 'table-danger' : 'table-success' ?>">
                            <th>Balance Due:</th>
                            <th class="text-end">$<?= number_format($order->getBalanceDue(), 2) ?></th>
                        </tr>
                        <tr>
                            <td>Status:</td>
                            <td class="text-end">
                                <?php if ($order->payment_status === 'paid'): ?>
                                    <span class="badge badge-success">Paid</span>
                                <?php elseif ($order->payment_status === 'partial'): ?>
                                    <span class="badge badge-warning">Partial</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Unpaid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php if ($order->getBalanceDue() <= 0): ?>
                <hr>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> Order Fully Paid
                </div>
                <?php endif; ?>
                
                <!-- Payment History -->
                <?php 
                $payments = $order->getPaymentHistory();
                if (!empty($payments)): 
                ?>
                <hr>
                <h6>Payment History</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($payment->payment_date)) ?></td>
                                <td>$<?= number_format($payment->payment_amount, 2) ?></td>
                                <td><?= htmlspecialchars($payment->payment_method ?: 'N/A') ?></td>
                                <td><?= htmlspecialchars($payment->recorded_by_name) ?></td>
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

<!-- Delete Order Modal -->
<?php if ($_SESSION['user_role'] === 'administrator'): ?>
<div class="modal fade" id="deleteOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this order for <strong><?= htmlspecialchars($customer ? $customer->full_name : 'Unknown Customer') ?></strong>?</p>
                <p class="text-danger">This action cannot be undone and will also delete all associated order items.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="/azteamcrm/orders/<?= $order->order_id ?>/delete" class="form-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <button type="submit" class="btn btn-danger">Delete Order</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Edit Order Item Modal -->
<div class="modal fade" id="editOrderItemModal" tabindex="-1" aria-labelledby="editOrderItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editOrderItemForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="editOrderItemModalLabel">Edit Order Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" id="edit_order_item_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_product_description" class="form-label">Product Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_product_description" name="product_description" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_product_type" class="form-label">Product Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_product_type" name="product_type" required>
                                <option value="">Select Product Type</option>
                                <option value="shirt">Shirt</option>
                                <option value="apron">Apron</option>
                                <option value="scrub">Scrub</option>
                                <option value="hat">Hat</option>
                                <option value="bag">Bag</option>
                                <option value="beanie">Beanie</option>
                                <option value="business_card">Business Card</option>
                                <option value="yard_sign">Yard Sign</option>
                                <option value="car_magnet">Car Magnet</option>
                                <option value="greeting_card">Greeting Card</option>
                                <option value="door_hanger">Door Hanger</option>
                                <option value="magnet_business_card">Magnet Business Card</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_product_size" class="form-label">Size <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_product_size" name="product_size" required>
                                <option value="">Select Size</option>
                                <optgroup label="Child Sizes">
                                    <option value="child_xs">Child XS</option>
                                    <option value="child_s">Child S</option>
                                    <option value="child_m">Child M</option>
                                    <option value="child_l">Child L</option>
                                    <option value="child_xl">Child XL</option>
                                </optgroup>
                                <optgroup label="Adult Sizes">
                                    <option value="xs">XS</option>
                                    <option value="s">S</option>
                                    <option value="m">M</option>
                                    <option value="l">L</option>
                                    <option value="xl">XL</option>
                                    <option value="xxl">XXL</option>
                                    <option value="xxxl">XXXL</option>
                                    <option value="xxxxl">XXXXL</option>
                                </optgroup>
                                <optgroup label="Other">
                                    <option value="one_size">One Size</option>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_quantity" name="quantity" min="1" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_unit_price" class="form-label">Unit Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="edit_unit_price" name="unit_price" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_custom_method" class="form-label">Customization Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_custom_method" name="custom_method" required>
                                <option value="">Select Method</option>
                                <option value="htv">HTV (Heat Transfer Vinyl)</option>
                                <option value="dft">DFT (Direct Film Transfer)</option>
                                <option value="embroidery">Embroidery</option>
                                <option value="sublimation">Sublimation</option>
                                <option value="printing_services">Printing Services</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customization Areas</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_customization_area_front" name="customization_area_front">
                                    <label class="form-check-label" for="edit_customization_area_front">Front</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_customization_area_back" name="customization_area_back">
                                    <label class="form-check-label" for="edit_customization_area_back">Back</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_customization_area_sleeve" name="customization_area_sleeve">
                                    <label class="form-check-label" for="edit_customization_area_sleeve">Sleeve</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_order_item_status" class="form-label">Item Status</label>
                            <select class="form-select" id="edit_order_item_status" name="order_item_status">
                                <option value="pending">Pending</option>
                                <option value="in_production">In Production</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_supplier_status" class="form-label">Supplier Status</label>
                            <select class="form-select" id="edit_supplier_status" name="supplier_status">
                                <option value="awaiting_order">Awaiting Order</option>
                                <option value="order_made">Order Made</option>
                                <option value="order_arrived">Order Arrived</option>
                                <option value="order_delivered">Order Delivered</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_note_item" class="form-label">Special Instructions / Notes</label>
                        <textarea class="form-control" id="edit_note_item" name="note_item" rows="3" placeholder="Any special instructions or notes for this item"></textarea>
                    </div>
                    
                    <div id="editModalErrors" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Order Item Modal -->
<div class="modal fade" id="createOrderItemModal" tabindex="-1" aria-labelledby="createOrderItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="createOrderItemForm" method="POST" action="/azteamcrm/orders/<?= $order->order_id ?>/order-items/store">
                <div class="modal-header">
                    <h5 class="modal-title" id="createOrderItemModalLabel">Add New Order Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="create_product_description" class="form-label">Product Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="create_product_description" name="product_description" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="create_product_type" class="form-label">Product Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="create_product_type" name="product_type" required>
                                <option value="">Select Product Type</option>
                                <option value="shirt">Shirt</option>
                                <option value="apron">Apron</option>
                                <option value="scrub">Scrub</option>
                                <option value="hat">Hat</option>
                                <option value="bag">Bag</option>
                                <option value="beanie">Beanie</option>
                                <option value="business_card">Business Card</option>
                                <option value="yard_sign">Yard Sign</option>
                                <option value="car_magnet">Car Magnet</option>
                                <option value="greeting_card">Greeting Card</option>
                                <option value="door_hanger">Door Hanger</option>
                                <option value="magnet_business_card">Magnet Business Card</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="create_product_size" class="form-label">Size <span class="text-danger">*</span></label>
                            <select class="form-select" id="create_product_size" name="product_size" required>
                                <option value="">Select Size</option>
                                <optgroup label="Child Sizes">
                                    <option value="child_xs">Child XS</option>
                                    <option value="child_s">Child S</option>
                                    <option value="child_m">Child M</option>
                                    <option value="child_l">Child L</option>
                                    <option value="child_xl">Child XL</option>
                                </optgroup>
                                <optgroup label="Adult Sizes">
                                    <option value="xs">XS</option>
                                    <option value="s">S</option>
                                    <option value="m">M</option>
                                    <option value="l">L</option>
                                    <option value="xl">XL</option>
                                    <option value="xxl">XXL</option>
                                    <option value="xxxl">XXXL</option>
                                    <option value="xxxxl">XXXXL</option>
                                </optgroup>
                                <optgroup label="Other">
                                    <option value="one_size">One Size</option>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="create_quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="create_quantity" name="quantity" min="1" value="1" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="create_unit_price" class="form-label">Unit Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="create_unit_price" name="unit_price" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="create_custom_method" class="form-label">Customization Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="create_custom_method" name="custom_method" required>
                                <option value="">Select Method</option>
                                <option value="htv">HTV (Heat Transfer Vinyl)</option>
                                <option value="dft">DFT (Direct Film Transfer)</option>
                                <option value="embroidery">Embroidery</option>
                                <option value="sublimation">Sublimation</option>
                                <option value="printing_services">Printing Services</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customization Areas</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="create_customization_area_front" name="customization_area_front">
                                    <label class="form-check-label" for="create_customization_area_front">Front</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="create_customization_area_back" name="customization_area_back">
                                    <label class="form-check-label" for="create_customization_area_back">Back</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="create_customization_area_sleeve" name="customization_area_sleeve">
                                    <label class="form-check-label" for="create_customization_area_sleeve">Sleeve</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="create_order_item_status" class="form-label">Item Status</label>
                            <select class="form-select" id="create_order_item_status" name="order_item_status">
                                <option value="pending" selected>Pending</option>
                                <option value="in_production">In Production</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="create_supplier_status" class="form-label">Supplier Status</label>
                            <select class="form-select" id="create_supplier_status" name="supplier_status">
                                <option value="awaiting_order" selected>Awaiting Order</option>
                                <option value="order_made">Order Made</option>
                                <option value="order_arrived">Order Arrived</option>
                                <option value="order_delivered">Order Delivered</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_note_item" class="form-label">Special Instructions / Notes</label>
                        <textarea class="form-control" id="create_note_item" name="note_item" rows="3" placeholder="Any special instructions or notes for this item"></textarea>
                    </div>
                    
                    <div id="createModalErrors" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Cache-busting: Force browser to use latest version
console.log('Script loaded at:', new Date().toISOString());

document.addEventListener('DOMContentLoaded', function() {
    // Show/hide paid amount field based on payment status
    const paymentStatusSelect = document.getElementById('payment_status');
    const paidAmountGroup = document.getElementById('paidAmountGroup');
    
    if (paymentStatusSelect && paidAmountGroup) {
        paymentStatusSelect.addEventListener('change', function() {
            if (this.value === 'partial') {
                paidAmountGroup.classList.remove('d-none');
            } else {
                paidAmountGroup.classList.add('d-none');
            }
        });
        
        // Trigger change event on page load to set initial state
        if (paymentStatusSelect.value === 'partial') {
            paidAmountGroup.classList.remove('d-none');
        }
    }
    
    // Inline editing functionality for order items
    const editableFields = document.querySelectorAll('.editable-field');
    
    editableFields.forEach(field => {
        field.addEventListener('click', function() {
            if (this.querySelector('input')) return; // Already editing
            
            const currentValue = this.getAttribute('data-value');
            const fieldName = this.getAttribute('data-field');
            const isPrice = fieldName === 'unit_price';
            
            // Create input element
            const input = document.createElement('input');
            input.type = isPrice ? 'number' : 'text';
            if (isPrice) {
                input.step = '0.01';
                input.min = '0';
            }
            input.value = currentValue;
            input.className = 'form-control form-control-sm';
            input.style.width = fieldName === 'product_description' ? '200px' : '80px';
            
            // Replace span content with input
            this.innerHTML = '';
            this.appendChild(input);
            input.focus();
            input.select();
            
            // Handle save on blur or enter
            const saveField = () => {
                const newValue = input.value;
                const itemId = this.closest('tr').getAttribute('data-item-id');
                
                // Update the display
                if (isPrice) {
                    this.innerHTML = parseFloat(newValue).toFixed(2);
                } else {
                    this.innerHTML = newValue;
                }
                this.setAttribute('data-value', newValue);
                
                // Send update to server
                fetch('/azteamcrm/order-items/' + itemId + '/update-inline', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'csrf_token=<?= $csrf_token ?>&field=' + fieldName + '&value=' + newValue
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update total if quantity or price changed
                        if (fieldName === 'quantity' || fieldName === 'unit_price') {
                            const row = document.querySelector('tr[data-item-id="' + itemId + '"]');
                            const qty = parseFloat(row.querySelector('[data-field="quantity"]').getAttribute('data-value'));
                            const price = parseFloat(row.querySelector('[data-field="unit_price"]').getAttribute('data-value'));
                            const totalCell = row.querySelector('.item-total');
                            totalCell.innerHTML = '$' + (qty * price).toFixed(2);
                            
                            // Update order total in financial summary if provided
                            if (data.new_total) {
                                location.reload(); // Reload to update all totals
                            }
                        }
                    } else {
                        alert('Failed to update field: ' + (data.message || 'Unknown error'));
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update field');
                    location.reload();
                });
            };
            
            input.addEventListener('blur', saveField);
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveField();
                }
            });
        });
    });
    
    // Direct event attachment for status updates - proven to work
    // Handle item status updates
    document.querySelectorAll('.update-item-status').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent Bootstrap interference
            
            const itemId = this.dataset.id;
            const status = this.dataset.status;
            
            // Add debugging
            console.log('Updating item status:', itemId, status);
            
            // Close the dropdown manually
            const dropdownToggle = this.closest('.dropdown').querySelector('.dropdown-toggle');
            const dropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
            if (dropdown) dropdown.hide();
            
            // Call the update function
            updateItemStatus(itemId, status);
        });
    });
    
    // Handle supplier status updates
    document.querySelectorAll('.update-supplier-status').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent Bootstrap interference
            
            const itemId = this.dataset.id;
            const status = this.dataset.status;
            
            // Add debugging
            console.log('Updating supplier status:', itemId, status);
            
            // Close the dropdown manually
            const dropdownToggle = this.closest('.dropdown').querySelector('.dropdown-toggle');
            const dropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
            if (dropdown) dropdown.hide();
            
            // Call the update function
            updateSupplierStatus(itemId, status);
        });
    });
    
    // Simple function for item status updates
    function updateItemStatus(itemId, newStatus) {
        console.log('Sending item status update request for item:', itemId, 'to status:', newStatus);
        
        fetch('/azteamcrm/order-items/' + itemId + '/update-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'csrf_token=<?= $csrf_token ?>&status_type=order_item_status&status=' + newStatus
        })
        .then(response => response.json())
        .then(data => {
            console.log('Item status update response:', data);
            if (data.success) {
                // Simple reload after successful update
                console.log('Status updated successfully, reloading page...');
                setTimeout(() => location.reload(), 500);
            } else {
                alert('Failed to update status: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error updating item status:', error);
            alert('Failed to update status: ' + error.message);
        });
    }
    
    // Simple function for supplier status updates
    function updateSupplierStatus(itemId, newStatus) {
        console.log('Sending supplier status update request for item:', itemId, 'to status:', newStatus);
        
        fetch('/azteamcrm/order-items/' + itemId + '/update-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'csrf_token=<?= $csrf_token ?>&status_type=supplier_status&status=' + newStatus
        })
        .then(response => response.json())
        .then(data => {
            console.log('Supplier status update response:', data);
            if (data.success) {
                // Simple reload after successful update
                console.log('Status updated successfully, reloading page...');
                setTimeout(() => location.reload(), 500);
            } else {
                alert('Failed to update supplier status: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error updating supplier status:', error);
            alert('Failed to update supplier status: ' + error.message);
        });
    }
    
    // Simple Bootstrap dropdown configuration for better positioning
    document.querySelectorAll('.dropdown').forEach(dropdown => {
        dropdown.setAttribute('data-bs-boundary', 'viewport');
        dropdown.setAttribute('data-bs-flip', 'true');
    });
    
    // Edit Order Item Modal functionality
    const editButtons = document.querySelectorAll('.edit-item-modal');
    const editModal = document.getElementById('editOrderItemModal');
    const editForm = document.getElementById('editOrderItemForm');
    
    // Create modal instance once and reuse it
    let editModalInstance = null;
    if (editModal) {
        editModalInstance = new bootstrap.Modal(editModal, {
            backdrop: 'static',
            keyboard: true
        });
        
        // Reset form and clear dynamic alerts when modal is hidden
        editModal.addEventListener('hidden.bs.modal', function () {
            if (editForm) {
                editForm.reset();
            }
            // Remove any dynamic alerts
            const alerts = document.querySelectorAll('.modal-dynamic-alert');
            alerts.forEach(alert => alert.remove());
        });
    }
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Clear any previous dynamic alerts
            const alerts = document.querySelectorAll('.modal-dynamic-alert');
            alerts.forEach(alert => alert.remove());
            
            // Get item data from data attributes
            const itemId = this.dataset.itemId;
            const productDescription = this.dataset.productDescription;
            const productType = this.dataset.productType;
            const productSize = this.dataset.productSize;
            const quantity = this.dataset.quantity;
            const unitPrice = this.dataset.unitPrice;
            const customMethod = this.dataset.customMethod;
            const customArea = this.dataset.customArea;
            const orderItemStatus = this.dataset.orderItemStatus;
            const supplierStatus = this.dataset.supplierStatus;
            const noteItem = this.dataset.noteItem;
            
            // Populate modal fields
            document.getElementById('edit_order_item_id').value = itemId;
            document.getElementById('edit_product_description').value = productDescription;
            document.getElementById('edit_product_type').value = productType || '';
            document.getElementById('edit_product_size').value = productSize || '';
            document.getElementById('edit_quantity').value = quantity;
            document.getElementById('edit_unit_price').value = unitPrice;
            document.getElementById('edit_custom_method').value = customMethod || '';
            document.getElementById('edit_order_item_status').value = orderItemStatus || 'pending';
            document.getElementById('edit_supplier_status').value = supplierStatus || 'awaiting_order';
            document.getElementById('edit_note_item').value = noteItem || '';
            
            // Handle customization areas (checkboxes)
            const frontCb = document.getElementById('edit_customization_area_front');
            const backCb = document.getElementById('edit_customization_area_back');
            const sleeveCb = document.getElementById('edit_customization_area_sleeve');
            
            if (frontCb) frontCb.checked = false;
            if (backCb) backCb.checked = false;
            if (sleeveCb) sleeveCb.checked = false;
            
            if (customArea) {
                const areas = customArea.split(',');
                areas.forEach(area => {
                    const checkbox = document.getElementById('edit_customization_area_' + area.trim());
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
            
            // Trigger product type change event to handle size disabling for non-apparel items
            const productTypeSelect = document.getElementById('edit_product_type');
            const sizeSelect = document.getElementById('edit_product_size');
            const nonApparelTypes = ['business_card', 'yard_sign', 'car_magnet', 'greeting_card', 'door_hanger', 'magnet_business_card'];
            
            if (nonApparelTypes.includes(productType)) {
                sizeSelect.disabled = true;
                sizeSelect.value = '';
            } else {
                sizeSelect.disabled = false;
            }
            
            // Show the modal using the existing instance
            if (editModalInstance) {
                editModalInstance.show();
            }
        });
    });
    
    // Handle product type change in modal
    const editProductType = document.getElementById('edit_product_type');
    if (editProductType) {
        editProductType.addEventListener('change', function() {
            const nonApparelTypes = ['business_card', 'yard_sign', 'car_magnet', 'greeting_card', 'door_hanger', 'magnet_business_card'];
            const sizeSelect = document.getElementById('edit_product_size');
            
            if (nonApparelTypes.includes(this.value)) {
                sizeSelect.disabled = true;
                sizeSelect.value = '';
            } else {
                sizeSelect.disabled = false;
            }
        });
    }
    
    // Dynamic error/success display function that always works
    function showModalMessage(message, type = 'error') {
        // Remove any existing alerts
        const existingAlerts = document.querySelectorAll('.modal-dynamic-alert');
        existingAlerts.forEach(alert => alert.remove());
        
        // Create new alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show modal-dynamic-alert`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Try to insert in modal body
        const modalBody = document.querySelector('#editOrderItemModal .modal-body');
        if (modalBody) {
            modalBody.insertBefore(alertDiv, modalBody.firstChild);
            alertDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            // Fallback to alert if modal not found
            alert(message);
        }
    }
    
    // Handle form submission
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const itemId = document.getElementById('edit_order_item_id').value;
            
            // Validate required fields
            const productDesc = formData.get('product_description');
            const quantity = formData.get('quantity');
            const unitPrice = formData.get('unit_price');
            
            if (!itemId) {
                alert('Error: No item ID found. Please refresh the page and try again.');
                return;
            }
            
            if (!productDesc || productDesc.trim() === '') {
                alert('Product description is required.');
                return;
            }
            
            if (!quantity || quantity <= 0) {
                alert('Quantity must be greater than 0.');
                return;
            }
            
            if (!unitPrice || unitPrice < 0) {
                alert('Unit price must be 0 or greater.');
                return;
            }
            
            // Disable submit button to prevent double submission
            const submitButton = editForm.querySelector('button[type="submit"]');
            let originalButtonText = '';
            if (submitButton) {
                originalButtonText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            }
            
            // Build customization areas string
            const areas = [];
            const frontCheckbox = document.getElementById('edit_customization_area_front');
            const backCheckbox = document.getElementById('edit_customization_area_back');
            const sleeveCheckbox = document.getElementById('edit_customization_area_sleeve');
            
            if (frontCheckbox && frontCheckbox.checked) areas.push('front');
            if (backCheckbox && backCheckbox.checked) areas.push('back');
            if (sleeveCheckbox && sleeveCheckbox.checked) areas.push('sleeve');
            formData.append('custom_area', areas.join(','));
            
            // Clear any previous messages
            const existingAlerts = document.querySelectorAll('.modal-dynamic-alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Log the data being sent
            console.log('Form data before sending:', {
                itemId: itemId,
                product_description: formData.get('product_description'),
                quantity: formData.get('quantity'),
                unit_price: formData.get('unit_price'),
                product_type: formData.get('product_type'),
                product_size: formData.get('product_size'),
                custom_method: formData.get('custom_method'),
                order_item_status: formData.get('order_item_status'),
                supplier_status: formData.get('supplier_status')
            });
            
            // Convert FormData to URLSearchParams for application/x-www-form-urlencoded
            const params = new URLSearchParams();
            for (const [key, value] of formData) {
                if (!key.startsWith('customization_area_')) {
                    params.append(key, value);
                }
            }
            params.append('ajax', '1'); // Flag for AJAX request
            
            // Debug log - show all parameters being sent
            console.log('Submitting edit for item:', itemId);
            console.log('Parameters being sent:', params.toString());
            
            // Send AJAX request
            fetch(`/azteamcrm/order-items/${itemId}/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params.toString()
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                if (data.success) {
                    // Show success message in modal first
                    showModalMessage(data.message || 'Order item updated successfully!', 'success');
                    
                    // Close modal and reload page after a short delay
                    setTimeout(() => {
                        if (editModalInstance) {
                            editModalInstance.hide();
                        }
                        location.reload();
                    }, 1000);
                } else {
                    // Re-enable button on error
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonText;
                    }
                    
                    // Show errors using dynamic message function
                    let errorMessage = '';
                    if (data.errors && Array.isArray(data.errors)) {
                        errorMessage = '<ul class="mb-0">' + data.errors.map(error => `<li>${error}</li>`).join('') + '</ul>';
                    } else {
                        errorMessage = data.message || 'Failed to update order item.';
                    }
                    showModalMessage(errorMessage, 'error');
                    console.error('Update failed:', data.message || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                
                // Re-enable button on error
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
                
                // Show error using dynamic message function
                showModalMessage('An error occurred while updating the item. Error: ' + error.message, 'error');
            });
        });
    }
    
    // Create Order Item Modal Form Handler
    const createForm = document.getElementById('createOrderItemForm');
    const createModalInstance = new bootstrap.Modal(document.getElementById('createOrderItemModal'));
    
    if (createForm) {
        // Handle product type change for non-apparel items
        const createProductType = document.getElementById('create_product_type');
        if (createProductType) {
            createProductType.addEventListener('change', function() {
                const nonApparelTypes = ['business_card', 'yard_sign', 'car_magnet', 'greeting_card', 'door_hanger', 'magnet_business_card'];
                const sizeSelect = document.getElementById('create_product_size');
                
                if (nonApparelTypes.includes(this.value)) {
                    sizeSelect.disabled = true;
                    sizeSelect.value = '';
                    sizeSelect.removeAttribute('required');
                } else {
                    sizeSelect.disabled = false;
                    sizeSelect.setAttribute('required', 'required');
                }
            });
        }
        
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(createForm);
            
            // Basic validation
            const productDesc = formData.get('product_description');
            const quantity = parseInt(formData.get('quantity'));
            const unitPrice = parseFloat(formData.get('unit_price'));
            
            if (!productDesc || productDesc.trim() === '') {
                alert('Product description is required.');
                return;
            }
            
            if (!quantity || quantity <= 0) {
                alert('Quantity must be greater than 0.');
                return;
            }
            
            if (!unitPrice || unitPrice < 0) {
                alert('Unit price must be 0 or greater.');
                return;
            }
            
            // Disable submit button to prevent double submission
            const submitButton = createForm.querySelector('button[type="submit"]');
            let originalButtonText = '';
            if (submitButton) {
                originalButtonText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
            }
            
            // Build customization areas string
            const areas = [];
            const frontCheckbox = document.getElementById('create_customization_area_front');
            const backCheckbox = document.getElementById('create_customization_area_back');
            const sleeveCheckbox = document.getElementById('create_customization_area_sleeve');
            
            if (frontCheckbox && frontCheckbox.checked) areas.push('front');
            if (backCheckbox && backCheckbox.checked) areas.push('back');
            if (sleeveCheckbox && sleeveCheckbox.checked) areas.push('sleeve');
            formData.append('custom_area', areas.join(','));
            
            // Clear any previous messages
            const existingAlerts = document.querySelectorAll('.modal-dynamic-alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Log the data being sent
            console.log('Create form data before sending:', {
                product_description: formData.get('product_description'),
                quantity: formData.get('quantity'),
                unit_price: formData.get('unit_price'),
                product_type: formData.get('product_type'),
                product_size: formData.get('product_size'),
                custom_method: formData.get('custom_method'),
                order_item_status: formData.get('order_item_status'),
                supplier_status: formData.get('supplier_status'),
                custom_area: areas.join(',')
            });
            
            // Convert FormData to URLSearchParams for application/x-www-form-urlencoded
            const params = new URLSearchParams();
            for (const [key, value] of formData) {
                if (!key.startsWith('customization_area_')) {
                    params.append(key, value);
                }
            }
            params.append('ajax', '1'); // Flag for AJAX request
            
            // Debug log - show all parameters being sent
            console.log('Submitting new item to order <?= $order->order_id ?>');
            console.log('Parameters being sent:', params.toString());
            
            // Send AJAX request
            fetch('/azteamcrm/orders/<?= $order->order_id ?>/order-items/store', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params.toString()
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                if (data.success) {
                    // Show success message in modal first
                    showModalMessage(data.message || 'Order item added successfully!', 'success');
                    
                    // Close modal and reload page after a short delay
                    setTimeout(() => {
                        if (createModalInstance) {
                            createModalInstance.hide();
                        }
                        location.reload();
                    }, 1000);
                } else {
                    // Re-enable button on error
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonText;
                    }
                    
                    // Show errors using dynamic message function
                    let errorMessage = '';
                    if (data.errors && Array.isArray(data.errors)) {
                        errorMessage = '<ul class="mb-0">' + data.errors.map(error => `<li>${error}</li>`).join('') + '</ul>';
                    } else {
                        errorMessage = data.message || 'Failed to add order item.';
                    }
                    showModalMessage(errorMessage, 'error');
                    console.error('Create failed:', data.message || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                
                // Re-enable button on error
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
                
                // Show error using dynamic message function
                showModalMessage('An error occurred while adding the item. Error: ' + error.message, 'error');
            });
        });
    }
});
</script>

<!-- Cancel Order Modal -->
<?php if (!in_array($order->order_status, ['cancelled', 'completed'])): ?>
<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this order for <strong><?= htmlspecialchars($customer ? $customer->full_name : 'Unknown Customer') ?></strong>?</p>
                <p class="text-warning"><i class="bi bi-exclamation-triangle"></i> This will mark the order as cancelled. The order items will remain unchanged.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <form action="/azteamcrm/orders/<?= $order->order_id ?>/cancel" method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> Cancel Order
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Record Payment Modal -->
<?php if ($order->getBalanceDue() > 0): ?>
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/azteamcrm/orders/<?= $order->order_id ?>/process-payment">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Recording payment for Order #<?= $order->order_id ?>
                        <br>
                        <small>Balance Due: $<?= number_format($order->getBalanceDue(), 2) ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_amount" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="payment_amount" name="payment_amount" 
                                   step="0.01" min="0.01" max="<?= $order->getBalanceDue() ?>"
                                   value="<?= $order->getBalanceDue() ?>" required>
                        </div>
                        <small class="text-muted">Maximum: $<?= number_format($order->getBalanceDue(), 2) ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <option value="">Select Method (Optional)</option>
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="payment_notes" name="payment_notes" rows="3" 
                                  placeholder="Optional notes about this payment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>

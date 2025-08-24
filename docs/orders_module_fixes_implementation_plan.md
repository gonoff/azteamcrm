# Orders Module Complete Overhaul - Implementation Plan

## Executive Summary
This document provides a comprehensive implementation plan to restructure the Orders module financial workflow, implement proper payment tracking, integrate inline item editing, and align with industry-standard terminology and UX practices.

## Critical Issues Identified

### 1. Financial Tracking Deficiencies
- **No payment tracking**: System only tracks payment status (unpaid/partial/paid) without actual payment amounts
- **No balance calculation**: Cannot determine amount due after partial payments
- **Missing payment history**: No audit trail for payments received

### 2. UI/UX Issues
- Payment status field exposed during order creation (should always be unpaid)
- Order total field shown during creation (should be calculated from items)
- Separate view for item editing (should be inline)
- Incorrect terminology ("Total Value" instead of "Total Amount")

### 3. Workflow Problems
- Payment status changes don't affect financial calculations
- No proper subtotal/discount/tax breakdown
- Missing standard financial fields (amount paid, balance due)

## Implementation Phases

## Phase 1: Database Schema Updates

### 1.1 Add Payment Tracking to Orders Table
```sql
-- Add payment tracking fields to orders table
ALTER TABLE `orders` 
ADD COLUMN `amount_paid` DECIMAL(10,2) DEFAULT 0.00 AFTER `order_total`,
ADD COLUMN `discount_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `amount_paid`,
ADD COLUMN `tax_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `discount_amount`,
ADD COLUMN `shipping_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `tax_amount`;

-- Add indexes for financial queries
ALTER TABLE `orders` 
ADD INDEX idx_payment_status (payment_status),
ADD INDEX idx_date_due (date_due);
```

### 1.2 Create Payments History Table
```sql
-- Create payments table for audit trail
CREATE TABLE `order_payments` (
  `payment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `payment_amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `payment_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_notes` TEXT DEFAULT NULL,
  `recorded_by` INT(11) NOT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `order_id` (`order_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Phase 2: Model Updates

### 2.1 Update Order Model
**File:** `/app/Models/Order.php`

Add new fillable fields:
```php
protected $fillable = [
    'order_status', 'payment_status', 'customer_id', 'user_id',
    'order_total', 'amount_paid', 'discount_amount', 'tax_amount', 
    'shipping_amount', 'date_created', 'date_due', 'order_notes'
];
```

Add new financial calculation methods:
```php
public function getSubtotal()
{
    // Calculate from order items
    return $this->order_total;
}

public function getTotalAmount()
{
    // Subtotal + tax + shipping - discount
    $subtotal = $this->getSubtotal();
    $total = $subtotal + $this->tax_amount + $this->shipping_amount - $this->discount_amount;
    return max(0, $total);
}

public function getBalanceDue()
{
    // Total amount - amount paid
    return max(0, $this->getTotalAmount() - $this->amount_paid);
}

public function updatePaymentStatus($status, $amountPaid = null)
{
    $this->payment_status = $status;
    $this->attributes['payment_status'] = $status;
    
    if ($amountPaid !== null) {
        $this->amount_paid = floatval($amountPaid);
        $this->attributes['amount_paid'] = floatval($amountPaid);
    }
    
    // Auto-determine payment status based on amounts
    $totalAmount = $this->getTotalAmount();
    if ($this->amount_paid >= $totalAmount) {
        $this->payment_status = 'paid';
        $this->attributes['payment_status'] = 'paid';
    } elseif ($this->amount_paid > 0) {
        $this->payment_status = 'partial';
        $this->attributes['payment_status'] = 'partial';
    } else {
        $this->payment_status = 'unpaid';
        $this->attributes['payment_status'] = 'unpaid';
    }
    
    return $this->update();
}

public function addPayment($amount, $method = null, $notes = null, $userId = null)
{
    // Record payment in history table
    $sql = "INSERT INTO order_payments (order_id, payment_amount, payment_method, payment_notes, recorded_by) 
            VALUES (:order_id, :amount, :method, :notes, :user_id)";
    
    $params = [
        'order_id' => $this->order_id,
        'amount' => $amount,
        'method' => $method,
        'notes' => $notes,
        'user_id' => $userId ?? $_SESSION['user_id']
    ];
    
    $stmt = $this->db->query($sql, $params);
    
    if ($stmt) {
        // Update order's amount_paid
        $this->amount_paid += $amount;
        $this->attributes['amount_paid'] = $this->amount_paid;
        
        // Update payment status
        $this->updatePaymentStatus($this->payment_status, $this->amount_paid);
        
        return true;
    }
    
    return false;
}

public function getPaymentHistory()
{
    $sql = "SELECT p.*, u.full_name as recorded_by_name 
            FROM order_payments p 
            LEFT JOIN users u ON p.recorded_by = u.id 
            WHERE p.order_id = :order_id 
            ORDER BY p.payment_date DESC";
    
    $stmt = $this->db->query($sql, ['order_id' => $this->order_id]);
    
    if ($stmt) {
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }
    
    return [];
}
```

Update existing methods:
```php
public function getOutstandingBalance()
{
    return $this->getBalanceDue();
}
```

### 2.2 Create OrderPayment Model
**File:** `/app/Models/OrderPayment.php`

```php
<?php

namespace App\Models;

use App\Core\Model;

class OrderPayment extends Model
{
    protected $table = 'order_payments';
    protected $primaryKey = 'payment_id';
    protected $fillable = [
        'order_id', 'payment_amount', 'payment_method', 
        'payment_notes', 'recorded_by'
    ];
    
    public function getOrder()
    {
        $order = new Order();
        return $order->find($this->order_id);
    }
    
    public function getUser()
    {
        $user = new User();
        return $user->find($this->recorded_by);
    }
}
```

## Phase 3: Controller Updates

### 3.1 Update OrderController
**File:** `/app/Controllers/OrderController.php`

Update `store()` method:
```php
public function store()
{
    $this->requireAuth();
    
    if (!$this->isPost()) {
        $this->redirect('/orders');
    }
    
    $this->verifyCsrf();
    
    $data = $this->sanitize($_POST);
    
    $errors = $this->validate($data, [
        'customer_id' => 'required',
        'date_due' => 'required'
    ]);
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old_input'] = $data;
        $this->redirect('/orders/create');
    }
    
    // New orders always start with these defaults
    $data['order_total'] = 0.00;
    $data['amount_paid'] = 0.00;
    $data['discount_amount'] = 0.00;
    $data['tax_amount'] = 0.00;
    $data['shipping_amount'] = 0.00;
    $data['user_id'] = $_SESSION['user_id'];
    $data['order_status'] = 'pending';
    $data['payment_status'] = 'unpaid';  // Always unpaid for new orders
    $data['date_created'] = date('Y-m-d H:i:s');
    
    $order = new Order();
    $newOrder = $order->create($data);
    
    if ($newOrder) {
        $_SESSION['success'] = 'Order created successfully! Add items to build the order.';
        $this->redirect('/orders/' . $newOrder->order_id);
    } else {
        $_SESSION['error'] = 'Failed to create order.';
        $this->redirect('/orders/create');
    }
}
```

Add new payment processing method:
```php
public function processPayment($id)
{
    $this->requireAuth();
    $this->verifyCsrf();
    
    if (!$this->isPost()) {
        $this->redirect('/orders/' . $id);
    }
    
    $order = new Order();
    $orderData = $order->find($id);
    
    if (!$orderData) {
        $this->redirect('/orders');
    }
    
    $paymentAmount = floatval($_POST['payment_amount'] ?? 0);
    $paymentMethod = $this->sanitize($_POST['payment_method'] ?? '');
    $paymentNotes = $this->sanitize($_POST['payment_notes'] ?? '');
    
    if ($paymentAmount <= 0) {
        $_SESSION['error'] = 'Invalid payment amount.';
        $this->redirect('/orders/' . $id);
    }
    
    // Add payment to history and update order
    if ($orderData->addPayment($paymentAmount, $paymentMethod, $paymentNotes)) {
        $_SESSION['success'] = 'Payment of $' . number_format($paymentAmount, 2) . ' recorded successfully.';
    } else {
        $_SESSION['error'] = 'Failed to process payment.';
    }
    
    $this->redirect('/orders/' . $id);
}
```

### 3.2 Update OrderItemController for Inline Editing
Add AJAX methods for inline editing:

```php
public function updateInline($id)
{
    $this->requireAuth();
    $this->verifyCsrf();
    
    if (!$this->isPost()) {
        $this->json(['success' => false, 'message' => 'Invalid request']);
        return;
    }
    
    $orderItem = new OrderItem();
    $itemData = $orderItem->find($id);
    
    if (!$itemData) {
        $this->json(['success' => false, 'message' => 'Item not found']);
        return;
    }
    
    $field = $this->sanitize($_POST['field'] ?? '');
    $value = $this->sanitize($_POST['value'] ?? '');
    
    // Validate field is allowed for inline editing
    $allowedFields = ['quantity', 'unit_price', 'product_description', 'order_item_status'];
    if (!in_array($field, $allowedFields)) {
        $this->json(['success' => false, 'message' => 'Field not editable']);
        return;
    }
    
    // Update the field
    $itemData->$field = $value;
    $itemData->attributes[$field] = $value;
    
    if ($itemData->update()) {
        // Recalculate order total if price/quantity changed
        if (in_array($field, ['quantity', 'unit_price'])) {
            $order = $itemData->getOrder();
            $order->calculateTotal();
        }
        
        // Sync order status if item status changed
        if ($field === 'order_item_status') {
            $order = $itemData->getOrder();
            $order->syncStatusFromItems();
        }
        
        $this->json([
            'success' => true,
            'message' => 'Item updated successfully',
            'new_total' => $itemData->getOrder()->order_total
        ]);
    } else {
        $this->json(['success' => false, 'message' => 'Failed to update item']);
    }
}
```

## Phase 4: View Updates

### 4.1 Update Order Creation Form
**File:** `/app/Views/orders/form.php`

Remove payment status and order total fields for new orders:

```php
<!-- Remove these sections when creating new order -->
<?php if (!$order): ?>
    <!-- Payment status and order total fields removed for new orders -->
<?php else: ?>
    <!-- Show read-only financial summary for existing orders -->
    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">Financial Summary</h6>
                    <div class="row">
                        <div class="col-md-3">
                            <small class="text-muted">Subtotal</small>
                            <h5>$<?= number_format($order->order_total, 2) ?></h5>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Total Amount</small>
                            <h5>$<?= number_format($order->getTotalAmount(), 2) ?></h5>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Amount Paid</small>
                            <h5 class="text-success">$<?= number_format($order->amount_paid, 2) ?></h5>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Balance Due</small>
                            <h5 class="<?= $order->getBalanceDue() > 0 ? 'text-danger' : 'text-success' ?>">
                                $<?= number_format($order->getBalanceDue(), 2) ?>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
```

### 4.2 Update Order Show View with Inline Item Editing
**File:** `/app/Views/orders/show.php`

Replace the order items section with inline editing capabilities:

```php
<!-- Order Items Card with Inline Editing -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Order Items</h5>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="bi bi-plus"></i> Add Item
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($orderItems)): ?>
            <p class="text-muted text-center">No order items added yet.</p>
            <div class="text-center">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                    <i class="bi bi-plus-circle"></i> Add First Item
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="orderItemsTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Line Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                        <tr data-item-id="<?= $item->order_item_id ?>">
                            <td>
                                <div class="editable-field" data-field="product_description">
                                    <span class="field-value"><?= htmlspecialchars($item->product_description) ?></span>
                                    <input type="text" class="form-control form-control-sm d-none field-input" 
                                           value="<?= htmlspecialchars($item->product_description) ?>">
                                </div>
                                <small class="text-muted">
                                    <?= $item->getProductTypeLabel() ?> | 
                                    <?= $item->getSizeLabel() ?> | 
                                    <?= $item->getCustomMethodLabel() ?>
                                </small>
                            </td>
                            <td width="100">
                                <div class="editable-field" data-field="quantity">
                                    <span class="field-value"><?= $item->quantity ?></span>
                                    <input type="number" class="form-control form-control-sm d-none field-input" 
                                           value="<?= $item->quantity ?>" min="1">
                                </div>
                            </td>
                            <td width="120">
                                <div class="editable-field" data-field="unit_price">
                                    <span class="field-value">$<?= number_format($item->unit_price, 2) ?></span>
                                    <input type="number" class="form-control form-control-sm d-none field-input" 
                                           value="<?= $item->unit_price ?>" step="0.01" min="0">
                                </div>
                            </td>
                            <td width="120">
                                <strong>$<?= number_format($item->total_price, 2) ?></strong>
                            </td>
                            <td width="150">
                                <select class="form-select form-select-sm status-select" data-field="order_item_status">
                                    <option value="pending" <?= $item->order_item_status === 'pending' ? 'selected' : '' ?>>
                                        Pending
                                    </option>
                                    <option value="in_production" <?= $item->order_item_status === 'in_production' ? 'selected' : '' ?>>
                                        In Production
                                    </option>
                                    <option value="completed" <?= $item->order_item_status === 'completed' ? 'selected' : '' ?>>
                                        Completed
                                    </option>
                                </select>
                            </td>
                            <td width="100">
                                <button class="btn btn-sm btn-outline-primary edit-btn" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success save-btn d-none" title="Save">
                                    <i class="bi bi-check"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary cancel-btn d-none" title="Cancel">
                                    <i class="bi bi-x"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete-btn" title="Delete" 
                                        data-item-id="<?= $item->order_item_id ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                            <td colspan="3"><strong>$<?= number_format($order->order_total, 2) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
```

Update Financial Summary Card:
```php
<!-- Financial Summary Card -->
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
                    <td>Tax:</td>
                    <td class="text-end">+$<?= number_format($order->tax_amount, 2) ?></td>
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
                            <span class="badge bg-success">Paid</span>
                        <?php elseif ($order->payment_status === 'partial'): ?>
                            <span class="badge bg-warning">Partial</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Unpaid</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <hr>
        
        <!-- Add Payment Form -->
        <?php if ($order->getBalanceDue() > 0): ?>
        <form method="POST" action="/azteamcrm/orders/<?= $order->order_id ?>/process-payment">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <h6>Record Payment</h6>
            
            <div class="mb-2">
                <label class="form-label">Amount</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" name="payment_amount" 
                           step="0.01" min="0.01" max="<?= $order->getBalanceDue() ?>"
                           value="<?= $order->getBalanceDue() ?>" required>
                </div>
            </div>
            
            <div class="mb-2">
                <label class="form-label">Method</label>
                <select class="form-select form-select-sm" name="payment_method">
                    <option value="">Select Method</option>
                    <option value="cash">Cash</option>
                    <option value="check">Check</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="mb-2">
                <label class="form-label">Notes</label>
                <textarea class="form-control form-control-sm" name="payment_notes" rows="2"></textarea>
            </div>
            
            <button type="submit" class="btn btn-success w-100">
                <i class="bi bi-cash"></i> Record Payment
            </button>
        </form>
        <?php else: ?>
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
```

### 4.3 Add JavaScript for Inline Editing
Add to the bottom of `/app/Views/orders/show.php`:

```javascript
<script>
// Inline editing functionality
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?= $csrf_token ?>';
    
    // Edit button click
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            enterEditMode(row);
        });
    });
    
    // Save button click
    document.querySelectorAll('.save-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            saveChanges(row);
        });
    });
    
    // Cancel button click
    document.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            exitEditMode(row);
        });
    });
    
    // Status change
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const row = this.closest('tr');
            const itemId = row.dataset.itemId;
            const field = this.dataset.field;
            const value = this.value;
            
            updateField(itemId, field, value, row);
        });
    });
    
    function enterEditMode(row) {
        // Show input fields
        row.querySelectorAll('.editable-field').forEach(field => {
            field.querySelector('.field-value').classList.add('d-none');
            field.querySelector('.field-input').classList.remove('d-none');
        });
        
        // Toggle buttons
        row.querySelector('.edit-btn').classList.add('d-none');
        row.querySelector('.save-btn').classList.remove('d-none');
        row.querySelector('.cancel-btn').classList.remove('d-none');
        row.querySelector('.delete-btn').classList.add('d-none');
    }
    
    function exitEditMode(row) {
        // Hide input fields
        row.querySelectorAll('.editable-field').forEach(field => {
            const input = field.querySelector('.field-input');
            const value = field.querySelector('.field-value');
            
            // Reset input value to original
            if (field.dataset.field === 'unit_price') {
                input.value = value.textContent.replace('$', '').replace(',', '');
            } else {
                input.value = value.textContent;
            }
            
            value.classList.remove('d-none');
            input.classList.add('d-none');
        });
        
        // Toggle buttons
        row.querySelector('.edit-btn').classList.remove('d-none');
        row.querySelector('.save-btn').classList.add('d-none');
        row.querySelector('.cancel-btn').classList.add('d-none');
        row.querySelector('.delete-btn').classList.remove('d-none');
    }
    
    function saveChanges(row) {
        const itemId = row.dataset.itemId;
        const updates = [];
        
        row.querySelectorAll('.editable-field').forEach(field => {
            const fieldName = field.dataset.field;
            const input = field.querySelector('.field-input');
            const value = field.querySelector('.field-value');
            
            if (input.value !== value.textContent.replace('$', '').replace(',', '')) {
                updates.push({
                    field: fieldName,
                    value: input.value
                });
            }
        });
        
        if (updates.length === 0) {
            exitEditMode(row);
            return;
        }
        
        // Send updates via AJAX
        updates.forEach(update => {
            updateField(itemId, update.field, update.value, row);
        });
        
        exitEditMode(row);
    }
    
    function updateField(itemId, field, value, row) {
        fetch(`/azteamcrm/order-items/${itemId}/update-inline`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `csrf_token=${csrfToken}&field=${field}&value=${value}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update display value
                const fieldDiv = row.querySelector(`[data-field="${field}"]`);
                if (fieldDiv && fieldDiv.querySelector('.field-value')) {
                    const valueSpan = fieldDiv.querySelector('.field-value');
                    if (field === 'unit_price') {
                        valueSpan.textContent = `$${parseFloat(value).toFixed(2)}`;
                    } else {
                        valueSpan.textContent = value;
                    }
                }
                
                // Update line total if quantity or price changed
                if (field === 'quantity' || field === 'unit_price') {
                    location.reload(); // Reload to update totals
                }
                
                // Show success message
                showAlert('success', 'Item updated successfully');
            } else {
                showAlert('danger', data.message || 'Failed to update item');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while updating');
        });
    }
    
    // Delete functionality
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this item?')) {
                const itemId = this.dataset.itemId;
                window.location.href = `/azteamcrm/order-items/${itemId}/delete`;
            }
        });
    });
    
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        alertDiv.style.zIndex = '9999';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
});
</script>
```

### 4.4 Add Item Modal
Add modal for adding new items in `/app/Views/orders/show.php`:

```html
<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Order Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/azteamcrm/orders/<?= $order->order_id ?>/order-items/store">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Product Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="product_description" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Product Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="product_type" required>
                                <option value="">Select Type</option>
                                <option value="shirt">Shirt</option>
                                <option value="apron">Apron</option>
                                <option value="scrub">Scrub</option>
                                <option value="hat">Hat</option>
                                <option value="bag">Bag</option>
                                <!-- Add other product types -->
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Size <span class="text-danger">*</span></label>
                            <select class="form-select" name="product_size" required>
                                <option value="">Select Size</option>
                                <optgroup label="Adult Sizes">
                                    <option value="xs">XS</option>
                                    <option value="s">S</option>
                                    <option value="m">M</option>
                                    <option value="l">L</option>
                                    <option value="xl">XL</option>
                                    <option value="xxl">XXL</option>
                                </optgroup>
                                <optgroup label="Child Sizes">
                                    <option value="child_xs">Child XS</option>
                                    <option value="child_s">Child S</option>
                                    <option value="child_m">Child M</option>
                                    <option value="child_l">Child L</option>
                                    <option value="child_xl">Child XL</option>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Customization Method</label>
                            <select class="form-select" name="custom_method">
                                <option value="">Select Method</option>
                                <option value="htv">HTV</option>
                                <option value="dft">DFT</option>
                                <option value="embroidery">Embroidery</option>
                                <option value="sublimation">Sublimation</option>
                                <option value="printing_services">Printing Services</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantity" min="1" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="unit_price" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Initial Status</label>
                            <select class="form-select" name="order_item_status">
                                <option value="pending" selected>Pending</option>
                                <option value="in_production">In Production</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Item Notes</label>
                            <textarea class="form-control" name="note_item" rows="2"></textarea>
                        </div>
                    </div>
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
```

## Phase 5: Routes Configuration

### 5.1 Add New Routes
**File:** `/config/routes.php`

Add these routes:
```php
// Payment processing
'/orders/{id}/process-payment' => 'OrderController@processPayment',

// Inline item updates
'/order-items/{id}/update-inline' => 'OrderItemController@updateInline',
```

## Phase 6: Migration & Cleanup

### 6.1 Remove Separate Order Items Views
Delete these files as they're no longer needed:
- `/app/Views/order-items/index.php`
- `/app/Views/order-items/form.php`

### 6.2 Update Existing Data
Run these SQL queries to update existing orders:

```sql
-- Set amount_paid for paid orders
UPDATE orders 
SET amount_paid = order_total 
WHERE payment_status = 'paid';

-- Set amount_paid to 0 for unpaid orders
UPDATE orders 
SET amount_paid = 0 
WHERE payment_status = 'unpaid';

-- For partial payments, admin needs to manually enter amounts
-- Or you can set a default like 50% of total
UPDATE orders 
SET amount_paid = order_total * 0.5 
WHERE payment_status = 'partial';
```

## Testing Checklist

### Phase 1: Database Changes
- [ ] Execute all ALTER TABLE statements successfully
- [ ] Create order_payments table
- [ ] Verify all indexes are created

### Phase 2: Order Creation
- [ ] Create new order without payment status field
- [ ] Create new order without order total field
- [ ] Verify order starts with payment_status = 'unpaid'
- [ ] Verify order_total = 0.00 initially

### Phase 3: Financial Tracking
- [ ] Add items to order and verify subtotal calculation
- [ ] Add discount/tax/shipping and verify total calculation
- [ ] Process payment and verify amount_paid updates
- [ ] Verify balance due calculation
- [ ] Test payment status auto-update based on amounts

### Phase 4: Inline Item Editing
- [ ] Edit item description inline
- [ ] Edit quantity inline and verify total recalculation
- [ ] Edit unit price inline and verify total recalculation
- [ ] Change item status inline
- [ ] Delete item from order view
- [ ] Add new item via modal

### Phase 5: Payment Processing
- [ ] Record full payment
- [ ] Record partial payment
- [ ] View payment history
- [ ] Verify payment status changes automatically

### Phase 6: UI/UX Updates
- [ ] Verify "Total Amount" label change
- [ ] Verify proper financial summary display
- [ ] Test all status badges
- [ ] Verify mobile responsiveness

## Rollback Plan

If issues occur, use these rollback steps:

### Database Rollback:
```sql
-- Remove added columns
ALTER TABLE `orders` 
DROP COLUMN `amount_paid`,
DROP COLUMN `discount_amount`,
DROP COLUMN `tax_amount`,
DROP COLUMN `shipping_amount`;

-- Drop payments table
DROP TABLE IF EXISTS `order_payments`;
```

### Code Rollback:
1. Restore original OrderController.php
2. Restore original Order.php model
3. Restore original order views
4. Restore original order-items views

## Summary

This implementation plan transforms the Orders module into a professional financial tracking system with:

1. **Proper Financial Workflow**: Subtotal → Discounts → Tax → Shipping → Total Amount → Payments → Balance Due
2. **Payment Tracking**: Complete audit trail with payment history
3. **Inline Item Management**: Edit items directly from order view
4. **Industry-Standard Terminology**: Following best practices for labeling and UX
5. **Automatic Calculations**: Payment status and balances update automatically
6. **Better UX**: Simplified order creation, clearer financial information

The implementation maintains backward compatibility while significantly improving the financial tracking capabilities of the system.
# Critical Codebase Issues Report

## 🔴 CRITICAL: Database Schema Mismatches

### 1. Missing Database Columns (WILL CAUSE CRASHES)
The code is trying to use columns that **DO NOT EXIST** in the database:

#### Orders Table
- ❌ `amount_paid` - Used in Order model and views
- ❌ `discount_amount` - Used in financial calculations
- ❌ `tax_amount` - Used in financial calculations  
- ❌ `shipping_amount` - Used in financial calculations

#### Customers Table
- ❌ `email` - Customer model includes it in fillable, forms show email field

#### Missing Tables
- ❌ `order_payments` table - Referenced in Order model methods

**Impact**: These will cause **SQL errors** when:
- Creating new orders (tries to set non-existent columns)
- Viewing orders (getTotalAmount(), getBalanceDue() use missing columns)
- Processing payments (addPayment() inserts into non-existent table)
- Editing customers (email field doesn't exist)

### Immediate Fix Required:
```sql
-- Add missing columns to orders table
ALTER TABLE `orders` 
ADD COLUMN `amount_paid` DECIMAL(10,2) DEFAULT 0.00 AFTER `order_total`,
ADD COLUMN `discount_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `amount_paid`,
ADD COLUMN `tax_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `discount_amount`,
ADD COLUMN `shipping_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `tax_amount`;

-- Add email to customers
ALTER TABLE `customers` 
ADD COLUMN `email` VARCHAR(255) DEFAULT NULL AFTER `phone_number`;

-- Create payments table
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

## 🟠 HIGH: Missing Routes

The following routes are used in the code but **NOT defined** in `/config/routes.php`:

1. **`/orders/{id}/process-payment`** - Used in payment form but route doesn't exist
2. **`/order-items/{id}/update-inline`** - Used for inline editing but route doesn't exist

### Fix:
Add to `/config/routes.php`:
```php
// Payment processing
'/orders/{id}/process-payment' => 'OrderController@processPayment',

// Inline item updates  
'/order-items/{id}/update-inline' => 'OrderItemController@updateInline',
```

## 🟡 MEDIUM: Orphaned/Duplicate Code

### 1. LineItem vs OrderItem Confusion
- ✅ **Active**: `OrderItemController.php`, `/app/Views/order-items/`
- ❌ **Orphaned**: `LineItemController.php`, `/app/Views/line-items/`
  
**Files to delete:**
- `/app/Controllers/LineItemController.php`
- `/app/Views/line-items/index.php`
- `/app/Views/line-items/form.php`

## 🟡 MEDIUM: Method Mismatches

### 1. Missing Method in Order Model
- **View calls**: `$order->getPaymentStatusBadge()` in `/app/Views/orders/form.php`
- **Reality**: Method doesn't exist in Order model
- **Available**: `getStatusBadge()` (for payment status), `getOrderStatusBadge()` (for order status)

### Fix:
Add to Order model:
```php
public function getPaymentStatusBadge()
{
    return $this->getStatusBadge(); // Reuse existing payment status badge method
}
```

## 🟡 MEDIUM: UI/UX Inconsistencies

### 1. Button Color Inconsistencies
- Edit Order button changed from `btn-primary` to `btn-secondary` (line 9 in show.php)
- Cancel Order button changed from `btn-warning` to `btn-danger` (line 14)
- Edit Customer button changed from `btn-primary` to `btn-secondary`
- New Customer button changed from `btn-outline-primary` to `btn-primary` (line 107 in form.php)
- Add Item button changed from `btn-success` to `btn-primary` (line 131)
- Edit item button changed from `btn-outline-primary` to `btn-outline-secondary` (line 167)

### 2. Missing "No newline at end of file"
Multiple files are missing the final newline which can cause Git issues:
- `/app/Controllers/OrderItemController.php`
- `/app/Controllers/OrderController.php`
- `/app/Views/orders/show.php`
- `/app/Views/orders/form.php`

## 🟢 LOW: Code Quality Issues

### 1. Inconsistent Error Handling
- Some controllers redirect on error, others show 404
- Missing validation in several controller methods
- No consistent pattern for AJAX vs regular responses

### 2. Security Concerns
- CSRF token not consistently checked in all POST operations
- Some delete operations don't verify ownership
- Payment processing lacks validation for negative amounts

### 3. Performance Issues
- No pagination on orders/customers lists
- Loading all order items without limit
- Missing indexes on frequently queried columns

## Workflow Breaking Issues Summary

### Will Break Immediately:
1. **Creating orders** - Database columns don't exist
2. **Processing payments** - Table and columns missing
3. **Viewing financial summary** - Methods use non-existent columns
4. **Adding email to customers** - Column doesn't exist

### Will Cause Confusion:
1. **Orphaned line-items code** - Old code still present
2. **Missing routes** - 404 errors on payment/inline edit
3. **UI inconsistencies** - Buttons changing colors randomly

## Recommended Action Plan

### Phase 1: Critical Database Fixes (DO IMMEDIATELY)
1. Run the SQL commands above to add missing columns/tables
2. Test order creation and payment processing

### Phase 2: Route Fixes
1. Add missing routes to `/config/routes.php`
2. Test payment processing and inline editing

### Phase 3: Code Cleanup
1. Delete orphaned LineItem files
2. Add missing methods to models
3. Fix button styling consistency

### Phase 4: Quality Improvements
1. Add proper error handling
2. Implement pagination
3. Add validation for all inputs
4. Add database indexes for performance

## Testing After Fixes

Run these tests to confirm fixes:
1. Create a new order
2. Add items to the order
3. Process a payment
4. Edit items inline
5. Create customer with email
6. Check financial calculations

## Notes
- The codebase shows signs of incomplete migration from old schema
- Payment system was partially implemented but not finished
- UI updates were started but not consistently applied
- Consider using migrations for database changes in future
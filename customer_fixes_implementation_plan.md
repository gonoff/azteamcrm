# Customer Module Fixes - Implementation Plan

## Overview
This document provides a detailed implementation plan for three critical fixes to the customer module:
1. Duplicate customer detection with proper error handling
2. Adding an optional email field to customers
3. Pre-selecting customer when creating orders from customer view

## Fix 1: Duplicate Customer Detection

### Problem
When creating a customer that already exists, the system doesn't show an error message and redirects away from the form, losing user input.

### Solution Architecture
Implement duplicate checking based on customer name and phone number combination, similar to how the User model handles duplicates.

### Implementation Steps

#### Step 1.1: Add Duplicate Check Method to Customer Model
**File:** `/app/Models/Customer.php`

Add the following method after the `searchCustomers` method:

```php
public function isDuplicate($fullName, $phoneNumber = null, $excludeId = null)
{
    $sql = "SELECT COUNT(*) as count FROM {$this->table} 
            WHERE LOWER(REPLACE(full_name, ' ', '')) = LOWER(REPLACE(:full_name, ' ', ''))";
    
    $params = ['full_name' => $fullName];
    
    // Include phone number in duplicate check if provided
    if ($phoneNumber) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        $sql .= " OR (phone_number IS NOT NULL AND REPLACE(phone_number, ' ', '') = :phone)";
        $params['phone'] = $cleanPhone;
    }
    
    // Exclude current record when updating
    if ($excludeId) {
        $sql .= " AND customer_id != :exclude_id";
        $params['exclude_id'] = $excludeId;
    }
    
    $stmt = $this->db->query($sql, $params);
    if ($stmt) {
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    return false;
}

public function findDuplicate($fullName, $phoneNumber = null)
{
    $sql = "SELECT customer_id, full_name, company_name, phone_number 
            FROM {$this->table} 
            WHERE LOWER(REPLACE(full_name, ' ', '')) = LOWER(REPLACE(:full_name, ' ', ''))";
    
    $params = ['full_name' => $fullName];
    
    if ($phoneNumber) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        $sql .= " OR (phone_number IS NOT NULL AND REPLACE(phone_number, ' ', '') = :phone)";
        $params['phone'] = $cleanPhone;
    }
    
    $sql .= " LIMIT 1";
    
    $stmt = $this->db->query($sql, $params);
    if ($stmt) {
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }
    return null;
}
```

#### Step 1.2: Modify CustomerController Store Method
**File:** `/app/Controllers/CustomerController.php`

Replace the `store()` method validation section (after line 82) with:

```php
// After existing validation (line 82)
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $data;
    
    // Preserve return URL on validation error
    $returnUrl = $_POST['return_url'] ?? null;
    $redirectUrl = '/customers/create';
    if ($returnUrl) {
        $redirectUrl .= '?return_url=' . urlencode($returnUrl);
    }
    $this->redirect($redirectUrl);
    return;
}

// Check for duplicate customer (ADD THIS NEW SECTION)
$customer = new Customer();
if ($customer->isDuplicate($data['full_name'], $data['phone_number'])) {
    $duplicate = $customer->findDuplicate($data['full_name'], $data['phone_number']);
    
    $errorMsg = "A customer with this name already exists.";
    if ($duplicate) {
        $errorMsg .= " (Customer #" . $duplicate->customer_id . ": " . 
                     $duplicate->full_name;
        if ($duplicate->company_name) {
            $errorMsg .= " - " . $duplicate->company_name;
        }
        $errorMsg .= ")";
    }
    
    $_SESSION['errors'] = ['duplicate' => $errorMsg];
    $_SESSION['old'] = $data;
    
    // Preserve return URL on duplicate error
    $returnUrl = $_POST['return_url'] ?? null;
    $redirectUrl = '/customers/create';
    if ($returnUrl) {
        $redirectUrl .= '?return_url=' . urlencode($returnUrl);
    }
    $this->redirect($redirectUrl);
    return;
}

// Continue with existing code...
```

#### Step 1.3: Modify CustomerController Update Method
**File:** `/app/Controllers/CustomerController.php`

Add duplicate check in the `update()` method after validation (around line 223):

```php
// After existing validation (line 223)
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $data;
    $this->redirect('/customers/' . $id . '/edit');
    return;
}

// Check for duplicate customer (ADD THIS NEW SECTION)
$customerModel = new Customer();
if ($customerModel->isDuplicate($data['full_name'], $data['phone_number'], $id)) {
    $duplicate = $customerModel->findDuplicate($data['full_name'], $data['phone_number']);
    
    $errorMsg = "Another customer with this name already exists.";
    if ($duplicate && $duplicate->customer_id != $id) {
        $errorMsg .= " (Customer #" . $duplicate->customer_id . ": " . 
                     $duplicate->full_name;
        if ($duplicate->company_name) {
            $errorMsg .= " - " . $duplicate->company_name;
        }
        $errorMsg .= ")";
    }
    
    $_SESSION['errors'] = ['duplicate' => $errorMsg];
    $_SESSION['old'] = $data;
    $this->redirect('/customers/' . $id . '/edit');
    return;
}

// Continue with existing code...
```

## Fix 2: Add Email Field to Customers

### Implementation Steps

#### Step 2.1: Database Schema Update
Run this SQL command to add the email column:

```sql
ALTER TABLE `customers` 
ADD COLUMN `email` VARCHAR(255) DEFAULT NULL 
AFTER `phone_number`;
```

#### Step 2.2: Update Customer Model
**File:** `/app/Models/Customer.php`

Update the `$fillable` array (line 11-15):

```php
protected $fillable = [
    'customer_status', 'full_name', 'company_name', 
    'address_line_1', 'address_line_2', 'city', 
    'state', 'zip_code', 'phone_number', 'email'  // Added email
];
```

#### Step 2.3: Update CustomerController
**File:** `/app/Controllers/CustomerController.php`

In both `store()` and `update()` methods, add email to the data array:

For `store()` method (after line 62):
```php
'phone_number' => $this->sanitize($_POST['phone_number'] ?? ''),
'email' => $this->sanitize($_POST['email'] ?? '')  // Add this line
```

For `update()` method (after line 196):
```php
'phone_number' => $this->sanitize($_POST['phone_number'] ?? ''),
'email' => $this->sanitize($_POST['email'] ?? '')  // Add this line
```

Add email validation (optional field) in both methods:
```php
// In validation rules, add (but don't require):
if (!empty($data['email'])) {
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please provide a valid email address';
    }
}
```

#### Step 2.4: Update Customer Form View
**File:** `/app/Views/customers/form.php`

Add email field after the phone number field (after line 165):

```php
<!-- Phone and Email -->
<div class="row mb-3">
    <div class="col-md-6">
        <label for="phone_number" class="form-label">Phone Number</label>
        <input type="tel" 
               class="form-control" 
               id="phone_number" 
               name="phone_number" 
               value="<?= htmlspecialchars($_SESSION['old']['phone_number'] ?? $customer->phone_number ?? '') ?>" 
               placeholder="(555) 123-4567">
        <small class="text-muted">Optional</small>
    </div>
    
    <div class="col-md-6">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" 
               class="form-control" 
               id="email" 
               name="email" 
               value="<?= htmlspecialchars($_SESSION['old']['email'] ?? $customer->email ?? '') ?>" 
               placeholder="customer@example.com">
        <small class="text-muted">Optional</small>
    </div>
</div>

<!-- Status (move to separate row) -->
<div class="row mb-3">
    <div class="col-md-6">
        <label for="customer_status" class="form-label">Status</label>
        <select class="form-select" id="customer_status" name="customer_status">
            <?php $selectedStatus = $_SESSION['old']['customer_status'] ?? $customer->customer_status ?? 'active'; ?>
            <option value="active" <?= $selectedStatus === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $selectedStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>
</div>
```

#### Step 2.5: Update Customer Show View
**File:** `/app/Views/customers/show.php`

Add email display after phone number (after line 71):

```php
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
```

#### Step 2.6: Update Customer Index View
**File:** `/app/Views/customers/index.php`

Add email to the table display if you want it visible in the list.

## Fix 3: Pre-select Customer When Creating Order from Customer View

### Implementation Steps

#### Step 3.1: Update Customer Show View Links
**File:** `/app/Views/customers/show.php`

Update the "New Order" links (lines 110 and 118):

```php
<!-- Line 110 -->
<a href="/azteamcrm/orders/create?customer_id=<?= $customer->customer_id ?>" class="btn btn-sm btn-success">
    <i class="bi bi-plus"></i> New Order
</a>

<!-- Line 118 -->
<a href="/azteamcrm/orders/create?customer_id=<?= $customer->customer_id ?>" class="alert-link">Create the first order</a>
```

#### Step 3.2: Verify OrderController Handles Customer ID
**File:** `/app/Controllers/OrderController.php`

The OrderController's `create()` method should already handle the customer_id parameter (verify this is working):

```php
public function create()
{
    // Check if customer_id is passed in URL
    $customerId = $_GET['customer_id'] ?? null;
    $selectedCustomer = null;
    
    if ($customerId) {
        $customerModel = new Customer();
        $selectedCustomer = $customerModel->find($customerId);
    }
    
    // Pass to view
    $this->view('orders/form', [
        'selectedCustomer' => $selectedCustomer,
        // ... other data
    ]);
}
```

## Testing Checklist

### Test Fix 1: Duplicate Customer Detection
- [ ] Create a new customer with name "John Smith"
- [ ] Try to create another customer with the same name
- [ ] Verify error message appears and form data is preserved
- [ ] Verify the error message shows the existing customer ID
- [ ] Test with same phone number but different name
- [ ] Test editing a customer to match another existing customer

### Test Fix 2: Email Field
- [ ] Add a customer with email
- [ ] Add a customer without email (should work)
- [ ] Edit existing customer to add email
- [ ] Test invalid email format validation
- [ ] Verify email displays in customer show view
- [ ] Verify email is saved correctly in database

### Test Fix 3: Customer Pre-selection
- [ ] Go to a customer's detail page
- [ ] Click "New Order" button
- [ ] Verify the customer is pre-selected in the order form
- [ ] Test from both the header button and the empty state link
- [ ] Verify the customer cannot be changed once pre-selected

## Rollback Plan

If any issues occur:

1. **Database Rollback:**
   ```sql
   ALTER TABLE `customers` DROP COLUMN `email`;
   ```

2. **Code Rollback:**
   - Revert changes to CustomerController.php
   - Revert changes to Customer.php model
   - Revert changes to customer form and show views

## Notes

- The duplicate check is case-insensitive and ignores spaces in names
- Phone number comparison strips all non-numeric characters
- Email field is completely optional and won't break existing functionality
- Customer pre-selection uses URL parameters which is already supported by the existing OrderController implementation
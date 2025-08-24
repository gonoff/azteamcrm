# Business Workflows Documentation

## Table of Contents
1. [Workflow Overview](#workflow-overview)
2. [Order Lifecycle](#order-lifecycle)
3. [Production Workflow](#production-workflow)
4. [Payment Processing](#payment-processing)
5. [Customer Management](#customer-management)
6. [Status Synchronization](#status-synchronization)
7. [Automatic Calculations](#automatic-calculations)
8. [Business Rules](#business-rules)

---

## Workflow Overview

The AZTEAM CRM/ERP system manages several interconnected business workflows that automate and streamline operations from customer inquiry to order fulfillment.

### Core Workflows
1. **Order Management**: Quote → Order → Production → Delivery
2. **Production Pipeline**: Pending → In Production → Completed
3. **Payment Tracking**: Unpaid → Partial → Paid
4. **Customer Lifecycle**: Lead → Active Customer → Order History

## Order Lifecycle

### Complete Order Flow
```
1. Customer Inquiry
   ↓
2. Create Customer Record (if new)
   ↓
3. Create Order
   - Link to customer
   - Set due date
   - Add notes
   ↓
4. Add Order Items
   - Product details
   - Pricing
   - Customization specs
   ↓
5. Order Confirmation
   - Review total
   - Set payment terms
   ↓
6. Production
   - Items move through status
   - Order status auto-syncs
   ↓
7. Quality Check
   - Mark items completed
   ↓
8. Payment Collection
   - Record payments
   - Update payment status
   ↓
9. Delivery
   - Order completed
   - Archive for history
```

### Order Creation Process

#### Step 1: Customer Selection
```javascript
// Customer can be selected via:
1. Search and select existing customer
2. Create new customer and return
3. Direct link from customer profile
```

#### Step 2: Order Details
```php
// Required fields
- Customer ID (pre-selected or chosen)
- Due Date
- Payment Status (default: unpaid)
- Order Status (default: pending)

// Optional fields
- Order Notes
- Rush Order Flag (auto-detected)
```

#### Step 3: Add Order Items
```php
// For each item:
- Product Type (shirt, hat, etc.)
- Quantity
- Unit Price
- Size
- Customization Method
- Customization Area
- Special Instructions
```

### Order Status Progression

```
PENDING ──────► IN_PRODUCTION ──────► COMPLETED
   │                  │                    │
   └──────────► CANCELLED ◄────────────────┘
         (Manual Override Only)
```

**Automatic Status Rules:**
- `pending`: All items are pending
- `in_production`: At least one item is in production
- `completed`: All items are completed
- `cancelled`: Manual override (stops auto-sync)

## Production Workflow

### Production Pipeline

```
Order Placed
     │
     ▼
PENDING QUEUE
- Awaiting materials
- Scheduled for production
     │
     ▼
IN PRODUCTION
- Active work
- Progress tracking
     │
     ▼
COMPLETED
- Quality checked
- Ready for delivery
```

### Daily Production Flow

#### Morning
1. **Review Dashboard**
   - Check overdue items (red)
   - Review rush orders (orange)
   - Plan day's production

2. **Materials Check**
   - Run materials report
   - Verify inventory
   - Order supplies if needed

#### Production Hours
1. **Start Production**
   - Update item status to "in_production"
   - Work on priority items first

2. **Progress Updates**
   - Update status as items complete
   - Note any issues

3. **Bulk Operations**
   - Select multiple items
   - Update status together

#### End of Day
1. **Complete Items**
   - Mark finished items as completed
   - Update supplier status if applicable

2. **Review Tomorrow**
   - Check upcoming due dates
   - Prepare materials

### Priority System

**Urgency Levels:**
1. **Overdue** (Red)
   - Past due date
   - Highest priority

2. **Due Today** (Orange)
   - Must complete today

3. **Rush Order** (Yellow)
   - Due within 7 days
   - Expedited processing

4. **Normal** (Default)
   - Standard timeline

### Production Dashboard Features

```
┌─────────────────────────────────────┐
│ Production Statistics               │
├─────────────────────────────────────┤
│ Pending: 45                        │
│ In Production: 12                  │
│ Completed Today: 8                 │
│ Rush Orders: 5                     │
│ Overdue: 2                        │
└─────────────────────────────────────┘

Filter Tabs:
[All Active] [Pending] [In Production] [Overdue] [Due Today] [Rush]

Actions:
- Individual status updates
- Bulk status updates
- Export to CSV
- Print production sheet
```

## Payment Processing

### Payment Status Flow

```
UNPAID ──────► PARTIAL ──────► PAID
   │              │              │
   │              └──────────────┤
   └─────────────────────────────┤
              (Can skip stages)
```

### Payment Recording Process

1. **Initial Order**
   - Default status: `unpaid`
   - Total calculated from items

2. **Partial Payment**
   - Record amount received
   - System calculates balance
   - Status: `partial`

3. **Final Payment**
   - Record remaining amount
   - Status: `paid`
   - Order can be closed

### Payment Tracking Features

```php
// Automatic status determination
if ($amount_paid >= $order_total) {
    $status = 'paid';
} elseif ($amount_paid > 0) {
    $status = 'partial';
} else {
    $status = 'unpaid';
}
```

### Outstanding Balance Calculation

```php
$outstanding = $order_total - $amount_paid;

// Dashboard aggregation
$total_outstanding = sum(all_orders.outstanding_balance)
```

## Customer Management

### Customer Lifecycle

```
Lead/Inquiry
     │
     ▼
New Customer Created
- Contact details
- Company info
- Address
     │
     ▼
First Order
- Becomes active customer
- Start tracking history
     │
     ▼
Repeat Customer
- Order history builds
- Revenue tracking
- Relationship insights
```

### Customer Data Management

#### Required Information
- Full Name
- Address Line 1
- City, State, ZIP
- Phone Number

#### Optional Information
- Company Name
- Address Line 2
- Email Address

#### Automatic Formatting
- Names → Title Case
- States → Uppercase
- Phone → (xxx) xxx-xxxx

### Customer Insights

```sql
For each customer:
- Total Orders: COUNT(orders)
- Total Revenue: SUM(order_totals)
- Last Order Date: MAX(date_created)
- Average Order Value: AVG(order_total)
- Payment History: Payment status distribution
```

## Status Synchronization

### Automatic Order Status Sync

The system automatically synchronizes order status based on the collective status of its items.

#### Sync Rules

```javascript
// Order status is determined by items
function syncOrderStatus(items) {
    if (all items are 'completed') {
        order.status = 'completed';
    } else if (any item is 'in_production') {
        order.status = 'in_production';
    } else {
        order.status = 'pending';
    }
}
```

#### Sync Triggers
1. Item status change
2. Item added to order
3. Item deleted from order

#### Manual Override
- Cancelling an order stops auto-sync
- Allows manual status control

### Item Status Management

```php
// When updating item status
$item->updateStatus('in_production');
$order->syncStatusFromItems(); // Auto-sync order
```

## Automatic Calculations

### Order Total Calculation

```sql
-- Automatic total from items
UPDATE orders 
SET order_total = (
    SELECT SUM(total_price) 
    FROM order_items 
    WHERE order_id = orders.order_id
)
```

### Item Price Calculation

```sql
-- Generated column in database
total_price = quantity * unit_price
```

### Rush Order Detection

```php
function isRushOrder($due_date) {
    $days_until_due = (strtotime($due_date) - time()) / 86400;
    return $days_until_due <= 7 && $days_until_due >= 0;
}
```

### Production Progress

```php
function getProductionProgress($order) {
    $total_items = count($order->items);
    $completed_items = count(
        array_filter($order->items, 
        fn($item) => $item->status === 'completed')
    );
    
    return ($completed_items / $total_items) * 100;
}
```

## Business Rules

### Order Rules

1. **Order Creation**
   - Must have a customer
   - Due date required
   - Initial total is 0
   - Status starts as 'pending'

2. **Order Modification**
   - Can edit until completed
   - Admin can delete
   - Items can be added anytime

3. **Order Completion**
   - All items must be completed
   - Payment should be recorded
   - Cannot modify after completion

### Production Rules

1. **Item Status Progression**
   - Must follow: pending → in_production → completed
   - Cannot skip stages
   - Cannot go backward

2. **Priority Rules**
   - Overdue items first
   - Then due today
   - Then rush orders
   - Then normal orders

3. **Capacity Management**
   - Monitor items in production
   - Balance workload
   - Meet deadlines

### Customer Rules

1. **Customer Creation**
   - Unique name/phone combination
   - Active by default
   - Can't delete with orders

2. **Customer Status**
   - Active: Can create orders
   - Inactive: Historical only
   - Cannot delete, only deactivate

### Financial Rules

1. **Pricing**
   - Unit price required
   - Total auto-calculated
   - Cannot be negative

2. **Payments**
   - Cannot exceed order total
   - Must be positive
   - Tracked chronologically

### User Access Rules

1. **Administrator**
   - Full access to all features
   - Can delete records
   - Can manage users

2. **Production Team**
   - View all orders
   - Update production status
   - Cannot delete
   - Cannot access financial reports

## Workflow Automation

### Automated Processes

1. **Status Synchronization**
   - Order status from items
   - Real-time updates

2. **Calculations**
   - Order totals
   - Item totals
   - Outstanding balances

3. **Formatting**
   - Name capitalization
   - Phone formatting
   - Address standardization

4. **Detection**
   - Rush orders
   - Overdue items
   - Due soon alerts

### Manual Interventions

1. **Order Cancellation**
   - Requires manual action
   - Stops auto-sync

2. **Special Pricing**
   - Manual price overrides
   - Discount application

3. **Priority Changes**
   - Manual rush designation
   - Special handling notes

## Performance Metrics

### Key Performance Indicators

```
Daily Metrics:
- Orders created
- Items completed
- Revenue collected
- Outstanding balance

Production Metrics:
- Average completion time
- On-time delivery rate
- Items per day capacity
- Rush order percentage

Customer Metrics:
- New customers
- Repeat rate
- Average order value
- Customer lifetime value
```

### Dashboard Analytics

```
Real-time displays:
┌─────────────────┬─────────────────┐
│ Total Orders    │ Revenue         │
│ 156            │ $45,230         │
├─────────────────┼─────────────────┤
│ Pending Orders  │ Outstanding     │
│ 23             │ $12,450         │
├─────────────────┼─────────────────┤
│ Rush Orders     │ Overdue         │
│ 5              │ 2               │
└─────────────────┴─────────────────┘
```

---

*Last Updated: August 2025*
*Workflow Version: 2.0*
*Business Logic Implementation*
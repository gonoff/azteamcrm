# Models and Data Layer Documentation

## Table of Contents
1. [Data Layer Overview](#data-layer-overview)
2. [Base Model Class](#base-model-class)
3. [Model Implementation](#model-implementation)
4. [Model Relationships](#model-relationships)
5. [Database Query Patterns](#database-query-patterns)
6. [Business Logic Methods](#business-logic-methods)
7. [Data Validation](#data-validation)
8. [Model Reference](#model-reference)

---

## Data Layer Overview

The data layer implements the Active Record pattern where models represent both database tables and business entities. Each model class extends a base Model class that provides common database operations.

### Key Principles
1. **Active Record Pattern**: Models handle their own persistence
2. **Mass Assignment Protection**: Fillable properties control what can be set
3. **Prepared Statements**: All queries use PDO prepared statements
4. **Business Logic Encapsulation**: Models contain domain-specific methods
5. **Relationship Management**: Models define and manage relationships

## Base Model Class

Location: `/app/Core/Model.php`

### Core Properties
```php
abstract class Model {
    protected $db;              // Database instance
    protected $table;           // Table name
    protected $primaryKey = 'id'; // Primary key field
    protected $fillable = [];   // Mass assignable fields
    protected $attributes = []; // Current data
}
```

### CRUD Methods

#### Create
```php
public function create($data) {
    $filteredData = $this->filterFillable($data);
    $fields = array_keys($filteredData);
    $placeholders = array_map(function($field) { 
        return ":{$field}"; 
    }, $fields);
    
    $sql = "INSERT INTO {$this->table} (" . implode(", ", $fields) . ") 
            VALUES (" . implode(", ", $placeholders) . ")";
    
    $stmt = $this->db->query($sql, $filteredData);
    
    if ($stmt) {
        $this->attributes[$this->primaryKey] = $this->db->lastInsertId();
        $this->attributes = array_merge($this->attributes, $filteredData);
        return $this;
    }
    return false;
}
```

#### Read
```php
public function find($id) {
    $sql = "SELECT * FROM {$this->table} 
            WHERE {$this->primaryKey} = :id LIMIT 1";
    $stmt = $this->db->query($sql, ['id' => $id]);
    
    if ($stmt) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $this->attributes = $result;
            return $this;
        }
    }
    return null;
}

public function findAll($conditions = [], $orderBy = null, $limit = null) {
    $sql = "SELECT * FROM {$this->table}";
    $params = [];
    
    if (!empty($conditions)) {
        $whereClause = [];
        foreach ($conditions as $field => $value) {
            $whereClause[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }
        $sql .= " WHERE " . implode(" AND ", $whereClause);
    }
    
    if ($orderBy) {
        $sql .= " ORDER BY {$orderBy}";
    }
    
    if ($limit) {
        $sql .= " LIMIT {$limit}";
    }
    
    $stmt = $this->db->query($sql, $params);
    return $stmt ? $stmt->fetchAll(PDO::FETCH_CLASS, static::class) : [];
}
```

#### Update
```php
public function update() {
    $data = $this->filterFillable($this->attributes);
    unset($data[$this->primaryKey]);
    
    $fields = array_map(function($field) { 
        return "{$field} = :{$field}"; 
    }, array_keys($data));
    
    $sql = "UPDATE {$this->table} 
            SET " . implode(", ", $fields) . " 
            WHERE {$this->primaryKey} = :primary_key";
    
    $data['primary_key'] = $this->attributes[$this->primaryKey];
    
    return $this->db->query($sql, $data) !== false;
}
```

#### Delete
```php
public function delete() {
    $sql = "DELETE FROM {$this->table} 
            WHERE {$this->primaryKey} = :id";
    
    return $this->db->query($sql, [
        'id' => $this->attributes[$this->primaryKey]
    ]) !== false;
}
```

### Query Builder Methods
```php
public function where($field, $operator = '=', $value = null) {
    if ($value === null) {
        $value = $operator;
        $operator = '=';
    }
    
    $sql = "SELECT * FROM {$this->table} 
            WHERE {$field} {$operator} :value";
    $stmt = $this->db->query($sql, ['value' => $value]);
    
    return $stmt ? $stmt->fetchAll(PDO::FETCH_CLASS, static::class) : [];
}

public function count($conditions = []) {
    $sql = "SELECT COUNT(*) as count FROM {$this->table}";
    // Add WHERE clause if conditions exist
    // Execute and return count
}
```

## Model Implementation

### User Model
Location: `/app/Models/User.php`

```php
class User extends Model {
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'username', 'email', 'password_hash', 
        'role', 'full_name', 'is_active'
    ];
    
    // Authentication
    public function authenticate($username, $password) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE username = :username 
                AND is_active = 1 LIMIT 1";
        
        $stmt = $this->db->query($sql, ['username' => $username]);
        
        if ($stmt) {
            $user = $stmt->fetchObject(static::class);
            if ($user && password_verify($password, $user->password_hash)) {
                return $user;
            }
        }
        return false;
    }
    
    // Password management
    public function setPassword($password) {
        $this->password_hash = password_hash($password, PASSWORD_BCRYPT);
        $this->attributes['password_hash'] = $this->password_hash;
    }
    
    // Validation
    public function existsExcept($field, $value, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE {$field} = :value";
        $params = ['value' => $value];
        
        if ($excludeId) {
            $sql .= " AND {$this->primaryKey} != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
}
```

### Customer Model
Location: `/app/Models/Customer.php`

```php
class Customer extends Model {
    protected $table = 'customers';
    protected $primaryKey = 'customer_id';
    protected $fillable = [
        'customer_status', 'full_name', 'company_name',
        'address_line_1', 'address_line_2', 'city',
        'state', 'zip_code', 'phone_number', 'email'
    ];
    
    // Relationships
    public function getOrders() {
        $order = new Order();
        return $order->where('customer_id', '=', $this->customer_id);
    }
    
    // Statistics
    public function getTotalOrders() {
        $sql = "SELECT COUNT(*) as count FROM orders 
                WHERE customer_id = :customer_id";
        $stmt = $this->db->query($sql, ['customer_id' => $this->customer_id]);
        
        if ($stmt) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        }
        return 0;
    }
    
    public function getTotalRevenue() {
        $sql = "SELECT SUM(order_total) as total FROM orders 
                WHERE customer_id = :customer_id";
        $stmt = $this->db->query($sql, ['customer_id' => $this->customer_id]);
        
        if ($stmt) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        }
        return 0;
    }
    
    // Search functionality
    public function searchCustomers($query, $limit = 20) {
        $searchTerm = '%' . $query . '%';
        $phoneSearch = preg_replace('/[^0-9]/', '', $query);
        $phoneSearchTerm = '%' . $phoneSearch . '%';
        
        $sql = "SELECT customer_id, full_name, company_name, 
                       phone_number, customer_status 
                FROM {$this->table} 
                WHERE customer_status = 'active' 
                AND (
                    full_name LIKE :search1 
                    OR company_name LIKE :search2 
                    OR REPLACE(REPLACE(REPLACE(phone_number, '-', ''), '(', ''), ')', '') LIKE :phone
                )
                ORDER BY full_name ASC 
                LIMIT :limit";
        
        // Execute with bound parameters
        return $results;
    }
}
```

### Order Model
Location: `/app/Models/Order.php`

```php
class Order extends Model {
    protected $table = 'orders';
    protected $primaryKey = 'order_id';
    protected $fillable = [
        'order_status', 'payment_status', 'customer_id', 'user_id',
        'order_total', 'date_created', 'date_due', 'order_notes'
    ];
    
    // Relationships
    public function getOrderItems() {
        $orderItem = new OrderItem();
        return $orderItem->where('order_id', '=', $this->order_id);
    }
    
    public function getCustomer() {
        $customer = new Customer();
        return $customer->find($this->customer_id);
    }
    
    public function getUser() {
        $user = new User();
        return $user->find($this->user_id);
    }
    
    // Status management
    public function syncStatusFromItems() {
        // Don't auto-sync if manually overridden
        if (in_array($this->order_status, ['cancelled', 'on_hold'])) {
            return true;
        }
        
        $orderItems = $this->getOrderItems();
        
        if (empty($orderItems)) {
            $this->order_status = 'pending';
            $this->attributes['order_status'] = 'pending';
            return $this->update();
        }
        
        $allCompleted = true;
        $anyInProduction = false;
        
        foreach ($orderItems as $item) {
            if ($item->order_item_status !== 'completed') {
                $allCompleted = false;
                if ($item->order_item_status === 'in_production') {
                    $anyInProduction = true;
                }
            }
        }
        
        // Determine new status
        if ($allCompleted) {
            $newStatus = 'completed';
        } elseif ($anyInProduction) {
            $newStatus = 'in_production';
        } else {
            $newStatus = 'pending';
        }
        
        // Update if changed
        if ($this->order_status !== $newStatus) {
            $this->order_status = $newStatus;
            $this->attributes['order_status'] = $newStatus;
            return $this->update();
        }
        
        return true;
    }
    
    // Financial calculations
    public function calculateTotal() {
        $sql = "SELECT SUM(total_price) as total FROM order_items 
                WHERE order_id = :order_id";
        $stmt = $this->db->query($sql, ['order_id' => $this->order_id]);
        
        if ($stmt) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->order_total = $result['total'] ?? 0;
            $this->attributes['order_total'] = $this->order_total;
            return $this->update();
        }
        return false;
    }
    
    // Business rules
    public function isOverdue() {
        if (!$this->date_due) return false;
        return strtotime($this->date_due) < strtotime('today') 
               && $this->order_status !== 'completed';
    }
    
    public function isRushOrder() {
        if (!$this->date_due) return false;
        $daysUntilDue = (strtotime($this->date_due) - strtotime('today')) / 86400;
        return $daysUntilDue <= 7 && $daysUntilDue >= 0;
    }
}
```

### OrderItem Model
Location: `/app/Models/OrderItem.php`

```php
class OrderItem extends Model {
    protected $table = 'order_items';
    protected $primaryKey = 'order_item_id';
    protected $fillable = [
        'order_item_status', 'order_id', 'user_id', 'quantity',
        'unit_price', 'product_type', 'product_description',
        'product_size', 'custom_method', 'custom_area',
        'supplier_status', 'note_item'
    ];
    
    // Status updates with proper attribute handling
    public function updateStatus($status) {
        $this->attributes['order_item_status'] = $status;
        $this->order_item_status = $status;
        return $this->update();
    }
    
    public function updateSupplierStatus($status) {
        $this->attributes['supplier_status'] = $status;
        $this->supplier_status = $status;
        return $this->update();
    }
    
    // Production queries
    public function getProductionItems() {
        $sql = "SELECT oi.*, o.date_due, o.order_id, o.payment_status,
                       c.full_name as customer_name, c.company_name,
                       CASE 
                           WHEN o.date_due < CURDATE() THEN 'overdue'
                           WHEN o.date_due = CURDATE() THEN 'due_today'
                           WHEN o.date_due <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'due_soon'
                           WHEN o.date_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'rush'
                           ELSE 'normal'
                       END as urgency_level
                FROM {$this->table} oi
                JOIN orders o ON oi.order_id = o.order_id
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE oi.order_item_status IN ('pending', 'in_production')
                ORDER BY o.date_due ASC, oi.order_item_id ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_CLASS, static::class) : [];
    }
}
```

## Model Relationships

### Relationship Types

#### One-to-Many (1:N)
```php
// Customer has many Orders
class Customer {
    public function getOrders() {
        $order = new Order();
        return $order->where('customer_id', '=', $this->customer_id);
    }
}
```

#### Many-to-One (N:1)
```php
// Order belongs to Customer
class Order {
    public function getCustomer() {
        $customer = new Customer();
        return $customer->find($this->customer_id);
    }
}
```

### Relationship Map
```
Users
  ├─► Orders (created by)
  └─► OrderItems (created by)

Customers
  └─► Orders (has many)
      └─► OrderItems (has many)
```

## Database Query Patterns

### Prepared Statements
All queries use PDO prepared statements:
```php
$sql = "SELECT * FROM users WHERE email = :email";
$stmt = $this->db->query($sql, ['email' => $email]);
```

### Transaction Support
```php
$this->db->beginTransaction();
try {
    // Multiple operations
    $order->create($orderData);
    $orderItem->create($itemData);
    $this->db->commit();
} catch (Exception $e) {
    $this->db->rollback();
    throw $e;
}
```

### Complex Queries
```php
// Join with aggregation
$sql = "SELECT o.*, COUNT(oi.order_item_id) as item_count,
               SUM(oi.total_price) as calculated_total
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.customer_id = :customer_id
        GROUP BY o.order_id
        ORDER BY o.date_created DESC";
```

## Business Logic Methods

### Validation Methods
```php
// Check for duplicates
public function isDuplicate() {
    $sql = "SELECT COUNT(*) as count FROM {$this->table} 
            WHERE (LOWER(REPLACE(full_name, ' ', '')) = LOWER(REPLACE(:name, ' ', ''))
            OR phone_number = :phone)
            AND customer_id != :id";
    // Execute and return boolean
}
```

### Calculation Methods
```php
// Calculate order total from items
public function calculateTotal() {
    $total = 0;
    foreach ($this->getOrderItems() as $item) {
        $total += $item->quantity * $item->unit_price;
    }
    $this->order_total = $total;
    return $this->update();
}
```

### Status Methods
```php
// Generate status badge HTML
public function getStatusBadge() {
    $badges = [
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'in_production' => '<span class="badge badge-info">In Production</span>',
        'completed' => '<span class="badge badge-success">Completed</span>',
    ];
    return $badges[$this->order_item_status] ?? '';
}
```

### Formatting Methods
```php
// Format phone number
public function formatPhoneNumber() {
    $phone = preg_replace('/[^0-9]/', '', $this->phone_number);
    if (strlen($phone) === 10) {
        return sprintf('(%s) %s-%s', 
            substr($phone, 0, 3), 
            substr($phone, 3, 3), 
            substr($phone, 6, 4)
        );
    }
    return $this->phone_number;
}
```

## Data Validation

### Input Sanitization
```php
protected function filterFillable($data) {
    return array_intersect_key($data, array_flip($this->fillable));
}
```

### Type Casting
```php
// Boolean to integer for MySQL
$this->is_active = $value ? 1 : 0;

// Decimal formatting
$this->unit_price = number_format($value, 2, '.', '');
```

### Required Fields
Validation happens in controllers before model operations:
```php
$errors = $this->validate($_POST, [
    'full_name' => 'required|min:3',
    'email' => 'required|email',
    'phone_number' => 'required|phone'
]);
```

## Model Reference

### Available Models

| Model | Table | Primary Key | Purpose |
|-------|-------|-------------|---------|
| User | users | id | System users and authentication |
| Customer | customers | customer_id | Customer management |
| Order | orders | order_id | Order tracking |
| OrderItem | order_items | order_item_id | Individual products |
| LineItem | line_items | id | Legacy (deprecated) |
| OrderPayment | order_payments | id | Legacy (deprecated) |

### Model Methods Summary

#### Base Methods (all models)
- `find($id)` - Find by primary key
- `findAll($conditions, $orderBy, $limit)` - Find multiple
- `where($field, $operator, $value)` - Query builder
- `create($data)` - Insert new record
- `update()` - Update existing record
- `delete()` - Delete record
- `save()` - Insert or update
- `count($conditions)` - Count records
- `fill($data)` - Mass assignment

#### Model-Specific Methods
**User:**
- `authenticate($username, $password)`
- `setPassword($password)`
- `existsExcept($field, $value, $excludeId)`

**Customer:**
- `getOrders()`
- `getTotalOrders()`
- `getTotalRevenue()`
- `searchCustomers($query, $limit)`
- `formatPhoneNumber()`

**Order:**
- `getOrderItems()`
- `getCustomer()`
- `syncStatusFromItems()`
- `calculateTotal()`
- `isOverdue()`
- `isRushOrder()`

**OrderItem:**
- `updateStatus($status)`
- `updateSupplierStatus($status)`
- `getProductionItems()`
- `getStatusBadge()`

---

*Last Updated: August 2025*
*Model Version: 2.0*
*Active Record Implementation*
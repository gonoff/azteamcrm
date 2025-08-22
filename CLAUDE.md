# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AZTEAM CRM/ERP is a custom apparel and merchandise order management system for a custom apparel company specializing in personalized clothing and promotional products. The system manages orders for corporate clients, teams, and organizations with various customization methods.

**Current Database**: `azteamerp` (migrated from `azteamcrm`)

**Current Phase**: Phase 2 - Successfully migrated to new database schema with separate customer management, improved order items with pricing, and simplified status tracking.

## Development Workflow

### Our Methodology: Incremental Feature Development
We follow a strict incremental development approach to ensure quality and stability:

1. **Plan** - Analyze requirements and existing code before implementation
2. **Implement** - Build one small feature at a time
3. **Test** - Thoroughly test each feature before proceeding
4. **Document** - Update documentation after successful testing
5. **Iterate** - Move to the next feature only after current one is complete

### Development Process
```
Plan → Implement → Test → Fix Bugs → Test Again → Update Docs → Next Feature
```

**Key Principles:**
- Never implement multiple features simultaneously
- Always test each feature completely before moving on
- Update documentation immediately after successful implementation
- Maintain backward compatibility with existing features
- Use existing patterns and conventions in the codebase

## System Architecture

### Technology Stack
- **Backend**: PHP 8.x with custom MVC framework (no external dependencies)
- **Database**: MySQL 8.0 / MariaDB 10.5+
- **Frontend**: Bootstrap 5.1.3, Bootstrap Icons 1.8.1
- **Environment**: XAMPP/LAMPP stack
- **Web Root**: `/opt/lampp/htdocs/azteamcrm/`
- **Color Scheme**: White, Black, Red theme

### MVC Framework Structure
```
/app/Core/           # Custom framework classes
├── Database.php     # PDO singleton with transaction support
├── Model.php        # Active Record base with CRUD operations
├── Controller.php   # Base controller with auth/CSRF/validation
└── Router.php       # Pattern-based routing with parameter extraction

/index.php          # Single entry point (routes via ?route= parameter)
/bootstrap.php      # Application initialization and autoloading
/.htaccess          # URL rewriting for clean URLs
```

### Core Data Model
- **Customers**: Separate entity for customer management with addresses and contact info
- **Orders**: Links to customers via foreign key, payment tracking (unpaid/partial/paid), rush orders
- **Order Items**: Individual products with single-status tracking and pricing (unit_price, total_price)
- **Users**: Role-based authentication (administrator, production_team)

### Status Workflows
1. **Order Status**: pending → processing → completed (or cancelled/on_hold)
2. **Order Item Status**: pending → in_production → completed
3. **Supplier Status**: awaiting_order → order_made → order_arrived → order_delivered
4. **Payment Status**: unpaid → partial → paid

## Development Commands

### Database Setup
```bash
# Import database schema
mysql -u root -p azteamerp < azteamerp.sql

# Access MySQL via XAMPP
/opt/lampp/bin/mysql -u root -p

# Configure environment
cp .env.example .env
# Edit .env with your database credentials
```

### XAMPP/LAMPP Management
```bash
# Start services
sudo /opt/lampp/lampp start

# Stop services
sudo /opt/lampp/lampp stop

# Restart services
sudo /opt/lampp/lampp restart

# Check status
sudo /opt/lampp/lampp status

# View Apache error logs
tail -f /opt/lampp/logs/error_log

# View PHP error logs
tail -f /opt/lampp/logs/php_error_log
```

### Development Access
```bash
# Application URL
http://localhost/azteamcrm

# Default admin credentials
Username: haniel
Password: [set in database]

# Fix session storage permissions if needed
chmod -R 777 /opt/lampp/htdocs/azteamcrm/storage/
```

## Current Implementation Status

### ✅ Completed Features

#### User Authentication System
- Login/logout with session management
- Session timeout (30 minutes configurable)
- CSRF protection on all forms
- Password hashing with bcrypt
- Role-based access control (Administrator, Production Team)

#### User Management Module
- Full CRUD operations for users
- User listing with search functionality
- Create/edit user forms with validation
- User status management (active/inactive toggle)
- Profile page with password change capability
- Duplicate username/email validation
- Self-modification protection

#### Order Management Module
- Complete CRUD operations for orders
- Order listing with real-time search
- Create new orders with customer selection (not inline client data)
- Edit existing orders
- Delete orders (admin only)
- View detailed order information with customer links
- Payment status management (unpaid/partial/paid)
- Order status management (pending/processing/completed/cancelled/on_hold)
- Rush order detection (auto-flags orders due within 7 days)
- Order status badges (rush, overdue, due soon)
- Financial summary per order
- Production progress tracking
- Order notes support
- Links to customer records
- Captured by user tracking

#### Dashboard
- Comprehensive statistics display
- Total orders, pending orders, rush orders
- Financial metrics (revenue, outstanding balance)
- Orders due today and overdue tracking
- Items in production counter
- Urgent orders section with quick actions
- Recent orders table with inline actions
- Direct links to order management

#### Core Framework
- Custom MVC architecture
- Active Record pattern models
- PHP 8+ compatible router (fixed array_values issue)
- Session-based authentication with security features
- CSRF protection middleware
- Input sanitization helpers
- Validation framework

#### Customer Management Module
- Full CRUD operations for customers
- Customer listing with search functionality
- Customer details view with order history
- Company name support (optional)
- Full address management (line 1, line 2, city, state, zip)
- Phone number formatting
- Customer status (active/inactive)
- Revenue and order statistics per customer
- Relationship with orders via foreign key

#### Order Item Management Module (formerly Line Items)
- Full CRUD operations for order items within orders
- Add/edit/delete individual products in orders
- Single status tracking (pending → in_production → completed)
- Pricing at item level (unit_price, auto-calculated total_price)
- Product type selection (shirt, apron, scrub, hat, bag, etc.)
- Size management with proper field name (product_size)
- Customization method tracking (custom_method field)
- Customization areas (custom_area field)
- Supplier status tracking (separate from item status)
- Special notes per item (note_item field)
- Quantity tracking with price calculations
- Visual status badges
- Cascade deletion with orders

### 🔴 Pending Implementation

#### Production Dashboard (`/production`)
**Purpose**: Dedicated workspace for production team to manage daily manufacturing workflow
- **Different from Main Dashboard**: Main dashboard shows business metrics (orders, revenue, statistics), while Production Dashboard shows factory floor view (what to make today)
- **Key Features**:
  - View all line items across ALL orders in one place
  - Filter by production status stages:
    - Waiting Approval queue
    - Artwork Approved queue  
    - Material Prepared queue
    - Ready for production
  - Supplier tracking view:
    - Items awaiting order from supplier
    - Items with orders made
    - Items arrived and ready
  - Priority sorting:
    - Rush orders at top
    - Sort by due date
    - Overdue items highlighted
  - Bulk operations:
    - Update multiple items' status at once
    - Mark batch as completed
  - Today's production schedule
  - Materials needed report
- **Target Users**: Production team members who need task-focused view rather than order-focused view
- **Routes**: `/production`, `/production/pending`, `/production/today`

#### Financial Reporting Module (`/reports`)
- Revenue reports by date range
- Outstanding balance reports  
- Payment status summaries
- Client-wise financial breakdown
- Export to CSV/PDF
#### Advanced Search & Filtering
- Search orders by client name, phone, date range
- Filter by payment status, rush orders, overdue
- Search line items across all orders
- Filter by product type, customization method
- Save frequent filter combinations

#### Bulk Operations
- Update multiple line item statuses at once
- Bulk payment status updates
- Mass order assignment to production team
- Batch printing of order sheets

#### Export Functionality
- Export orders to CSV/Excel
- Generate PDF invoices
- Export production schedules
- Financial reports export
- Client order history export

#### Email Notifications
- Order confirmation emails to clients
- Rush order alerts to production team
- Payment reminder emails
- Order completion notifications
- Daily production summary emails

#### Activity Logging & Audit Trail
- Track all status changes with timestamp and user
- Order modification history
- Payment history tracking
- User activity logs
- Status change reasons/notes
- Database table needed: `order_status_history`

### Active Routes
```php
// Authentication
'/login', '/logout'

// Dashboard
'/', '/dashboard'

// User Management (Admin only)
'/users', '/users/create', '/users/{id}/edit'
'/users/{id}/update', '/users/{id}/delete'
'/users/{id}/toggle-status'

// Profile
'/profile', '/profile/update-password'

// Order Management
'/orders'                      // List all orders
'/orders/create'              // New order form
'/orders/store'               // Save new order
'/orders/{id}'                // View order details
'/orders/{id}/edit'           // Edit order form
'/orders/{id}/update'         // Update order
'/orders/{id}/delete'         // Delete order (admin)
'/orders/{id}/update-status'  // Update payment status

// Customer Management
'/customers'                    // List all customers
'/customers/create'            // New customer form
'/customers/store'             // Save new customer
'/customers/{id}'              // View customer details
'/customers/{id}/edit'         // Edit customer form
'/customers/{id}/update'       // Update customer
'/customers/{id}/delete'       // Delete customer (admin)

// Order Item Management (formerly Line Items)
'/orders/{order_id}/order-items'       // List order items for order
'/orders/{order_id}/order-items/create' // Add new order item
'/orders/{order_id}/order-items/store'  // Save new order item
'/order-items/{id}/edit'                // Edit order item
'/order-items/{id}/update'              // Update order item
'/order-items/{id}/delete'              // Delete order item
'/order-items/{id}/update-status'       // Update status via AJAX

// Pending Implementation
'/production'                     // Production dashboard
'/reports'                        // Reporting module
```

## Key Architectural Patterns

### Request Flow
1. All requests route through `/index.php` via `.htaccess` rewriting
2. Router matches URL pattern and extracts parameters (using `array_values()` for PHP 8+ compatibility)
3. Controller method handles business logic and auth checks
4. Model performs database operations with prepared statements
5. View renders HTML with Bootstrap components

### Authentication Pattern
```php
// Protected controllers extend Controller and use:
$this->requireAuth();                // Ensures user is logged in
$this->requireRole('administrator'); // Checks specific role
$this->verifyCsrf();                // Validates CSRF token
```

### Model Patterns
```php
// Base Model methods:
$model->find($id);                    // Find by primary key
$model->findAll($conditions, $orderBy, $limit);
$model->where($field, $operator, $value);
$model->save();                       // Create or update
$model->delete();                     // Delete record
$model->count($conditions);           // Count records
$model->fill($data);                  // Mass assignment

// Order model specifics:
$order->getOrderItems();              // Get associated order items
$order->getCustomer();                // Get customer record
$order->getUser();                    // Get user who created order
$order->updatePaymentStatus($status);
$order->updateOrderStatus($status);
$order->isOverdue();                  // Check if past due date
$order->isDueSoon();                  // Check if due within 3 days
$order->isRushOrder();                // Check if due within 7 days
$order->getOrderStatusBadge();        // HTML badge for order status
$order->getPaymentStatusBadge();      // HTML badge for payment status

// User model specifics:
$user->authenticate($username, $password);
$user->existsExcept($field, $value, $excludeId);
$user->setPassword($password);       // Hashes with bcrypt

// Customer model specifics:
$customer->getOrders();               // Get all customer orders
$customer->getTotalOrders();          // Count of orders
$customer->getTotalRevenue();         // Sum of order totals
$customer->getStatusBadge();          // HTML badge for status
$customer->formatPhoneNumber();       // Format phone for display

// OrderItem model specifics:
$orderItem->getOrder();               // Get parent order
$orderItem->getUser();                // Get user who created item
$orderItem->updateStatus($status);    // Update item status
$orderItem->updateSupplierStatus($status);
$orderItem->getStatusBadge();         // HTML badge for item status
$orderItem->getSupplierStatusBadge(); // HTML badge for supplier status
$orderItem->getSizeLabel();           // Format size for display
$orderItem->getCustomMethodLabel();   // Format method for display
$orderItem->getProductTypeLabel();

// Boolean handling for MySQL:
$model->field = $value ? 1 : 0;      // Convert to int for MySQL
```

### Controller Helpers
```php
// Validation
$errors = $this->validate($data, [
    'field' => 'required|email|min:3'
]);

// CSRF
$token = $this->csrf();               // Generate token
$this->verifyCsrf();                 // Verify POST request

// Response helpers
$this->json(['success' => true]);    // JSON response
$this->redirect('/path');            // Redirect
$this->view('folder/file', $data);   // Render view

// Input sanitization
$clean = $this->sanitize($input);

// Request checks
$this->isPost();                     // Check if POST request
$this->isGet();                      // Check if GET request
```

## Business Domain Rules

### Order Management
- **Rush orders**: Automatically flagged when due date is within 7 days
- **Overdue**: Orders past due date with payment not completed
- **Due Soon**: Orders due within 3 days
- **Order totals**: Fixed amount set at order creation
- **Payment status**: Tracks unpaid/partial/paid status
- Orders link to customers via foreign key
- Orders can contain multiple order items with individual pricing
- Orders track the employee who captured them

### Production Workflow
- Order items use single status tracking (pending → in_production → completed)
- **Item status**: Main production progress tracking
- **Supplier status**: Separate field for vendor fulfillment tracking
- Production progress calculated as percentage of completed items
- Orders have independent status from items (pending → processing → completed)

### User Roles
- **Administrator**: 
  - Full system access
  - User management capabilities
  - Delete orders
  - View financial reports
  - Access all modules
- **Production Team**: 
  - View and edit orders
  - Update production status
  - Update supplier status
  - View production queue
  - Cannot delete orders or manage users

### Product Types & Customization
- **Products**: shirt, apron, scrub, hat, bag, beanie, business_card, yard_sign, car_magnet, greeting_card, door_hanger, magnet_business_card
- **Methods**: htv (HTV), dft (DFT), embroidery, sublimation, printing_services
- **Areas**: front, back, sleeve (stored in custom_area field)
- **Sizes**: Stored in product_size field - child sizes (child_xs to child_xl), adult sizes (xs-xxxxl), one_size

## Database Schema Notes

### Key Tables
- **users**: Authentication, roles, active status
- **customers**: Customer data, addresses, contact info, status (PRIMARY KEY: customer_id)
- **orders**: Links to customers, financial tracking, order status, payment status (PRIMARY KEY: order_id)
- **order_items**: Products with single-status tracking, pricing, linked to orders (PRIMARY KEY: order_item_id)
- **order_status_history**: Audit trail for status changes (future)

### Important Fields
- Boolean fields stored as TINYINT(1) (0/1)
- Timestamps: date_created (orders), created_at/updated_at (users)
- Foreign keys with CASCADE operations
- CHECK constraints for status values (not ENUM)
- Decimal(10,2) for financial values
- Generated columns for calculations (total_price in order_items)
- Proper field naming: order_id, customer_id, order_item_id (not just 'id')
- SET field type for multiple customization areas

## Common Development Tasks

### Adding a New Module
1. Create controller in `/app/Controllers/`
2. Extend `App\Core\Controller`
3. Add authentication in constructor
4. Create model in `/app/Models/`
5. Create views in `/app/Views/{module}/`
6. Define routes in `/config/routes.php`
7. Add navigation link in `/app/Views/layouts/header.php`
8. Test all CRUD operations
9. Update this documentation

### Creating Views
1. Add view file in `/app/Views/{module}/`
2. Include header/footer from layouts
3. Use Bootstrap 5 classes for consistent styling
4. Add CSRF token to all forms: `<?= $csrf_token ?>`
5. Handle session messages (success/error)
6. Add client-side validation where appropriate

### Working with Models
1. Create model in `/app/Models/`
2. Extend `App\Core\Model`
3. Define `$table` and `$fillable` properties
4. Add relationship methods (e.g., getLineItems())
5. Add business logic methods
6. Handle boolean conversions for MySQL

## Error Handling

### Common Issues & Solutions
- **500 Error on toggle**: Convert boolean to int (0/1) for MySQL
- **Router named argument error**: Use `array_values()` on params in Router.php line 58
- **Session errors**: Check `/storage/` permissions (777)
- **Database connection**: Verify `.env` settings match XAMPP config
- **CSRF token error**: Ensure token is included in all POST forms
- **Date validation**: Ensure date format matches MySQL (Y-m-d)
- **Router underscore parameters**: Router regex pattern must include underscore `[a-z_]+` (line 43)
- **Cannot call constructor**: Remove `parent::__construct()` if parent class has no constructor
- **Undefined session key**: Use `$_SESSION['user_role']` not `$_SESSION['role']`
- **PHP Deprecation warnings**: Use model methods (getSizeLabel, getCustomMethodLabel) instead of str_replace on null fields

### Debugging
```php
// Enable debug mode in .env
APP_DEBUG=true

// Check Apache logs
tail -f /opt/lampp/logs/error_log

// Check PHP errors in code
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug variables
var_dump($variable);
print_r($array);
die(); // Stop execution
```

## Security Considerations
- All user inputs sanitized via `Controller::sanitize()`
- Prepared statements for all database queries
- CSRF protection on all state-changing operations
- Password hashing with bcrypt (cost factor 10)
- Session regeneration on login
- Session timeout after 30 minutes of inactivity
- XSS protection headers in .htaccess
- Role-based access control on sensitive operations
- Self-modification protection (users cannot delete/deactivate themselves)

## Testing Checklist for New Features

Before marking a feature as complete:
- [ ] Create functionality works with all fields
- [ ] Read/View functionality displays all data correctly
- [ ] Update functionality saves changes properly
- [ ] Delete functionality works (if applicable)
- [ ] Form validation prevents invalid data
- [ ] CSRF protection is active on all forms
- [ ] Error messages display correctly
- [ ] Success messages display correctly
- [ ] Navigation links work properly
- [ ] Search/filter functionality works (if applicable)
- [ ] Permission checks are enforced
- [ ] Mobile responsive design is maintained
- [ ] No PHP errors in Apache logs
- [ ] Documentation is updated
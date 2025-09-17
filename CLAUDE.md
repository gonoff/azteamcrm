# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AZTEAM CRM/ERP is a custom apparel and merchandise order management system for a custom apparel company specializing in personalized clothing and promotional products. The system manages orders for corporate clients, teams, and organizations with various customization methods.

**Development Database**: `azteamerp` (local XAMPP/LAMPP)
**Production Database**: `u150881160_azteamte_erp` (Hostinger)

**Current Phase**: Production Ready - Successfully deployed to production with full payment functionality, optimized performance indexes, and proper environment configuration.

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

#### Automatic Order Status Synchronization (NEW)
- **Order status is automatically determined** by the status of its items
- Users only manage item statuses; order status updates automatically
- Order status logic:
  - `pending`: When all items are pending (or no items exist)
  - `in_production`: When at least one item is in production
  - `completed`: When all items are completed
  - `cancelled`: Manual override only (stops auto-sync)
- Manual cancellation available for special cases

#### Status Progression
1. **Order Status**: Automatically synced from items (pending → in_production → completed) or manually cancelled
2. **Order Item Status**: pending → in_production → completed (managed by users)
3. **Supplier Status**: awaiting_order → order_made → order_arrived → order_delivered
4. **Payment Status**: unpaid → partial → paid

## Development Commands

### Database Setup

#### Development Environment (Local)
```bash
# Import database schema
mysql -u root -p azteamerp < azteamerp.sql

# Access MySQL via XAMPP
/opt/lampp/bin/mysql -u root -p

# Configure development environment
cp .env.example .env.development
# Edit .env.development for local database:
# DB_HOST=localhost
# DB_DATABASE=azteamerp  
# DB_USERNAME=root
# DB_PASSWORD=

# Reset database (if needed)
/opt/lampp/bin/mysql -u root -p -e "DROP DATABASE IF EXISTS azteamerp; CREATE DATABASE azteamerp;"
/opt/lampp/bin/mysql -u root -p azteamerp < azteamerp.sql
```

#### Production Environment (Hostinger)
```bash
# Configure production environment  
# Create .env file with Hostinger credentials:
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=u150881160_azteamte_erp
# DB_USERNAME=u150881160_azteamte_admin
# DB_PASSWORD=Net12net12!
# DB_CHARSET=utf8mb4

# Production database managed via Hostinger phpMyAdmin
# Import schema via: azteamerp_production.sql
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
# Development URL
http://localhost/azteamcrm

# Production URL  
https://erp.azteamtech.com (or your Hostinger domain)

# Admin credentials (both environments)
Username: haniel
Password: [set in database]
Username: marceli  
Password: [set in database]

# Fix session storage permissions if needed (development)
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
- Create new orders with searchable customer selection
  - AJAX-powered customer search with autocomplete
  - Search by name, company, or phone number
  - Keyboard navigation support (arrow keys, enter, escape)
  - Visual feedback with loading spinner
  - Selected customer display with change option
  - URL parameter-based customer selection when returning from customer creation
  - JavaScript bridge ensures customer ID is always set in hidden input
- Edit existing orders
- Delete orders (admin only)
- **Improved Order Details View** (NEW):
  - Restructured layout with better information hierarchy
  - Order Details and Production Status cards side-by-side at top
  - Full-width Order Items table for better visibility
  - Full-width Order Summary at bottom for clearer financial information
  - All order item management via modals (no separate page needed)
- Payment status management via modal (unpaid/partial/paid)
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
- Return URL mechanism for seamless workflow when creating customers from orders
- Automatic customer selection via URL parameters when returning to order form
- Customer ID passed in URL for reliable state management across redirects

#### Order Item Management Module (formerly Line Items)
- Full CRUD operations for order items within orders
- **Modal-based Management** (NEW):
  - Add new items via modal (no page redirect)
  - Edit items via modal with all fields accessible
  - Both modals use AJAX for seamless updates
  - Automatic page refresh after successful operations
- Single status tracking (pending → in_production → completed)
- Pricing at item level (unit_price, auto-calculated total_price)
- Product type selection (shirt, apron, scrub, hat, bag, etc.)
- Size management with proper field name (product_size)
- Customization method tracking (custom_method field)
- **Multiple customization areas** (NEW): Can select Front, Back, Sleeve simultaneously
- Supplier status tracking (separate from item status)
- Special notes per item (note_item field)
- Quantity tracking with price calculations
- Visual status badges with dropdown menus for status updates
- AJAX-based status updates without page reload
- Dynamic badge refresh after status changes
- Automatic order total recalculation when items change
- Cascade deletion with orders

#### CSS Architecture & Styling
- Centralized styling in `/assets/css/app.css` (no inline styles)
- Color scheme variables: White, Black, Red theme
- Bootstrap 5.1.3 with custom overrides
- Utility classes for common patterns:
  - `.form-inline` for inline forms
  - `.form-hidden` for hidden forms  
  - `.form-control-readonly` for readonly inputs
  - `.dropdown-scrollable` for scrollable dropdowns
  - `.alert-fixed-top` for fixed positioning alerts
- Error page styling classes for 403/404 pages
- All JavaScript style manipulations use CSS classes (no direct style changes)
- Responsive design with mobile-first approach

#### Production Dashboard Module
- **Main Dashboard** (`/production`): Factory floor view of all active production items
  - Real-time statistics: pending, in production, completed today, rush items, overdue
  - Filter tabs: All Active, Pending, In Production, Overdue, Due Today, Rush Orders
  - Search functionality across order numbers, customers, and products
  - Individual status updates via dropdown menus
  - Bulk selection and status updates
  - Color-coded rows for urgency (red for overdue, orange for rush, yellow for due soon)
- **Pending Items View** (`/production/pending`): Focus on items awaiting production start
  - Quick "Start Production" buttons
  - Sorted by due date with visual urgency indicators
- **Today's Schedule** (`/production/today`): Items due today and tomorrow
  - Grouped by priority: Due Today, Due Tomorrow, Consider Starting
  - Quick action buttons for status changes
- **Materials Report** (`/production/materials`): Aggregated materials needed
  - Grouped by product type, size, and customization method
  - Export to CSV functionality
  - Summary by product type
- **Supplier Tracking** (`/production/supplier-tracking`): NEW - Dedicated supplier management
  - Track supplier orders and deliveries
  - Material preparation tracking
  - Supplier status workflows
- **Features**:
  - AJAX-based status updates without page reload
  - Keyboard navigation support
  - Mobile responsive tables
  - Real-time search and filtering
  - Bulk operations with visual feedback

### 🔴 Pending Implementation

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
'/login' => 'AuthController@showLogin'
'/login/submit' => 'AuthController@login'
'/logout' => 'AuthController@logout'

// Dashboard
'/' => 'DashboardController@index'
'/dashboard' => 'DashboardController@index'

// User Management (Admin only)
'/users' => 'UserController@index'
'/users/create' => 'UserController@create'
'/users/store' => 'UserController@store'
'/users/{id}/edit' => 'UserController@edit'
'/users/{id}/update' => 'UserController@update'
'/users/{id}/delete' => 'UserController@delete'
'/users/{id}/toggle-status' => 'UserController@toggleStatus'

// Profile
'/profile' => 'UserController@profile'
'/profile/update-password' => 'UserController@updatePassword'

// Order Management
'/orders' => 'OrderController@index'              // List all orders
'/orders/create' => 'OrderController@create'      // New order form
'/orders/store' => 'OrderController@store'        // Save new order
'/orders/{id}' => 'OrderController@show'          // View order details
'/orders/{id}/edit' => 'OrderController@edit'     // Edit order form
'/orders/{id}/update' => 'OrderController@update' // Update order
'/orders/{id}/delete' => 'OrderController@delete' // Delete order (admin)
'/orders/{id}/update-status' => 'OrderController@updateStatus' // Update payment status
'/orders/{id}/cancel' => 'OrderController@cancelOrder'          // Cancel order (manual override)

// Customer Management
'/customers' => 'CustomerController@index'         // List all customers
'/customers/search' => 'CustomerController@search' // AJAX search endpoint
'/customers/create' => 'CustomerController@create' // New customer form
'/customers/store' => 'CustomerController@store'   // Save new customer
'/customers/{id}' => 'CustomerController@show'     // View customer details
'/customers/{id}/edit' => 'CustomerController@edit' // Edit customer form
'/customers/{id}/update' => 'CustomerController@update' // Update customer
'/customers/{id}/delete' => 'CustomerController@delete' // Delete customer (admin)
'/customers/{id}/toggle-status' => 'CustomerController@toggleStatus' // Toggle active status

// Order Item Management (formerly Line Items)
// Note: Index route removed - order items are now displayed directly in order show page
'/orders/{order_id}/order-items/create' => 'OrderItemController@create' // Add new item (modal)
'/orders/{order_id}/order-items/store' => 'OrderItemController@store'   // Save new item (AJAX)
'/order-items/{id}/edit' => 'OrderItemController@edit'                  // Edit item (modal)
'/order-items/{id}/update' => 'OrderItemController@update'              // Update item (AJAX)
'/order-items/{id}/delete' => 'OrderItemController@delete'              // Delete item
'/order-items/{id}/update-status' => 'OrderItemController@updateStatus' // Update status AJAX

// Production Dashboard
'/production' => 'ProductionController@index'                     // Main production dashboard
'/production/pending' => 'ProductionController@pending'           // Pending items view
'/production/today' => 'ProductionController@today'               // Today's schedule
'/production/materials' => 'ProductionController@materials'       // Materials report
'/production/bulk-update' => 'ProductionController@updateBulkStatus' // Bulk status update
'/production/supplier-tracking' => 'ProductionController@supplierTracking'   // Supplier tracking view
'/production/update-material-prepared' => 'ProductionController@updateMaterialPrepared' // Material prep updates

// Order-specific routes (NEW)
'/orders/{id}/update-shipping' => 'OrderController@updateShipping'    // Update shipping info
'/orders/{id}/update-discount' => 'OrderController@updateDiscount'    // Update discount
'/orders/{id}/toggle-tax' => 'OrderController@toggleTax'              // Toggle tax calculation
'/orders/{id}/process-payment' => 'OrderController@processPayment'    // Process payment
'/order-items/{id}/update-inline' => 'OrderItemController@updateInline' // Inline item updates

// Pending Implementation (currently commented in routes.php)
// '/reports' => 'ReportController@index'                  // Reports dashboard
// '/reports/financial' => 'ReportController@financial'    // Financial reports
// '/reports/production' => 'ReportController@production'  // Production reports
```

## Key Architectural Patterns

### CSS & Styling Guidelines
- **No inline styles**: All styling must be in `/assets/css/app.css` or use Bootstrap utility classes
- **JavaScript style changes**: Use `classList.add()`, `classList.remove()`, `classList.toggle()` instead of `element.style`
- **Common utility classes**:
  - Use `d-none` instead of `style="display: none"`
  - Use `form-inline` for inline forms instead of `style="display: inline"`
  - Use Bootstrap spacing utilities (`mt-3`, `p-2`, etc.) instead of inline margins/padding
- **Error pages**: Must include both Bootstrap and app.css stylesheets
- **Custom classes naming**: Use descriptive, kebab-case names (e.g., `error-page-gradient`, `form-control-readonly`)

### Request Flow
1. All requests route through `/index.php` via `.htaccess` rewriting
2. Router matches URL pattern and extracts parameters (using `array_values()` for PHP 8+ compatibility)
3. Controller method handles business logic and auth checks
4. Model performs database operations with prepared statements
5. View renders HTML with Bootstrap components

### Model Update Pattern
When updating model properties that need to be saved to database:
```php
// IMPORTANT: Set both attributes array AND object property
$this->attributes['field_name'] = $value;  // For database persistence
$this->field_name = $value;                // For immediate access
return $this->update();                     // Save to database
```

### URL Pattern Rules
- **HTML forms and links** (`action=` and `href=`): Use full path with `/azteamcrm/` prefix
  - Example: `action="/azteamcrm/customers/store"`, `href="/azteamcrm/orders/create"`
- **Controller redirects**: Use relative paths without `/azteamcrm/` prefix
  - Example: `$this->redirect('/customers')` (the redirect() method adds the prefix automatically)

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
$order->updateOrderStatus($status);   // Manual override (use for cancel only)
$order->syncStatusFromItems();        // Auto-sync order status from item statuses
$order->calculateTotal();             // Recalculate total from all order items
$order->isOverdue();                  // Check if past due date
$order->isDueSoon();                  // Check if due within 3 days
$order->isRushOrder();                // Check if due within 7 days
$order->getOrderStatusBadge();        // HTML badge for order status
$order->getPaymentStatusBadge();      // HTML badge for payment status
$order->getOutstandingBalance();      // Calculate outstanding balance for order

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
$customer->searchCustomers($query, $limit = 20); // Search customers by name/company/phone

// OrderItem model specifics:
$orderItem->getOrder();               // Get parent order
$orderItem->getUser();                // Get user who created item
$orderItem->updateStatus($status);    // Update item status (sets both attributes and properties)
$orderItem->updateSupplierStatus($status); // Update supplier status (sets both attributes and properties)
$orderItem->getStatusBadge();         // HTML badge for item status
$orderItem->getSupplierStatusBadge(); // HTML badge for supplier status
$orderItem->getSizeLabel();           // Format size for display
$orderItem->getCustomMethodLabel();   // Format method for display
$orderItem->getProductTypeLabel();
$orderItem->calculateTotalPrice();    // Returns quantity * unit_price

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

// Title case formatting (for names)
$name = $this->toTitleCase($input);  // Converts to title case with special handling
// Handles: McDonald, O'Brien, Portuguese names (de, da, do), etc.

// Request checks
$this->isPost();                     // Check if POST request
$this->isGet();                      // Check if GET request
```

## Business Domain Rules

### Data Formatting Standards
- **Name Formatting**: All customer and user names are automatically converted to title case
  - Applied on create and update operations
  - Handles special cases: McDonald, MacDonald, O'Brien, O'Connor
  - Preserves lowercase articles: de, da, do, dos, das, van, von (except at start)
  - Example: "JOHN MCDONALD" → "John McDonald"
  - Example: "maria da silva" → "Maria da Silva"
- **Location Formatting**:
  - **City names**: Automatically converted to title case
    - Example: "NEW YORK" → "New York", "los angeles" → "Los Angeles"
  - **State codes**: Automatically converted to uppercase (US standard)
    - Example: "ca" → "CA", "ny" → "NY"
- **Database update script**: Available at `/public/run_title_case_update.php` (one-time use)
  - Updates all existing customer names, company names, and cities to title case
  - Ensures all state codes are uppercase
  - Handles special name cases (McDonald, O'Brien, etc.)
  - Delete after running for security

### Order Management
- **Order Status Synchronization** (NEW):
  - Order status is **automatically determined** from item statuses
  - No manual order status changes allowed (except cancellation)
  - Status updates when any item status changes
  - Cancelled orders stop auto-sync (manual override)
- **Rush orders**: Automatically flagged when due date is within 7 days
- **Overdue**: Orders past due date with payment not completed
- **Due Soon**: Orders due within 3 days
- **Order totals**: Automatically calculated from order items (quantity × unit_price)
  - New orders start with total = 0.00
  - Total recalculated whenever items are added/updated/deleted
  - Order total field is read-only in forms
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

### Environment Differences

#### Development Database (`azteamerp`)
- **Location**: Local XAMPP/LAMPP MySQL
- **Performance**: Missing some indexes (acceptable for development)
- **Collation**: Mixed (users table uses utf8mb4_unicode_ci, others use utf8mb4_general_ci)

#### Production Database (`u150881160_azteamte_erp`)  
- **Location**: Hostinger MySQL hosting
- **Performance**: ✅ Fully optimized with ALL performance indexes
- **Collation**: Consistent utf8mb4_general_ci across all tables
- **Optimization**: Complete index coverage for fast queries

### Key Tables
- **users**: Authentication, roles, active status
- **customers**: Customer data, addresses, contact info, status (PRIMARY KEY: customer_id)
- **orders**: Links to customers, financial tracking, order status, payment status (PRIMARY KEY: order_id)
- **order_items**: Products with single-status tracking, pricing, linked to orders (PRIMARY KEY: order_item_id)
- **order_payments**: Payment history with proper column names (PRIMARY KEY: payment_id)
- **settings**: Application configuration with all required default values

### Important Fields
- Boolean fields stored as TINYINT(1) (0/1)
- Timestamps: date_created (orders), created_at/updated_at (users)
- Foreign keys with CASCADE operations
- CHECK constraints for status values (not ENUM)
- Decimal(10,2) for financial values
- Generated columns for calculations (total_price in order_items)
- Proper field naming: order_id, customer_id, order_item_id (not just 'id')
- SET field type for multiple customization areas

### Production Performance Indexes (Complete)
```sql
-- Customer indexes for search and filtering
idx_customer_full_name, idx_customer_company_name, idx_customer_status, 
idx_customer_zip_code, idx_customer_date_created

-- Order indexes for dashboard and date filtering  
idx_order_status, idx_payment_status, idx_order_date_created, idx_order_date_due

-- Order item indexes for production dashboard
idx_order_item_status, idx_order_item_product_type, idx_order_item_supplier_status

-- Settings performance
idx_settings_category
```

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

### Implementing AJAX Search Features
1. Create search endpoint in controller with CSRF verification
2. Add search method to model using `$this->db->getConnection()->prepare()`
3. Implement JavaScript with:
   - Debouncing (300ms recommended)
   - Loading indicators
   - Keyboard navigation
   - Error handling
4. Use fetch API with proper headers:
   ```javascript
   fetch('/azteamcrm/endpoint', {
       method: 'POST',
       headers: {'Content-Type': 'application/x-www-form-urlencoded'},
       body: `csrf_token=<?= $csrf_token ?>&param=${value}`
   })
   ```

### Customer Selection in Order Forms
1. **URL Parameter Approach**: Pass customer ID in URL when returning from customer creation
   - CustomerController adds `?customer_id=X` to return URL
   - OrderController checks `$_GET['customer_id']` first
2. **JavaScript Bridge**: Fallback that syncs display with hidden input
   - Add `data-customer-id` attribute to customer display
   - JavaScript extracts ID and sets hidden input if empty
3. **State Management**: URL parameters more reliable than sessions for cross-redirect state

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
- **Outstanding balance null error**: Use `$order->getOutstandingBalance()` method instead of accessing non-existent property
- **404 on form submission**: Fixed redirect() method to prevent double base path (/azteamcrm//azteamcrm/...)
- **Customer creation flow**: Implemented URL parameter mechanism for reliable customer selection after creation
- **CSRF token validation on delete**: Pass csrf_token to views and use consistent variable names
- **404 on delete/form actions**: Form actions and links in views must use full `/azteamcrm/` prefix, while controller redirects use relative paths
- **Order total before items**: Order total is now auto-calculated from items, no longer required at order creation
- **Status updates not saving**: Model update methods must set both `$this->attributes[]` array AND object properties
- **AJAX status update errors**: Use fetch API instead of form submission for dynamic updates without page reload
- **Database prepare() method not found**: Use `$this->db->getConnection()->prepare()` for prepared statements in models
- **Customer not selected after creation**: Use URL parameters (`?customer_id=X`) to pass customer ID across redirects, with JavaScript bridge as fallback
- **Inline styles in views**: Move all inline styles to `/assets/css/app.css` and use CSS classes instead
- **JavaScript style.display changes**: Use Bootstrap's `d-none` class with `classList.add/remove()` instead of direct style manipulation

### Production-Specific Issues & Solutions

#### Payment Functionality Fails
**Symptoms**: Payment recording doesn't work, payment history empty
**Root Cause**: Missing `order_payments` table or wrong column names
**Solution**: 
```sql
-- Check if table exists and has correct structure
DESCRIBE order_payments;

-- If columns are wrong (amount, processed_by instead of payment_amount, recorded_by):
ALTER TABLE `order_payments` CHANGE `amount` `payment_amount` decimal(10,2) NOT NULL;
ALTER TABLE `order_payments` CHANGE `processed_by` `recorded_by` int(11) DEFAULT NULL;
```

#### Database Connection Fails in Production  
**Symptoms**: "Access denied for user" or "Connection failed"
**Root Cause**: Missing or incorrect `.env` file
**Solution**:
```bash
# Create .env file with production credentials:
DB_HOST=127.0.0.1
DB_DATABASE=u150881160_azteamte_erp  
DB_USERNAME=u150881160_azteamte_admin
DB_PASSWORD=Net12net12!
```

#### Settings/Tax Functionality Fails
**Symptoms**: Tax toggle doesn't work, dashboard shows errors
**Root Cause**: Empty `settings` table
**Solution**:
```sql
-- Add required settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `display_name`) VALUES
('business.ct_tax_rate', '0.0635', 'float', 'business', 'Connecticut Tax Rate'),
('business.rush_order_threshold_days', '7', 'integer', 'business', 'Rush Order Threshold'),
('ui.pagination.default_page_size', '20', 'integer', 'ui', 'Default Page Size');
```

#### Slow Performance in Production
**Symptoms**: Dashboard takes >5 seconds, search timeouts  
**Root Cause**: Missing database indexes
**Solution**: Production should already have all indexes. Verify with:
```sql
SELECT TABLE_NAME, INDEX_NAME FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'u150881160_azteamte_erp' AND INDEX_NAME LIKE 'idx_%';
```

#### Application Loads but Features Don't Work
**Symptoms**: Can login but specific modules fail
**Root Cause**: Environment-specific path issues or missing tables
**Debugging Steps**:
1. Check `.env` file exists and has correct database name
2. Verify all tables exist: `SHOW TABLES;`  
3. Test specific failing queries in phpMyAdmin
4. Check for missing foreign key constraints

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

## UI/UX Color System (v2.0 - Semantic Colors)

### Design Philosophy
The application uses a semantic color system to improve usability and reduce cognitive load. Red is reserved ONLY for destructive/critical actions. Blue handles primary actions. Neutrals carry layout weight.

### Color Palette
- **Primary (Blue)**: `#2563EB` - Main CTAs like "Add New", "Save", "Create", "Start Production"
- **Secondary (Gray)**: `#6B7280` - Neutral actions like "Edit", "View", "Back"
- **Danger (Red)**: `#DC2626` - ONLY for delete, cancel order, and critical alerts
- **Success (Green)**: `#16A34A` - Completed items, paid status, success messages
- **Warning (Amber)**: `#F59E0B` - Pending items, partial payments, due soon
- **Info (Teal)**: `#0891B2` - In-production items, informational messages
- **Brand Accent**: `#E5192B` - AZTEAM red used sparingly for navigation active states

### Button Usage Guidelines
- **btn-primary** (Blue): Add New, Save, Create, Submit, Start Production, Export
- **btn-secondary** (Gray): Edit, View, Back, Cancel (non-destructive)
- **btn-danger** (Red): Delete, Cancel Order (destructive actions only)
- **btn-success** (Green): Complete, Mark as Paid, Record Payment
- **btn-warning** (Amber): Rarely used, special attention actions

### Status Badge Semantics
- **Paid/Completed**: Green badge with light green background
- **Unpaid/Overdue**: Red badge with light red background
- **Pending/Partial**: Amber badge with light amber background
- **In Production**: Teal/info badge with light blue background
- **Rush Order**: Red or amber badge with urgency icon

### Implementation Notes
- CSS variables defined in `/assets/css/app.css`
- Bootstrap classes overridden with semantic colors
- Focus states use primary blue for accessibility
- Dark mode support included (future enhancement)

## Production Deployment Checklist

### Pre-Deployment (Development → Production)
- [ ] **Environment Configuration**
  - [ ] Create `.env` file with Hostinger credentials
  - [ ] Verify database connection settings
  - [ ] Test all environment-specific paths
- [ ] **Database Migration**  
  - [ ] Import `azteamerp_production.sql` to Hostinger database
  - [ ] Verify all tables created with proper indexes
  - [ ] Run settings population SQL
  - [ ] Test database connectivity from application
- [ ] **File Permissions**
  - [ ] Verify storage/ directory permissions on hosting
  - [ ] Test session file creation
  - [ ] Check asset file accessibility

### Post-Deployment Verification
- [ ] **Core Functionality**
  - [ ] User authentication works
  - [ ] Dashboard loads with correct statistics  
  - [ ] Customer management (search, create, edit)
  - [ ] Order management (create, edit, view)
  - [ ] Payment functionality (record payments, view history)
  - [ ] Production dashboard and filtering
- [ ] **Performance Testing**
  - [ ] Dashboard loads quickly (< 2 seconds)
  - [ ] Customer search responds quickly
  - [ ] Date-based filtering is fast
  - [ ] Production pages load without timeout
- [ ] **Data Integrity**
  - [ ] All foreign key constraints active
  - [ ] No orphaned records
  - [ ] Payment statuses consistent
  - [ ] Order totals calculate correctly

### Production Maintenance
```bash
# Monitor production database health
# Run these queries periodically in phpMyAdmin:

# Check for data integrity issues
SELECT 'Orphaned Orders' as issue, COUNT(*) as count 
FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id 
WHERE c.customer_id IS NULL;

# Monitor performance (slow queries)
SHOW PROCESSLIST;

# Check index usage
SHOW INDEX FROM orders;
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
- **Production**: Database credentials secured in .env file (not in code)
- **Production**: Error logging enabled (not displayed to users)

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

## Quick Reference

### File Locations
- **Controllers**: `/app/Controllers/`
- **Models**: `/app/Models/`
- **Views**: `/app/Views/`
- **Core Framework**: `/app/Core/`
- **Configuration**: `/config/`
- **Public Assets**: `/public/`
- **CSS Stylesheets**: `/assets/css/` (app.css for main styles, login.css for login page)
- **JavaScript**: `/assets/js/app.js` (main application JavaScript)
- **Storage**: `/storage/` (session files)
- **Database Schema**: `/azteamerp.sql`
- **Environment Config**: `/.env`

### No Build Process Required
This project uses CDN-hosted dependencies (Bootstrap, jQuery, DataTables) and requires no build tools like npm, composer, webpack, or gulp. All dependencies are loaded via CDN links in the views.
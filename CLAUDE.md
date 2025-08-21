# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AZTEAM CRM/ERP is a custom apparel and merchandise order management system for a custom apparel company specializing in personalized clothing and promotional products. The system manages orders for corporate clients, teams, and organizations with various customization methods.

**Current Phase**: Phase 1 (MVP) - User authentication, user management, order management, and line item management complete with dual status tracking.

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
- **Orders**: Client info, payment tracking (unpaid/partial/paid), rush orders, financial calculations
- **Line Items**: Individual products with dual-status tracking (supplier + completion)
- **Users**: Role-based authentication (administrator, production_team)

### Status Workflows
1. **Supplier Status**: awaiting_to_order → order_made → order_arrived → order_delivered
2. **Completion Status**: waiting_approval → artwork_approved → material_prepared → work_completed
3. **Payment Status**: unpaid → partial → paid

## Development Commands

### Database Setup
```bash
# Import database schema
mysql -u root -p < azteam_database_schema.sql

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
```

### Development Access
```bash
# Application URL
http://localhost/azteamcrm

# Default admin credentials
Username: haniel
Password: [set in database]

# Session storage permissions (if needed)
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
- Create new orders with validation
- Edit existing orders
- Delete orders (admin only)
- View detailed order information
- Payment status management (unpaid/partial/paid)
- Outstanding balance auto-calculation
- Rush order detection (auto-flags orders due within 7 days)
- Order status badges (rush, overdue, due soon)
- Financial summary per order
- Production progress tracking
- Phone number auto-formatting
- Order notes support
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

#### Line Item Management Module
- Full CRUD operations for line items within orders
- Add/edit/delete individual products in orders
- Dual status tracking system:
  - Supplier status workflow (awaiting → order made → arrived → delivered)
  - Completion status workflow (waiting approval → artwork approved → material prepared → completed)
- Independent status updates for supplier and completion tracking
- Product type selection (shirt, apron, scrub, hat, bag, etc.)
- Size management (child sizes XS-XL, adult sizes XS-XXXXL)
- Customization method tracking (HTV, DFT, Embroidery, Sublimation, Printing)
- Customization areas (front, back, sleeve)
- Color specification and special notes
- Quantity tracking per line item
- Progress calculation based on dual status completion
- Dropdown status updates with visual badges
- Cascade deletion with orders

### 🔴 Pending Implementation
- Production workflow interfaces
- Financial reporting module
- Advanced search and filtering
- Bulk order operations
- Export functionality (CSV/PDF)
- Email notifications
- Activity logging

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

// Line Item Management
'/orders/{order_id}/line-items'       // List line items for order
'/orders/{order_id}/line-items/create' // Add new line item
'/orders/{order_id}/line-items/store'  // Save new line item
'/line-items/{id}/edit'                // Edit line item
'/line-items/{id}/update'              // Update line item
'/line-items/{id}/delete'              // Delete line item
'/line-items/{id}/update-status'       // Update status via AJAX

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
$order->getLineItems();               // Get associated line items
$order->getCapturedByUser();          // Get user who created order
$order->updatePaymentStatus($status, $amount);
$order->isOverdue();                  // Check if past due date
$order->isDueSoon();                  // Check if due within 3 days
$order->getStatusBadge();             // HTML badge for status
$order->getUrgencyBadge();            // HTML badge for urgency

// User model specifics:
$user->authenticate($username, $password);
$user->existsExcept($field, $value, $excludeId);
$user->setPassword($password);       // Hashes with bcrypt

// LineItem model specifics:
$lineItem->getOrder();                // Get parent order
$lineItem->updateSupplierStatus($status);
$lineItem->updateCompletionStatus($status);
$lineItem->getSupplierStatusBadge();  // HTML badge for supplier status
$lineItem->getCompletionStatusBadge(); // HTML badge for completion status
$lineItem->getSizeLabel();            // Format size for display
$lineItem->getCustomizationMethodLabel();
$lineItem->getProductTypeLabel();
$lineItem->getCustomizationAreasArray(); // Parse SET field to array

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
- **Outstanding balance**: Total value minus payments received
- **Payment updates**: Recalculates outstanding balance automatically
- Orders can contain multiple line items
- Orders track the employee who captured them

### Production Workflow
- Line items track through dual status systems independently
- **Supplier status**: External vendor fulfillment tracking
- **Completion status**: Internal production progress
- Both statuses must complete for item to be marked done
- Production progress calculated as percentage of completed items

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
- **Methods**: HTV, DFT, Embroidery, Sublimation, Printing Services
- **Areas**: Front, Back, Sleeve (multiple selections allowed)
- **Sizes**: Child sizes (child_xs to child_xl), Adult sizes (XS-XXXXL)

## Database Schema Notes

### Key Tables
- **users**: Authentication, roles, active status
- **orders**: Client info, financial tracking, rush flag, captured_by_user_id
- **line_items**: Products with dual-status tracking, linked to orders
- **order_status_history**: Audit trail for status changes (future)

### Important Fields
- Boolean fields stored as TINYINT(1) (0/1)
- Timestamps: created_at, updated_at (automatic)
- Foreign keys enforce referential integrity
- ENUM types for predefined status values
- Decimal(10,2) for financial values

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
- **Router named argument error**: Use `array_values()` on params
- **Session errors**: Check `/storage/` permissions (777)
- **Database connection**: Verify `.env` settings match XAMPP config
- **CSRF token error**: Ensure token is included in all POST forms
- **Date validation**: Ensure date format matches MySQL (Y-m-d)

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
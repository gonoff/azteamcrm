# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AZTEAM CRM/ERP is a custom apparel and merchandise order management system built for tracking orders, production workflows, and financial transactions. The system handles personalized clothing and promotional products with various customization methods.

**Current Phase**: Phase 1 (MVP) - User authentication and management complete, ready for order management implementation.

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
- **Orders**: Client information, payment tracking, rush orders, financial calculations
- **Line Items**: Individual products with dual-status tracking (supplier + completion)
- **Users**: Role-based authentication (administrator, production_team)

### Status Workflows
1. **Supplier Status**: awaiting_to_order → order_made → order_arrived → order_delivered
2. **Completion Status**: waiting_approval → artwork_approved → material_prepared → work_completed
3. **Payment Status**: unpaid → partial → paid

## Development Commands

### Database Setup
```bash
# Import database schema (adjust credentials as needed)
mysql -u root -p < azteam_database_schema.sql

# Access MySQL via XAMPP
/opt/lampp/bin/mysql -u root -p

# Create .env file from example
cp .env.example .env
# Edit .env with your database credentials
```

### XAMPP/LAMPP Management
```bash
# Start XAMPP services
sudo /opt/lampp/lampp start

# Stop XAMPP services
sudo /opt/lampp/lampp stop

# Restart XAMPP services
sudo /opt/lampp/lampp restart

# Check XAMPP status
sudo /opt/lampp/lampp status
```

### Development Access
```bash
# Application URL
http://localhost/azteamcrm

# Default login credentials
Username: haniel
Password: [set in database]
# Note: Create additional users through the Users management interface

# Session storage permissions (if needed)
chmod -R 777 /opt/lampp/htdocs/azteamcrm/storage/
```

## Current Implementation Status

### ✅ Completed Features
- **User Authentication System**
  - Login/logout with session management
  - Session timeout (30 minutes of inactivity)
  - CSRF protection on all forms
  - Password hashing with bcrypt
  - Role-based access control (Administrator, Production Team)

- **User Management Module**
  - Full CRUD operations for users
  - User listing with search functionality
  - Create/edit user forms with validation
  - User status management (active/inactive toggle)
  - Profile page with password change
  - Duplicate username/email validation
  - Prevents self-deletion and self-deactivation

- **Dashboard**
  - Comprehensive statistics display
  - Recent orders listing
  - Urgent orders tracking
  - Financial overview (revenue, outstanding balance)
  - Order metrics (pending, rush, overdue)

- **Core Framework**
  - Custom MVC architecture
  - Database models with Active Record pattern
  - Router with parameter extraction (fixed for PHP 8+)
  - Session-based authentication with security features

### 🔴 Pending Implementation
- Order CRUD views (`/app/Views/orders/`)
- Line item management views (`/app/Views/line-items/`)
- Controllers: LineItemController, ProductionController, ReportController
- Production workflow interfaces
- Financial reporting functionality
- Advanced search and filtering

### Active Routes
Working routes in `/config/routes.php`:
- Authentication: `/login`, `/logout`
- Dashboard: `/`, `/dashboard`
- User Management: `/users`, `/users/create`, `/users/{id}/edit`, `/users/{id}/toggle-status`
- Profile: `/profile`, `/profile/update-password`

Commented routes (pending implementation):
- Orders: `/orders`, `/orders/create`, `/orders/{id}/edit`
- Line Items: `/orders/{order_id}/line-items`
- Production: `/production`
- Reports: `/reports`

## Key Architectural Patterns

### Request Flow
1. All requests route through `/index.php` via `.htaccess` rewriting
2. Router matches URL pattern and extracts parameters (using `array_values()` for PHP 8+ compatibility)
3. Controller method handles business logic and auth checks
4. Model performs database operations with prepared statements
5. View renders HTML with Bootstrap components

### Authentication Pattern
```php
// All protected controllers extend Controller and use:
$this->requireAuth();        // Ensures user is logged in
$this->requireRole('admin'); // Checks specific role
$this->validateCSRF();       // Validates CSRF token in forms
```

### Model Patterns
```php
// Base Model methods available:
$model->find($id);                    // Find by primary key
$model->findAll();                    // Get all records
$model->where($field, $op, $value);   // Simple where clause (returns array)
$model->save($data);                  // Create or update based on primary key
$model->delete();                     // Delete current record

// User model specific methods:
$user->authenticate($username, $pass); // Verify credentials
$user->existsExcept($field, $val, $excludeId); // Check duplicates
$user->setPassword($password);        // Hash password with bcrypt

// Boolean handling for MySQL:
$model->field = $value ? 1 : 0;       // Convert boolean to int for MySQL
```

### Form Handling Pattern
```php
// Controllers use validation helpers:
$this->validate($data, [
    'client_name' => 'required|string|max:255',
    'due_date' => 'required|date|after:today'
]);
```

## Business Domain Rules

### Order Management
- Rush orders: Due date within 7 days
- Overdue: Past due date with incomplete items
- Outstanding balance: Total value minus payments received
- Orders link to multiple line items

### Production Workflow
- Line items track through dual status systems independently
- Supplier status: External vendor fulfillment tracking
- Completion status: Internal production progress
- Both statuses must complete for item to be done

### User Roles
- **Administrator**: Full system access, user management, financial reporting
- **Production Team**: Update production/supplier status, view production queue

### Product Types & Customization
- **Products**: shirt, apron, scrub, hat, bag, beanie, business_card, yard_sign, car_magnet, greeting_card, door_hanger, magnet_business_card
- **Methods**: HTV, DFT, Embroidery, Sublimation, Printing Services
- **Areas**: Front, Back, Sleeve (multiple selections allowed)
# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AZTEAM CRM/ERP is a custom apparel and merchandise order management system built for tracking orders, production workflows, and financial transactions. The system handles personalized clothing and promotional products with various customization methods.

## System Architecture

### Technology Stack
- **Backend**: PHP 8.x
- **Database**: MySQL 8.0 / MariaDB 10.5+
- **Environment**: XAMPP/LAMPP stack
- **Web Root**: `/opt/lampp/htdocs/azteamcrm/`

### Core Data Model
- **Orders**: Client information, payment tracking, rush orders, dual-status system
- **Line Items**: Individual products with supplier status and completion status tracking
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

### PHP Execution
```bash
# Run PHP scripts via XAMPP
/opt/lampp/bin/php script.php
```

## Implementation Phases

### Phase 1 (MVP - Current Focus)
- User authentication and role management
- Order creation with client information
- Line item management with dual status tracking
- Basic financial tracking (payment status, outstanding balance)
- Order listing and basic search

### Phase 2 (Operational Enhancement)
- Production team dashboard
- Status update workflows
- Advanced search and filtering
- Basic financial reporting

### Phase 3 (Growth Features)
- Enhanced reporting capabilities
- Performance metrics
- System optimizations

## Key Implementation Considerations

### Security
- All passwords must use bcrypt hashing (`password_hash()` in PHP)
- Implement CSRF protection for all forms
- Use prepared statements for all database queries
- Session management with secure cookies

### Database Indexing
Critical indexes are already defined in schema:
- Client name, dates, and payment status for orders
- Supplier and completion status for line items
- Composite indexes for performance optimization

### User Roles
- **Administrator**: Full system access, user management, financial reporting
- **Production Team**: Update production/supplier status, view production queue

### Product Classifications
Supported product types: shirt, apron, scrub, hat, bag, beanie, business_card, yard_sign, car_magnet, greeting_card, door_hanger, magnet_business_card

### Customization Methods
Available methods: HTV, DFT, Embroidery, Sublimation, Printing Services
Customization areas: Front, Back, Sleeve (can be multiple)
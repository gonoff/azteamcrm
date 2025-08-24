# AZTEAM CRM/ERP System Overview

## Table of Contents
1. [Executive Summary](#executive-summary)
2. [Business Context](#business-context)
3. [System Purpose](#system-purpose)
4. [Technology Stack](#technology-stack)
5. [Key Features](#key-features)
6. [System Modules](#system-modules)
7. [User Roles](#user-roles)
8. [System Requirements](#system-requirements)
9. [Architecture Highlights](#architecture-highlights)

---

## Executive Summary

AZTEAM CRM/ERP is a custom-built, web-based order management system designed specifically for a custom apparel and merchandise company. The system provides comprehensive order tracking, customer relationship management, production workflow management, and financial tracking capabilities. Built with PHP and MySQL, it follows a clean MVC architecture pattern without external framework dependencies.

## Business Context

### Company Profile
- **Industry**: Custom apparel and promotional products
- **Services**: Personalized clothing, corporate merchandise, team uniforms
- **Clients**: Corporate clients, sports teams, organizations
- **Production Methods**: HTV, DFT, embroidery, sublimation, printing services

### Business Challenges Addressed
- Manual order tracking leading to errors and delays
- Lack of centralized customer information
- Difficulty managing production workflow
- Incomplete financial tracking and reporting
- No real-time visibility into order status
- Inefficient communication between sales and production teams

## System Purpose

The AZTEAM CRM/ERP system serves as the central nervous system for all business operations:

1. **Order Management**: Complete lifecycle from quote to delivery
2. **Customer Relations**: Centralized customer data and history
3. **Production Control**: Real-time production tracking and scheduling
4. **Financial Management**: Payment tracking, invoicing, and reporting
5. **Team Collaboration**: Role-based access for different departments

## Technology Stack

### Backend
- **Language**: PHP 8.x
- **Framework**: Custom MVC (no external dependencies)
- **Database**: MySQL 8.0 / MariaDB 10.5+
- **Server**: Apache (via XAMPP/LAMPP)
- **Session Management**: File-based PHP sessions

### Frontend
- **CSS Framework**: Bootstrap 5.1.3
- **Icons**: Bootstrap Icons 1.8.1
- **JavaScript**: Vanilla JS (ES6+)
- **jQuery**: 3.6.0 (for Bootstrap components)
- **Charts**: Chart.js (for dashboard visualizations)

### Development Environment
- **Platform**: Linux (optimized for Ubuntu/Debian)
- **Web Server**: XAMPP/LAMPP stack
- **Version Control**: Git
- **Database Management**: phpMyAdmin

### No Build Process
- No NPM/Composer dependencies
- No webpack/gulp/build tools required
- CDN-hosted frontend libraries
- Direct file editing without compilation

## Key Features

### Core Functionality
1. **Multi-tier Order Management**
   - Order creation with customer selection
   - Multiple items per order
   - Automatic status synchronization
   - Rush order detection
   - Due date tracking

2. **Customer Management**
   - Complete customer profiles
   - Address management
   - Order history tracking
   - Revenue analytics per customer
   - Active/inactive status

3. **Production Dashboard**
   - Real-time production queue
   - Priority-based scheduling
   - Bulk status updates
   - Materials report generation
   - Overdue alerts

4. **Financial Tracking**
   - Payment status management
   - Partial payment support
   - Outstanding balance calculations
   - Revenue reporting
   - Customer credit tracking

5. **User Management**
   - Role-based access control
   - User profile management
   - Activity tracking
   - Password management
   - Session timeout

## System Modules

### 1. Authentication Module
- Secure login/logout
- Session management
- Password hashing (bcrypt)
- CSRF protection
- Auto-logout on inactivity

### 2. Dashboard Module
- Real-time statistics
- Urgent orders display
- Financial summaries
- Production metrics
- Recent activity feed

### 3. Customer Module
- CRUD operations
- Search functionality
- Order history
- Revenue tracking
- Contact management

### 4. Order Module
- Order creation/editing
- Customer association
- Payment tracking
- Status management
- Notes and documentation

### 5. Order Items Module
- Product management
- Pricing calculations
- Status tracking
- Supplier management
- Customization details

### 6. Production Module
- Production queue
- Status updates
- Materials planning
- Daily scheduling
- Bulk operations

### 7. User Management Module
- User CRUD operations
- Role assignment
- Profile management
- Access control
- Activity logging

### 8. Reports Module (Planned)
- Financial reports
- Production analytics
- Customer insights
- Export capabilities

## User Roles

### Administrator
- **Access Level**: Full system access
- **Capabilities**:
  - Manage all modules
  - Create/modify users
  - Delete records
  - Access financial data
  - System configuration
  - Generate all reports

### Production Team
- **Access Level**: Limited to production-related functions
- **Capabilities**:
  - View all orders
  - Update production status
  - Manage order items
  - Access production dashboard
  - Generate production reports
  - Cannot delete records
  - Cannot manage users

## System Requirements

### Server Requirements
- **PHP**: Version 8.0 or higher
- **MySQL**: Version 8.0 or MariaDB 10.5+
- **Apache**: Version 2.4+
- **Memory**: Minimum 512MB RAM
- **Storage**: 1GB for application + database growth

### Client Requirements
- **Browser**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **JavaScript**: Must be enabled
- **Cookies**: Must be enabled for sessions
- **Screen Resolution**: Minimum 1024x768

### Network Requirements
- **Bandwidth**: Minimum 1 Mbps
- **Latency**: < 200ms for optimal performance
- **Protocol**: HTTPS recommended for production

## Architecture Highlights

### Design Patterns
1. **MVC Architecture**: Clean separation of concerns
2. **Active Record Pattern**: Simplified database operations
3. **Singleton Pattern**: Database connection management
4. **Front Controller Pattern**: Single entry point
5. **Repository Pattern**: Data access abstraction

### Security Features
- **Authentication**: Session-based with timeout
- **Authorization**: Role-based access control
- **CSRF Protection**: Token validation on all forms
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization and output encoding
- **Password Security**: Bcrypt hashing with salt

### Performance Optimizations
- **Database Indexing**: Strategic indexes on frequently queried columns
- **Lazy Loading**: Load related data only when needed
- **Pagination**: Large datasets are paginated
- **Caching**: Session-based caching for user data
- **Optimized Queries**: Minimal database calls

### Scalability Considerations
- **Modular Architecture**: Easy to add new modules
- **Database Normalization**: Efficient data storage
- **Stateless Design**: Horizontal scaling ready
- **File Organization**: Clear separation of concerns
- **API-Ready**: RESTful endpoints for future integrations

---

## Next Steps

For detailed information about specific aspects of the system, please refer to the following documentation:

- [Database Architecture](02-database-architecture.md) - Complete database schema and relationships
- [Application Architecture](03-application-architecture.md) - Technical architecture details
- [Routing System](04-routing-system.md) - URL routing and request handling
- [Business Workflows](10-business-workflows.md) - Detailed business process documentation

---

*Last Updated: August 2025*
*Version: 2.0*
*Status: Production*
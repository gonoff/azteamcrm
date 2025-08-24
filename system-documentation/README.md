# AZTEAM CRM/ERP System Documentation

## Welcome to the Complete System Documentation

This documentation provides a comprehensive blueprint of the AZTEAM CRM/ERP system, including technical architecture, database design, business workflows, and implementation details.

## 📚 Documentation Index

### Core Documentation

1. **[System Overview](01-system-overview.md)**
   - Executive summary and business context
   - Technology stack overview
   - Key features and modules
   - System requirements

2. **[Database Architecture](02-database-architecture.md)**
   - Complete database schema
   - Entity-relationship diagrams
   - Table structures and relationships
   - Indexes and constraints

3. **[Application Architecture](03-application-architecture.md)**
   - MVC implementation
   - Directory structure
   - Core framework components
   - Design patterns

4. **[Routing System](04-routing-system.md)**
   - URL structure and rewriting
   - Route definitions
   - Parameter extraction
   - Controller mapping

5. **[Models and Data Layer](05-models-and-data-layer.md)**
   - Active Record implementation
   - Model relationships
   - Database query patterns
   - Business logic methods

6. **[Business Workflows](10-business-workflows.md)**
   - Order lifecycle
   - Production workflow
   - Payment processing
   - Status synchronization

### Additional Documentation (To Be Created)

- **Controllers and Business Logic** - Request handling and business operations
- **Views and UI Layer** - Frontend architecture and templates
- **Authentication and Security** - Security implementation details
- **API Endpoints** - AJAX and API documentation
- **Data Flow Diagrams** - Visual system flows
- **Technical Specifications** - Detailed technical specs
- **Deployment Guide** - Installation and deployment
- **Troubleshooting Guide** - Common issues and solutions

## 🚀 Quick Start Guide

### For Developers
1. Start with [System Overview](01-system-overview.md) for context
2. Review [Application Architecture](03-application-architecture.md) for structure
3. Study [Models and Data Layer](05-models-and-data-layer.md) for data operations
4. Understand [Routing System](04-routing-system.md) for request handling

### For Database Administrators
1. Review [Database Architecture](02-database-architecture.md) for schema
2. Check index strategies and constraints
3. Understand relationship mappings

### For Business Analysts
1. Read [System Overview](01-system-overview.md) for business context
2. Study [Business Workflows](10-business-workflows.md) for processes
3. Review feature descriptions and capabilities

### For System Administrators
1. Check system requirements in [System Overview](01-system-overview.md)
2. Review security considerations
3. Understand deployment requirements

## 📋 System Information

### Current Version
- **Application Version**: 2.0
- **Database Version**: azteamerp (migrated from azteamcrm)
- **PHP Version**: 8.x
- **Last Updated**: August 2025

### Technology Stack
- **Backend**: PHP 8.x (Custom MVC)
- **Database**: MySQL 8.0 / MariaDB 10.5+
- **Frontend**: Bootstrap 5.1.3, Vanilla JavaScript
- **Server**: Apache (XAMPP/LAMPP)

### Key Features
- Order Management System
- Customer Relationship Management
- Production Workflow Tracking
- Financial Management
- User Access Control
- Real-time Dashboard
- Automated Status Synchronization

## 🔍 How to Use This Documentation

### Navigation
- Each document has a table of contents for easy navigation
- Cross-references link related topics
- Code examples demonstrate implementation

### Conventions
- `Code blocks` show actual implementation
- **Bold text** highlights important concepts
- Tables organize structured information
- Diagrams visualize relationships and flows

### Updates
Documentation is versioned and dated. Check the footer of each document for:
- Last update date
- Version number
- Module status

## 🛠️ System Modules

### Core Modules
- **Authentication**: User login and session management
- **Dashboard**: Real-time statistics and overview
- **Customers**: Customer management and history
- **Orders**: Order creation and tracking
- **Order Items**: Product and customization details
- **Production**: Production workflow management
- **Users**: User management and roles

### Planned Modules
- **Reports**: Financial and production reporting
- **Inventory**: Stock management
- **Invoicing**: Invoice generation
- **Email**: Automated notifications

## 📊 Database Tables

| Table | Purpose | Records |
|-------|---------|---------|
| users | System users | Active |
| customers | Customer data | Active |
| orders | Order tracking | Active |
| order_items | Product details | Active |

## 🔐 Security Features

- Session-based authentication
- CSRF protection on all forms
- Prepared statements for SQL
- Input sanitization
- Role-based access control
- Password hashing (bcrypt)
- Session timeout
- XSS protection headers

## 📈 Performance Optimizations

- Database indexing strategies
- Lazy loading of relationships
- Pagination for large datasets
- No external framework overhead
- CDN-hosted frontend libraries
- Optimized SQL queries

## 🤝 Contributing

When updating the system:
1. Follow existing code patterns
2. Update relevant documentation
3. Test thoroughly
4. Document new features

## 📞 Support

For questions about this documentation:
- Review troubleshooting guides
- Check code comments
- Consult CLAUDE.md for AI assistance

---

*This documentation is actively maintained and updated with system changes.*

**Documentation Version**: 1.0  
**System Version**: 2.0  
**Last Updated**: August 2025  
**Status**: Production
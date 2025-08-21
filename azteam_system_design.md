# AZTEAM CRM/ERP System Design Document

## 1. Business Overview

AZTEAM is a custom apparel and merchandise company specializing in personalized clothing and promotional products. The company processes orders for corporate clients, teams, and organizations using various customization methods.

## 2. System Data Structure

### 2.1 Order Structure
Each order contains:
- Client name
- Client phone number
- Date of order received
- Due date
- Total value to charge
- Employee that captured order
- Rush order flag
- Order notes
- **Payment status** (Unpaid, Partial, Paid)
- **Outstanding balance**

### 2.2 Line Item Structure
Each line item represents:
- Product description
- Size (Child sizes, XS, S, M, L, XL, XXL, XXXL, XXXXL)
- Method of customization (HTV, DFT, Embroidery, Sublimation, Printing Services)
- Areas to customize (Front, Back, Sleeve)
- Quantity
- **Supplier status** (Awaiting to order, Order made, Order arrived at Company, Order delivered to Client)
- **Completion status** (Waiting for approval, Artwork/mockup approved, Material ordered/prepared, Work completed)
- Product classification (Shirt, Apron, Scrub, Hat, Bag, Beanie, Business Card, Yard Sign, Car Magnet, Greeting Card, Door Hanger, Magnet Business Card)
- Color specification
- Line item notes

## 3. User Roles & Permissions

### 3.1 Administrator
- Full system access
- User management
- System settings
- Financial reporting access

### 3.2 Production Team
- Update production status
- Update supplier status
- View production queue
- Update completion status

## 4. Technology Stack

- **Backend**: PHP 8.x
- **Database**: MySQL 8.0 / MariaDB 10.5+
- **Architecture**: Traditional LAMP stack

## 5. Implementation Phases

### Phase 1: MVP (4-6 weeks)
**Core Functionality**
- User authentication and role management
- Order creation with client information
- Line item management with dual status tracking
- Basic financial tracking (payment status, outstanding balance)
- Order listing and basic search

**Deliverables:**
- Functional order management system
- User login/logout functionality
- Basic dashboard for orders

### Phase 2: Operational Enhancement (4-6 weeks)
**Production Workflow**
- Production team dashboard
- Status update workflows for both supplier and completion tracking
- Advanced search and filtering (by status, client, date range)
- Basic financial reporting (outstanding balances, payment summaries)

**Deliverables:**
- Production-ready system
- Status workflow management
- Financial oversight capabilities

### Phase 3: Growth Features (Ongoing)
**Business Intelligence**
- Enhanced reporting capabilities
- Performance metrics
- System optimizations based on usage patterns

**Deliverables:**
- Scalable system architecture
- Business performance insights

## 6. Core System Requirements

### 6.1 Functional Requirements
- Multi-user access with role-based permissions
- Dual status tracking for complete workflow visibility
- Financial transaction tracking
- Order and line item management
- Production queue visibility

### 6.2 Technical Requirements
- Web-based interface accessible from multiple devices
- Secure user authentication
- Data backup and recovery capabilities
- Responsive design for mobile access

## 7. Success Metrics
- Reduction in order tracking errors
- Improved production workflow visibility
- Accurate financial tracking
- Faster order processing times
- Enhanced team collaboration
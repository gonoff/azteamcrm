# Database Architecture

## Table of Contents
1. [Database Overview](#database-overview)
2. [Entity Relationship Diagram](#entity-relationship-diagram)
3. [Table Schemas](#table-schemas)
4. [Relationships and Foreign Keys](#relationships-and-foreign-keys)
5. [Indexes](#indexes)
6. [Data Types and Constraints](#data-types-and-constraints)
7. [Database Conventions](#database-conventions)
8. [Migration History](#migration-history)

---

## Database Overview

### Database Information
- **Database Name**: `azteamerp` (migrated from `azteamcrm`)
- **Character Set**: `utf8mb4`
- **Collation**: `utf8mb4_unicode_ci`
- **Engine**: InnoDB (for transaction support and foreign keys)
- **Version**: MySQL 8.0+ / MariaDB 10.5+

### Key Design Principles
1. **Normalization**: 3NF (Third Normal Form) to minimize redundancy
2. **Referential Integrity**: Foreign key constraints with CASCADE operations
3. **Audit Fields**: Timestamps for tracking record creation and updates
4. **Soft Deletes**: Status fields instead of physical deletion
5. **Generated Columns**: Automatic calculations (e.g., total_price)

## Entity Relationship Diagram

```
┌─────────────────┐
│     USERS       │
│─────────────────│
│ PK: id          │
│ username        │
│ email           │
│ password_hash   │
│ role            │
│ full_name       │
│ is_active       │
│ created_at      │
│ updated_at      │
└────────┬────────┘
         │
         │ 1:N (created by)
         │
         ├──────────────┐
         │              │
    ┌────▼────┐    ┌────▼──────────┐
    │ ORDERS  │    │ ORDER_ITEMS   │
    │─────────│    │───────────────│
    │ PK: order_id │ PK: order_item_id│
    │ FK: customer_id│ FK: order_id │
    │ FK: user_id │ FK: user_id    │
    │ order_status│ order_item_status│
    │ payment_status│ quantity    │
    │ order_total│ unit_price     │
    │ date_created│ total_price(gen)│
    │ date_due   │ product_type   │
    │ order_notes│ product_size   │
    └─────┬──────┘ custom_method  │
          │        │ supplier_status│
          │        └───────────────┘
          │ N:1
          │
    ┌─────▼────────┐
    │  CUSTOMERS   │
    │──────────────│
    │ PK: customer_id│
    │ customer_status│
    │ full_name    │
    │ company_name │
    │ address_line_1│
    │ city, state  │
    │ phone_number │
    │ email        │
    │ date_created │
    └──────────────┘

Legend:
PK = Primary Key
FK = Foreign Key
1:N = One to Many
N:1 = Many to One
(gen) = Generated Column
```

## Table Schemas

### 1. USERS Table
Stores system user accounts and authentication data.

```sql
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('administrator','production_team') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB;
```

**Field Descriptions:**
- `id`: Unique identifier for each user
- `username`: Login username (unique)
- `email`: User email address (unique)
- `password_hash`: Bcrypt hashed password
- `role`: User role (administrator or production_team)
- `full_name`: Display name of the user
- `is_active`: Boolean flag for active/inactive status
- `created_at`: Timestamp of user creation
- `updated_at`: Auto-updated on record modification

### 2. CUSTOMERS Table
Stores customer information and contact details.

```sql
CREATE TABLE `customers` (
  `customer_id` int(20) NOT NULL AUTO_INCREMENT,
  `customer_status` varchar(50) NOT NULL DEFAULT 'active',
  `full_name` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `address_line_1` varchar(255) NOT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` char(2) NOT NULL,
  `zip_code` varchar(10) NOT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`customer_id`),
  KEY `idx_customer_full_name` (`full_name`),
  KEY `idx_customer_company_name` (`company_name`),
  KEY `idx_customer_status` (`customer_status`),
  KEY `idx_customer_zip_code` (`zip_code`),
  KEY `idx_customer_date_created` (`date_created`)
);
```

**Field Descriptions:**
- `customer_id`: Unique identifier for each customer
- `customer_status`: Active/inactive status
- `full_name`: Customer's full name (title case enforced)
- `company_name`: Optional company name
- `address_line_1`: Primary address
- `address_line_2`: Optional secondary address
- `city`: City name (title case enforced)
- `state`: 2-letter state code (uppercase enforced)
- `zip_code`: Postal code
- `phone_number`: Contact phone number
- `email`: Customer email address
- `date_created`: Customer creation timestamp

### 3. ORDERS Table
Stores order information and links to customers.

```sql
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_status` varchar(50) NOT NULL DEFAULT 'pending',
  `payment_status` varchar(50) NOT NULL DEFAULT 'unpaid',
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_total` decimal(10,2) DEFAULT 0.00,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_due` date DEFAULT NULL,
  `order_notes` text DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `idx_order_customer_id` (`customer_id`),
  KEY `idx_order_user_id` (`user_id`),
  KEY `idx_order_status` (`order_status`),
  KEY `idx_order_payment_status` (`payment_status`),
  KEY `idx_order_date_created` (`date_created`),
  KEY `idx_order_date_due` (`date_due`),
  CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) 
    REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON UPDATE CASCADE
);
```

**Field Descriptions:**
- `order_id`: Unique order identifier
- `order_status`: Auto-synced from items (pending/in_production/completed) or manual (cancelled)
- `payment_status`: Payment tracking (unpaid/partial/paid)
- `customer_id`: Link to customer record
- `user_id`: User who created the order
- `order_total`: Auto-calculated from order items
- `date_created`: Order creation timestamp
- `date_due`: Expected delivery date
- `order_notes`: Additional order information

### 4. ORDER_ITEMS Table
Stores individual products within orders.

```sql
CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_item_status` varchar(50) NOT NULL DEFAULT 'pending',
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `product_type` varchar(100) DEFAULT NULL,
  `product_description` text DEFAULT NULL,
  `product_size` varchar(50) DEFAULT NULL,
  `custom_method` varchar(100) DEFAULT NULL,
  `custom_area` varchar(50) DEFAULT NULL,
  `supplier_status` varchar(50) DEFAULT NULL,
  `note_item` text DEFAULT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `idx_order_item_order_id` (`order_id`),
  KEY `idx_order_item_user_id` (`user_id`),
  KEY `idx_order_item_status` (`order_item_status`),
  KEY `idx_order_item_product_type` (`product_type`),
  KEY `idx_order_item_supplier_status` (`supplier_status`),
  CONSTRAINT `fk_order_item_order` FOREIGN KEY (`order_id`) 
    REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_item_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON UPDATE CASCADE
);
```

**Field Descriptions:**
- `order_item_id`: Unique item identifier
- `order_item_status`: Production status (pending/in_production/completed)
- `order_id`: Parent order reference
- `user_id`: User who created the item
- `quantity`: Number of units
- `unit_price`: Price per unit
- `total_price`: Auto-calculated (quantity × unit_price)
- `product_type`: Type of product (shirt/hat/bag/etc.)
- `product_description`: Detailed product description
- `product_size`: Size specification
- `custom_method`: Customization technique (HTV/embroidery/etc.)
- `custom_area`: Location of customization (front/back/sleeve)
- `supplier_status`: Vendor fulfillment tracking
- `note_item`: Additional item notes

## Relationships and Foreign Keys

### Primary Relationships

1. **Orders → Customers** (N:1)
   - Foreign Key: `orders.customer_id` → `customers.customer_id`
   - Constraint: `fk_order_customer`
   - Action: ON UPDATE CASCADE

2. **Orders → Users** (N:1)
   - Foreign Key: `orders.user_id` → `users.id`
   - Constraint: `fk_order_user`
   - Action: ON UPDATE CASCADE

3. **Order Items → Orders** (N:1)
   - Foreign Key: `order_items.order_id` → `orders.order_id`
   - Constraint: `fk_order_item_order`
   - Action: ON DELETE CASCADE, ON UPDATE CASCADE

4. **Order Items → Users** (N:1)
   - Foreign Key: `order_items.user_id` → `users.id`
   - Constraint: `fk_order_item_user`
   - Action: ON UPDATE CASCADE

### Cascade Behaviors
- **Order Deletion**: Automatically deletes all associated order items
- **Customer Update**: Updates all associated order references
- **User Update**: Updates all associated order and item references

## Indexes

### Performance Indexes

**USERS Table:**
- Primary: `id`
- Unique: `username`, `email`

**CUSTOMERS Table:**
- Primary: `customer_id`
- Regular: `full_name`, `company_name`, `customer_status`, `zip_code`, `date_created`

**ORDERS Table:**
- Primary: `order_id`
- Foreign: `customer_id`, `user_id`
- Regular: `order_status`, `payment_status`, `date_created`, `date_due`

**ORDER_ITEMS Table:**
- Primary: `order_item_id`
- Foreign: `order_id`, `user_id`
- Regular: `order_item_status`, `product_type`, `supplier_status`

### Index Strategy
- Indexes on foreign key columns for JOIN performance
- Indexes on status fields for filtering
- Indexes on date fields for sorting
- Composite indexes avoided for flexibility

## Data Types and Constraints

### String Fields
- **VARCHAR**: Variable-length strings with defined maximum
- **CHAR(2)**: Fixed-length for state codes
- **TEXT**: Unlimited length for notes and descriptions
- **ENUM**: Restricted values for roles

### Numeric Fields
- **INT**: Integer values for IDs and quantities
- **DECIMAL(10,2)**: Precise decimal for financial values
- **TINYINT(1)**: Boolean values (0/1)

### Date/Time Fields
- **TIMESTAMP**: Automatic timestamps with timezone
- **DATE**: Date-only fields for due dates
- **ON UPDATE**: Auto-update timestamps

### Constraints
- **NOT NULL**: Required fields
- **DEFAULT**: Default values for new records
- **UNIQUE**: Prevent duplicate values
- **CHECK**: Value validation (implicit in ENUM)
- **GENERATED**: Calculated columns

## Database Conventions

### Naming Conventions
1. **Tables**: Plural, lowercase, underscore-separated
2. **Columns**: Lowercase, underscore-separated
3. **Primary Keys**: `table_name_id` format (except users.id)
4. **Foreign Keys**: Match referenced column name
5. **Indexes**: `idx_table_column` format
6. **Constraints**: `fk_child_parent` format

### Data Standards
1. **Status Fields**: Lowercase with underscores
2. **Dates**: ISO format (YYYY-MM-DD)
3. **Phone Numbers**: Stored with formatting
4. **Money**: DECIMAL with 2 decimal places
5. **Booleans**: TINYINT(1) with 0/1 values

### Best Practices
1. Use InnoDB for all tables
2. Define foreign keys explicitly
3. Add indexes on frequently queried columns
4. Use appropriate data types and lengths
5. Include audit timestamps
6. Avoid NULL where possible
7. Use ENUM for fixed value sets

## Migration History

### Phase 1: Initial Schema (azteamcrm)
- Basic tables without relationships
- No foreign key constraints
- Mixed naming conventions

### Phase 2: Current Schema (azteamerp)
- Renamed database from azteamcrm to azteamerp
- Added proper foreign key constraints
- Implemented cascade operations
- Added generated columns
- Standardized naming conventions
- Added comprehensive indexes
- Removed unused tables (LineItem, OrderPayment)

### Future Considerations
1. **Audit Tables**: Track all changes to critical data
2. **Archive Tables**: Move old orders for performance
3. **Cache Tables**: Pre-calculated reports
4. **Session Table**: Database-backed sessions
5. **File Attachments**: Document storage metadata

---

## Maintenance Scripts

### Backup Command
```bash
mysqldump -u root -p azteamerp > backup_$(date +%Y%m%d).sql
```

### Restore Command
```bash
mysql -u root -p azteamerp < backup_20250824.sql
```

### Optimization Command
```sql
OPTIMIZE TABLE users, customers, orders, order_items;
```

---

*Last Updated: August 2025*
*Schema Version: 2.0*
*Database: azteamerp*
-- AZTEAM CRM/ERP Database Schema
-- MySQL 8.0 / MariaDB 10.5+

-- Users table for authentication and role management
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('administrator', 'production_team') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Orders table - main order information
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_name VARCHAR(100) NOT NULL,
    client_phone VARCHAR(20) NOT NULL,
    date_received DATE NOT NULL,
    due_date DATE NOT NULL,
    total_value DECIMAL(10,2) NOT NULL,
    outstanding_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    captured_by_user_id INT NOT NULL,
    is_rush_order BOOLEAN DEFAULT FALSE,
    order_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (captured_by_user_id) REFERENCES users(id),
    INDEX idx_client_name (client_name),
    INDEX idx_date_received (date_received),
    INDEX idx_due_date (due_date),
    INDEX idx_payment_status (payment_status)
);

-- Line items table - individual products within orders
CREATE TABLE line_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_description VARCHAR(255) NOT NULL,
    size ENUM('child_xs', 'child_s', 'child_m', 'child_l', 'child_xl', 'xs', 's', 'm', 'l', 'xl', 'xxl', 'xxxl', 'xxxxl') NOT NULL,
    customization_method ENUM('htv', 'dft', 'embroidery', 'sublimation', 'printing_services') NOT NULL,
    customization_areas SET('front', 'back', 'sleeve') NOT NULL,
    quantity INT NOT NULL,
    supplier_status ENUM('awaiting_to_order', 'order_made', 'order_arrived', 'order_delivered') DEFAULT 'awaiting_to_order',
    completion_status ENUM('waiting_approval', 'artwork_approved', 'material_prepared', 'work_completed') DEFAULT 'waiting_approval',
    product_type ENUM('shirt', 'apron', 'scrub', 'hat', 'bag', 'beanie', 'business_card', 'yard_sign', 'car_magnet', 'greeting_card', 'door_hanger', 'magnet_business_card') NOT NULL,
    color_specification VARCHAR(100),
    line_item_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_supplier_status (supplier_status),
    INDEX idx_completion_status (completion_status),
    INDEX idx_product_type (product_type)
);

-- Optional: Create indexes for better performance
CREATE INDEX idx_orders_rush ON orders(is_rush_order, due_date);
CREATE INDEX idx_line_items_status ON line_items(supplier_status, completion_status);

-- Insert default admin user (password should be hashed in real implementation)
INSERT INTO users (username, email, password_hash, role, full_name) 
VALUES ('admin', 'admin@azteam.com', '$2y$10$example_hash_here', 'administrator', 'System Administrator');
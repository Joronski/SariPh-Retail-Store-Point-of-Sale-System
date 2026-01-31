CREATE DATABASE IF NOT EXISTS sariph_pos;
USE sariph_pos;

-- Users table for POS
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('Administrator', 'Supervisor', 'Cashier') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products table for POS
CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    barcode VARCHAR(50) UNIQUE NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sales table for POS
CREATE TABLE sales (
    sale_id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_number VARCHAR(50) UNIQUE NOT NULL,
    cashier_id INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    discount_type ENUM('None', 'Senior Citizen', 'Person With Disability', 'Athlete', 'Solo Parent') DEFAULT 'None',
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    change_amount DECIMAL(10,2) NOT NULL,
    status ENUM('Completed', 'Cancelled', 'Voided') DEFAULT 'Completed',
    voided_by INT NULL,
    void_reason TEXT NULL,
    voided_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cashier_id) REFERENCES users(user_id),
    FOREIGN KEY (voided_by) REFERENCES users(user_id)
);

-- Sale Items table for POS
CREATE TABLE sale_items (
    sale_item_id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    is_voided TINYINT(1) DEFAULT 0,
    voided_at TIMESTAMP NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Audit Log table for POS
CREATE TABLE audit_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Receipt reprints table for POS
CREATE TABLE receipt_reprints (
    reprint_id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT NOT NULL,
    reprinted_by INT NOT NULL,
    reprint_reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id),
    FOREIGN KEY (reprinted_by) REFERENCES users(user_id)
);

-- Inserting default admin user
INSERT INTO users (username, password, full_name, role)
VALUES ('admin', 'administrator123', 'System Administrator', 'Administrator');

-- Inserting sample products
INSERT INTO products (barcode, product_name, price, stock_quantity)
VALUES ('1', 'Rice 5kg', 50.00, 100),
('2', 'Cooking Oil 1L', 120.00, 50),
('3', 'Sugar 1kg', 65.00, 80),
('4', 'Notebook', 25.00, 200),
('5', 'Ballpen 5 Pcs (1 pack box)', 100.00, 150);
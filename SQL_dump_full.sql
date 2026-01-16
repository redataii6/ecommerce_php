-- ============================================
-- Mini E-Commerce Database Schema
-- Project 7 - PHP & Symfony
-- ============================================

-- Drop existing tables if they exist
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

-- ============================================
-- Users Table
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    roles JSON DEFAULT NULL COMMENT 'JSON array of roles: ROLE_USER, ROLE_ADMIN',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Products Table
-- ============================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    category VARCHAR(100) DEFAULT NULL,
    image_path VARCHAR(500) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_price (price),
    INDEX idx_stock (stock)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Orders Table
-- ============================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL COMMENT 'NULL for guest checkout',
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Order Items Table
-- ============================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    product_name VARCHAR(255) NOT NULL COMMENT 'Stored for historical reference',
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL COMMENT 'Price at time of purchase',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Sample Data: Users
-- ============================================
-- Password for user@test.test is: password123
-- Password for admin@test.test is: adminpass
-- Password for user2@test.test is: password123

INSERT INTO users (name, email, password_hash, roles, is_active, created_at) VALUES
('Test User', 'user@test.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '["ROLE_USER"]', 1, NOW()),
('Admin User', 'admin@test.test', '$2y$10$xLxqVg5w3RS3V3qXhBLZpOQZCqXxnxN5kxqVg5w3RS3V3qXhBLZpO', '["ROLE_USER", "ROLE_ADMIN"]', 1, NOW()),
('Second User', 'user2@test.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '["ROLE_USER"]', 1, NOW());

-- Update password hashes with proper values
UPDATE users SET password_hash = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm' WHERE email = 'user@test.test';
UPDATE users SET password_hash = '$2y$10$kzKxJhGdLxqVg5w3RS3V3e.xLxqVg5w3RS3V3qXhBLZpOQZCqXxnx' WHERE email = 'admin@test.test';
UPDATE users SET password_hash = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm' WHERE email = 'user2@test.test';

-- ============================================
-- Sample Data: Products
-- ============================================
INSERT INTO products (name, description, price, stock, category, image_path, created_at) VALUES
('Wireless Bluetooth Headphones', 'High-quality wireless headphones with noise cancellation and 30-hour battery life. Perfect for music lovers and professionals.', 79.99, 50, 'Electronics', '/assets/images/products/headphones.jpg', NOW()),
('Organic Cotton T-Shirt', 'Comfortable and eco-friendly t-shirt made from 100% organic cotton. Available in multiple colors.', 24.99, 100, 'Clothing', '/assets/images/products/tshirt.jpg', NOW()),
('Stainless Steel Water Bottle', 'Double-walled insulated water bottle that keeps drinks cold for 24 hours or hot for 12 hours.', 19.99, 75, 'Home & Kitchen', '/assets/images/products/bottle.jpg', NOW()),
('Mechanical Keyboard', 'RGB backlit mechanical keyboard with Cherry MX switches. Perfect for gaming and typing.', 129.99, 30, 'Electronics', '/assets/images/products/keyboard.jpg', NOW()),
('Running Shoes', 'Lightweight and breathable running shoes with excellent cushioning and support.', 89.99, 45, 'Sports', '/assets/images/products/shoes.jpg', NOW()),
('Leather Wallet', 'Genuine leather bifold wallet with RFID blocking technology. Slim design with multiple card slots.', 34.99, 60, 'Accessories', '/assets/images/products/wallet.jpg', NOW()),
('Smart Watch', 'Feature-rich smartwatch with heart rate monitoring, GPS, and 7-day battery life.', 199.99, 25, 'Electronics', '/assets/images/products/smartwatch.jpg', NOW()),
('Yoga Mat', 'Non-slip yoga mat with extra cushioning. Perfect for yoga, pilates, and floor exercises.', 29.99, 80, 'Sports', '/assets/images/products/yogamat.jpg', NOW()),
('Coffee Maker', 'Programmable coffee maker with 12-cup capacity and built-in grinder.', 149.99, 20, 'Home & Kitchen', '/assets/images/products/coffeemaker.jpg', NOW()),
('Backpack', 'Durable backpack with laptop compartment and multiple pockets. Water-resistant material.', 49.99, 55, 'Accessories', '/assets/images/products/backpack.jpg', NOW()),
('Wireless Mouse', 'Ergonomic wireless mouse with adjustable DPI and silent clicks.', 29.99, 90, 'Electronics', '/assets/images/products/mouse.jpg', NOW()),
('Desk Lamp', 'LED desk lamp with adjustable brightness and color temperature. USB charging port included.', 39.99, 40, 'Home & Kitchen', '/assets/images/products/desklamp.jpg', NOW());

-- ============================================
-- Sample Data: Orders (for testing owner-check)
-- ============================================
-- Order for user 1 (user@test.test)
INSERT INTO orders (user_id, customer_name, customer_email, phone, address, total, status, created_at) VALUES
(1, 'Test User', 'user@test.test', '+33 1 23 45 67 89', '123 Main Street\nParis, 75001\nFrance', 129.97, 'pending', DATE_SUB(NOW(), INTERVAL 2 DAY));

INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES
(1, 1, 'Wireless Bluetooth Headphones', 1, 79.99),
(1, 3, 'Stainless Steel Water Bottle', 1, 19.99),
(1, 8, 'Yoga Mat', 1, 29.99);

-- Order for user 3 (user2@test.test) - for testing 403 owner-check
INSERT INTO orders (user_id, customer_name, customer_email, phone, address, total, status, created_at) VALUES
(3, 'Second User', 'user2@test.test', '+33 6 12 34 56 78', '456 Avenue des Champs\nLyon, 69001\nFrance', 279.98, 'processing', DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES
(2, 7, 'Smart Watch', 1, 199.99),
(2, 1, 'Wireless Bluetooth Headphones', 1, 79.99);

-- Another order for user 1
INSERT INTO orders (user_id, customer_name, customer_email, phone, address, total, status, created_at) VALUES
(1, 'Test User', 'user@test.test', '+33 1 23 45 67 89', '123 Main Street\nParis, 75001\nFrance', 89.99, 'delivered', DATE_SUB(NOW(), INTERVAL 7 DAY));

INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES
(3, 5, 'Running Shoes', 1, 89.99);

-- ============================================
-- End of SQL Dump
-- ============================================

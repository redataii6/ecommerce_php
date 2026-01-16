# Project 7 - Mini E-Commerce (Catalogue et Panier)

## Overview

This project implements a complete mini e-commerce application with two parts:

- **Part A (partieA/)**: Native PHP + PDO + Session-based Cart - Product catalog, shopping cart, and checkout
- **Part B (symfony/)**: Symfony 6.4 - Orders management module with admin panel

## Features

### Part A - Native PHP
- Product catalog with categories and search
- Session-based shopping cart (no database for cart)
- User registration and authentication
- Checkout with order creation
- Email confirmation via MailHog
- Admin panel for products and orders
- **Owner-check security** (403 Forbidden for unauthorized access)

### Part B - Symfony
- User orders list (/symfony/me/orders)
- Order detail view with owner-check
- Admin orders management (/symfony/admin/orders)
- Role-based access control (ROLE_USER, ROLE_ADMIN)
- **Owner-check security** (403 Forbidden for unauthorized access)

## Requirements

- PHP 8.1+
- MySQL 8.0+
- Composer
- MailHog (for email testing)

## Installation

### Step 1: Install MySQL

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install mysql-server

# Start MySQL
sudo systemctl start mysql

# Secure installation (optional)
sudo mysql_secure_installation
```

### Step 2: Create Database

```bash
# Login to MySQL
sudo mysql -u root -p

# Create database and user
CREATE DATABASE ecommerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'root'@'localhost' IDENTIFIED BY 'root';
GRANT ALL PRIVILEGES ON ecommerce_db.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 3: Import SQL Dump

```bash
# Import the database schema and sample data
mysql -u root -p ecommerce_db < SQL_dump_full.sql
```

### Step 4: Install MailHog

```bash
# Download and install MailHog
go install github.com/mailhog/MailHog@latest

# Or using Docker
docker run -d -p 1025:1025 -p 8025:8025 mailhog/mailhog

# Or download binary
wget https://github.com/mailhog/MailHog/releases/download/v1.0.1/MailHog_linux_amd64
chmod +x MailHog_linux_amd64
./MailHog_linux_amd64
```

MailHog will be available at:
- SMTP: localhost:1025
- Web UI: http://localhost:8025

### Step 5: Configure Part A

```bash
cd partieA

# Edit configuration if needed
nano src/config.php
```

Default configuration in `src/config.php`:
```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'ecommerce_db');
define('DB_USER', 'root');
define('DB_PASS', 'root');

define('MAIL_HOST', '127.0.0.1');
define('MAIL_PORT', 1025);
```

### Step 6: Run Part A

```bash
cd partieA/public

# Start PHP built-in server
php -S localhost:8000

# Access at http://localhost:8000
```

### Step 7: Configure Part B (Symfony)

```bash
cd symfony

# Install dependencies
composer install

# Configure database (edit .env.local)
cp .env.local.example .env.local
nano .env.local

# Set DATABASE_URL and MAILER_DSN
DATABASE_URL="mysql://root:root@127.0.0.1:3306/ecommerce_db?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN=smtp://127.0.0.1:1025

# Clear cache
php bin/console cache:clear
```

### Step 8: Run Part B (Symfony)

```bash
cd symfony

# Start Symfony server
php -S localhost:8001 -t public

# Or using Symfony CLI
symfony server:start --port=8001

# Access at http://localhost:8001/symfony/login
```

## Test Accounts

| Role  | Email           | Password     |
|-------|-----------------|--------------|
| User  | user@test.test  | password123  |
| Admin | admin@test.test | adminpass    |
| User2 | user2@test.test | password123  |

## URLs

### Part A (Native PHP)
- Home/Catalog: http://localhost:8000/
- Cart: http://localhost:8000/cart.php
- Checkout: http://localhost:8000/checkout.php
- Login: http://localhost:8000/login.php
- My Orders: http://localhost:8000/orders.php
- Admin Products: http://localhost:8000/admin/products.php
- Admin Orders: http://localhost:8000/admin/orders.php

### Part B (Symfony)
- Login: http://localhost:8001/symfony/login
- My Orders: http://localhost:8001/symfony/me/orders
- Admin Orders: http://localhost:8001/symfony/admin/orders

### MailHog
- Web UI: http://localhost:8025

## Security Features

### Owner-Check (403 Forbidden)

Both Part A and Part B implement owner-check security:

**Part A (order.php):**
```php
// OWNER-CHECK: Verify user owns this order or is admin
if ($order['user_id'] !== getCurrentUserId() && !isAdmin()) {
    http_response_code(403);
    include __DIR__ . '/403.php';
    exit;
}
```

**Part B (MeOrderController.php):**
```php
// OWNER-CHECK: Verify the current user owns this order
if ($order->getUser() !== $this->getUser()) {
    throw $this->createAccessDeniedException('You are not allowed to view this order.');
}
```

### Testing Owner-Check

1. Login as `user@test.test`
2. Go to My Orders
3. Try to access Order #2 (belongs to user2@test.test)
4. You will see 403 Forbidden page

See `screenshots/403_owner_check.png` for the expected result.

### CSRF Protection

All forms include CSRF tokens to prevent cross-site request forgery attacks.

### Password Hashing

Passwords are hashed using PHP's `password_hash()` with `PASSWORD_DEFAULT` algorithm.

### Prepared Statements

All database queries use PDO prepared statements to prevent SQL injection.

## Email Testing with MailHog

1. Start MailHog (see installation above)
2. Place an order through checkout
3. Open MailHog Web UI at http://localhost:8025
4. View the order confirmation email

See `screenshots/mailhog_email.png` for the expected result.

## Project Structure

```
e-commerce-for-submission/
├── partieA/                    # Part A - Native PHP
│   ├── public/                 # Web root
│   │   ├── index.php          # Product catalog
│   │   ├── product.php        # Product detail
│   │   ├── cart.php           # Shopping cart
│   │   ├── checkout.php       # Checkout
│   │   ├── login.php          # Login
│   │   ├── register.php       # Registration
│   │   ├── orders.php         # User orders
│   │   ├── order.php          # Order detail (owner-check)
│   │   ├── 403.php            # 403 Forbidden page
│   │   ├── 404.php            # 404 Not Found page
│   │   └── admin/             # Admin panel
│   │       ├── products.php
│   │       ├── product-edit.php
│   │       ├── orders.php
│   │       └── order-view.php
│   └── src/                    # PHP source files
│       ├── config.php         # Configuration
│       ├── db.php             # PDO connection
│       ├── auth.php           # Authentication
│       ├── cart.php           # Cart functions
│       ├── mail.php           # MailHog integration
│       ├── helpers.php        # Utility functions
│       └── templates/         # Header/Footer
│
├── symfony/                    # Part B - Symfony
│   ├── src/
│   │   ├── Controller/
│   │   │   ├── MeOrderController.php      # User orders
│   │   │   ├── AdminOrderController.php   # Admin orders
│   │   │   └── SecurityController.php     # Login/Logout
│   │   ├── Entity/
│   │   │   ├── User.php
│   │   │   ├── Order.php
│   │   │   └── OrderItem.php
│   │   └── Repository/
│   ├── templates/
│   │   ├── base.html.twig
│   │   ├── security/login.html.twig
│   │   ├── me/                # User templates
│   │   └── admin/             # Admin templates
│   └── config/
│       └── packages/security.yaml
│
├── screenshots/                # Documentation screenshots
│   ├── 403_owner_check.png    # 403 Forbidden screenshot
│   └── mailhog_email.png      # MailHog email screenshot
│
├── SQL_dump_full.sql          # Database schema and sample data
└── README.md                  # This file
```

## Troubleshooting

### Database Connection Error
- Verify MySQL is running: `sudo systemctl status mysql`
- Check credentials in `partieA/src/config.php` and `symfony/.env.local`
- Ensure database exists: `mysql -u root -p -e "SHOW DATABASES;"`

### MailHog Not Receiving Emails
- Verify MailHog is running on port 1025
- Check SMTP settings in config files
- Test connection: `telnet localhost 1025`

### Symfony Cache Issues
```bash
cd symfony
php bin/console cache:clear
rm -rf var/cache/*
```

### Permission Issues
```bash
chmod -R 755 partieA/public
chmod -R 755 symfony/public
chmod -R 777 symfony/var
```

## Author

Project 7 - PHP & Symfony Course

## License

Educational use only.

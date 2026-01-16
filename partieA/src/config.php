<?php
/**
 * Configuration file for the e-commerce application
 * Part A - Native PHP + PDO
 */

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'ecommerce_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Mail configuration (MailHog)
define('MAIL_HOST', getenv('MAIL_HOST') ?: '127.0.0.1');
define('MAIL_PORT', getenv('MAIL_PORT') ?: 1025);
define('MAIL_FROM', 'noreply@ecommerce.local');
define('MAIL_FROM_NAME', 'E-Commerce Shop');

// Application settings
define('APP_NAME', 'Mini E-Commerce');
define('APP_URL', 'http://127.0.0.1:8000');
define('UPLOAD_DIR', __DIR__ . '/../public/assets/images/products/');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// Timezone
date_default_timezone_set('Europe/Paris');

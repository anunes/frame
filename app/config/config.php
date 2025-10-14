<?php

define('BASE_PATH', dirname(__FILE__, 3));
define('APP_PATH', BASE_PATH . '/app');

// Application configuration
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Framework');
define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?? 'UTC');
define('APP_LOCALE', $_ENV['APP_LOCALE'] ?? 'en_US');

// Database configuration
define('DB_TYPE', $_ENV['DB_TYPE'] ?? 'mysql');

define('DB_HOST', $_ENV['DB_HOST'] ?? '127.0.0.1');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_DATABASE'] ?? 'framework');
define('DB_USER', $_ENV['DB_USERNAME'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASSWORD'] ?? '');
define('DB_CHAR', $_ENV['DB_CHAR'] ?? 'utf8');

// Mail configuration
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? 587);
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_USERNAME', $_ENV['MAIL_FROM_ADDRESS'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? '');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'Framework');
define('MAIL_DEBUG', $_ENV['MAIL_DEBUG'] ?? 0);
define('CONTACT_MAIL_TO', $_ENV['CONTACT_MAIL_TO'] ?? 'contact@example.com');

<?php

defined('BASE_PATH') || define('BASE_PATH', dirname(__FILE__, 4));
defined('CORE_PATH') || define('CORE_PATH', BASE_PATH . '/core');
defined('APP_PATH') || define('APP_PATH', CORE_PATH . '/app');
defined('DATABASE_PATH') || define('DATABASE_PATH', CORE_PATH . '/database');
defined('STORAGE_PATH') || define('STORAGE_PATH', CORE_PATH . '/storage');

// Application configuration
defined('APP_NAME') || define('APP_NAME', $_ENV['APP_NAME'] ?? 'Framework');
defined('APP_URL') || define('APP_URL', rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'));
defined('APP_TIMEZONE') || define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?? 'UTC');
defined('APP_LOCALE') || define('APP_LOCALE', $_ENV['APP_LOCALE'] ?? 'en_US');

// Database configuration
defined('DB_TYPE') || define('DB_TYPE', $_ENV['DB_TYPE'] ?? 'mysql');

defined('DB_HOST') || define('DB_HOST', $_ENV['DB_HOST'] ?? '127.0.0.1');
defined('DB_PORT') || define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
defined('DB_NAME') || define('DB_NAME', $_ENV['DB_DATABASE'] ?? 'framework');
defined('DB_USER') || define('DB_USER', $_ENV['DB_USERNAME'] ?? 'root');
defined('DB_PASS') || define('DB_PASS', $_ENV['DB_PASSWORD'] ?? '');
defined('DB_CHAR') || define('DB_CHAR', $_ENV['DB_CHAR'] ?? 'utf8');

// Mail configuration
defined('MAIL_HOST') || define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
defined('MAIL_PORT') || define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? 587);
defined('MAIL_ENCRYPTION') || define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
defined('MAIL_USERNAME') || define('MAIL_USERNAME', $_ENV['MAIL_FROM_ADDRESS'] ?? '');
defined('MAIL_PASSWORD') || define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
defined('MAIL_FROM_ADDRESS') || define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? '');
defined('MAIL_FROM_NAME') || define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'Framework');
defined('MAIL_DEBUG') || define('MAIL_DEBUG', $_ENV['MAIL_DEBUG'] ?? 0);
defined('CONTACT_MAIL_TO') || define('CONTACT_MAIL_TO', $_ENV['CONTACT_MAIL_TO'] ?? 'contact@example.com');

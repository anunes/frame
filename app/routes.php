<?php

namespace app;

use app\core\Router;

/**
 * Application Routes
 *
 * Routes are organized into separate files for better maintainability:
 * - routes/web.php: Public routes (home, about, contact)
 * - routes/auth.php: Authentication routes (login, register, password reset)
 * - routes/user.php: User profile routes
 * - routes/admin.php: Admin panel routes
 */

// Create a new Router instance
$router = new Router();

// Load route files
require_once __DIR__ . '/routes/web.php';
require_once __DIR__ . '/routes/auth.php';
require_once __DIR__ . '/routes/user.php';
require_once __DIR__ . '/routes/admin.php';

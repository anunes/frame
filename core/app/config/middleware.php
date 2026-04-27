<?php

/**
 * Middleware Configuration
 *
 * Register middleware aliases here for easy reference in routes.
 *
 * Usage in routes:
 * - $router->get('/admin', [AdminController::class, 'index'])->middleware('auth', 'admin');
 * - $router->group(['middleware' => ['auth']], function($router) { ... });
 */

return [
    // Authentication middleware
    'auth' => \app\middleware\Auth::class,

    // Guest-only middleware (redirects authenticated users)
    'guest' => \app\middleware\Guest::class,

    // Public user/account system availability middleware
    'user-system' => \app\middleware\UserSystem::class,

    // Admin role middleware
    'admin' => \app\middleware\Admin::class,
];

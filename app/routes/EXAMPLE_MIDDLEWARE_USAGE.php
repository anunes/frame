<?php

/**
 * EXAMPLE: Middleware Usage in Routes
 *
 * This file demonstrates how to use middleware in your route files.
 * Copy these patterns to your actual route files.
 */

use app\controllers\UserController;
use app\controllers\AdminController;
use app\controllers\AuthController;

// ========================================
// METHOD 1: Route-Level Middleware (Recommended)
// ========================================

// Single middleware - protect a route
$router->get('/profile', [UserController::class, 'showProfile'])
    ->middleware('auth')  // Only authenticated users
    ->name('profile');

// Multiple middleware - auth + admin
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware('auth', 'admin')  // Must be authenticated AND admin
    ->name('admin');

// Guest-only route
$router->get('/login', [AuthController::class, 'showLogin'])
    ->middleware('guest')  // Only for non-authenticated users
    ->name('login');

// ========================================
// METHOD 2: Route Groups (Cleaner for Multiple Routes)
// ========================================

// Group 1: All user profile routes (auth required)
$router->group(['prefix' => 'profile', 'middleware' => ['auth']], function($router) {
    $router->get('/', [UserController::class, 'showProfile'])->name('profile');
    $router->get('/edit', [UserController::class, 'showEditProfile'])->name('profile.edit');
    $router->post('/update', [UserController::class, 'updateProfile']);
    $router->post('/delete', [UserController::class, 'deleteAccount']);
});

// Group 2: All admin routes (auth + admin required)
$router->group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function($router) {
    // Dashboard
    $router->get('/', [AdminController::class, 'index'])->name('admin');

    // User management
    $router->get('/users', [AdminController::class, 'getUsers'])->name('admin.users');
    $router->get('/users/create', [AdminController::class, 'showCreateUser'])->name('admin.users.create');
    $router->post('/users/create', [AdminController::class, 'createUser']);
    $router->get('/users/{id}', [AdminController::class, 'editUser'])->name('admin.users.edit');
    $router->post('/users/{id}', [AdminController::class, 'updateUser']);

    // Settings
    $router->get('/settings', [AdminController::class, 'showSettings'])->name('admin.settings');
    $router->post('/settings', [AdminController::class, 'updateSettings']);
});

// Group 3: Guest-only routes (login, register, etc.)
$router->group(['middleware' => ['guest']], function($router) {
    $router->get('/login', [AuthController::class, 'showLogin'])->name('login');
    $router->get('/register', [AuthController::class, 'showRegister'])->name('register');
    $router->get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('forgot-password');
    $router->get('/reset-password', [AuthController::class, 'showResetPassword'])->name('reset-password');
});

// Note: POST routes for auth typically don't need guest middleware
$router->post('/login', [AuthController::class, 'login']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);

// ========================================
// METHOD 3: Using Helper Functions in Controllers
// ========================================

/**
 * You can also use helper functions directly in your controller methods.
 * This is useful when you need more control or custom logic.
 *
 * Example in a controller:
 *
 * public function showProfile(): void
 * {
 *     require_auth(); // Redirects if not authenticated
 *
 *     // Your code here
 *     view('user/profile');
 * }
 *
 * public function showAdmin(): void
 * {
 *     require_admin(); // Redirects if not admin
 *
 *     // Your code here
 *     view('admin/dashboard');
 * }
 *
 * public function showLogin(): void
 * {
 *     require_guest(); // Redirects if already authenticated
 *
 *     // Your code here
 *     view('auth/login');
 * }
 */

// ========================================
// BEST PRACTICES
// ========================================

/**
 * 1. Use route groups for multiple related routes
 * 2. Use route-level middleware for single routes
 * 3. Always protect admin routes with both 'auth' and 'admin'
 * 4. Use 'guest' middleware for login/register pages
 * 5. Name your routes for easier reference
 * 6. Keep middleware order consistent: ['auth', 'admin']
 */

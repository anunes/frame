<?php

/**
 * Authentication Routes
 * Routes for registration, login, logout, and password management
 */

use app\controllers\AuthController;

// Login remains directly available so admins can sign in when public account
// content is hidden.
$router->get('/login', [AuthController::class, 'showLogin'])
    ->middleware('guest')
    ->name('login');

// Guest-only routes hidden when the public user system is turned off
$router->group(['middleware' => ['user-system', 'guest']], function($router) {
    // Registration
    $router->get('/register', [AuthController::class, 'showRegister'])->name('register');

    // Forgot Password
    $router->get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('forgot-password');

    // Reset Password
    $router->get('/reset-password', [AuthController::class, 'showResetPassword'])->name('reset-password');
});

// POST routes - don't need guest middleware (forms can be submitted)
$router->post('/register', [AuthController::class, 'register'])
    ->middleware('user-system');
$router->post('/login', [AuthController::class, 'login']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('user-system');
$router->post('/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('user-system');

// Authenticated user routes
$router->get('/change-password', [AuthController::class, 'showChangePassword'])
    ->middleware('auth')
    ->name('change-password');

$router->post('/change-password', [AuthController::class, 'changePassword'])
    ->middleware('auth');

// Logout requires authentication
$router->post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

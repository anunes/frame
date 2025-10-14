<?php

/**
 * Authentication Routes
 * Routes for registration, login, logout, and password management
 */

use app\controllers\AuthController;

// Guest-only routes (login, register, forgot password)
$router->group(['middleware' => ['guest']], function($router) {
    // Registration
    $router->get('/register', [AuthController::class, 'showRegister'])->name('register');

    // Login
    $router->get('/login', [AuthController::class, 'showLogin'])->name('login');

    // Forgot Password
    $router->get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('forgot-password');

    // Reset Password
    $router->get('/reset-password', [AuthController::class, 'showResetPassword'])->name('reset-password');
});

// POST routes - don't need guest middleware (forms can be submitted)
$router->post('/register', [AuthController::class, 'register']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);

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

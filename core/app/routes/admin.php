<?php

/**
 * Admin Routes
 * Routes for administration panel (requires auth + admin role)
 */

use app\controllers\AdminController;

// Admin dashboard without group (handles /admin without trailing slash)
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware('auth', 'admin')
    ->name('admin');

// All other admin routes in a group
$router->group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function($router) {

    // User Management
    $router->get('/users', [AdminController::class, 'getUsers'])->name('admin.users');
    $router->get('/users/create', [AdminController::class, 'showCreateUser'])->name('admin.users.create');
    $router->post('/users/create', [AdminController::class, 'createUser']);
    $router->post('/users/toggle-status', [AdminController::class, 'toggleUserStatus']); // Must be before {id} routes
    $router->post('/users/delete', [AdminController::class, 'deleteUser']); // Must be before {id} routes
    $router->get('/users/{id}', [AdminController::class, 'editUser'])->name('admin.users.edit');
    $router->post('/users/{id}', [AdminController::class, 'updateUser']);

    // Contact Settings
    $router->get('/settings', [AdminController::class, 'showSettings'])->name('admin.settings');
    $router->post('/settings', [AdminController::class, 'updateSettings']);

    // Registration Settings
    $router->get('/registration-settings', [AdminController::class, 'showRegistrationSettings'])->name('admin.registration-settings');
    $router->post('/registration-settings', [AdminController::class, 'updateRegistrationSettings']);
});

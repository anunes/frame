<?php

/**
 * User Profile Routes
 * Routes for user profile management (requires authentication)
 */

use app\controllers\UserController;

// Protected routes - require authentication
$router->get('/profile', [UserController::class, 'showProfile'])
    ->middleware('user-system', 'auth')
    ->name('profile');

$router->get('/profile/edit', [UserController::class, 'showEditProfile'])
    ->middleware('user-system', 'auth')
    ->name('profile.edit');

$router->post('/profile/edit', [UserController::class, 'updateProfile'])
    ->middleware('user-system', 'auth');

$router->post('/profile/delete', [UserController::class, 'deleteAccount'])
    ->middleware('user-system', 'auth');

// Avatar serving route - public access for avatars
$router->get('/avatars/{filename}', [UserController::class, 'serveAvatar'])->name('avatar');

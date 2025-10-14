<?php

/**
 * User Profile Routes
 * Routes for user profile management (requires authentication)
 */

use app\controllers\UserController;

// TEST: Simple route without middleware
$router->get('/test-simple', function() {
    echo "<h3>Test Route Debug</h3>";
    echo "Session status: " . session_status() . "<br>";
    echo "Session ID (from session_id()): " . session_id() . "<br>";
    echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";
    echo "Session['id']: " . ($_SESSION['id'] ?? 'NOT SET') . "<br>";
    echo "Session::loggedIn(): " . (\app\core\Session::loggedIn() ? 'YES' : 'NO') . "<br>";
    echo "<br><a href='/'>Go back home</a>";
});

// TEST: Profile route WITHOUT middleware
$router->get('/profile-no-auth', [UserController::class, 'showProfile'])
    ->name('profile-no-auth');

// Protected routes - require authentication
$router->get('/profile', [UserController::class, 'showProfile'])
    ->middleware('auth')
    ->name('profile');

$router->get('/profile/edit', [UserController::class, 'showEditProfile'])
    ->middleware('auth')
    ->name('profile.edit');

$router->post('/profile/edit', [UserController::class, 'updateProfile'])
    ->middleware('auth');

$router->post('/profile/delete', [UserController::class, 'deleteAccount'])
    ->middleware('auth');

// Avatar serving route - public access for avatars
$router->get('/avatars/{filename}', [UserController::class, 'serveAvatar'])->name('avatar');

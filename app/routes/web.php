<?php

/**
 * Public Web Routes
 * Routes accessible to all users
 */

use app\core\Session as SE;
use app\controllers\MainController;

// Main routes
$controller = !SE::loggedIn() ? 'guest' : 'home';
$router->get('/', [MainController::class, $controller])->name('main/' . $controller);

$router->get('/about', [MainController::class, 'about'])->name('main/about');
$router->get('/contact', [MainController::class, 'contact'])->name('main/contact');

// Handle contact form submissions
$router->post('/contact', [MainController::class, 'submitContact']);

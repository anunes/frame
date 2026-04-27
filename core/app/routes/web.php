<?php

/**
 * Public Web Routes
 * Routes accessible to all users
 */

use app\core\Session as SE;
use app\core\ModuleManager;
use app\controllers\MainController;

// Main routes
$startModule = ModuleManager::startModule();
if ($startModule === '' || !in_array($startModule, ModuleManager::modules(), true)) {
    $userSystemHidden = function_exists('user_content_hidden') && user_content_hidden();
    $controller = !SE::loggedIn() && !$userSystemHidden ? 'guest' : 'home';
    $router->get('/', [MainController::class, $controller])->name('main/' . $controller);
}

$router->get('/about', [MainController::class, 'about'])->name('main/about');
$router->get('/contact', [MainController::class, 'contact'])->name('main/contact');
$router->get('/contact/captcha', [MainController::class, 'contactCaptcha'])->name('main/contact/captcha');

// Handle contact form submissions
$router->post('/contact', [MainController::class, 'submitContact']);

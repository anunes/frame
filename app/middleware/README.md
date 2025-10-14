# Middleware System

This framework includes a powerful middleware system for securing routes and controlling access to controllers.

## Available Middleware

### 1. Auth Middleware (`auth`)
Ensures the user is authenticated before accessing a route.
- Redirects to `/login` if not authenticated
- Shows a "Please log in" warning message

### 2. Guest Middleware (`guest`)
Ensures the user is NOT authenticated (for guest-only pages like login/register).
- Redirects authenticated users to home page

### 3. Admin Middleware (`admin`)
Ensures the user is authenticated AND has admin role.
- Redirects to `/login` if not authenticated
- Redirects to home with "Access denied" message if not admin

## Usage

### Method 1: Route-level Middleware (Recommended)

Apply middleware to individual routes using the `middleware()` method:

```php
use app\controllers\UserController;
use app\controllers\AdminController;
use app\controllers\AuthController;

// Single middleware
$router->get('/profile', [UserController::class, 'showProfile'])
    ->middleware('auth');

// Multiple middleware (chainable)
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware('auth', 'admin');

// Guest-only routes
$router->get('/login', [AuthController::class, 'showLogin'])
    ->middleware('guest');
```

### Method 2: Route Groups

Apply middleware to multiple routes at once using groups:

```php
// Auth-protected routes
$router->group(['middleware' => ['auth']], function($router) {
    $router->get('/profile', [UserController::class, 'showProfile']);
    $router->get('/profile/edit', [UserController::class, 'showEditProfile']);
});

// Admin-only routes
$router->group(['middleware' => ['auth', 'admin']], function($router) {
    $router->get('/admin', [AdminController::class, 'index']);
    $router->get('/admin/users', [AdminController::class, 'getUsers']);
});

// Guest-only routes
$router->group(['middleware' => ['guest']], function($router) {
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->get('/register', [AuthController::class, 'showRegister']);
});
```

### Method 3: Using Helper Functions

For simpler cases, you can use helper functions directly in your controllers:

```php
public function showProfile(): void
{
    require_auth(); // Redirects if not authenticated
    view('user/profile');
}

public function showAdmin(): void
{
    require_admin(); // Redirects if not admin
    view('admin/dashboard');
}

public function showLogin(): void
{
    require_guest(); // Redirects if already authenticated
    view('auth/login');
}
```

## Creating Custom Middleware

You can create your own middleware classes by extending the `Middleware` base class:

```php
<?php

namespace app\middleware;

class CustomMiddleware extends Middleware
{
    public function handle(): bool
    {
        // Your custom logic here
        if (/* condition */) {
            // Allow access
            return true;
        }

        // Deny access
        redirect('/');
        exit;
    }
}
```

Then register it in `/app/config/middleware.php`:

```php
return [
    'auth' => \app\middleware\Auth::class,
    'guest' => \app\middleware\Guest::class,
    'admin' => \app\middleware\Admin::class,
    'custom' => \app\middleware\CustomMiddleware::class, // Add your custom middleware
];
```

Use it in your routes:

```php
$router->get('/custom-route', [Controller::class, 'method'])
    ->middleware('custom');
```

## Examples

### Protecting User Routes
```php
// app/routes/user.php
$router->group(['prefix' => 'user', 'middleware' => ['auth']], function($router) {
    $router->get('/profile', [UserController::class, 'showProfile']);
    $router->get('/edit', [UserController::class, 'showEditProfile']);
    $router->post('/update', [UserController::class, 'updateProfile']);
});
```

### Protecting Admin Routes
```php
// app/routes/admin.php
$router->group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function($router) {
    $router->get('/', [AdminController::class, 'index']);
    $router->get('/users', [AdminController::class, 'getUsers']);
    $router->post('/users/create', [AdminController::class, 'createUser']);
});
```

### Guest-Only Routes
```php
// app/routes/auth.php
$router->group(['middleware' => ['guest']], function($router) {
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->get('/register', [AuthController::class, 'showRegister']);
    $router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
});

// POST routes typically don't need guest middleware
$router->post('/login', [AuthController::class, 'login']);
$router->post('/register', [AuthController::class, 'register']);
```

## Best Practices

1. **Use Route Groups**: Group related routes together with shared middleware for cleaner code
2. **Apply Middleware Early**: Middleware runs before your controller, preventing unauthorized access
3. **Combine with Helper Functions**: Use both middleware and helper functions based on your needs
4. **Name Your Routes**: Named routes with middleware are easier to maintain
5. **Test Your Middleware**: Always test that unauthorized access is properly blocked

## Helper Functions Reference

These functions are available in your controllers:

- `require_auth()` - Redirects to login if not authenticated
- `require_guest()` - Redirects to home if authenticated
- `require_admin()` - Redirects if not admin (checks auth first)
- `is_logged_in()` - Check if user is logged in (returns bool)
- `is_admin()` - Check if user is admin (returns bool)

# Framework Improvements

This document outlines the improvements made to the framework.

## 1. Controllers Now Use `view()` Instead of `echo blade()`

### What Changed
All controllers have been updated to use `view()` instead of `echo blade()` for rendering views.

### Why
The `view()` helper function automatically outputs the rendered view, so using `echo` is redundant and less clean.

### Before
```php
public function showProfile(): void
{
    echo blade('user/profile', ['title' => 'Profile']);
}
```

### After
```php
public function showProfile(): void
{
    view('user/profile', ['title' => 'Profile']);
}
```

### Files Updated
- `app/controllers/MainController.php`
- `app/controllers/AuthController.php`
- `app/controllers/UserController.php`
- `app/controllers/AdminController.php`
- `app/console/MakeController.php` (template updated)

---

## 2. Simplified Navbar Link Management

### What's New
A configuration-based system for managing navbar links with automatic permission filtering.

### Features
- **Configuration File**: All navbar links defined in `/app/config/navbar.php`
- **Automatic Filtering**: Links automatically show/hide based on user permissions
- **Easy to Extend**: Add new links without touching view files
- **Icon Support**: Bootstrap Icons integration
- **Active State Detection**: Automatic active link highlighting

### How to Use

#### Adding New Navbar Links

Edit `/app/config/navbar.php`:

```php
return [
    'main' => [
        [
            'label' => 'Home',
            'url' => '/',
            'icon' => 'bi-house-door',  // Optional
            'active' => 'home'          // Optional
        ],
        [
            'label' => 'My New Page',
            'url' => '/new-page',
            'auth' => true,              // Requires authentication
            'icon' => 'bi-star',
            'active' => 'new-page'
        ],
    ],
];
```

#### Link Options
- `label`: Link text (required)
- `url`: Link URL (required)
- `auth`: Requires authentication (optional, default: false)
- `guest`: Show only to guests (optional, default: false)
- `admin`: Requires admin role (optional, default: false)
- `icon`: Bootstrap icon class (optional)
- `active`: Page identifier for active state (optional)
- `show_if`: Conditional visibility (e.g., 'registration_enabled')

### Helper Functions
- `navbar_items($section)` - Get filtered navbar items
- `navbar_item_visible($item)` - Check if item should be visible
- `navbar_item_active($item)` - Get active CSS class

### Files Added
- `/app/config/navbar.php` - Navbar configuration
- Helper functions in `/app/helpers/functions.php`

### Files Updated
- `/app/views/layouts/partials/nav.blade.php` - Uses new system

---

## 3. Authorization Middleware System

### What's New
A comprehensive middleware system for securing routes and controllers.

### Features
- **Class-Based Middleware**: Clean, reusable middleware classes
- **Route Protection**: Secure routes with one line of code
- **Group Middleware**: Apply middleware to multiple routes at once
- **Alias Support**: Use simple names like 'auth' instead of full class names
- **Chainable**: Combine multiple middleware easily

### Available Middleware

#### 1. Auth Middleware (`auth`)
Requires user to be authenticated.
```php
$router->get('/profile', [UserController::class, 'showProfile'])
    ->middleware('auth');
```

#### 2. Guest Middleware (`guest`)
Only allows non-authenticated users (for login/register pages).
```php
$router->get('/login', [AuthController::class, 'showLogin'])
    ->middleware('guest');
```

#### 3. Admin Middleware (`admin`)
Requires user to be authenticated AND have admin role.
```php
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware('auth', 'admin');
```

### Usage Examples

#### Single Route Protection
```php
$router->get('/profile', [UserController::class, 'showProfile'])
    ->middleware('auth')
    ->name('profile');
```

#### Multiple Middleware
```php
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware('auth', 'admin')
    ->name('admin');
```

#### Route Groups (Recommended)
```php
// All admin routes
$router->group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function($router) {
    $router->get('/', [AdminController::class, 'index'])->name('admin');
    $router->get('/users', [AdminController::class, 'getUsers'])->name('admin.users');
});

// Guest-only routes
$router->group(['middleware' => ['guest']], function($router) {
    $router->get('/login', [AuthController::class, 'showLogin'])->name('login');
    $router->get('/register', [AuthController::class, 'showRegister'])->name('register');
});
```

### Helper Functions
You can still use helper functions in controllers:
- `require_auth()` - Redirect if not authenticated
- `require_guest()` - Redirect if authenticated
- `require_admin()` - Redirect if not admin

### Files Added
- `/app/middleware/Middleware.php` - Base middleware class
- `/app/middleware/Auth.php` - Authentication middleware
- `/app/middleware/Guest.php` - Guest-only middleware
- `/app/middleware/Admin.php` - Admin role middleware
- `/app/config/middleware.php` - Middleware aliases
- `/app/middleware/README.md` - Detailed documentation
- `/app/routes/EXAMPLE_MIDDLEWARE_USAGE.php` - Usage examples

### Files Updated
- `/app/core/Router.php` - Enhanced middleware support
- `/app/routes/user.php` - Protected with auth middleware
- `/app/routes/admin.php` - Protected with auth + admin middleware
- `/app/routes/auth.php` - Guest middleware for login/register

---

## Creating Custom Middleware

You can create custom middleware by extending the base `Middleware` class:

```php
<?php

namespace app\middleware;

class CustomMiddleware extends Middleware
{
    public function handle(): bool
    {
        // Your custom logic
        if (/* condition */) {
            return true; // Allow access
        }

        // Deny access
        redirect('/');
        exit;
    }
}
```

Register it in `/app/config/middleware.php`:
```php
return [
    'auth' => \app\middleware\Auth::class,
    'custom' => \app\middleware\CustomMiddleware::class,
];
```

Use it in routes:
```php
$router->get('/custom-route', [Controller::class, 'method'])
    ->middleware('custom');
```

---

## Migration Guide

### For Existing Projects

1. **Controllers**: No changes needed - views will still work
2. **Navbar**: Update `/app/config/navbar.php` to add new links
3. **Routes**: Add middleware to secure routes:
   ```php
   // Before
   $router->get('/admin', [AdminController::class, 'index']);

   // After
   $router->get('/admin', [AdminController::class, 'index'])
       ->middleware('auth', 'admin');
   ```

4. **Remove Manual Checks**: You can remove manual auth checks from controllers:
   ```php
   // Before
   public function showProfile(): void
   {
       if (!Session::loggedIn()) {
           Session::setflash('Please log in', 'warning');
           redirect('/login');
           return;
       }
       view('user/profile');
   }

   // After (middleware handles auth check)
   public function showProfile(): void
   {
       view('user/profile');
   }
   ```

---

## Benefits

1. **Cleaner Code**: Less boilerplate in controllers
2. **Better Security**: Centralized authorization logic
3. **Easier Maintenance**: Change navbar in one place
4. **Consistent UX**: Automatic permission-based UI
5. **Flexible**: Multiple ways to secure routes
6. **Reusable**: Create custom middleware for any logic

---

## Documentation

For detailed documentation, see:
- Navbar: `/app/config/navbar.php` (configuration with comments)
- Middleware: `/app/middleware/README.md`
- Examples: `/app/routes/EXAMPLE_MIDDLEWARE_USAGE.php`

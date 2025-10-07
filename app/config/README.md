# Configuration Files

This directory contains configuration files for various framework features.

## navbar.php

Configuration for navbar links with automatic permission filtering.

**Features:**
- Define all navbar links in one place
- Automatic show/hide based on user permissions
- Support for authentication, guest, and admin requirements
- Bootstrap icon integration
- Active state detection

**Example:**
```php
return [
    'main' => [
        [
            'label' => 'Home',
            'url' => '/',
            'icon' => 'bi-house-door',
            'active' => 'home'
        ],
        [
            'label' => 'Admin',
            'url' => '/admin',
            'auth' => true,      // Requires login
            'admin' => true,     // Requires admin role
            'icon' => 'bi-gear'
        ],
    ],
];
```

## middleware.php

Configuration for middleware aliases.

**Features:**
- Register middleware with simple aliases
- Use aliases in routes instead of full class names
- Easy to extend with custom middleware

**Example:**
```php
return [
    'auth' => \app\middleware\Auth::class,
    'guest' => \app\middleware\Guest::class,
    'admin' => \app\middleware\Admin::class,
];
```

**Usage in routes:**
```php
$router->get('/profile', [UserController::class, 'showProfile'])
    ->middleware('auth');
```

---

For more information, see:
- `/IMPROVEMENTS.md` - Overview of all improvements
- `/app/middleware/README.md` - Middleware documentation

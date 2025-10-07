# Frame PHP Framework - Quick Setup Guide

> **For comprehensive documentation, see [README.md](README.md)**
>
> **For CLI tool documentation, see [CLI.md](CLI.md)**

This is a quick setup guide to get Frame up and running in 5 minutes.

---

## Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Composer

---

## Installation Steps

### 1. Install Dependencies

```bash
composer install
```

### 2. Configure Environment

Copy `.env.example` to `.env` (if needed) and update:

```env
APP_NAME=Frame

# Database
DB_HOST=127.0.0.1
DB_DATABASE=frame
DB_USERNAME=root
DB_PASSWORD=

# Mail (for password reset)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_PASSWORD=your-app-password
```

**Gmail Users**: Use an [App Password](https://myaccount.google.com/apppasswords), not your regular password.

### 3. Database Setup

Create database:

```sql
CREATE DATABASE frame CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Option A:** Import schema directly

```bash
mysql -u root -p frame < database/schema.sql
```

**Option B:** Use migrations (recommended)

```bash
php frame migrate
```

### 4. Start Development Server

```bash
php frame serve
```

Visit: **http://127.0.0.1:8000**

---

## First Steps

### Register a User

1. Go to `/register`
2. Create an account

### Make Yourself Admin

```sql
UPDATE users SET role = 1 WHERE email = 'your-email@example.com';
```

### Access Admin Panel

Visit `/admin` to manage:
- Users
- Contact settings
- Registration toggle

---

## Using the CLI Tool

Frame includes a powerful CLI tool similar to Laravel Artisan:

```bash
# Create files
php frame make:controller ProductController
php frame make:model Product
php frame make:view products/index

# Migrations
php frame make:migration create_products_table
php frame migrate
php frame migrate:rollback

# Routing
php frame route:list

# Development
php frame serve [port]
php frame cache:clear

# Help
php frame list
```

See [CLI.md](CLI.md) for complete documentation.

---

## Project Structure

```
frame/
├── app/
│   ├── console/         # CLI commands
│   ├── controllers/     # Controllers
│   ├── models/          # Models
│   ├── routes/          # Route files (web, auth, user, admin)
│   └── views/           # Blade templates
├── database/
│   ├── migrations/      # Database migrations
│   └── schema.sql       # Database schema
├── public/              # Web root
│   ├── assets/          # CSS, JS, images
│   └── index.php        # Entry point
├── storage/
│   ├── cache/           # Blade cache
│   └── uploads/avatars/ # User avatars
├── frame                # CLI tool
├── .env                 # Environment config
└── README.md            # Full documentation
```

---

## Common Tasks

### Creating a New Feature

```bash
# 1. Create migration
php frame make:migration create_products_table

# 2. Edit migration file, then run
php frame migrate

# 3. Create model
php frame make:model Product

# 4. Create controller
php frame make:controller ProductController

# 5. Create view
php frame make:view products/index

# 6. Add routes to app/routes/web.php
```

### Adding Routes

Edit the appropriate route file:

- `app/routes/web.php` - Public routes
- `app/routes/auth.php` - Authentication
- `app/routes/user.php` - User profiles
- `app/routes/admin.php` - Admin panel

Example:

```php
use app\controllers\ProductController;

$router->get('/products', [ProductController::class, 'index']);
```

### Creating Controllers

```php
<?php

namespace app\controllers;

class ProductController extends Controller
{
    public function index(): void
    {
        echo blade('products/index', [
            'title' => 'Products'
        ]);
    }
}
```

### Creating Views

Views use Blade templating:

```blade
@component('components.layout-app', ['title' => 'Products'])

<div class="row">
    <div class="col-12">
        <h2>Products</h2>

        @foreach($products as $product)
            <div class="card">
                <div class="card-body">
                    <h5>{{ $product->name }}</h5>
                    <p>{{ $product->description }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>

@endcomponent
```

---

## Helper Functions

```php
// Authentication
Session::loggedIn()              // Check if logged in
Session::user()                  // Get current user
Session::user()->isAdmin()       // Check if admin

// Flash messages
Session::setflash($msg, $type)   // Set flash (success, danger, warning, info)

// CSRF
csrf_field()                     // Output hidden CSRF input
csrf_token()                     // Get CSRF token
csrf_verify()                    // Verify CSRF token

// Navigation
redirect($url)                   // Redirect
goback()                         // Go back

// Views
blade('view.name', $data)        // Render Blade view
```

---

## Troubleshooting

### Avatars/uploads not showing
1. Verify BASE_PATH in `/app/config/config.php`:
   ```php
   define('BASE_PATH', dirname(__FILE__, 3));
   ```
2. Check file permissions: `chmod -R 755 storage/uploads`
3. Verify route: `php frame route:list | grep avatar`

### Blade cache issues
```bash
php frame cache:clear
```

### Database connection errors
- Check `.env` credentials
- Make sure database exists
- Test connection: `mysql -u root -p`

### Email not working
- Use Gmail App Password (not regular password)
- Check MAIL settings in `.env`
- Verify port 587 for TLS

### Permission issues
```bash
chmod -R 755 storage
chmod +x frame
```

### Route not found (404)
- Check route exists: `php frame route:list`
- Verify controller method exists
- Check web server configuration

---

## Security Notes

1. Never commit `.env` with real credentials
2. Use strong passwords for database and email
3. Keep dependencies updated: `composer update`
4. Use HTTPS in production
5. Set proper file permissions (755/644)

---

## Next Steps

1. Read [README.md](README.md) for comprehensive documentation
2. Read [CLI.md](CLI.md) for CLI tool documentation
3. Explore the admin panel at `/admin`
4. Check out the example controllers and views
5. Start building your application!

---

## Support

- **Documentation**: README.md
- **CLI Help**: `php frame list`
- **Issues**: Create an issue in the repository

---

**Frame PHP Framework** - Simple. Powerful. Modern.

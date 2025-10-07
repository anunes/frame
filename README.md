# Frame PHP Framework

A lightweight, modern PHP framework inspired by Laravel, built for developers who want simplicity without sacrificing power.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Directory Structure](#directory-structure)
- [Getting Started](#getting-started)
- [Routing](#routing)
- [Controllers](#controllers)
- [Models](#models)
- [Views (Blade Templates)](#views-blade-templates)
- [Navbar](#navbar)
- [Database](#database)
- [Authentication](#authentication)
- [Authorization & Middleware](#authorization--middleware)
- [CLI Tool (Frame)](#cli-tool-frame)
- [Admin Panel](#admin-panel)
- [File Uploads](#file-uploads)
- [Troubleshooting](#troubleshooting)
- [Deployment](#deployment)

---

## Features

✅ **MVC Architecture** - Clean separation of concerns
✅ **Laravel-style Routing** - Named routes, route parameters, RESTful routing
✅ **Blade Templating** - Powerful template engine with components
✅ **Database Abstraction** - PDO-based database layer
✅ **Authentication System** - Built-in user authentication with sessions
✅ **Admin Panel** - Ready-to-use admin dashboard with user management
✅ **CLI Tool** - Artisan-inspired command-line interface
✅ **Migrations** - Database version control
✅ **CSRF Protection** - Built-in security
✅ **Theme Switching** - Light/Dark mode support
✅ **Email Support** - PHPMailer integration
✅ **Form Validation** - Server-side validation helpers
✅ **Secure File Uploads** - Protected file storage with automatic optimization and secure serving

---

## Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Composer
- Node.js and npm (for frontend dependencies)
- Web server (Apache/Nginx) or PHP built-in server

---

## Installation

### 1. Clone or Download the Repository

```bash
git clone <repository-url> frame
cd frame
```

### 2. Install Dependencies

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies (Bootstrap):

```bash
npm install
```

### 3. Environment Configuration

Copy the `.env.example` file to `.env` and configure your settings:

```bash
cp .env.example .env
```

Edit `.env` with your settings:

```env
APP_NAME=Frame

# Database Configuration
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=frame
DB_USERNAME=root
DB_PASSWORD=

# Mail Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@example.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_NAME=Frame
```

### 4. Database Setup

**Option 1: Automatic Setup (Recommended)**

Run the setup script, which will automatically create the database if it doesn't exist, create all tables, and configure default settings:

```bash
php database/setup_settings.php
```

**Option 2: Manual Setup**

Create the database manually:

```sql
CREATE DATABASE frame CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then either import the schema:

```bash
mysql -u root -p frame < database/schema.sql
```

Or run migrations:

```bash
php frame migrate
```

### 5. Set Permissions

Make sure the storage directory is writable:

```bash
chmod -R 755 storage
```

### 6. Start Development Server

```bash
php frame serve
```

Visit: `http://127.0.0.1:8000`

---

## Configuration

All configuration is done through environment variables (`.env`) and loaded in `/app/config/config.php`.

### Available Configuration Options

- **APP_NAME** - Application name
- **DB\_\*** - Database connection settings
- **MAIL\_\*** - Email/SMTP settings

---

## Directory Structure

```
frame/
├── app/
│   ├── config/          # Configuration files
│   ├── console/         # CLI command classes
│   ├── controllers/     # Controller classes
│   ├── core/           # Framework core classes
│   ├── models/         # Model classes
│   ├── routes/         # Route definition files
│   │   ├── web.php     # Public routes
│   │   ├── auth.php    # Authentication routes
│   │   ├── user.php    # User profile routes
│   │   └── admin.php   # Admin routes
│   ├── views/          # Blade template files
│   └── routes.php      # Main route loader
├── database/
│   ├── migrations/     # Database migration files
│   ├── schema.sql      # Database schema
│   └── setup_settings.php
├── public/             # Public web root
│   ├── assets/         # CSS, JS, images
│   └── index.php       # Entry point
├── storage/
│   ├── cache/          # Blade template cache
│   └── uploads/        # User uploaded files
│       └── avatars/    # User avatar images
├── vendor/             # Composer dependencies
├── frame               # CLI tool
├── .env                # Environment variables
└── composer.json       # Composer configuration
```

---

## Getting Started

### Creating Your First Feature

Let's create a simple "Products" feature:

#### 1. Create a Migration

```bash
php frame make:migration create_products_table
```

Edit the migration file in `database/migrations/`:

```sql
-- Up Migration
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Down Migration
DROP TABLE IF EXISTS products;
```

Run the migration:

```bash
php frame migrate
```

#### 2. Create a Model

```bash
php frame make:model Product
```

Edit `app/models/Product.php` if needed.

#### 3. Create a Controller

```bash
php frame make:controller ProductController
```

Edit `app/controllers/ProductController.php`:

```php
<?php

namespace app\controllers;

use app\models\Product;

class ProductController extends Controller
{
    private Product $productModel;

    public function __construct()
    {
        $this->productModel = new Product();
    }

    public function index(): void
    {
        $products = $this->productModel->getAll();
        view('products/index', [
            'title' => 'Products',
            'products' => $products
        ]);
    }
}
```

#### 4. Create Views

```bash
php frame make:view products/index
```

Edit `app/views/products/index.blade.php`:

```blade
@component('components.layout-app', ['title' => $title])

<div class="row">
    <div class="col-12">
        <h2>Products</h2>

        <div class="row">
            @foreach($products as $product)
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5>{{ $product->name }}</h5>
                        <p>{{ $product->description }}</p>
                        <strong>${{ $product->price }}</strong>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endcomponent
```

#### 5. Add Routes

Edit `app/routes/web.php`:

```php
use app\controllers\ProductController;

$router->get('/products', [ProductController::class, 'index'])->name('products');
```

#### 6. Test It

Visit: `http://127.0.0.1:8000/products`

---

### Building Secured Pages

To make a new page only accessible to logged-in users or admins, apply middleware to the route when you register it:

```php
// Logged-in users only
$router->get('/orders', [OrderController::class, 'index'])
    ->middleware('auth')
    ->name('orders');

// Admin-only
$router->get('/admin/reports', [AdminController::class, 'reports'])
    ->middleware('auth', 'admin')
    ->name('admin.reports');
```

Inside the controller you can simply call `view()`—no manual auth checks needed because middleware already guards access.

See [Authorization & Middleware](#authorization--middleware) for more.

---

## Routing

Routes are defined in separate files under `app/routes/`:

### Basic Routing

```php
// GET route
$router->get('/path', [Controller::class, 'method']);

// POST route
$router->post('/path', [Controller::class, 'method']);

// Named routes
$router->get('/path', [Controller::class, 'method'])->name('route.name');
```

### Route Parameters

```php
$router->get('/user/{id}', [UserController::class, 'show']);
$router->get('/post/{id}/comment/{commentId}', [PostController::class, 'showComment']);
```

In your controller:

```php
public function show(string $id): void
{
    $userId = (int)$id;
    // ...
}
```

### Route Files

- **web.php** - Public routes
- **auth.php** - Authentication routes
- **user.php** - User profile routes
- **admin.php** - Admin panel routes

---

## Controllers

Controllers handle the application logic and are located in `app/controllers/`.

### Creating a Controller

```bash
php frame make:controller ExampleController
```

### Basic Controller Example

```php
<?php

namespace app\controllers;

use app\core\Session;

class ExampleController extends Controller
{
    public function index(): void
    {
    view('view-name', ['title' => 'Page Title']);
    }

    public function store(): void
    {
        // Validate CSRF
        if (!csrf_verify()) {
            Session::setflash('Invalid token', 'danger');
            goback();
            return;
        }

        // Process form data
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? '')
        ];

        // Save to database
        // ...

        Session::setflash('Success!', 'success');
        redirect('/path');
    }
}
```

### Helper Functions

- `blade($view, $data)` - Render a Blade template
- `redirect($path)` - Redirect to a path
- `goback()` - Redirect back to previous page
- `csrf_verify()` - Verify CSRF token
- `csrf_field()` - Generate CSRF hidden input
- `csrf_token()` - Get CSRF token value

---

## Models

Models interact with the database and are located in `app/models/`.

### Creating a Model

```bash
php frame make:model Example
```

### Basic Model Example

```php
<?php

namespace app\models;

use app\core\Database;

class Example extends Database
{
    protected string $table = 'examples';

    public function findById(int $id): ?object
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->row($sql, [$id]) ?: null;
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $result = $this->rows($sql);
        return is_array($result) ? $result : [];
    }

    public function create(array $data): ?int
    {
        $id = $this->insert($this->table, $data);
        return $id ? (int)$id : null;
    }
}
```

### Database Methods

- `$this->query($sql, $params)` - Execute a query
- `$this->row($sql, $params)` - Fetch single row
- `$this->rows($sql, $params)` - Fetch multiple rows
- `$this->insert($table, $data)` - Insert record
- `$this->update($table, $data, $where)` - Update record
- `$this->deleteById($table, $id)` - Delete record

---

## Views (Blade Templates)

Views use Laravel's Blade templating engine and are located in `app/views/`.

### Creating a View

```bash
php frame make:view folder/view-name
```

### Blade Syntax

```blade
{{-- Comments --}}

{{-- Output variables --}}
{{ $variable }}

{{-- Conditionals --}}
@if($condition)
    <p>True</p>
@else
    <p>False</p>
@endif

{{-- Loops --}}
@foreach($items as $item)
    <p>{{ $item->name }}</p>
@endforeach

{{-- Components --}}
@component('components.layout-app', ['title' => 'Page Title'])
    <p>Content here</p>
@endcomponent

{{-- PHP Code --}}
@php
    $variable = 'value';
@endphp
```

### Layout Components

Main layout: `components/layout-app.blade.php`

```blade
@component('components.layout-app', ['title' => 'Page Title'])
    <!-- Your content -->
@endcomponent
```

### Rendering Views (Updated)

Prefer the `view()` helper to render a template and output it immediately:

```php
// Old
// echo blade('products/index', ['title' => 'Products']);

// New (preferred)
view('products/index', ['title' => 'Products']);
```

Helpers:

- `view($view, $data = [])` — render and output a Blade view
- `blade($view, $data = [])` — returns a rendered string (useful for composing emails, etc.)

---

## Navbar

The navbar links are configured centrally and auto-filtered based on authentication and role.

### Configure Links

Edit `app/config/navbar.php` to add, remove, or reorder links. Example:

```php
return [
    'main' => [
        [
            'label' => 'Home',
            'url' => '/',
            'icon' => 'bi-house-door', // optional Bootstrap Icons class
            'active' => 'home',         // active key used for highlighting
        ],
        [
            'label' => 'About',
            'url' => '/about',
            'icon' => 'bi-info-circle',
            'active' => 'about',
        ],
    ],

    // Links visible when user is logged in (rendered in user dropdown)
    'user' => [
        [
            'label' => 'Profile',
            'url' => '/profile',
            'icon' => 'bi-person',
            'active' => 'profile',
        ],
        [
            'label' => 'Administration',
            'url' => '/admin',
            'admin' => true, // requires admin role
            'icon' => 'bi-gear',
            'active' => 'admin',
        ],
    ],

    // Links for guests only (when not logged in)
    'guest' => [
        [
            'label' => 'Login',
            'url' => '/login',
            'guest' => true,
            'icon' => 'bi-box-arrow-in-right',
        ],
        [
            'label' => 'Register',
            'url' => '/register',
            'guest' => true,
            'show_if' => 'registration_enabled', // optional conditional
            'icon' => 'bi-person-plus',
        ],
    ],
];
```

Supported options per link:

- `label` (string, required)
- `url` (string, required)
- `auth` (bool) show only to authenticated users
- `guest` (bool) show only to guests
- `admin` (bool) show only to admin users
- `icon` (string) Bootstrap Icons class
- `active` (string) key to match the current page for highlighting
- `show_if` (string) optional feature flag handled in helpers

The navbar partial (`app/views/layouts/partials/nav.blade.php`) renders these with helpers:

- `navbar_items($section)` returns filtered items for a section (`main`, `user`, `guest`)
- `navbar_item_active($item)` applies the `active` class when appropriate

To remove a link, delete it from `navbar.php`. No changes to the Blade partial are needed.

---

## Database

### Migrations

Create a migration:

```bash
php frame make:migration create_table_name
```

Edit the migration file:

```sql
-- Up Migration
CREATE TABLE IF NOT EXISTS table_name (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Down Migration
DROP TABLE IF EXISTS table_name;
```

Run migrations:

```bash
php frame migrate
```

Rollback last batch:

```bash
php frame migrate:rollback
```

---

## Authentication

The framework includes a complete authentication system.

### User Registration

```php
// Automatic - handled by AuthController
// Can be disabled in admin panel
```

### Login/Logout

Users can log in at `/login` and logout via POST to `/logout`.

### Checking Authentication

```php
use app\core\Session;

// Check if logged in
if (Session::loggedIn()) {
    // User is authenticated
}

// Get current user
$user = Session::user();

// Check if admin
if ($user->isAdmin()) {
    // User is admin
}
```

### Password Reset

Users can request password reset at `/forgot-password` and receive email with reset link.

### Protecting Routes

Add checks in your controllers:

```php
public function index(): void
{
    if (!Session::loggedIn()) {
        redirect('/login');
        return;
    }

    // Your code
}
```

For admin routes:

````php
public function index(): void

## Authorization & Middleware

Use middleware to protect routes without adding manual checks in controllers. Middleware aliases are configured in `app/config/middleware.php` and implemented under `app/middleware/`.

### Available Middleware
- `auth` — requires a logged-in user
- `guest` — only for non-authenticated users
- `admin` — requires authenticated admin

### Protect Single Route

```php
use app\controllers\UserController;

$router->get('/profile', [UserController::class, 'showProfile'])
    ->middleware('auth')
    ->name('profile');
````

### Multiple Middleware

```php
use app\controllers\AdminController;

$router->get('/admin', [AdminController::class, 'index'])
    ->middleware('auth', 'admin')
    ->name('admin');
```

### Route Groups

```php
// Guest-only pages
$router->group(['middleware' => ['guest']], function($router) {
    $router->get('/login', [AuthController::class, 'showLogin'])->name('login');
    $router->get('/register', [AuthController::class, 'showRegister'])->name('register');
});

// Admin section
$router->group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function($router) {
    $router->get('/', [AdminController::class, 'index'])->name('admin');
    $router->get('/users', [AdminController::class, 'getUsers'])->name('admin.users');
});
```

With middleware in place, controller methods can simply render views:

```php
public function showProfile(): void
{
    view('user/profile');
}
```

---

{
if (!Session::loggedIn() || !Session::user()->isAdmin()) {
Session::setflash('Access denied', 'danger');
redirect('/');
return;
}

    // Your code

}

````

---

## CLI Tool (Frame)

The Frame CLI tool helps you generate code and manage your application. Similar to Laravel's Artisan, Frame provides a powerful command-line interface for common development tasks.

### Available Commands

```bash
# Generate files
php frame make:controller ControllerName
php frame make:model ModelName
php frame make:view folder/view-name
php frame make:migration migration_name

# Database
php frame migrate
php frame migrate:rollback

# Routing
php frame route:list

# Development
php frame serve [port]

# Cache
php frame cache:clear

# Help
php frame list
````

### Command Details

#### Generate Commands

**make:controller** - Creates a new controller with CRUD methods

```bash
php frame make:controller ProductController
# Creates: app/controllers/ProductController.php
```

**make:model** - Creates a new model with database methods

```bash
php frame make:model Product
# Creates: app/models/Product.php
```

**make:view** - Creates a new Blade view file

```bash
php frame make:view products/index
# Creates: app/views/products/index.blade.php
```

**make:migration** - Creates a timestamped migration file

```bash
php frame make:migration create_products_table
# Creates: database/migrations/YYYYMMDD_HHMMSS_create_products_table.sql
```

#### Database Commands

**migrate** - Run pending migrations

```bash
php frame migrate
```

**migrate:rollback** - Rollback the last batch of migrations

```bash
php frame migrate:rollback
```

#### Routing Commands

**route:list** - Display all registered routes with their details

```bash
php frame route:list
```

Output shows:

- HTTP method (color-coded: GET=cyan, POST=yellow, PUT=magenta, DELETE=red)
- Route URI (with parameters like `/users/{id}`)
- Controller action
- Named route identifier (if assigned)

Example output:

```
METHOD  URI                  ACTION                      NAME
───────────────────────────────────────────────────────────────
GET     /                    MainController@home         home
GET     /profile             UserController@showProfile  profile
POST    /profile/edit        UserController@updateProfile
GET     /admin               AdminController@index       admin
```

#### Development Commands

**serve** - Start the PHP built-in development server

```bash
php frame serve        # Starts on port 8000
php frame serve 3000   # Starts on port 3000
```

**cache:clear** - Clear all application caches

```bash
php frame cache:clear
```

Clears:

- Blade template cache
- PHP OPcache (if enabled)
- Session files

#### Help Commands

**list** - Show all available commands with descriptions

```bash
php frame list
```

See [CLI.md](CLI.md) for detailed documentation.

---

## Admin Panel

Access the admin panel at `/admin` (requires admin account).

### Features

- **User Management** - Create, edit, activate/deactivate, delete users
- **Contact Settings** - Manage company contact information and logo
- **Registration Toggle** - Enable/disable new user registrations

### Creating Admin User

Register normally, then update the database:

```sql
UPDATE users SET role = 1 WHERE email = 'admin@example.com';
```

---

## File Uploads

Frame provides secure file upload handling with automatic optimization.

### Avatar Uploads

User avatars are stored securely outside the public directory in `/storage/uploads/avatars/` and served through a protected route.

**How it works:**

1. Files are uploaded to `/storage/uploads/avatars/` (not publicly accessible)
2. Files are automatically optimized (WebP format, 300x300px)
3. Files are served through `/avatars/{filename}` route with validation
4. Only image files are served (MIME type validation)
5. Files are cached for 1 year for optimal performance

**Security Features:**

- Files stored outside public directory
- MIME type validation on serving
- Path sanitization using `basename()`
- File existence and type checks
- Direct file access blocked by web server

### Adding Custom File Uploads

To add your own file upload functionality:

```php
// In your controller
$uploadDir = BASE_PATH . '/storage/uploads/your-folder/';

// Validate file
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    Session::setflash('Upload failed', 'danger');
    goback();
    return;
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $_FILES['file']['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    Session::setflash('Invalid file type', 'danger');
    goback();
    return;
}

// Generate unique filename
$filename = 'file_' . uniqid() . '_' . time() . '.webp';
$destination = $uploadDir . $filename;

// Move uploaded file
if (move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
    // Success - save filename to database
    Session::setflash('File uploaded successfully', 'success');
}
```

**Create a serving route** in `app/routes/web.php`:

```php
$router->get('/files/{filename}', [YourController::class, 'serveFile']);
```

**Add serving method** to your controller:

```php
public function serveFile(string $filename): void
{
    $filePath = BASE_PATH . '/storage/uploads/your-folder/' . basename($filename);

    if (!file_exists($filePath) || !is_file($filePath)) {
        http_response_code(404);
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=31536000');

    readfile($filePath);
    exit;
}
```

---

## Troubleshooting

### Common Issues

#### Avatars/Uploads Not Displaying

If uploaded files are not displaying:

1. **Check BASE_PATH constant** - In `/app/config/config.php`, verify:

   ```php
   define('BASE_PATH', dirname(__FILE__, 3)); // Should point to project root
   ```

2. **Verify file permissions**:

   ```bash
   chmod -R 755 storage/uploads
   ```

3. **Check route is registered**:

   ```bash
   php frame route:list | grep avatar
   ```

4. **Test file access directly**:
   ```php
   $path = BASE_PATH . '/storage/uploads/avatars/filename.webp';
   echo file_exists($path) ? 'EXISTS' : 'NOT FOUND';
   ```

#### Blade Cache Issues

If views are not updating:

```bash
php frame cache:clear
```

#### Database Connection Errors

1. Check `.env` credentials
2. Verify database exists
3. Test connection:
   ```bash
   mysql -u root -p
   ```

#### Email Not Working

1. For Gmail, use an [App Password](https://myaccount.google.com/apppasswords), not your regular password
2. Check MAIL settings in `.env`
3. Verify TLS/SSL port is correct (usually 587 for TLS)

#### Permission Issues

```bash
chmod -R 755 storage
chmod +x frame
```

#### Route Not Found (404 Errors)

1. Check route is registered: `php frame route:list`
2. Verify controller and method exist
3. Check `.htaccess` or web server configuration
4. Ensure all route files are loaded in `/app/routes.php`

---

## Deployment

### Production Checklist

1. **Environment Variables**

   - Set production database credentials
   - Set proper MAIL settings
   - Set APP_NAME

2. **Security**

   - Remove `/database/setup_settings.php` or restrict access
   - Set proper file permissions (755 for directories, 644 for files)
   - Keep `.env` secure (not accessible via web)
   - Ensure `/storage/uploads/` is not directly accessible via web server
   - User uploads are served through protected routes, not direct file access

3. **Web Server Configuration**

#### Apache (.htaccess)

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

#### Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

4. **Optimize**

   - Enable OPcache
   - Use production database
   - Clear cache regularly

5. **Database**
   - Run migrations on production server
   - Backup database regularly

---

## Contributing

Contributions are welcome! Please feel free to submit pull requests.

---

## License

This framework is open-source software.

---

## Support

For issues and questions, please create an issue in the repository.

---

**Frame PHP Framework** - Simple. Powerful. Modern.

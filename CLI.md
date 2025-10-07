# Frame CLI Tool

A powerful command-line interface for the Frame PHP framework, inspired by Laravel Artisan.

## Installation

The Frame CLI tool is already installed. Make sure the `frame` file in the root directory is executable:

```bash
chmod +x frame
```

## Usage

```bash
php frame <command> [arguments]
```

Or if you've set up the executable properly:

```bash
./frame <command> [arguments]
```

## Available Commands

### Generate Commands

#### Create a Controller
```bash
php frame make:controller ControllerName
```
Creates a new controller with standard CRUD methods.

**Example:**
```bash
php frame make:controller ProductController
```

#### Create a Model
```bash
php frame make:model ModelName
```
Creates a new model with basic CRUD operations.

**Example:**
```bash
php frame make:model Product
```

#### Create a View
```bash
php frame make:view folder/view-name
```
Creates a new Blade view file with basic structure.

**Examples:**
```bash
php frame make:view products/index
php frame make:view admin/dashboard
```

#### Create a Migration
```bash
php frame make:migration migration_name
```
Creates a new database migration file with timestamp prefix.

**Example:**
```bash
php frame make:migration create_products_table
```

### Database Commands

#### Run Migrations
```bash
php frame migrate
```
Executes all pending database migrations. The command automatically tracks which migrations have been run and only executes new ones.

**How it works:**
- Creates a `migrations` table if it doesn't exist
- Reads all `.sql` files from `database/migrations/`
- Executes migrations in chronological order (sorted by timestamp)
- Records each migration in the database

#### Rollback Migrations
```bash
php frame migrate:rollback
```
Rolls back the last batch of migrations by executing the "Down Migration" section of each migration file.

**Note:** Make sure to write rollback statements in your migration files after the `-- Down Migration` comment.

### Cache Commands

#### Clear Cache
```bash
php frame cache:clear
```
Clears the application cache including:
- Blade template cache
- Old session files (older than 1 hour)
- OPcache (if enabled)

### Development Commands

#### Start Development Server
```bash
php frame serve [port]
```
Starts PHP's built-in development server.

**Examples:**
```bash
php frame serve          # Starts on port 8000 (default)
php frame serve 8080     # Starts on port 8080
```

The server will be accessible at `http://127.0.0.1:8000` (or your specified port).

Press `Ctrl+C` to stop the server.

#### List All Routes
```bash
php frame route:list
```
Displays a formatted table of all registered routes in your application.

**Output includes:**
- HTTP Method (color-coded: GET=cyan, POST=yellow, PUT=magenta, DELETE=red)
- Route URI/path (with parameters like `/users/{id}`)
- Controller and method (e.g., `UserController@index`)
- Route name (if defined with `->name('route.name')`)
- Total route count

**Example output:**
```
METHOD  URI                  ACTION                      NAME
───────────────────────────────────────────────────────────────
GET     /                    MainController@guest        main/guest
GET     /profile             UserController@showProfile  profile
POST    /profile/edit        UserController@updateProfile
GET     /admin               AdminController@index       admin
GET     /avatars/{filename}  UserController@serveAvatar  avatar
```

This is useful for:
- Debugging routing issues
- Understanding your application's URL structure
- Verifying route names for use in `redirect()` or `route()` helpers
- Checking which routes have protection or middleware

### Other Commands

#### List All Commands
```bash
php frame list
```
Displays a formatted list of all available commands with descriptions and examples.

## Migration Files

Migration files are stored in `database/migrations/` and use the following naming convention:
```
YYYY_MM_DD_HHMMSS_migration_name.sql
```

### Migration File Structure

```sql
-- Migration: migration_name
-- Created: timestamp

-- Up Migration
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Down Migration
DROP TABLE IF EXISTS products;
```

The "Up Migration" section is executed when running `php frame migrate`.
The "Down Migration" section is executed when running `php frame migrate:rollback`.

## Tips

1. **Controllers**: Controller names should end with "Controller" (automatically added if omitted)
2. **Models**: Model names should be singular (e.g., Product, not Products)
3. **Views**: View paths are relative to `app/views/` and automatically get `.blade.php` extension
4. **Migrations**: Use descriptive names like `create_products_table` or `add_status_to_users`

## Examples Workflow

### Creating a New Feature

```bash
# 1. Create database migration
php frame make:migration create_products_table

# 2. Edit the migration file in database/migrations/
# Add your SQL statements

# 3. Run the migration
php frame migrate

# 4. Create model
php frame make:model Product

# 5. Create controller
php frame make:controller ProductController

# 6. Create views
php frame make:view products/index
php frame make:view products/create
php frame make:view products/edit

# 7. Add routes in app/routes/web.php

# 8. Verify routes are registered
php frame route:list

# 9. Start development server to test
php frame serve
```

## Troubleshooting

### Permission Issues
If you get permission errors:
```bash
chmod +x frame
```

### Database Connection Errors
Make sure your `.env` file is properly configured with database credentials.

### Migration Already Exists
If a migration file already exists with the same name, you'll get an error. Use a different name or manually edit the existing migration.

## Color Codes

The CLI uses ANSI color codes for better readability:
- 🟢 **Green**: Success messages
- 🔵 **Blue**: Info/File paths
- 🟡 **Yellow**: Warnings
- 🔴 **Red**: Errors

---

**Frame CLI Tool v1.0.0**

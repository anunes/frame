# `php frame` Instructions

This file explains how to use the Frame CLI tool.

## What It Is

`frame` is the project's command-line entrypoint. You run it with PHP:

```bash
php frame <command> [arguments]
```

Run commands from the project root, where the `frame` file exists.

## Quick Start

Common examples:

```bash
php frame make:controller ProductController
php frame make:model Product
php frame make:view admin/products
php frame make:module employees --start --nav
php frame make:migration create_products_table
php frame migrate
php frame route:list
php frame module:start employees
php frame module:start --clear
php frame module:delete employees --force
php frame serve 8080
```

## Initial Admin Account

After importing `core/database/schema.sql` or `core/database/schema_sqlite.sql`, Frame creates a default administrator account:

- Name: `Admin`
- Email: `admin@admin.com`
- Password: `admin`

Change this password immediately after your first login.

## Command List

To list all supported commands:

```bash
php frame list
php frame -help
```

Available commands:

- `make:controller`
- `make:model`
- `make:view`
- `make:module`
- `make:migration`
- `migrate`
- `migrate:rollback`
- `cache:clear`
- `serve`
- `route:list`
- `module:start`
- `module:delete`
- `list`
- `help`, `-help`, `--help`, `-h`

## How To Use Each Command

### Create a controller

Command:

```bash
php frame make:controller ProductController
```

What it does:

- Creates a controller file in `core/app/controllers/`
- If you omit the `Controller` suffix, Frame adds it automatically

Example output file:

- `core/app/controllers/ProductController.php`

Use this when you want to add request-handling logic for a page or resource.

### Create a model

Command:

```bash
php frame make:model Product
```

What it does:

- Creates a model file in `core/app/models/`
- Generates basic CRUD-style helper methods
- Sets the default table name to the lowercase model name plus `s`

Example output file:

- `core/app/models/Product.php`

Generated default table:

- `products`

Use this when you want a database-backed model class.

### Create a view

Command:

```bash
php frame make:view admin/products
```

What it does:

- Creates a view file in `core/app/views/`
- Creates missing folders automatically
- Generates a starter `.view.php` template

Example output file:

- `core/app/views/admin/products.view.php`

Use slash-based names for nested folders.

### Create a content module

Command:

```bash
php frame make:module employees
```

What it does:

- Creates `files/employees/controllers/`
- Creates `files/employees/models/`
- Creates `files/employees/views/`
- Creates `files/employees/routes/`
- Adds starter controller, model, view, and route files

Example output files:

- `files/employees/controllers/EmployeesController.php`
- `files/employees/models/Employees.php`
- `files/employees/views/index.view.php`
- `files/employees/routes/web.php`

Use `--start` to make the module handle the home page:

```bash
php frame make:module employees --start
```

Use `--nav` to add the module to the main navbar between Home and About:

```bash
php frame make:module employees --nav
```

Customize the generated navbar label and Bootstrap icon:

```bash
php frame make:module employees --nav --nav-label="Team" --nav-icon=bi-people
```

Generated routes:

- `/employees`
- `/` when `--start` is used

Generated views use module notation:

```php
view('employees::index');
```

Module controller classes use the `files\<module>\controllers` namespace, and model classes use the `files\<module>\models` namespace.

### Choose the starting module

Command:

```bash
php frame module:start employees
```

What it does:

- Updates `APP_START_MODULE` in `.env`
- Lets the selected module handle `/`
- Leaves the module's own route, such as `/employees`, available

Use this when you want to change the first page users see without recreating the module.

To return to the original Frame start page:

```bash
php frame module:start --clear
```

You can also use `clear`, `default`, or `app` in place of `--clear`.

Module loading can also be configured manually in `.env`:

```dotenv
APP_MODULES=
APP_START_MODULE=employees
```

Leave `APP_MODULES` empty to auto-load all valid folders in `files/`. Use a comma-separated list, such as `employees,products`, to load only selected modules. Leave `APP_START_MODULE` empty to use the original Frame start page.

### Delete a content module

Command:

```bash
php frame module:delete employees --force
```

What it does:

- Removes the full `files/employees/` folder
- Clears `APP_START_MODULE` when it points to the deleted module
- Removes the module from `APP_MODULES` when it is listed there
- Removes the module navbar entry when one exists
- Clears compiled view cache

Important:

- `--force` is required because this permanently deletes module files
- Database tables, migrations, and uploaded files outside `files/employees/` are not deleted automatically

### Create a migration

Command:

```bash
php frame make:migration create_products_table
```

What it does:

- Creates a timestamped SQL migration file in `core/database/migrations/`
- Adds starter comments for up and down migration logic

Example output file:

- `core/database/migrations/2026_04_22_120000_create_products_table.sql`

After creating the migration:

1. Open the generated SQL file.
2. Add the SQL statements for the schema change.
3. Run `php frame migrate`.

### Run migrations

Command:

```bash
php frame migrate
```

What it does:

- Creates the `migrations` tracking table if needed
- Runs all pending `.sql` migration files in timestamp order
- Records each migration after success

Use this after creating or editing migration files.

### Roll back the last migration batch

Command:

```bash
php frame migrate:rollback
```

What it does:

- Finds the last migration batch
- Reads the rollback section from each migration file
- Executes rollback SQL
- Removes rolled-back migrations from the tracking table

Important:

- Rollback only works if the migration file contains valid down-migration SQL
- If no rollback SQL is present, the migration record may still be removed

### Clear cache

Command:

```bash
php frame cache:clear
```

What it does:

- Clears compiled view cache from `core/storage/cache/views`
- Clears old session files when possible
- Resets OPcache if enabled

Use this after changing templates or when troubleshooting cached output.

### List routes

Command:

```bash
php frame route:list
```

What it does:

- Loads the app routes
- Prints each route's method, URI, action, and route name

Use this when checking route registration or debugging route names.

### Start the development server

Command:

```bash
php frame serve
```

Default behavior:

- Starts the built-in PHP server on `127.0.0.1:8000`
- Uses `public/` as the document root

Custom port example:

```bash
php frame serve 8080
```

Then open:

```text
http://127.0.0.1:8080
```

Use this for local development.

## Typical Workflow

If you are building a regular app feature in `core/app/`, a normal sequence is:

1. Create a controller:

```bash
php frame make:controller ProductController
```

2. Create a model:

```bash
php frame make:model Product
```

3. Create a view:

```bash
php frame make:view admin/products
```

4. Create a migration:

```bash
php frame make:migration create_products_table
```

5. Edit the migration SQL file.

6. Run the migration:

```bash
php frame migrate
```

7. Check routes if needed:

```bash
php frame route:list
```

8. Start the local server:

```bash
php frame serve 8080
```

If you are building user content, start with a module:

```bash
php frame make:module employees --start --nav
php frame route:list
php frame serve 8080
```

Then edit the generated files in `files/employees/`.

To remove that content module later:

```bash
php frame module:delete employees --force
```

## Notes

- Run all commands from the project root.
- The CLI depends on your `.env` and app configuration being valid.
- Migration SQL must match your configured database type.
- `serve` is intended for local development, not production hosting.

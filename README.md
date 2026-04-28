# Frame - Production Build

This is a production-ready build of the Frame application.

## Requirements

- PHP 7.4 or higher
- PHP Extensions: PDO, Intl
- Composer (for PHP dependency management)
- Node.js and npm (for frontend assets)
- Web server (Apache/Nginx)
- MySQL or SQLite database

## Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/anunes/frame.git
   cd frame
   ```

2. Install PHP dependencies:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. Install Node.js dependencies:

   ```bash
   npm install
   ```

4. Configure your web server to point to the `public` directory as the document root

5. Set up environment configuration:

   ```bash
   cp .env.example .env
   ```

6. Edit `.env` file with your database credentials and application settings

7. Set proper permissions:

   ```bash
   chmod -R 755 core/storage
   chmod -R 755 core/database
   ```

8. Run database setup:

   - For MySQL: Import `core/database/schema.sql`
   - For SQLite: Import `core/database/schema_sqlite.sql`

   Both schema files create an initial administrator account:

   - Name: `Admin`
   - Email: `admin@admin.com`
   - Password: `admin`

   Change this password immediately after your first login.

9. Configure database settings using:
   ```bash
   php core/database/setup_settings.php
   ```

## Web Server Configuration

### Apache

Ensure you have `.htaccess` enabled with `mod_rewrite`:

```apache
<VirtualHost *:80>
    DocumentRoot /path/to/frame/public
    <Directory /path/to/frame/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/frame/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Directory Structure

```
build/
├── core/               # Framework internals
│   ├── app/            # Application code
│   ├── database/       # Database schemas and migrations
│   └── storage/        # Writable storage (cache, uploads, logs)
├── files/              # User content modules
├── public/             # Web accessible files (document root)
├── .env.example        # Environment configuration template
├── composer.json       # PHP dependencies manifest
├── composer.lock       # PHP dependencies lock file
├── package.json        # Node.js dependencies manifest
├── package-lock.json   # Node.js dependencies lock file
└── frame              # CLI console tool
```

**Note:** The `vendor/` and `node_modules/` directories are not included. Run `composer install` and `npm install` to download dependencies.

## User Content Modules

Frame can scaffold self-contained content modules inside `files/`. This keeps user-created features separate from the framework code in `core/app/`.

Create a module:

```bash
php frame make:module employees
```

This creates:

```text
files/employees/
├── controllers/
├── models/
├── views/
└── routes/
```

It also creates starter controller, model, view, and route files. The generated route is available at `/employees`, and module views use namespace notation:

```php
view('employees::index');
```

To make a module handle the home page, create it with `--start`:

```bash
php frame make:module employees --start
```

To add the module to the main navbar between Home and About:

```bash
php frame make:module employees --nav
```

You can also customize the generated navbar label and Bootstrap icon:

```bash
php frame make:module employees --nav --nav-label="Team" --nav-icon=bi-people
```

Or choose the starting module later:

```bash
php frame module:start employees
```

Return to the original Frame start page:

```bash
php frame module:start --clear
```

Delete a module completely from `files/`:

```bash
php frame module:delete employees --force
```

This removes the full `files/employees/` folder, clears compiled view cache, removes its module navbar entry, and updates `.env` if the module was listed in `APP_MODULES` or selected in `APP_START_MODULE`. It does not delete database tables or uploaded files outside the module folder.

Module loading can be configured in `.env`:

```dotenv
APP_MODULES=
APP_START_MODULE=employees
```

Leave `APP_MODULES` empty to auto-load every valid module folder in `files/`. Set it to a comma-separated list, such as `employees,products`, to load only specific modules. Leave `APP_START_MODULE` empty to use the original Frame start page.

## Support

For issues and documentation, please refer to the main project repository.

## CLI Instructions

For step-by-step `php frame` usage, see [FRAME_CLI.md](FRAME_CLI.md).

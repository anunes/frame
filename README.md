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
   chmod -R 755 storage
   chmod -R 755 database
   ```

8. Run database setup:

   - For MySQL: Import `database/schema.sql`
   - For SQLite: Import `database/schema_sqlite.sql`

9. Configure database settings using:
   ```bash
   php database/setup_settings.php
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
├── app/                 # Application code
├── database/           # Database schemas and migrations
├── public/             # Web accessible files (document root)
├── storage/            # Writable storage (cache, uploads, logs)
├── .env.example        # Environment configuration template
├── composer.json       # PHP dependencies manifest
├── composer.lock       # PHP dependencies lock file
├── package.json        # Node.js dependencies manifest
├── package-lock.json   # Node.js dependencies lock file
└── frame              # CLI console tool
```

**Note:** The `vendor/` and `node_modules/` directories are not included. Run `composer install` and `npm install` to download dependencies.

## Support

For issues and documentation, please refer to the main project repository.

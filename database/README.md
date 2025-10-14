# Database Setup Guide

This directory contains database schema files and setup scripts for both MySQL and SQLite databases.

---

## Quick Start

### For MySQL:

```bash
# 1. Edit .env file and set DB_TYPE=mysql
# 2. Configure MySQL connection details (host, port, username, password)
# 3. Run setup script
php database/setup_settings.php
```

**The setup script will automatically:**
- Comment out SQLite configuration in .env
- Uncomment MySQL configuration in .env
- Create the MySQL database if it doesn't exist
- Create all tables from schema.sql
- Insert default settings

### For SQLite:

```bash
# 1. Edit .env file and set DB_TYPE=sqlite
# 2. Set DB_DATABASE to your desired database filename (e.g., database.db)
# 3. Run setup script
php database/setup_settings.php
```

**The setup script will automatically:**
- Comment out MySQL-specific configuration in .env (DB_HOST, DB_PORT, etc.)
- Uncomment SQLite configuration in .env
- Create the storage/database directory if needed
- Create the SQLite database file
- Create all tables from schema_sqlite.sql
- Insert default settings

---

## Files Overview

### Setup Scripts

- **`setup_settings.php`** - Main database setup script
  - **Automatically manages .env configuration** (comments/uncomments database sections)
  - Automatically detects database type (MySQL or SQLite)
  - Creates database/file if it doesn't exist
  - Creates all tables from schema
  - Inserts default settings data
  - Provides verification and feedback
  - Beautiful console output with colors and status icons

### Schema Files

- **`schema.sql`** - MySQL/MariaDB database schema
  - Uses MySQL-specific syntax
  - AUTO_INCREMENT for primary keys
  - ENGINE=InnoDB
  - TIMESTAMP with auto-update

- **`schema_sqlite.sql`** - SQLite database schema
  - Uses SQLite-specific syntax
  - INTEGER PRIMARY KEY AUTOINCREMENT
  - DATETIME instead of TIMESTAMP
  - No ENGINE or CHARSET specifications

---

## Automatic .env Configuration Management

### How It Works

The `setup_settings.php` script automatically manages your `.env` file configuration:

1. **Detects** the selected database type from your `DB_TYPE` setting
2. **Comments out** configuration for the inactive database type
3. **Uncomments** configuration for the active database type
4. **Preserves** your custom values and other settings

### Example: Switching from MySQL to SQLite

**Your .env before running setup:**
```env
# --- MySQL Configuration ---
DB_TYPE=mysql
DB_HOST=127.0.0.1
DB_DATABASE=myapp
# ...

# --- SQLite Configuration ---
# DB_TYPE=sqlite
# DB_DATABASE=database.db
```

**You manually change DB_TYPE:**
```env
# --- MySQL Configuration ---
# DB_TYPE=mysql      ← Changed manually
DB_HOST=127.0.0.1
DB_DATABASE=myapp
# ...

# --- SQLite Configuration ---
DB_TYPE=sqlite       ← Changed manually
# DB_DATABASE=database.db
```

**After running `php database/setup_settings.php`:**
```env
# --- MySQL Configuration ---
# DB_TYPE=mysql
# DB_HOST=127.0.0.1      ← Auto-commented
# DB_DATABASE=myapp      ← Auto-commented
# ...

# --- SQLite Configuration ---
DB_TYPE=sqlite
DB_DATABASE=database.db  ← Auto-uncommented
```

**Benefits:**
- ✓ No need to manually comment/uncomment configuration lines
- ✓ Prevents configuration conflicts between database types
- ✓ Clean, organized .env file
- ✓ Reduces setup errors

---

## Configuration

### MySQL Configuration

In `.env` file:

```env
# --- MySQL Configuration ---
DB_TYPE=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_CHAR=utf8mb4
```

### SQLite Configuration

In `.env` file:

```env
# --- SQLite Configuration ---
DB_TYPE=sqlite
DB_DATABASE=database.db  # Will be created in storage/database/
# Note: SQLite doesn't use HOST, PORT, USERNAME, PASSWORD, or CHAR
```

---

## Running the Setup Script

### First Time Setup

```bash
php database/setup_settings.php
```

**Output Example (MySQL):**
```
==================================================
  DATABASE SETUP SCRIPT
==================================================
→ Database Type: MYSQL
→ Database Name: my_database

→ Setting up MySQL database...
✓ Connected to MySQL server
→ Creating database 'my_database' if it doesn't exist...
✓ Database 'my_database' ready
✓ Using database 'my_database'

==================================================
  CREATING DATABASE SCHEMA
==================================================
→ Loading schema from: schema.sql
✓ Executed 4 SQL statements
✓ All tables created successfully

==================================================
  INSERTING DEFAULT DATA
==================================================
→ Inserting default contact info...
✓ Default contact info inserted
→ Inserting default copyright text...
✓ Default copyright text inserted
→ Inserting default registration setting...
✓ Default registration setting inserted
→ Inserting default site logo setting...
✓ Default site logo setting inserted

==================================================
  VERIFYING SETUP
==================================================
✓ Found 4 tables in database
✓ Found 4 settings in database
→ Tables created:
  - migrations
  - users
  - password_resets
  - settings

==================================================
  SETUP COMPLETE
==================================================

✓ Database setup completed successfully!

→ MySQL database: my_database
→ MySQL host: localhost

→ Next steps:
  1. Update contact information in admin panel
  2. Create your first admin user
  3. Configure email settings in .env file
```

**Output Example (SQLite):**
```
==================================================
  DATABASE SETUP SCRIPT
==================================================
→ Database Type: SQLITE
→ Database Name: database.db

→ Setting up SQLite database...
→ Creating database directory: /path/to/storage/database
✓ Database directory created
✓ Connected to SQLite database: /path/to/storage/database/database.db

==================================================
  CREATING DATABASE SCHEMA
==================================================
→ Loading schema from: schema_sqlite.sql
✓ Executed 8 SQL statements
✓ All tables created successfully

==================================================
  INSERTING DEFAULT DATA
==================================================
→ Contact info already exists - skipping
→ Copyright text already exists - skipping
→ Registration setting already exists - skipping
→ Site logo setting already exists - skipping

==================================================
  VERIFYING SETUP
==================================================
✓ Found 4 tables in database
✓ Found 4 settings in database
→ Tables created:
  - migrations
  - password_resets
  - settings
  - users

==================================================
  SETUP COMPLETE
==================================================

✓ Database setup completed successfully!

→ SQLite database location: /path/to/storage/database/database.db
→ Database size: 16,384 bytes

→ Next steps:
  1. Update contact information in admin panel
  2. Create your first admin user
  3. Configure email settings in .env file
```

### Re-running the Setup Script

#### MySQL
The script will:
- Not recreate the database if it exists
- Skip table creation if tables exist (using IF NOT EXISTS)
- Skip inserting default data if it already exists

#### SQLite
The script will:
- Ask if you want to recreate the database file
- If you answer "yes", **ALL DATA WILL BE DELETED**
- If you answer "no", it will use the existing database
- Skip inserting default data if it already exists (using INSERT OR IGNORE)

---

## Database Tables

### 1. `migrations`
Tracks database migrations for version control.

**MySQL:**
```sql
CREATE TABLE migrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  migration VARCHAR(255) NOT NULL,
  batch INT NOT NULL,
  migrated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**SQLite:**
```sql
CREATE TABLE migrations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  migration VARCHAR(255) NOT NULL,
  batch INTEGER NOT NULL,
  migrated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### 2. `users`
Stores user accounts and authentication information.

**Columns:**
- `id` - Primary key
- `name` - User's full name
- `email` - Unique email address (used for login)
- `password` - Hashed password (using PASSWORD_DEFAULT)
- `role` - 0=user, 1=admin
- `avatar` - Profile picture filename
- `active` - 0=inactive, 1=active
- `must_change_password` - Force password change on next login
- `created_at` - Account creation timestamp
- `updated_at` - Last update timestamp

### 3. `password_resets`
Stores password reset tokens for forgot password functionality.

**Columns:**
- `id` - Primary key
- `email` - User's email address
- `token` - Hashed reset token
- `created_at` - Token creation timestamp

**Note:** SQLite version includes an index on `token` for faster lookups.

### 4. `settings`
Stores application-wide settings as key-value pairs.

**Columns:**
- `id` - Primary key
- `setting_key` - Unique setting identifier
- `setting_value` - Setting value (JSON for complex data)
- `created_at` - Setting creation timestamp
- `updated_at` - Last update timestamp

**Default Settings:**
- `contact_info` - Company contact information (JSON)
- `copyright_text` - Footer copyright text
- `registration_enabled` - Allow/prevent new registrations (1/0)
- `site_logo` - Path to site logo image

---

## Switching Between MySQL and SQLite

### From MySQL to SQLite

1. **Export your MySQL data:**
   ```bash
   mysqldump -u username -p database_name > backup.sql
   ```

2. **Update configuration:**
   ```php
   define('DB_TYPE', 'sqlite');
   define('DB_NAME', 'database.db');
   ```

3. **Run setup script:**
   ```bash
   php database/setup_settings.php
   ```

4. **Import data** (you'll need to manually convert and import)

### From SQLite to MySQL

1. **Export SQLite data:**
   ```bash
   sqlite3 database.db .dump > backup.sql
   ```

2. **Update configuration:**
   ```php
   define('DB_TYPE', 'mysql');
   define('DB_NAME', 'my_database');
   define('DB_USER', 'username');
   define('DB_PASS', 'password');
   ```

3. **Run setup script:**
   ```bash
   php database/setup_settings.php
   ```

4. **Import converted data**

---

## Troubleshooting

### MySQL Issues

**Problem: "Access denied for user"**
```bash
# Solution: Check credentials
mysql -u username -p
# Verify user has permissions:
GRANT ALL PRIVILEGES ON database_name.* TO 'username'@'localhost';
FLUSH PRIVILEGES;
```

**Problem: "Can't connect to MySQL server"**
- Verify MySQL is running: `systemctl status mysql` (Linux) or `brew services list` (Mac)
- Check host and port in configuration
- Check firewall settings

**Problem: "Unknown database"**
- The script will create it automatically
- Or manually: `CREATE DATABASE database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`

### SQLite Issues

**Problem: "Unable to open database file"**
```bash
# Solution: Check directory permissions
chmod 755 storage/database
chmod 644 storage/database/*.db
```

**Problem: "Database is locked"**
- SQLite doesn't support concurrent writes
- Close any other applications accessing the database
- Consider switching to MySQL for high-traffic applications

**Problem: "No such table"**
- Run the setup script: `php database/setup_settings.php`
- Check that schema_sqlite.sql exists

### General Issues

**Problem: "Class 'Dotenv\Dotenv' not found"**
```bash
# Solution: Install Composer dependencies
composer install
```

**Problem: "Cannot find config.php"**
- Ensure you're running from project root
- Check that `/app/config/config.php` exists

---

## Advanced Usage

### Custom Schema Modifications

If you need to modify the schema:

1. **Edit the appropriate schema file:**
   - `schema.sql` for MySQL
   - `schema_sqlite.sql` for SQLite

2. **Test on a development database first**

3. **Create a migration for existing databases** (see Migrations section)

### Manual Database Creation

#### MySQL:
```bash
mysql -u username -p
CREATE DATABASE my_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE my_database;
SOURCE database/schema.sql;
```

#### SQLite:
```bash
sqlite3 storage/database/database.db < database/schema_sqlite.sql
```

---

## Best Practices

### Development
- ✅ Use SQLite for quick local development
- ✅ Keep database in `storage/database/` directory
- ✅ Add `storage/database/*.db` to `.gitignore`

### Production
- ✅ Use MySQL for production applications
- ✅ Regular automated backups
- ✅ Use environment variables for credentials
- ✅ Enable SSL connections when possible

### Security
- ✅ Never commit database files to version control
- ✅ Use strong passwords for MySQL users
- ✅ Restrict database user permissions
- ✅ Keep SQLite databases outside web root
- ✅ Regular security updates

---

## Database Maintenance

### Backup MySQL Database
```bash
# Full backup
mysqldump -u username -p database_name > backup.sql

# With compression
mysqldump -u username -p database_name | gzip > backup.sql.gz
```

### Backup SQLite Database
```bash
# Simple copy (when database is not in use)
cp storage/database/database.db backup.db

# Using SQLite backup command
sqlite3 storage/database/database.db ".backup backup.db"
```

### Restore Database

**MySQL:**
```bash
mysql -u username -p database_name < backup.sql
```

**SQLite:**
```bash
cp backup.db storage/database/database.db
```

---

## Getting Help

If you encounter issues:

1. Check this README for troubleshooting steps
2. Review the [DATABASE_CONFIGURATION.md](../DATABASE_CONFIGURATION.md) guide
3. Check application logs in `/storage/logs/`
4. Enable debug mode in `/app/config/config.php`

---

## Summary

The database setup system provides:
- ✅ Automated database creation for MySQL and SQLite
- ✅ Clear, colorful console output
- ✅ Intelligent error handling
- ✅ Idempotent operations (safe to re-run)
- ✅ Comprehensive verification
- ✅ Default data seeding

Choose the database type that best fits your needs and run the setup script to get started!

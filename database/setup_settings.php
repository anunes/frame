<?php

/**
 * Database Setup Script
 *
 * This script creates and initializes the database with all required tables and default data.
 * Supports both MySQL and SQLite database types.
 *
 * Usage:
 *   php database/setup_settings.php
 *
 * What it does:
 *   1. Detects and configures system timezone automatically (if not already set)
 *   2. Updates .env file to activate the selected database configuration
 *   3. Creates the database (MySQL) or database file (SQLite)
 *   4. Creates all tables from schema
 *   5. Inserts default settings data
 *   6. Sets up migrations tracking
 *   7. Verifies the setup was successful
 *
 * Timezone Detection:
 *   - Automatically detects system timezone on first run
 *   - Only updates if APP_TIMEZONE is set to UTC (default)
 *   - Preserves manually configured timezones
 *   - Falls back to UTC if detection fails
 *
 * Configuration:
 *   Database type is determined by DB_TYPE constant in .env file
 *   Timezone is determined by APP_TIMEZONE constant in .env file
 *   The script will automatically comment/uncomment the appropriate database
 *   configuration section in .env based on the selected DB_TYPE
 */

// ============================================
// BOOTSTRAP
// ============================================

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';

// Load environment variables from .env file
try {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
    $dotenv->safeLoad();
} catch (Exception $e) {
    // Continue without .env
}

// Load application configuration
require_once dirname(__FILE__, 2) . '/app/config/config.php';

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Print success message with checkmark
 */
function success($message) {
    echo "✓ " . $message . "\n";
}

/**
 * Print info message
 */
function info($message) {
    echo "→ " . $message . "\n";
}

/**
 * Print error message and exit
 */
function error($message, $exception = null) {
    echo "\n✗ ERROR: " . $message . "\n";
    if ($exception) {
        echo "  Details: " . $exception->getMessage() . "\n";
    }
    exit(1);
}

/**
 * Print section header
 */
function section($title) {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "  " . strtoupper($title) . "\n";
    echo str_repeat("=", 50) . "\n";
}

/**
 * Update .env file to comment/uncomment database configuration based on selected DB_TYPE
 *
 * This function reads the .env file, determines which database type is active,
 * and automatically comments out the configuration for the unused database type.
 *
 * @param string $dbType The selected database type ('mysql', 'sqlite', etc.)
 * @return bool True on success, false on failure
 */
function updateEnvFile($dbType) {
    $envPath = dirname(__FILE__, 2) . '/.env';

    // Check if .env file exists
    if (!file_exists($envPath)) {
        info(".env file not found - skipping configuration update");
        return false;
    }

    // Read the .env file
    $envContent = file_get_contents($envPath);
    if ($envContent === false) {
        info("Unable to read .env file - skipping configuration update");
        return false;
    }

    $lines = explode("\n", $envContent);
    $updatedLines = [];
    $inMysqlSection = false;
    $inSqliteSection = false;
    $modified = false;

    foreach ($lines as $line) {
        $trimmedLine = trim($line);

        // Detect section headers
        if (strpos($trimmedLine, '--- MySQL Configuration') !== false) {
            $inMysqlSection = true;
            $inSqliteSection = false;
            $updatedLines[] = $line;
            continue;
        }

        if (strpos($trimmedLine, '--- SQLite Configuration') !== false) {
            $inSqliteSection = true;
            $inMysqlSection = false;
            $updatedLines[] = $line;
            continue;
        }

        // Reset section flags on empty lines or new sections
        if (empty($trimmedLine) || strpos($trimmedLine, '===') !== false) {
            $inMysqlSection = false;
            $inSqliteSection = false;
            $updatedLines[] = $line;
            continue;
        }

        // Skip comment-only lines
        if (strpos($trimmedLine, '#') === 0) {
            $updatedLines[] = $line;
            continue;
        }

        // Process MySQL configuration lines
        if ($inMysqlSection && preg_match('/^(#\s*)?(DB_TYPE|DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD|DB_CHAR)=/', $line)) {
            if ($dbType === 'mysql') {
                // Uncomment MySQL lines
                if (strpos($trimmedLine, '#') === 0) {
                    $line = preg_replace('/^#\s*/', '', $line);
                    $modified = true;
                }
            } else {
                // Comment all MySQL-specific lines when not using MySQL
                if (strpos($trimmedLine, '#') !== 0) {
                    $line = '# ' . $line;
                    $modified = true;
                }
            }
        }

        // Process SQLite configuration lines
        if ($inSqliteSection && preg_match('/^(#\s*)?(DB_TYPE|DB_DATABASE)=/', $line)) {
            if ($dbType === 'sqlite') {
                // Uncomment SQLite lines
                if (strpos($trimmedLine, '#') === 0) {
                    $line = preg_replace('/^#\s*/', '', $line);
                    $modified = true;
                }
            } else {
                // Comment SQLite lines when not using SQLite
                if (strpos($trimmedLine, '#') !== 0) {
                    $line = '# ' . $line;
                    $modified = true;
                }
            }
        }

        $updatedLines[] = $line;
    }

    // Write updated content back to .env file if changes were made
    if ($modified) {
        $updatedContent = implode("\n", $updatedLines);
        if (file_put_contents($envPath, $updatedContent) !== false) {
            success("Updated .env file - " . strtoupper($dbType) . " configuration activated");
            return true;
        } else {
            info("Unable to write .env file - configuration not updated");
            return false;
        }
    } else {
        info(".env file already configured for " . strtoupper($dbType));
        return true;
    }
}

/**
 * Detect user's timezone based on system settings
 *
 * Attempts to detect the timezone using various methods:
 * 1. PHP's date_default_timezone_get() (reads from php.ini or system)
 * 2. System timezone files on Linux/Mac
 * 3. Falls back to UTC if detection fails
 *
 * @return string The detected timezone identifier
 */
function detectTimezone(): string {
    // Try PHP's built-in detection
    $timezone = @date_default_timezone_get();

    // Validate the timezone
    if ($timezone && $timezone !== 'UTC' && in_array($timezone, timezone_identifiers_list())) {
        return $timezone;
    }

    // Try to read from system timezone file (Linux/Mac)
    if (file_exists('/etc/timezone')) {
        $timezone = trim(file_get_contents('/etc/timezone'));
        if ($timezone && in_array($timezone, timezone_identifiers_list())) {
            return $timezone;
        }
    }

    // Try symlink method (common on modern Linux/Mac)
    if (is_link('/etc/localtime')) {
        $linkTarget = readlink('/etc/localtime');
        if (preg_match('|/([^/]+/[^/]+)$|', $linkTarget, $matches)) {
            $timezone = $matches[1];
            if (in_array($timezone, timezone_identifiers_list())) {
                return $timezone;
            }
        }
    }

    // Fallback to UTC
    return 'UTC';
}

/**
 * Update timezone configuration in .env file
 *
 * @param string $timezone The timezone to set
 * @return bool True on success, false on failure
 */
function updateTimezoneInEnv(string $timezone): bool {
    $envPath = dirname(__FILE__, 2) . '/.env';

    if (!file_exists($envPath)) {
        return false;
    }

    $envContent = file_get_contents($envPath);
    if ($envContent === false) {
        return false;
    }

    // Check if APP_TIMEZONE is already set
    if (preg_match('/^APP_TIMEZONE\s*=\s*["\']?([^"\'\n]+)["\']?/m', $envContent, $matches)) {
        $currentTimezone = $matches[1];

        // If already set to a non-UTC timezone, keep it
        if ($currentTimezone !== 'UTC' && $currentTimezone !== '') {
            return true;
        }

        // Replace UTC with detected timezone
        $envContent = preg_replace(
            '/^(APP_TIMEZONE\s*=\s*["\']?)UTC(["\']?)$/m',
            '${1}' . $timezone . '${2}',
            $envContent
        );

        if (file_put_contents($envPath, $envContent) !== false) {
            return true;
        }
    }

    return false;
}

// ============================================
// MAIN SETUP LOGIC
// ============================================

try {
    section("Database Setup Script");

    $dbType = strtolower(DB_TYPE ?? 'mysql');
    info("Database Type: " . strtoupper($dbType));
    info("Database Name: " . DB_NAME);
    echo "\n";

    // Detect and configure timezone if needed
    $detectedTimezone = detectTimezone();
    $currentTimezone = defined('APP_TIMEZONE') ? APP_TIMEZONE : 'UTC';

    if ($currentTimezone === 'UTC' && $detectedTimezone !== 'UTC') {
        info("Detected system timezone: " . $detectedTimezone);
        if (updateTimezoneInEnv($detectedTimezone)) {
            success("Updated .env with detected timezone: " . $detectedTimezone);
        }
    } else {
        info("Application timezone: " . $currentTimezone);
    }
    echo "\n";

    // Update .env file to activate the correct database configuration
    updateEnvFile($dbType);
    echo "\n";

    // Create PDO connection based on database type
    if ($dbType === 'sqlite') {
        // ============================================
        // SQLITE SETUP
        // ============================================
        info("Setting up SQLite database...");

        // Determine database path
        if (DB_NAME[0] === '/' || (strlen(DB_NAME) > 1 && DB_NAME[1] === ':')) {
            // Absolute path
            $dbPath = DB_NAME;
        } else {
            // Relative path - use storage/database directory
            $dbPath = dirname(__FILE__, 2) . '/storage/database/' . DB_NAME;
        }

        // Create directory if it doesn't exist
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            info("Creating database directory: " . $dbDir);
            if (!mkdir($dbDir, 0755, true)) {
                error("Failed to create database directory");
            }
            success("Database directory created");
        }

        // Check if database already exists
        $dbExists = file_exists($dbPath);
        if ($dbExists) {
            info("Database file already exists: " . $dbPath);
            echo "Do you want to recreate it? This will DELETE all existing data! (yes/no): ";
            $handle = fopen("php://stdin", "r");
            $line = trim(fgets($handle));
            fclose($handle);

            if (strtolower($line) === 'yes') {
                unlink($dbPath);
                info("Existing database deleted");
            } else {
                info("Using existing database");
            }
        }

        // Connect to SQLite database
        $dsn = "sqlite:" . $dbPath;
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
        ]);

        // Enable foreign key constraints
        $pdo->exec('PRAGMA foreign_keys = ON');

        success("Connected to SQLite database: " . $dbPath);

    } else {
        // ============================================
        // MYSQL SETUP
        // ============================================
        info("Setting up MySQL database...");

        // First connect without database to create it
        $dsn = DB_TYPE . ":host=" . DB_HOST;

        if (!empty(DB_PORT)) {
            $dsn .= ";port=" . DB_PORT;
        }

        $dsn .= ";charset=" . (DB_CHAR ?? 'utf8mb4');

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
        ]);

        success("Connected to MySQL server");

        // Create database if it doesn't exist
        $dbName = DB_NAME;
        info("Creating database '$dbName' if it doesn't exist...");

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        success("Database '$dbName' ready");

        // Switch to the database
        $pdo->exec("USE `$dbName`");
        success("Using database '$dbName'");
    }

    // ============================================
    // CREATE SCHEMA
    // ============================================
    section("Creating Database Schema");

    // Load and execute schema based on database type
    $schemaFile = ($dbType === 'sqlite') ? 'schema_sqlite.sql' : 'schema.sql';
    $schemaPath = dirname(__FILE__) . '/' . $schemaFile;

    if (!file_exists($schemaPath)) {
        error("Schema file not found: " . $schemaFile);
    }

    info("Loading schema from: " . $schemaFile);
    $schemaSql = file_get_contents($schemaPath);

    // Remove SQL comments (-- comments)
    $lines = explode("\n", $schemaSql);
    $cleanedLines = [];
    foreach ($lines as $line) {
        // Remove lines that are only comments
        if (!preg_match('/^\s*--/', $line)) {
            $cleanedLines[] = $line;
        }
    }
    $schemaSql = implode("\n", $cleanedLines);

    // Split statements by semicolon followed by newline (more reliable than just semicolon)
    // This prevents splitting on semicolons inside VALUES clauses
    $statements = preg_split('/;\s*\n/', $schemaSql);
    $statements = array_filter(array_map('trim', $statements));
    $statementCount = 0;

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                // Add semicolon back if the statement doesn't end with one
                if (substr(trim($statement), -1) !== ';') {
                    $statement .= ';';
                }

                $pdo->exec($statement);
                $statementCount++;
            } catch (PDOException $e) {
                // Ignore "already exists" errors for SQLite
                if (strpos($e->getMessage(), 'already exists') === false) {
                    error("Failed to execute statement", $e);
                }
            }
        }
    }

    success("Executed $statementCount SQL statements");
    success("All tables created successfully");

    // ============================================
    // INSERT DEFAULT DATA
    // ============================================
    section("Inserting Default Data");

    // Check and insert contact_info
    $contactInfo = $pdo->query("SELECT * FROM settings WHERE setting_key = 'contact_info'")->fetch();

    if (!$contactInfo) {
        info("Inserting default contact info...");

        $defaultContactInfo = json_encode([
            'company_name' => 'Your Company Name',
            'address'      => '123 Main Street',
            'postal_code'  => '12345',
            'city'         => 'Your City',
            'email'        => 'info@example.com',
            'phone'        => '+1 (555) 123-4567'
        ]);

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('contact_info', ?)");
        $stmt->execute([$defaultContactInfo]);

        success("Default contact info inserted");
    } else {
        info("Contact info already exists - skipping");
    }

    // Check and insert copyright_text
    $copyrightText = $pdo->query("SELECT * FROM settings WHERE setting_key = 'copyright_text'")->fetch();

    if (!$copyrightText) {
        info("Inserting default copyright text...");

        $defaultCopyright = '© 2006-{year} @nunes.net';

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('copyright_text', ?)");
        $stmt->execute([$defaultCopyright]);

        success("Default copyright text inserted");
    } else {
        info("Copyright text already exists - skipping");
    }

    // Check and insert registration_enabled
    $registrationEnabled = $pdo->query("SELECT * FROM settings WHERE setting_key = 'registration_enabled'")->fetch();

    if (!$registrationEnabled) {
        info("Inserting default registration setting...");

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('registration_enabled', ?)");
        $stmt->execute(['1']);

        success("Default registration setting inserted");
    } else {
        info("Registration setting already exists - skipping");
    }

    // Check and insert site_logo
    $siteLogo = $pdo->query("SELECT * FROM settings WHERE setting_key = 'site_logo'")->fetch();

    if (!$siteLogo) {
        info("Inserting default site logo setting...");

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_logo', ?)");
        $stmt->execute(['/assets/img/logo.png']);

        success("Default site logo setting inserted");
    } else {
        info("Site logo setting already exists - skipping");
    }

    // ============================================
    // VERIFY SETUP
    // ============================================
    section("Verifying Setup");

    // Count tables
    if ($dbType === 'sqlite') {
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll();
    } else {
        $tables = $pdo->query("SHOW TABLES")->fetchAll();
    }

    $tableCount = count($tables);
    success("Found $tableCount tables in database");

    // Count settings
    $settingsCount = $pdo->query("SELECT COUNT(*) as count FROM settings")->fetch()->count;
    success("Found $settingsCount settings in database");

    // List tables
    info("Tables created:");
    foreach ($tables as $table) {
        $tableName = ($dbType === 'sqlite') ? $table->name : array_values((array)$table)[0];
        echo "  - " . $tableName . "\n";
    }

    // ============================================
    // COMPLETION
    // ============================================
    section("Setup Complete");

    echo "\n";
    success("Database setup completed successfully!");
    echo "\n";

    if ($dbType === 'sqlite') {
        info("SQLite database location: " . $dbPath);
        info("Database size: " . number_format(filesize($dbPath)) . " bytes");
    } else {
        info("MySQL database: " . DB_NAME);
        info("MySQL host: " . DB_HOST);
    }

    echo "\n";
    info("Next steps:");
    echo "  1. Update contact information in admin panel\n";
    echo "  2. Create your first admin user\n";
    echo "  3. Configure email settings in .env file\n";
    echo "\n";

} catch (PDOException $e) {
    error("Database error occurred", $e);
} catch (Exception $e) {
    error("An error occurred", $e);
}

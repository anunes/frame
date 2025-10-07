<?php

/**
 * Database Setup Script for Settings Table
 * Run this file once to create the settings table and insert initial data
 */

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

// Load config
require_once dirname(__FILE__, 2) . '/app/config/config.php';

try {
    // Generate database name from APP_NAME (lowercase, no spaces)
    $dbName = strtolower(str_replace(' ', '', APP_NAME));

    // First, connect without database to create it if needed
    $dsn = DB_TYPE . ":host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHAR;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ]);

    // Create database if it doesn't exist
    echo "Creating database '$dbName' if it doesn't exist...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$dbName' ready.\n\n";

    // Now connect to the specific database
    $pdo->exec("USE `$dbName`");
    echo "Connected to database '$dbName' successfully.\n\n";

    // Create migrations directory if it doesn't exist
    $migrationsDir = dirname(__FILE__) . '/migrations';
    if (!is_dir($migrationsDir)) {
        echo "Creating migrations directory...\n";
        mkdir($migrationsDir, 0755, true);
        echo "✓ Migrations directory created.\n\n";
    } else {
        echo "✓ Migrations directory already exists.\n\n";
    }

    // Create migrations table
    echo "Creating migrations table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `migration` varchar(255) NOT NULL,
      `batch` int(11) NOT NULL,
      `migrated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $pdo->exec($sql);
    echo "✓ Migrations table ready.\n\n";

    // Execute schema.sql to create all tables
    echo "Creating database tables from schema.sql...\n";
    $schemaPath = dirname(__FILE__) . '/schema.sql';
    if (file_exists($schemaPath)) {
        $schemaSql = file_get_contents($schemaPath);

        // Split statements by semicolon and execute each
        $statements = array_filter(array_map('trim', explode(';', $schemaSql)));
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        echo "✓ Database tables created from schema.sql.\n\n";
    }

    // Check if contact_info setting exists
    $contactInfo = $pdo->query("SELECT * FROM settings WHERE setting_key = 'contact_info'")->fetch();

    if (!$contactInfo) {
        echo "Inserting default contact info...\n";

        $defaultContactInfo = json_encode([
            'company_name' => 'Your Company Name',
            'address' => '123 Main Street',
            'postal_code' => '12345',
            'city' => 'Your City',
            'email' => 'info@example.com',
            'phone' => '+1 (555) 123-4567'
        ]);

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('contact_info', ?)");
        $stmt->execute([$defaultContactInfo]);

        echo "✓ Default contact info inserted successfully.\n\n";
    } else {
        echo "✓ Contact info already exists in database.\n\n";
    }

    // Check if copyright_text setting exists
    $copyrightText = $pdo->query("SELECT * FROM settings WHERE setting_key = 'copyright_text'")->fetch();

    if (!$copyrightText) {
        echo "Inserting default copyright text...\n";

        $defaultCopyright = '© 2006-{year} @nunes.net';

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('copyright_text', ?)");
        $stmt->execute([$defaultCopyright]);

        echo "✓ Default copyright text inserted successfully.\n\n";
    } else {
        echo "✓ Copyright text already exists in database.\n\n";
    }

    echo "=================================\n";
    echo "Database setup completed successfully!\n";
    echo "=================================\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

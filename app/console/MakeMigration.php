<?php

namespace app\console;

class MakeMigration
{
    /**
     * Handle the command
     */
    public function handle(array $arguments): void
    {
        if (empty($arguments[0])) {
            echo "\033[31mError: Migration name is required.\033[0m\n";
            echo "Usage: php frame make:migration <migration_name>\n";
            echo "Example: php frame make:migration create_products_table\n";
            exit(1);
        }

        $name = $arguments[0];
        $timestamp = date('Y_m_d_His');
        $filename = $timestamp . '_' . $name . '.sql';
        $migrationPath = BASE_PATH . '/database/migrations/' . $filename;

        // Ensure migrations directory exists
        $migrationsDir = BASE_PATH . '/database/migrations';
        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0755, true);
        }

        // Generate migration content
        $content = $this->getMigrationTemplate($name);

        // Create the migration file
        if (file_put_contents($migrationPath, $content)) {
            echo "\033[32mMigration created successfully:\033[0m {$migrationPath}\n";
            echo "\nEdit the file to add your SQL statements, then run: \033[33mphp frame migrate\033[0m\n";
        } else {
            echo "\033[31mError: Failed to create migration.\033[0m\n";
            exit(1);
        }
    }

    /**
     * Get migration template
     */
    private function getMigrationTemplate(string $name): string
    {
        return <<<SQL
-- Migration: {$name}
-- Created: %s

-- Up Migration
-- Write your SQL statements here to apply this migration

-- Example: Create a table
-- CREATE TABLE IF NOT EXISTS example_table (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(255) NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
-- );

-- Down Migration (for rollback)
-- Uncomment and modify for rollback support
-- DROP TABLE IF EXISTS example_table;

SQL;
    }
}

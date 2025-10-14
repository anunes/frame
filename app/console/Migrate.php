<?php

namespace app\console;

use app\core\Database;
use PDO;

class Migrate
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Handle the command
     */
    public function handle(array $arguments): void
    {
        echo "Running migrations...\n\n";

        // Create migrations table if it doesn't exist
        $this->createMigrationsTable();

        // Get all migration files
        $migrationsPath = BASE_PATH . '/database/migrations';
        if (!is_dir($migrationsPath)) {
            echo "\033[33mNo migrations directory found.\033[0m\n";
            exit(0);
        }

        $files = glob($migrationsPath . '/*.sql');
        if (empty($files)) {
            echo "\033[33mNo migration files found.\033[0m\n";
            exit(0);
        }

        // Sort files by name (timestamp prefix ensures chronological order)
        sort($files);

        // Get already run migrations
        $ranMigrations = $this->getRanMigrations();

        $migrated = 0;
        foreach ($files as $file) {
            $filename = basename($file);

            // Skip if already migrated
            if (in_array($filename, $ranMigrations)) {
                continue;
            }

            echo "Migrating: \033[36m{$filename}\033[0m\n";

            try {
                // Read and execute migration
                $sql = file_get_contents($file);

                // Remove comments and split by semicolon
                $statements = $this->parseSqlStatements($sql);

                foreach ($statements as $statement) {
                    if (!empty(trim($statement))) {
                        $this->db->query($statement);
                    }
                }

                // Record migration
                $this->recordMigration($filename);

                echo "\033[32m✓\033[0m Migrated: {$filename}\n";
                $migrated++;

            } catch (\Exception $e) {
                echo "\033[31m✗\033[0m Failed: {$filename}\n";
                echo "Error: " . $e->getMessage() . "\n";
                exit(1);
            }
        }

        if ($migrated === 0) {
            echo "\033[33mNothing to migrate.\033[0m\n";
        } else {
            echo "\n\033[32mMigrated {$migrated} file(s) successfully!\033[0m\n";
        }
    }

    /**
     * Create migrations tracking table
     */
    private function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            migrated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $this->db->query($sql);
    }

    /**
     * Get list of migrations that have already been run
     */
    private function getRanMigrations(): array
    {
        $sql = "SELECT migration FROM migrations";
        $result = $this->db->rows($sql);

        if (!$result) {
            return [];
        }

        return array_map(fn($row) => $row->migration, $result);
    }

    /**
     * Record a migration as having been run
     */
    private function recordMigration(string $filename): void
    {
        // Get current batch number
        $batchSql = "SELECT MAX(batch) as max_batch FROM migrations";
        $result = $this->db->row($batchSql);
        $batch = ($result && $result->max_batch) ? $result->max_batch + 1 : 1;

        $sql = "INSERT INTO migrations (migration, batch) VALUES (?, ?)";
        $this->db->query($sql, [$filename, $batch]);
    }

    /**
     * Parse SQL statements from file content
     */
    private function parseSqlStatements(string $sql): array
    {
        // Remove SQL comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        // Split by semicolon
        $statements = explode(';', $sql);

        // Filter out empty statements
        return array_filter(array_map('trim', $statements));
    }
}

<?php

namespace app\console;

use app\core\Database;

class MigrateRollback
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
        echo "Rolling back migrations...\n\n";

        // Check if migrations table exists
        $tableExists = $this->db->row(
            "SHOW TABLES LIKE 'migrations'"
        );

        if (!$tableExists) {
            echo "\033[33mNo migrations to rollback.\033[0m\n";
            exit(0);
        }

        // Get last batch of migrations
        $sql = "SELECT MAX(batch) as max_batch FROM migrations";
        $result = $this->db->row($sql);

        if (!$result || !$result->max_batch) {
            echo "\033[33mNo migrations to rollback.\033[0m\n";
            exit(0);
        }

        $lastBatch = $result->max_batch;

        // Get migrations from last batch
        $sql = "SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC";
        $migrations = $this->db->rows($sql, [$lastBatch]);

        if (empty($migrations)) {
            echo "\033[33mNo migrations to rollback.\033[0m\n";
            exit(0);
        }

        $rolledBack = 0;
        foreach ($migrations as $migration) {
            $filename = $migration->migration;
            $filepath = BASE_PATH . '/database/migrations/' . $filename;

            echo "Rolling back: \033[36m{$filename}\033[0m\n";

            if (!file_exists($filepath)) {
                echo "\033[33m⚠\033[0m Migration file not found, removing from database\n";
                $this->removeMigrationRecord($filename);
                continue;
            }

            try {
                // Read migration file and look for rollback statements
                $sql = file_get_contents($filepath);

                // Extract down migration (statements after "Down Migration" comment)
                if (preg_match('/--\s*Down Migration.*?$/s', $sql, $matches)) {
                    $downSql = $matches[0];
                    $statements = $this->parseSqlStatements($downSql);

                    foreach ($statements as $statement) {
                        if (!empty(trim($statement))) {
                            $this->db->query($statement);
                        }
                    }

                    $this->removeMigrationRecord($filename);
                    echo "\033[32m✓\033[0m Rolled back: {$filename}\n";
                    $rolledBack++;
                } else {
                    echo "\033[33m⚠\033[0m No rollback statements found, removing from database\n";
                    $this->removeMigrationRecord($filename);
                }

            } catch (\Exception $e) {
                echo "\033[31m✗\033[0m Failed: {$filename}\n";
                echo "Error: " . $e->getMessage() . "\n";
                exit(1);
            }
        }

        echo "\n\033[32mRolled back {$rolledBack} migration(s)!\033[0m\n";
    }

    /**
     * Remove migration record from database
     */
    private function removeMigrationRecord(string $filename): void
    {
        $sql = "DELETE FROM migrations WHERE migration = ?";
        $this->db->query($sql, [$filename]);
    }

    /**
     * Parse SQL statements from content
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

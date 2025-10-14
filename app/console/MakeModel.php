<?php

namespace app\console;

class MakeModel
{
    /**
     * Handle the command
     */
    public function handle(array $arguments): void
    {
        if (empty($arguments[0])) {
            echo "\033[31mError: Model name is required.\033[0m\n";
            echo "Usage: php frame make:model <ModelName>\n";
            exit(1);
        }

        $name = $arguments[0];
        $modelPath = BASE_PATH . '/app/models/' . $name . '.php';

        // Check if model already exists
        if (file_exists($modelPath)) {
            echo "\033[31mError: Model '{$name}' already exists.\033[0m\n";
            exit(1);
        }

        // Generate model content
        $content = $this->getModelTemplate($name);

        // Create the model file
        if (file_put_contents($modelPath, $content)) {
            echo "\033[32mModel created successfully:\033[0m {$modelPath}\n";
        } else {
            echo "\033[31mError: Failed to create model.\033[0m\n";
            exit(1);
        }
    }

    /**
     * Get model template
     */
    private function getModelTemplate(string $name): string
    {
        $tableName = strtolower($name) . 's'; // Simple pluralization

        return <<<PHP
<?php

namespace app\\models;

use app\\core\\Database;

class {$name} extends Database
{
    protected string \$table = '{$tableName}';

    /**
     * Find record by ID
     */
    public function findById(int \$id): ?object
    {
        \$sql = "SELECT * FROM {\$this->table} WHERE id = ?";
        \$result = \$this->row(\$sql, [\$id]);

        return \$result ?: null;
    }

    /**
     * Get all records
     */
    public function getAll(): array
    {
        \$sql = "SELECT * FROM {\$this->table} ORDER BY created_at DESC";
        \$result = \$this->rows(\$sql);
        return is_array(\$result) ? \$result : [];
    }

    /**
     * Create a new record
     */
    public function create(array \$data): ?int
    {
        \$id = \$this->insert(\$this->table, \$data);
        return \$id ? (int)\$id : null;
    }

    /**
     * Update a record
     */
    public function updateRecord(int \$id, array \$data): bool
    {
        \$rowsAffected = \$this->update(\$this->table, \$data, ['id' => \$id]);
        return \$rowsAffected > 0;
    }

    /**
     * Delete a record
     */
    public function deleteRecord(int \$id): bool
    {
        \$rowsAffected = \$this->deleteById(\$this->table, \$id);
        return \$rowsAffected > 0;
    }
}

PHP;
    }
}

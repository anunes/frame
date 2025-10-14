<?php

/**
 * Database Class - PDO Wrapper
 *
 * Provides a simple and secure interface to interact with databases using PDO.
 * Supports multiple database drivers including MySQL, SQLite, PostgreSQL, etc.
 *
 * Key Features:
 * - Prepared statements for SQL injection protection
 * - Support for MySQL and SQLite out of the box
 * - Flexible DSN building based on database type
 * - CRUD helper methods
 * - Transaction support
 *
 * @package app\core
 */

namespace app\core;

use PDO;
use PDOException;
use Exception;

class Database
{
    // ============================================
    // PROPERTIES
    // ============================================

    /**
     * PDO database connection instance
     *
     * @var PDO
     */
    protected PDO $db;

    /**
     * Database connection parameters
     * These are populated from constants defined in config.php
     */
    protected string $host;
    protected string $username;
    protected string $password;
    protected string $database;
    protected string $type;
    protected string $charset;
    protected string $port;

    // ============================================
    // CONSTRUCTOR
    // ============================================

    /**
     * Initialize database connection
     *
     * Establishes a PDO connection based on the database type (MySQL, SQLite, etc.)
     * Configuration is pulled from constants defined in config/config.php
     *
     * @throws Exception If required connection parameters are missing
     * @throws PDOException If connection fails
     */
    public function __construct()
    {
        // Load connection parameters from constants
        $this->type     = DB_TYPE ?? 'mysql';
        $this->host     = DB_HOST ?? 'localhost';
        $this->username = DB_USER ?? '';
        $this->password = DB_PASS ?? '';
        $this->database = DB_NAME ?? '';
        $this->charset  = DB_CHAR ?? 'utf8mb4';
        $this->port     = DB_PORT ?? '';

        // Validate required parameters
        if (empty($this->database)) {
            throw new Exception('Database name is required');
        }

        // SQLite doesn't require username, but other databases do
        if ($this->type !== 'sqlite' && empty($this->username)) {
            throw new Exception('Database username is required for ' . $this->type);
        }

        // Build DSN and establish connection
        $dsn = $this->buildDsn();

        try {
            // Create PDO connection
            // SQLite doesn't need username/password
            if ($this->type === 'sqlite') {
                $this->db = new PDO($dsn);
            } else {
                $this->db = new PDO($dsn, $this->username, $this->password);
            }

            // Set PDO attributes for better error handling and security
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // For SQLite, enable foreign key constraints
            if ($this->type === 'sqlite') {
                $this->db->exec('PRAGMA foreign_keys = ON');
            }

        } catch (PDOException $e) {
            throw new Exception(
                'Database connection failed: ' . $e->getMessage()
            );
        }
    }

    // ============================================
    // DSN BUILDING
    // ============================================

    /**
     * Build DSN (Data Source Name) string based on database type
     *
     * Different database drivers require different DSN formats.
     * This method handles MySQL, SQLite, PostgreSQL, and others.
     *
     * @return string The DSN connection string
     *
     * @example MySQL:  "mysql:host=localhost;port=3306;dbname=mydb;charset=utf8mb4"
     * @example SQLite: "sqlite:/path/to/database.db"
     */
    private function buildDsn(): string
    {
        $type = strtolower($this->type);

        switch ($type) {
            case 'sqlite':
                // SQLite uses a file path
                // If database name is absolute path, use it; otherwise relative to project root
                if ($this->database[0] === '/' || $this->database[1] === ':') {
                    // Absolute path
                    return "sqlite:{$this->database}";
                } else {
                    // Relative path - assume storage/database directory
                    $dbPath = BASE_PATH . '/storage/database/' . $this->database;

                    // Create directory if it doesn't exist
                    $dbDir = dirname($dbPath);
                    if (!is_dir($dbDir)) {
                        mkdir($dbDir, 0755, true);
                    }

                    return "sqlite:{$dbPath}";
                }

            case 'mysql':
                // MySQL DSN format
                $dsn = "mysql:host={$this->host}";

                // Add port if specified
                if (!empty($this->port)) {
                    $dsn .= ";port={$this->port}";
                }

                // Add database name
                $dsn .= ";dbname={$this->database}";

                // Add charset (utf8mb4 supports full Unicode including emojis)
                $dsn .= ";charset={$this->charset}";

                return $dsn;

            case 'pgsql':
            case 'postgresql':
                // PostgreSQL DSN format
                $dsn = "pgsql:host={$this->host}";

                if (!empty($this->port)) {
                    $dsn .= ";port={$this->port}";
                }

                $dsn .= ";dbname={$this->database}";

                return $dsn;

            case 'sqlsrv':
            case 'mssql':
                // Microsoft SQL Server DSN format
                $dsn = "sqlsrv:Server={$this->host}";

                if (!empty($this->port)) {
                    $dsn .= ",{$this->port}";
                }

                $dsn .= ";Database={$this->database}";

                return $dsn;

            default:
                // Generic DSN format for other drivers
                $dsn = "{$type}:host={$this->host}";

                if (!empty($this->port)) {
                    $dsn .= ";port={$this->port}";
                }

                $dsn .= ";dbname={$this->database}";

                return $dsn;
        }
    }

    // ============================================
    // CONNECTION ACCESS
    // ============================================

    /**
     * Get the PDO instance for direct access
     *
     * Use this when you need direct access to PDO methods
     * not wrapped by this class (e.g., transactions, specific PDO features)
     *
     * @return PDO The PDO database connection instance
     *
     * @example
     * $pdo = $db->getPdo();
     * $pdo->beginTransaction();
     */
    public function getPdo(): PDO
    {
        return $this->db;
    }

    /**
     * Get the database type currently in use
     *
     * @return string Database type (mysql, sqlite, pgsql, etc.)
     */
    public function getType(): string
    {
        return $this->type;
    }

    // ============================================
    // QUERY EXECUTION
    // ============================================

    /**
     * Execute a raw SQL query without parameters
     *
     * WARNING: Only use this for trusted SQL. Never use with user input.
     * For queries with user data, use run() with parameters instead.
     *
     * @param string $sql SQL query to execute
     * @return void
     *
     * @example $db->raw("CREATE TABLE users (id INT PRIMARY KEY)");
     */
    public function raw(string $sql): void
    {
        $this->db->query($sql);
    }

    /**
     * Execute a SQL query with optional parameters (prepared statement)
     *
     * This is the main query method. It uses prepared statements to
     * protect against SQL injection attacks.
     *
     * @param string $sql  SQL query with ? placeholders
     * @param array  $args Array of values to bind to placeholders
     * @return object      PDOStatement object with query results
     *
     * @example
     * // Without parameters
     * $stmt = $db->run("SELECT * FROM users");
     *
     * // With parameters (SAFE - protects against SQL injection)
     * $stmt = $db->run("SELECT * FROM users WHERE id = ?", [123]);
     */
    public function run(string $sql, array $args = []): object
    {
        // If no parameters, execute directly
        if (empty($args)) {
            return $this->db->query($sql);
        }

        // Prepare and execute with parameters
        $stmt = $this->db->prepare($sql);
        $stmt->execute($args);

        return $stmt;
    }

    /**
     * Alias for run() - for backward compatibility
     *
     * @param string $sql  SQL query
     * @param array  $args Parameters
     * @return object      PDOStatement object
     */
    public function query(string $sql, array $args = []): object
    {
        return $this->run($sql, $args);
    }

    // ============================================
    // FETCH METHODS
    // ============================================

    /**
     * Fetch multiple records
     *
     * @param string     $sql       SQL SELECT query
     * @param array      $args      Query parameters
     * @param object|int $fetchMode PDO fetch mode (default: FETCH_OBJ)
     * @return array                Array of records
     *
     * @example
     * // Get all users as objects
     * $users = $db->rows("SELECT * FROM users");
     *
     * // Get active users with parameters
     * $users = $db->rows("SELECT * FROM users WHERE active = ?", [1]);
     *
     * // Get as associative arrays instead of objects
     * $users = $db->rows("SELECT * FROM users", [], PDO::FETCH_ASSOC);
     */
    public function rows(string $sql, array $args = [], object|int $fetchMode = PDO::FETCH_OBJ): array
    {
        return $this->run($sql, $args)->fetchAll($fetchMode);
    }

    /**
     * Fetch a single record
     *
     * @param string     $sql       SQL SELECT query
     * @param array      $args      Query parameters
     * @param object|int $fetchMode PDO fetch mode (default: FETCH_OBJ)
     * @return object|false         Record object or false if not found
     *
     * @example
     * $user = $db->row("SELECT * FROM users WHERE email = ?", ['user@example.com']);
     * if ($user) {
     *     echo $user->name;
     * }
     */
    public function row(string $sql, array $args = [], object|int $fetchMode = PDO::FETCH_OBJ): object|false
    {
        return $this->run($sql, $args)->fetch($fetchMode);
    }

    /**
     * Get a single record by ID
     *
     * Convenience method for fetching by primary key.
     *
     * @param string     $table     Table name
     * @param int        $id        Record ID
     * @param object|int $fetchMode PDO fetch mode
     * @return object|false         Record or false if not found
     *
     * @example
     * $user = $db->getById('users', 123);
     */
    public function getById(
        string $table,
        int $id,
        object|int $fetchMode = PDO::FETCH_OBJ
    ): object|false {
        return $this->run("SELECT * FROM $table WHERE id = ?", [$id])->fetch($fetchMode);
    }

    /**
     * Count records matching a query
     *
     * @param string $sql  SQL query
     * @param array  $args Query parameters
     * @return int         Number of matching records
     *
     * @example
     * $userCount = $db->count("SELECT * FROM users WHERE active = ?", [1]);
     */
    public function count(string $sql, array $args = []): int
    {
        return $this->run($sql, $args)->rowCount();
    }

    // ============================================
    // INSERT OPERATIONS
    // ============================================

    /**
     * Insert a record and return the auto-increment ID
     *
     * @param string $table Table name
     * @param array  $data  Associative array of column => value pairs
     * @return false|string The ID of the inserted record (or false on failure)
     *
     * @example
     * $userId = $db->insert('users', [
     *     'name' => 'John Doe',
     *     'email' => 'john@example.com',
     *     'password' => password_hash('secret', PASSWORD_DEFAULT)
     * ]);
     */
    public function insert(string $table, array $data): false|string
    {
        // Build column list
        $columns = implode(',', array_keys($data));

        // Build placeholder list (?, ?, ?)
        $placeholders = implode(',', array_fill(0, count($data), '?'));

        // Get values in the same order as columns
        $values = array_values($data);

        // Execute insert
        $this->run(
            "INSERT INTO $table ($columns) VALUES ($placeholders)",
            $values
        );

        // Return the auto-increment ID
        return $this->lastInsertId();
    }

    /**
     * Get the ID of the last inserted record
     *
     * @return false|string Last insert ID
     */
    public function lastInsertId(): false|string
    {
        return $this->db->lastInsertId();
    }

    // ============================================
    // UPDATE OPERATIONS
    // ============================================

    /**
     * Update records
     *
     * @param string $table Table name
     * @param array  $data  Associative array of columns to update
     * @param array  $where Associative array of WHERE conditions
     * @return int          Number of affected rows
     *
     * @example
     * // Update single user
     * $affected = $db->update('users',
     *     ['name' => 'Jane Doe', 'email' => 'jane@example.com'],
     *     ['id' => 123]
     * );
     *
     * // Update multiple conditions
     * $affected = $db->update('users',
     *     ['active' => 0],
     *     ['last_login' => null, 'created_at' => '2020-01-01']
     * );
     */
    public function update(string $table, array $data, array $where): int
    {
        // Build SET clause (column = ?, column = ?)
        $setClause = implode(', ', array_map(
            fn($col) => "$col = ?",
            array_keys($data)
        ));

        // Build WHERE clause (column = ? AND column = ?)
        $whereClause = implode(' AND ', array_map(
            fn($col) => "$col = ?",
            array_keys($where)
        ));

        // Merge values (SET values first, then WHERE values)
        $values = array_merge(array_values($data), array_values($where));

        // Execute update
        $stmt = $this->run(
            "UPDATE $table SET $setClause WHERE $whereClause",
            $values
        );

        return $stmt->rowCount();
    }

    // ============================================
    // DELETE OPERATIONS
    // ============================================

    /**
     * Delete records with WHERE conditions
     *
     * @param string $table Table name
     * @param array  $where WHERE conditions
     * @param int    $limit Optional limit (default: 1)
     * @return int          Number of deleted rows
     *
     * @example
     * // Delete single record
     * $db->delete('users', ['id' => 123]);
     *
     * // Delete multiple records
     * $db->delete('users', ['active' => 0], 100);
     */
    public function delete(string $table, array $where, int $limit = 1): int
    {
        // Build WHERE clause
        $whereClause = implode(' AND ', array_map(
            fn($col) => "$col = ?",
            array_keys($where)
        ));

        // Get values
        $values = array_values($where);

        // Build query with optional LIMIT
        $sql = "DELETE FROM $table WHERE $whereClause";
        if (is_numeric($limit) && $limit > 0) {
            $sql .= " LIMIT $limit";
        }

        $stmt = $this->run($sql, $values);

        return $stmt->rowCount();
    }

    /**
     * Delete a single record by ID
     *
     * @param string $table Table name
     * @param int    $id    Record ID
     * @return int          Number of deleted rows (0 or 1)
     *
     * @example
     * $deleted = $db->deleteById('users', 123);
     */
    public function deleteById(string $table, int $id): int
    {
        $stmt = $this->run("DELETE FROM $table WHERE id = ?", [$id]);
        return $stmt->rowCount();
    }

    /**
     * Delete records by multiple IDs
     *
     * @param string $table  Table name
     * @param string $column Column name (usually 'id')
     * @param string $ids    Comma-separated list of IDs
     * @return int           Number of deleted rows
     *
     * @example
     * $deleted = $db->deleteByIds('users', 'id', '1,2,3,4,5');
     */
    public function deleteByIds(string $table, string $column, string $ids): int
    {
        // Note: This method assumes $ids is already validated
        // For better security, consider using array of IDs with placeholders
        $stmt = $this->run("DELETE FROM $table WHERE $column IN ($ids)");
        return $stmt->rowCount();
    }

    /**
     * Delete all records from a table
     *
     * WARNING: This deletes ALL records. Use with caution!
     *
     * @param string $table Table name
     * @return int          Number of deleted rows
     *
     * @example
     * $deleted = $db->deleteAll('temporary_cache');
     */
    public function deleteAll(string $table): int
    {
        $stmt = $this->run("DELETE FROM $table");
        return $stmt->rowCount();
    }

    /**
     * Truncate a table (delete all records and reset auto-increment)
     *
     * WARNING: This cannot be rolled back and resets AUTO_INCREMENT!
     * For SQLite, this uses DELETE instead of TRUNCATE.
     *
     * @param string $table Table name
     * @return int          Number of affected rows
     *
     * @example
     * $db->truncate('session_logs');
     */
    public function truncate(string $table): int
    {
        // SQLite doesn't support TRUNCATE, use DELETE instead
        if ($this->type === 'sqlite') {
            $stmt = $this->run("DELETE FROM $table");
            // Reset auto-increment counter
            $this->run("DELETE FROM sqlite_sequence WHERE name = ?", [$table]);
        } else {
            $stmt = $this->run("TRUNCATE TABLE $table");
        }

        return $stmt->rowCount();
    }

    // ============================================
    // TRANSACTION SUPPORT
    // ============================================

    /**
     * Begin a database transaction
     *
     * Transactions allow you to group multiple queries together.
     * If any query fails, you can rollback all changes.
     *
     * @return bool True on success
     *
     * @example
     * $db->beginTransaction();
     * try {
     *     $db->insert('users', [...]);
     *     $db->update('accounts', [...], [...]);
     *     $db->commit();
     * } catch (Exception $e) {
     *     $db->rollback();
     * }
     */
    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit the current transaction
     *
     * @return bool True on success
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /**
     * Rollback the current transaction
     *
     * @return bool True on success
     */
    public function rollback(): bool
    {
        return $this->db->rollBack();
    }

    /**
     * Check if currently in a transaction
     *
     * @return bool True if in transaction
     */
    public function inTransaction(): bool
    {
        return $this->db->inTransaction();
    }
}

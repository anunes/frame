<?php

/**
 * Wrapper for PDO
 */

namespace app\core;

use PDO;
use Exception;

class Database
{
    /**
     * hold database connection
     */
    protected PDO $db;
    /**
     * Array of connection arguments
     *
     *
     */
    protected string $host     = DB_HOST;
    protected string $username = DB_USER;
    protected string $password = DB_PASS;
    protected string $database = DB_NAME;
    protected string $type     = DB_TYPE;
    protected string $charset  = DB_CHAR;
    protected string $port     = DB_PORT;

    public function __construct()
    {

        if (!isset($this->database)) {
            throw new Exception('Database is required');
        }
        if (!isset($this->username)) {
            throw new Exception('Username  is required');
        }

        $type = $this->type ?? 'mysql';
        $host = $this->host ?? 'localhost';

        $charset = $this->charset ?? 'utf8';
        $port = isset($this->port) ? 'port=' . $this->port . ';' : '';
        $password = $this->password ?? '';
        $database = $this->database;
        $username = $this->username;

        $this->db = new PDO("$type:host=$host;$port" . "dbname=$database;charset=$charset", $username, $password);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    }

    /**
     * get PDO instance
     *
     * @return PDO $db PDO instance
     */

    public function getPdo(): PDO
    {
        return $this->db;
    }

    /**
     * Run raw sql query
     *
     * @param string $sql       sql query
     * @return void
     */

    public function raw(string $sql): void
    {
        $this->db->query($sql);
    }

    /**
     * Run sql query
     *
     * @param string $sql       sql query
     * @param array $args      params
     * @return object            returns a PDO object
     */

    public function run(string $sql, array $args = []): object
    {
        if (empty($args)) {
            return $this->db->query($sql);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($args);

        return $stmt;
    }

    /**
     * Get arrays of records
     *
     * @param string $sql       sql query
     * @param array $args      params
     * @param object|int $fetchMode set return mode ie object or array
     * @return object            returns multiple records
     */

    public function rows(string $sql, array $args = [], object|int $fetchMode = PDO::FETCH_OBJ)
    {
        return $this->run($sql, $args)->fetchAll($fetchMode);
    }

    /**
     * Get array of records
     *
     * @param string $sql       sql query
     * @param array $args      params
     * @param object|int $fetchMode set return mode ie object or array
     * @return object            returns single record
     */

    public function row(string $sql, array $args = [], object|int $fetchMode = PDO::FETCH_OBJ): object|false
    {
        return $this->run($sql, $args)->fetch($fetchMode);
    }

    /**
     * Get record by id
     *
     * @param string $table     name of table
     * @param integer $id       id of record
     * @param object|int $fetchMode set return mode ie object or array
     * @return object            returns single record
     */

    public function getById(
        string $table,
        int $id,
        object|int $fetchMode = PDO::FETCH_OBJ
    ): object|false
    {
        return $this->run("SELECT * FROM $table WHERE id = ?", [$id])->fetch($fetchMode);
    }

    /**
     * Get number of records
     *
     * @param string $sql sql query
     * @param array $args params
     * @return integer           returns number of records
     */

    public function count($sql, $args = []): int
    {
        return $this->run($sql, $args)->rowCount();
    }

    /**
     * Get primary key of last inserted record
     */

    public function lastInsertId(): false|string
    {
        return $this->db->lastInsertId();
    }

    /**
     * insert record
     *
     * @param string $table table name
     * @param array $data  array of columns and values
     */

    public function insert(string $table, array $data): false|string
    {
        //add columns into comma seperated string
        $columns = implode(',', array_keys($data));

        //get values
        $values = array_values($data);

        $placeholders = array_map(
            function ($val) {
                return '?';
            },
            array_keys($data)
        );

        //convert array into comma seperated string
        $placeholders = implode(',', array_values($placeholders));

        $this->run("INSERT INTO $table ($columns) VALUES ($placeholders)", $values);

        return $this->lastInsertId();
    }

    /**
     * update record
     *
     * @param string $table table name
     * @param array $data  array of columns and values
     * @param array $where array of columns and values
     */

    public function update(string $table, array $data, array $where)
    {
        //merge data and where together
        $collection = array_merge($data, $where);

        //collect the values from collection
        $values = array_values($collection);

        //setup fields
        $fieldDetails = null;
        foreach ($data as $key => $value) {
            $fieldDetails .= "$key = ?,";
        }
        $fieldDetails = rtrim($fieldDetails, ',');

        //setup where
        $whereDetails = null;
        $i = 0;
        foreach ($where as $key => $value) {
            $whereDetails .= $i == 0 ? "$key = ?" : " AND $key = ?";
            $i++;
        }

        $stmt = $this->run("UPDATE $table SET $fieldDetails WHERE $whereDetails", $values);

        return $stmt->rowCount();
    }

    /**
     * Delete records
     *
     * @param string $table table name
     * @param array $where array of columns and values
     * @param integer $limit limit number of records
     */

    public function delete(string $table, array $where, int $limit = 1)
    {
        //collect the values from collection
        $values = array_values($where);

        //setup where
        $whereDetails = null;
        $i = 0;
        foreach ($where as $key => $value) {
            $whereDetails .= $i == 0 ? "$key = ?" : " AND $key = ?";
            $i++;
        }

        //if limit is a number use a limit on the query
        if (is_numeric($limit)) {
            $limit = "LIMIT $limit";
        }

        $stmt = $this->run("DELETE FROM $table WHERE $whereDetails $limit", $values);

        return $stmt->rowCount();
    }

    /**
     * Delete all records
     *
     * @param string $table table name
     */

    public function deleteAll(string $table)
    {
        $stmt = $this->run("DELETE FROM $table");

        return $stmt->rowCount();
    }

    /**
     * Delete record by id
     *
     * @param string $table table name
     * @param integer $id id of record
     */

    public function deleteById(string $table, int $id)
    {
        $stmt = $this->run("DELETE FROM $table WHERE id = ?", [$id]);

        return $stmt->rowCount();
    }

    /**
     * Delete record by ids
     *
     * @param  string $table table name
     * @param  string $column name of column
     * @param  string $ids ids of records
     */

    public function deleteByIds(string $table, string $column, string $ids)
    {
        $stmt = $this->run("DELETE FROM $table WHERE $column IN ($ids)");

        return $stmt->rowCount();
    }

    /**
     * truncate table
     *
     * @param string $table table name
     */

    public function truncate(string $table)
    {
        $stmt = $this->run("TRUNCATE TABLE $table");

        return $stmt->rowCount();
    }

    public function query(string $sql, array $args = [])
    {
        if (empty($args)) {
            return $this->db->query($sql);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($args);

        return $stmt;
    }
}


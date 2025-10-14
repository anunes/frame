<?php

namespace app\core;

use app\core\Database;
use Exception;


class Model
{
    protected Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function findByEmail($table, $email): ?object
    {
        return $this->db->row("SELECT * FROM $table WHERE email = ?", [$email]);
    }

    public function findAll($table): ?object
    {
        return $this->db->rows("SELECT * FROM $table");
    }

    public function findOne($table, $id): ?object
    {
        return $this->db->row("SELECT * FROM $table WHERE id =?", [$id]);
    }

    public function insert($table, $data): bool
    {
        try {
            if ($this->db->insert($table, $data)) {
                return true;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return false;
    }

    public function patch($table, $data, $id): bool
    {
        try {
            if ($this->db->update($table, $data, $id)) {
                return true;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return false;
    }

    public function delete($table, $id): bool
    {
        try {
            if ($this->db->delete($table, $id)) {
                return true;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return false;
    }
}

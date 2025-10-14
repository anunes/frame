<?php

namespace app\models;

use app\core\Database;
use PDO;

class User extends Database
{
    /**
     * Find user by ID
     */
    public function findById(int $id): ?object
    {
        $sql = "SELECT * FROM users WHERE id = ?";
        $result = $this->row($sql, [$id]);

        return $result ?: null;
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?object
    {
        $sql = "SELECT * FROM users WHERE email = ?";
        $result = $this->row($sql, [$email]);

        return $result ?: null;
    }

    /**
     * Create a new user
     */
    public function create(array $data): ?int
    {
        $id = $this->insert('users', [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'] ?? 0,
            'avatar' => $data['avatar'] ?? null
        ]);

        return $id ? (int)$id : null;
    }

    /**
     * Update user data
     */
    public function updateUser(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }

        if (isset($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (isset($data['role'])) {
            $updateData['role'] = $data['role'];
        }

        if (isset($data['avatar'])) {
            $updateData['avatar'] = $data['avatar'];
        }

        if (isset($data['active'])) {
            $updateData['active'] = $data['active'];
        }

        if (isset($data['must_change_password'])) {
            $updateData['must_change_password'] = $data['must_change_password'];
        }

        if (empty($updateData)) {
            return false;
        }

        $rowsAffected = $this->update('users', $updateData, ['id' => $id]);

        return $rowsAffected > 0;
    }

    /**
     * Verify user credentials
     */
    public function verifyCredentials(string $email, string $password): ?object
    {
        $user = $this->findByEmail($email);

        if (!$user || !password_verify($password, $user->password)) {
            return null;
        }

        return $user;
    }

    /**
     * Check if email already exists
     */
    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    /**
     * Change user password
     */
    public function changePassword(int $userId, string $oldPassword, string $newPassword): bool
    {
        $user = $this->findById($userId);

        if (!$user || !password_verify($oldPassword, $user->password)) {
            return false;
        }

        return $this->updateUser($userId, ['password' => $newPassword]);
    }

    /**
     * Get all users
     */
    public function getAll(): array
    {
        $sql = "SELECT id, name, email, role, avatar, created_at, updated_at FROM users ORDER BY created_at DESC";
        $result = $this->rows($sql);
        return is_array($result) ? $result : [];
    }

    /**
     * Delete user
     */
    public function deleteUser(int $id): bool
    {
        $rowsAffected = $this->deleteById('users', $id);
        return $rowsAffected > 0;
    }

    /**
     * Create password reset token
     */
    public function createPasswordResetToken(string $email): ?string
    {
        // Check if user exists
        if (!$this->emailExists($email)) {
            return null;
        }

        // Delete any existing tokens for this email
        $this->deletePasswordResetTokens($email);

        // Generate token
        $token = bin2hex(random_bytes(32));

        // Store token in database
        $this->insert('password_resets', [
            'email' => $email,
            'token' => password_hash($token, PASSWORD_DEFAULT)
        ]);

        return $token;
    }

    /**
     * Verify password reset token
     */
    public function verifyPasswordResetToken(string $email, string $token): bool
    {
        // Build SQL query based on database type
        if ($this->getType() === 'sqlite') {
            $sql = "SELECT * FROM password_resets WHERE email = ? AND created_at > datetime('now', '-1 hour')";
        } else {
            // MySQL syntax
            $sql = "SELECT * FROM password_resets WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        }

        $reset = $this->row($sql, [$email]);

        if (!$reset) {
            return false;
        }

        return password_verify($token, $reset->token);
    }

    /**
     * Reset password using token
     */
    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        if (!$this->verifyPasswordResetToken($email, $token)) {
            return false;
        }

        // Update password
        $user = $this->findByEmail($email);
        if (!$user) {
            return false;
        }

        $this->updateUser($user->id, ['password' => $newPassword]);

        // Delete the used token
        $this->deletePasswordResetTokens($email);

        return true;
    }

    /**
     * Delete password reset tokens for an email
     */
    public function deletePasswordResetTokens(string $email): void
    {
        $sql = "DELETE FROM password_resets WHERE email = ?";
        $this->run($sql, [$email]);
    }

    /**
     * Get paginated users with search and status filter
     */
    public function getPaginated(int $page = 1, int $perPage = 10, string $search = '', string $status = 'active'): array
    {
        $offset = ($page - 1) * $perPage;

        $params = [];
        $conditions = [];

        // Add status filter
        if ($status === 'active') {
            $conditions[] = "active = 1";
        } elseif ($status === 'inactive') {
            $conditions[] = "active = 0";
        }
        // If $status is 'all', don't add any active filter

        // Add search filter
        if (!empty($search)) {
            $conditions[] = "(name LIKE ? OR email LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM users $whereClause";
        $countResult = $this->row($countSql, $params);
        $total = $countResult ? $countResult->total : 0;

        // Get paginated data
        $sql = "SELECT id, name, email, role, avatar, active, created_at
                FROM users
                $whereClause
                ORDER BY created_at DESC";

        if ($perPage !== PHP_INT_MAX) {
            // Safe to use direct concatenation as values are already cast to int
            $sql .= " LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
        }

        $users = $this->rows($sql, $params) ?: [];

        return [
            'data' => $users,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $perPage === PHP_INT_MAX ? 1 : ceil($total / $perPage)
        ];
    }
}

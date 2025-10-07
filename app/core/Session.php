<?php

namespace app\core;

use Exception;

class Session
{
    public static function setSession($user): void
    {
        $_SESSION['id'] = $user->id;
        $_SESSION['name'] = $user->name;
        $_SESSION['email'] = $user->email;
        $_SESSION['role'] = $user->role;
        $_SESSION['avatar'] = $user->avatar ?? null; // Include avatar filename
    }

    public static function destroy()
    {
        session_unset();
        session_destroy();
    }

    public static function flash(): void
    {
        if (isset($_SESSION['flash'])) {
            echo '<div class="flash-message alert alert-' . $_SESSION['flash']['type'] . ' alert-dismissible fade show" role="alert">
              ' . $_SESSION['flash']['message'] . '
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';

            unset($_SESSION['flash']);
        }
    }

    public static function setflash($message, $type): void
    {
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type,
        ];
    }


    public static function loggedIn(): bool
    {
        if (!isset($_SESSION['id'])) {
            return false;
        }
        return true;
    }

    public static function isAdmin(): bool
    {
        if (self::loggedIn() && $_SESSION['role'] == '1') {
            return true;
        }
        return false;
    }

    public static function setCsrf(): void
    {
        if (!isset($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(50));
        }
        echo '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
    }

    public static function checkCsrf(): bool
    {
        if (!isset($_SESSION['csrf']) || !isset($_POST['csrf'])) {
            return false;
        }
        if ($_SESSION['csrf'] != $_POST['csrf']) {
            return false;
        }
        return true;
    }

    /**
     * Check if the user is currently logged in (alias for loggedIn)
     */
    public static function isLogged(): bool
    {
        return self::loggedIn();
    }

    /**
     * Get current user object from session
     */
    public static function user(): ?object
    {
        if (!self::loggedIn()) {
            return null;
        }

        return new class {
            public $id;
            public $name;
            public $email;
            public $role;
            public $avatar;

            public function __construct() {
                $this->id = $_SESSION['id'] ?? null;
                $this->name = $_SESSION['name'] ?? null;
                $this->email = $_SESSION['email'] ?? null;
                $this->role = $_SESSION['role'] ?? 0;
                $this->avatar = $_SESSION['avatar'] ?? null;
            }

            public function isAdmin(): bool {
                return $this->role == 1;
            }
        };
    }

    /**
     * Check if current user is admin (for user object)
     */
    public static function userIsAdmin(): bool
    {
        $user = self::user();
        return $user && $user->role == 1;
    }
}

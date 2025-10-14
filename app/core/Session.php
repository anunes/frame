<?php

/**
 * Session Management Class
 *
 * Provides static methods for managing user sessions, including:
 * - User authentication state
 * - Flash messages
 * - CSRF protection
 * - Authorization checks
 *
 * @package app\core
 */

namespace app\core;

use Exception;

class Session
{
    // ============================================
    // SESSION DATA MANAGEMENT
    // ============================================

    /**
     * Store user data in session after successful login
     *
     * This method populates the session with user information
     * that persists across requests until logout or session expiry.
     *
     * @param object $user User object with id, name, email, role, avatar properties
     * @return void
     */
    public static function setSession($user): void
    {
        $_SESSION['id']     = $user->id;
        $_SESSION['name']   = $user->name;
        $_SESSION['email']  = $user->email;
        $_SESSION['role']   = $user->role;

        // Store avatar filename if available, null otherwise
        $_SESSION['avatar'] = $user->avatar ?? null;
    }

    /**
     * Destroy the current session and clear all session data
     *
     * This should be called on logout to completely remove
     * all session data and invalidate the session cookie.
     *
     * @return void
     */
    public static function destroy(): void
    {
        // Clear all session variables
        session_unset();

        // Destroy the session itself
        session_destroy();
    }

    // ============================================
    // FLASH MESSAGES
    // ============================================

    /**
     * Display and clear a flash message
     *
     * Flash messages are one-time notifications shown to users
     * after form submissions or actions. They're displayed once
     * and then automatically removed from the session.
     *
     * Outputs Bootstrap 5 alert HTML with XSS protection.
     *
     * @return void
     */
    public static function flash(): void
    {
        if (isset($_SESSION['flash'])) {
            // Escape output to prevent XSS attacks
            $type    = htmlspecialchars($_SESSION['flash']['type'], ENT_QUOTES, 'UTF-8');
            $message = htmlspecialchars($_SESSION['flash']['message'], ENT_QUOTES, 'UTF-8');

            // Output Bootstrap alert with dismissible button
            echo '<div class="flash-message alert alert-' . $type . ' alert-dismissible fade show" role="alert">
              ' . $message . '
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';

            // Remove flash message after display (one-time only)
            unset($_SESSION['flash']);
        }
    }

    /**
     * Set a flash message to be displayed on next page load
     *
     * Flash messages persist through a single redirect and are
     * automatically cleared after being displayed.
     *
     * @param string $message The message text to display
     * @param string $type    Bootstrap alert type (success, danger, warning, info)
     * @return void
     *
     * @example Session::setflash('User created successfully!', 'success');
     * @example Session::setflash('Invalid email address', 'danger');
     */
    public static function setflash($message, $type): void
    {
        $_SESSION['flash'] = [
            'message' => $message,
            'type'    => $type,
        ];
    }

    // ============================================
    // AUTHENTICATION CHECKS
    // ============================================

    /**
     * Check if a user is currently logged in
     *
     * Determines authentication status by checking for
     * the presence of a user ID in the session.
     *
     * @return bool True if user is logged in, false otherwise
     */
    public static function loggedIn(): bool
    {
        return isset($_SESSION['id']);
    }

    /**
     * Check if the current user is logged in (alias for loggedIn)
     *
     * This is an alias method that provides a more readable
     * alternative to loggedIn() for conditional statements.
     *
     * @return bool True if user is logged in, false otherwise
     *
     * @example if (Session::isLogged()) { ... }
     */
    public static function isLogged(): bool
    {
        return self::loggedIn();
    }

    // ============================================
    // AUTHORIZATION CHECKS
    // ============================================

    /**
     * Check if the current user has administrator privileges
     *
     * An administrator is identified by having a role value of '1'.
     * This method first checks if user is logged in, then verifies admin role.
     *
     * @return bool True if user is an admin, false otherwise
     */
    public static function isAdmin(): bool
    {
        // User must be logged in AND have role = 1
        return self::loggedIn() && $_SESSION['role'] == '1';
    }

    /**
     * Check if current user is admin (alternate method using user object)
     *
     * This method uses the user() object approach rather than
     * direct session access. Functionally equivalent to isAdmin().
     *
     * @return bool True if user is an admin, false otherwise
     */
    public static function userIsAdmin(): bool
    {
        $user = self::user();
        return $user && $user->role == 1;
    }

    // ============================================
    // USER DATA ACCESS
    // ============================================

    /**
     * Get current user object from session
     *
     * Returns an anonymous class object containing all user data
     * from the session. This provides object-oriented access to
     * user properties and includes an isAdmin() method.
     *
     * @return object|null User object if logged in, null otherwise
     *
     * @example
     * $user = Session::user();
     * if ($user) {
     *     echo $user->name;
     *     if ($user->isAdmin()) { ... }
     * }
     */
    public static function user(): ?object
    {
        // Return null if user is not logged in
        if (!self::loggedIn()) {
            return null;
        }

        // Return an anonymous class with user data
        // This approach provides a clean object interface to session data
        return new class {
            public $id;
            public $name;
            public $email;
            public $role;
            public $avatar;

            /**
             * Constructor - populate object from session data
             */
            public function __construct()
            {
                $this->id     = $_SESSION['id'] ?? null;
                $this->name   = $_SESSION['name'] ?? null;
                $this->email  = $_SESSION['email'] ?? null;
                $this->role   = $_SESSION['role'] ?? 0;
                $this->avatar = $_SESSION['avatar'] ?? null;
            }

            /**
             * Check if this user has administrator privileges
             *
             * @return bool True if role is 1 (admin)
             */
            public function isAdmin(): bool
            {
                return $this->role == 1;
            }
        };
    }

    // ============================================
    // CSRF PROTECTION
    // ============================================

    /**
     * Generate and output a CSRF token hidden input field
     *
     * CSRF tokens protect against Cross-Site Request Forgery attacks
     * by ensuring that form submissions originate from your application.
     *
     * This method generates a token once per session and outputs it
     * as a hidden form field. The token should be validated on form submission.
     *
     * @return void Outputs HTML directly
     *
     * @example
     * <form method="POST">
     *     <?php Session::setCsrf(); ?>
     *     <!-- other form fields -->
     * </form>
     */
    public static function setCsrf(): void
    {
        // Generate token only once per session
        if (!isset($_SESSION['csrf'])) {
            // Generate cryptographically secure random token (100 characters)
            $_SESSION['csrf'] = bin2hex(random_bytes(50));
        }

        // Output hidden form field with token
        echo '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
    }

    /**
     * Validate CSRF token from form submission
     *
     * This method should be called at the beginning of any POST request
     * handler to verify the request originated from your application.
     *
     * Uses timing-safe comparison to prevent timing attacks that could
     * be used to guess the token value.
     *
     * @return bool True if token is valid, false otherwise
     *
     * @example
     * if (!Session::checkCsrf()) {
     *     Session::setflash('Invalid security token', 'danger');
     *     goback();
     *     return;
     * }
     */
    public static function checkCsrf(): bool
    {
        // Both session and POST tokens must be present
        if (!isset($_SESSION['csrf']) || !isset($_POST['csrf'])) {
            return false;
        }

        // Use timing-safe comparison to prevent timing attacks
        // Regular comparison (==) could leak information through timing differences
        if (!hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
            return false;
        }

        return true;
    }
}

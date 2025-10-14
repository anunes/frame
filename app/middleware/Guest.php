<?php

namespace app\middleware;

use app\core\Session;

/**
 * Guest Middleware
 *
 * Ensures the user is NOT authenticated.
 * Redirects authenticated users away from guest-only pages (like login/register).
 */
class Guest extends Middleware
{
    /**
     * Handle guest check
     *
     * @return bool
     */
    public function handle(): bool
    {
        if (Session::loggedIn()) {
            redirect('/');
            exit;
        }

        return true;
    }
}

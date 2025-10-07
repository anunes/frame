<?php

namespace app\middleware;

use app\core\Session;

/**
 * Auth Middleware
 *
 * Ensures the user is authenticated before accessing a route.
 * Redirects to login page if not authenticated.
 */
class Auth extends Middleware
{
    /**
     * Handle authentication check
     *
     * @return bool
     */
    public function handle(): bool
    {
        if (!Session::loggedIn()) {
            Session::setflash('Please log in to access this page.', 'warning');
            redirect('/login');
            exit;
        }

        return true;
    }
}

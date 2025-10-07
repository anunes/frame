<?php

namespace app\middleware;

use app\core\Session;

/**
 * Admin Middleware
 *
 * Ensures the user is authenticated AND has admin role.
 * Redirects to home if not admin or not logged in.
 */
class Admin extends Middleware
{
    /**
     * Handle admin check
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

        if (!Session::user()->isAdmin()) {
            Session::setflash('Access denied. Admin privileges required.', 'danger');
            redirect('/');
            exit;
        }

        return true;
    }
}

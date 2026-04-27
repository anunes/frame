<?php

namespace app\middleware;

/**
 * User System Middleware
 *
 * Keeps public account routes unavailable when user-facing account content is
 * hidden by the site settings.
 */
class UserSystem extends Middleware
{
    /**
     * Handle user system availability check.
     */
    public function handle(): bool
    {
        if (\user_content_hidden()) {
            redirect('/');
            exit;
        }

        return true;
    }
}

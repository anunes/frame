<?php

namespace app\middleware;

/**
 * Base Middleware Class
 *
 * All middleware should extend this class and implement the handle() method.
 */
abstract class Middleware
{
    /**
     * Handle the request
     *
     * @return bool Return false to stop request execution
     */
    abstract public function handle(): bool;

    /**
     * Execute middleware
     *
     * @return bool
     */
    public function __invoke(): bool
    {
        return $this->handle();
    }
}

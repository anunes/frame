<?php

namespace app\console;

use app\core\Router;

class RouteList
{
    /**
     * Handle the command
     */
    public function handle(array $arguments): void
    {
        echo "\033[33mRegistered Routes:\033[0m\n\n";

        $router = require APP_PATH . '/routes.php';

        if (!$router instanceof Router) {
            echo "\033[31mError: Router bootstrap failed.\033[0m\n";
            exit(1);
        }

        $routes = $router->getRoutes();

        if (empty($routes)) {
            echo "\033[33mNo routes registered.\033[0m\n";
            return;
        }

        // Calculate column widths
        $maxMethodWidth = 6;
        $maxUriWidth = 4;
        $maxActionWidth = 6;
        $maxNameWidth = 4;

        foreach ($routes as $route) {
            $maxMethodWidth = max($maxMethodWidth, strlen($route['method']));
            $maxUriWidth = max($maxUriWidth, strlen($route['uri']));

            $action = $this->formatAction($route['action']);
            $maxActionWidth = max($maxActionWidth, strlen($action));

            $name = $route['name'] ?? '';
            $maxNameWidth = max($maxNameWidth, strlen($name));
        }

        // Print header
        echo $this->colorize('green',
            str_pad('METHOD', $maxMethodWidth + 2) .
            str_pad('URI', $maxUriWidth + 2) .
            str_pad('ACTION', $maxActionWidth + 2) .
            str_pad('NAME', $maxNameWidth + 2)
        );
        echo "\n";

        echo str_repeat('─', $maxMethodWidth + $maxUriWidth + $maxActionWidth + $maxNameWidth + 8) . "\n";

        // Print routes
        foreach ($routes as $route) {
            $method = $route['method'];
            $uri = $route['uri'];
            $action = $this->formatAction($route['action']);
            $name = $route['name'] ?? '';

            // Color code methods
            $coloredMethod = $this->colorizeMethod($method);

            echo sprintf(
                "%s  %s  %s  %s\n",
                str_pad($coloredMethod, $maxMethodWidth + 11), // +11 for ANSI color codes
                str_pad($uri, $maxUriWidth),
                str_pad($action, $maxActionWidth),
                str_pad($name, $maxNameWidth)
            );
        }

        echo "\n\033[32mTotal routes: " . count($routes) . "\033[0m\n";
    }

    /**
     * Format action to readable string
     */
    private function formatAction(callable|array $action): string
    {
        if (is_array($action)) {
            [$controller, $method] = $action;

            // Shorten controller namespace for display
            $shortController = str_replace('app\\controllers\\', '', $controller);
            $shortController = preg_replace('/^files\\\\([^\\\\]+)\\\\controllers\\\\/', '$1::', $shortController) ?? $shortController;

            return "{$shortController}@{$method}";
        }

        return 'Closure';
    }

    /**
     * Colorize method based on HTTP verb
     */
    private function colorizeMethod(string $method): string
    {
        return match($method) {
            'GET' => "\033[36m{$method}\033[0m",     // Cyan
            'POST' => "\033[33m{$method}\033[0m",    // Yellow
            'PUT' => "\033[35m{$method}\033[0m",     // Magenta
            'DELETE' => "\033[31m{$method}\033[0m",  // Red
            default => $method
        };
    }

    /**
     * Colorize text
     */
    private function colorize(string $color, string $text): string
    {
        $colors = [
            'green' => '32',
            'yellow' => '33',
            'blue' => '34',
            'cyan' => '36',
        ];

        $code = $colors[$color] ?? '0';
        return "\033[{$code}m{$text}\033[0m";
    }
}

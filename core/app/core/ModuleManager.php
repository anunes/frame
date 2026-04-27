<?php

namespace app\core;

class ModuleManager
{
    private static bool $autoloadRegistered = false;

    public static function filesPath(string $path = ''): string
    {
        $base = BASE_PATH . '/files';
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }

    public static function config(): array
    {
        $path = APP_PATH . '/config/modules.php';
        if (!is_file($path)) {
            return ['enabled' => [], 'start' => ''];
        }

        $config = require $path;
        return is_array($config) ? $config : ['enabled' => [], 'start' => ''];
    }

    public static function modules(): array
    {
        $config = self::config();
        $enabled = $config['enabled'] ?? [];

        if (is_array($enabled) && !empty($enabled)) {
            return array_values(array_filter(array_map('strval', $enabled), [self::class, 'isValidName']));
        }

        $filesPath = self::filesPath();
        if (!is_dir($filesPath)) {
            return [];
        }

        $modules = [];
        foreach (scandir($filesPath) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (is_dir($filesPath . '/' . $entry) && self::isValidName($entry)) {
                $modules[] = $entry;
            }
        }

        sort($modules);
        return $modules;
    }

    public static function startModule(): string
    {
        $start = (string) (self::config()['start'] ?? '');
        return self::isValidName($start) ? $start : '';
    }

    public static function isStarting(string $module): bool
    {
        return $module !== '' && $module === self::startModule();
    }

    public static function registerAutoloader(): void
    {
        if (self::$autoloadRegistered) {
            return;
        }

        spl_autoload_register(function (string $class): void {
            $prefix = 'files\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $path = self::filesPath(str_replace('\\', '/', $relative) . '.php');

            if (is_file($path)) {
                require_once $path;
            }
        });

        self::$autoloadRegistered = true;
    }

    public static function registerViewNamespaces(\PhpTemplate $template): void
    {
        foreach (self::modules() as $module) {
            $viewsPath = self::filesPath($module . '/views');
            if (is_dir($viewsPath)) {
                $template->addNamespace($module, $viewsPath);
            }
        }
    }

    public static function loadRoutes(Router $router): void
    {
        self::registerAutoloader();

        foreach (self::orderedModules() as $module) {
            $routeFile = self::filesPath($module . '/routes/web.php');
            if (is_file($routeFile)) {
                require $routeFile;
            }
        }
    }

    private static function orderedModules(): array
    {
        $modules = self::modules();
        $start = self::startModule();

        if ($start === '' || !in_array($start, $modules, true)) {
            return $modules;
        }

        return array_values(array_unique(array_merge([$start], $modules)));
    }

    private static function isValidName(string $name): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_]*$/', $name);
    }
}

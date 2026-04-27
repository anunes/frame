<?php

/**
 * User content module configuration.
 *
 * Modules live in files/<module-name>/ and can contain controllers, models,
 * views, and routes. Set APP_MODULES to a comma-separated list to load only
 * selected modules, or leave it empty to auto-load every valid module folder.
 *
 * APP_START_MODULE may point to one module that should own the "/" route.
 */
$enabledModules = $_ENV['APP_MODULES'] ?? getenv('APP_MODULES') ?: '';
$startModule = $_ENV['APP_START_MODULE'] ?? getenv('APP_START_MODULE') ?: '';

return [
    'enabled' => array_values(array_filter(array_map('trim', explode(',', $enabledModules)))),
    'start' => trim($startModule),
];

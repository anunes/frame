<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/routes.php';

echo "<h2>Registered Routes</h2>";
echo "<pre>";

// Use reflection to access protected routes property
$reflection = new ReflectionClass($router);
$property = $reflection->getProperty('routes');
$property->setAccessible(true);
$routes = $property->getValue($router);

foreach ($routes as $route) {
    echo sprintf(
        "%s %s -> %s (middleware: %d)\n",
        $route['method'],
        $route['uri'],
        is_array($route['action']) ? $route['action'][0] . '::' . $route['action'][1] : 'Closure',
        count($route['middleware'])
    );
}

echo "</pre>";

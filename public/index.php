<?php

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/routes.php';

// Make router globally available for route() helper
$GLOBALS['router'] = $router;

// Dispatch the request
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

error_log("=== INDEX.PHP ===");
error_log("Method: $method");
error_log("REQUEST_URI from \$_SERVER: $uri");
error_log("HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set'));
error_log("SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set'));

$router->dispatch($method, $uri);

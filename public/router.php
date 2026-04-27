<?php

$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$candidate = __DIR__ . $requestPath;

if ($requestPath !== '/' && is_file($candidate)) {
    return false;
}

require __DIR__ . '/index.php';

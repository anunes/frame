<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// Hide deprecation warnings from older Laravel packages on PHP 8.1+
error_reporting(E_ALL & ~E_DEPRECATED);

//date_default_timezone_set('Europe/Lisbon');
date_default_timezone_set('Atlantic/Azores');
setlocale(LC_ALL, ['pt_PT.utf8', 'pt_PT@euro', 'pt_UTF8', 'pt_PT', 'portuguese']);
ini_set('intl.default_locale', 'pt_PT');

/* Secure session initialization */
if (session_status() === PHP_SESSION_NONE) {
    // For local development with .test domain, don't use secure flag
    $secure = false; // Changed from checking HTTPS

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '', // Empty for current domain
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();

    // Debug session
    error_log("=== BOOTSTRAP SESSION DEBUG ===");
    error_log("Session ID: " . session_id());
    error_log("Session status: " . session_status());
    error_log("Cookie params: " . print_r(session_get_cookie_params(), true));
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
    error_log("Cookies received: " . print_r($_COOKIE, true));
}

/* Basic security headers (can be extended per route later) */
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    // Minimal CSP baseline - adjust as features grow
    // Updated CSP: allow Google Maps iframe embedding while keeping other sources restricted.
    header("Content-Security-Policy: default-src 'self'; frame-src 'self' https://www.google.com https://maps.google.com; child-src 'self' https://www.google.com https://maps.google.com; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self' 'unsafe-inline';");
}

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env (if present) before loading config
try {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    // safeLoad won't throw if .env is missing
    $dotenv->safeLoad();
} catch (\Throwable $e) {
    // proceed without .env
}

//require config file
require_once __DIR__ . '/config/config.php';

// Email validation removed
require_once __DIR__ . '/helpers/functions.php';
// forms.php removed - using component-based system instead

// Create cache directory if it doesn't exist
$viewsPath = __DIR__ . '/views';
$cachePath = __DIR__ . '/../storage/cache/views';

if (!file_exists($cachePath)) {
    mkdir($cachePath, 0755, true);
}

// Initialize Blade templating engine
use Jenssegers\Blade\Blade;

// Create Blade instance (pass null for container to use default)
$blade = new Blade($viewsPath, $cachePath);

// Make Blade globally accessible
$GLOBALS['blade'] = $blade;

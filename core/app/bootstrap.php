<?php

/**
 * Bootstrap File
 *
 * This file initializes the application, sets up the environment,
 * configures security settings, and loads essential dependencies.
 * It runs before every request.
 */

// ============================================
// DEPENDENCY LOADING
// ============================================
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// ============================================
// ENVIRONMENT VARIABLES
// ============================================
try {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
    $dotenv->safeLoad();
} catch (\Throwable $e) {
    // Fall back to OS-level environment variables if .env cannot be loaded.
}

// ============================================
// APPLICATION CONFIGURATION
// ============================================
if (!defined('DEBUG')) {
    define('DEBUG', filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL));
}

require_once __DIR__ . '/config/config.php';

// ============================================
// ERROR REPORTING CONFIGURATION
// ============================================
ini_set('display_errors', DEBUG ? '1' : '0');
ini_set('display_startup_errors', DEBUG ? '1' : '0');
error_reporting(DEBUG ? E_ALL : (E_ALL & ~E_DEPRECATED & ~E_NOTICE));

// ============================================
// TIMEZONE AND LOCALE CONFIGURATION
// ============================================
$timezone = APP_TIMEZONE;
if (!@date_default_timezone_set($timezone)) {
    date_default_timezone_set('UTC');
    error_log("Invalid timezone '{$timezone}' configured. Falling back to UTC.");
}

$locale = APP_LOCALE;
$localeVariants = [
    'en_US' => ['en_US.utf8', 'en_US.UTF-8', 'en_US', 'english'],
    'pt_PT' => ['pt_PT.utf8', 'pt_PT@euro', 'pt_UTF8', 'pt_PT', 'portuguese'],
    'es_ES' => ['es_ES.utf8', 'es_ES@euro', 'es_ES', 'spanish'],
    'fr_FR' => ['fr_FR.utf8', 'fr_FR@euro', 'fr_FR', 'french'],
    'de_DE' => ['de_DE.utf8', 'de_DE@euro', 'de_DE', 'german'],
    'it_IT' => ['it_IT.utf8', 'it_IT@euro', 'it_IT', 'italian'],
];
$localeFormats = $localeVariants[$locale] ?? [$locale . '.utf8', $locale . '.UTF-8', $locale];
setlocale(LC_ALL, $localeFormats);
ini_set('intl.default_locale', $locale);

// ============================================
// SECURE SESSION INITIALIZATION
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// ============================================
// SECURITY HEADERS
// ============================================
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header(
        "Content-Security-Policy: " .
            "default-src 'self'; " .
            "frame-src 'self'; " .
            "child-src 'self'; " .
            "img-src 'self' data: https:; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
            "font-src 'self' https://fonts.gstatic.com; " .
            "script-src 'self' 'unsafe-inline';"
    );
}

// Load global helper functions (view(), redirect(), etc.)
require_once __DIR__ . '/helpers/functions.php';

\app\core\ModuleManager::registerAutoloader();

// ============================================
// TEMPLATE ENGINE INITIALIZATION
// ============================================
$viewsPath = __DIR__ . '/views';
$cachePath = __DIR__ . '/../storage/cache/views';

if (!file_exists($cachePath)) {
    mkdir($cachePath, 0755, true);
}

require_once __DIR__ . '/core/PhpTemplate.php';

$phpTemplate = new PhpTemplate($viewsPath, $cachePath, true);
\app\core\ModuleManager::registerViewNamespaces($phpTemplate);
PhpTemplate::setGlobalInstance($phpTemplate);
$GLOBALS['phpTemplate'] = $phpTemplate;

<?php

/**
 * Bootstrap File
 *
 * This file initializes the application, sets up the environment,
 * configures security settings, and loads essential dependencies.
 * It runs before every request.
 */

// ============================================
// ERROR REPORTING CONFIGURATION
// ============================================
// Note: In production, set display_errors to 0 and log errors instead
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Hide deprecation warnings from older packages on PHP 8.1+
// This prevents notices from legacy dependencies while still showing errors
error_reporting(E_ALL & ~E_DEPRECATED);

// ============================================
// TIMEZONE AND LOCALE CONFIGURATION
// ============================================
/**
 * Set the default timezone for all date/time functions
 *
 * The timezone is configured in .env via APP_TIMEZONE
 * Falls back to UTC if not set or invalid
 *
 * @see https://www.php.net/manual/en/timezones.php for available timezones
 */
$timezone = defined('APP_TIMEZONE') ? APP_TIMEZONE : 'UTC';
try {
    date_default_timezone_set($timezone);
} catch (Exception $e) {
    // Fallback to UTC if timezone is invalid
    date_default_timezone_set('UTC');
    error_log("Invalid timezone '{$timezone}' configured. Falling back to UTC.");
}

/**
 * Set locale for formatting (dates, currency, numbers)
 *
 * The locale is configured in .env via APP_LOCALE
 * Tries multiple locale formats for better cross-platform compatibility
 * Falls back to English (en_US) if the configured locale is not available
 */
$locale = defined('APP_LOCALE') ? APP_LOCALE : 'en_US';

// Map common locale codes to their various system formats
$localeVariants = [
    'en_US' => ['en_US.utf8', 'en_US.UTF-8', 'en_US', 'english'],
    'pt_PT' => ['pt_PT.utf8', 'pt_PT@euro', 'pt_UTF8', 'pt_PT', 'portuguese'],
    'es_ES' => ['es_ES.utf8', 'es_ES@euro', 'es_ES', 'spanish'],
    'fr_FR' => ['fr_FR.utf8', 'fr_FR@euro', 'fr_FR', 'french'],
    'de_DE' => ['de_DE.utf8', 'de_DE@euro', 'de_DE', 'german'],
    'it_IT' => ['it_IT.utf8', 'it_IT@euro', 'it_IT', 'italian'],
];

// Use predefined variants if available, otherwise try the locale as-is
$localeFormats = $localeVariants[$locale] ?? [$locale . '.utf8', $locale . '.UTF-8', $locale];
setlocale(LC_ALL, $localeFormats);

// Set default locale for PHP internationalization functions (intl extension)
ini_set('intl.default_locale', $locale);

// ============================================
// SECURE SESSION INITIALIZATION
// ============================================
// Only start session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {

    // Determine if we should use the secure flag based on HTTPS availability
    // The secure flag ensures cookies are only sent over HTTPS connections
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    // Configure session cookie parameters for security
    session_set_cookie_params([
        'lifetime' => 0,           // Session cookie (expires when browser closes)
        'path'     => '/',         // Available across entire domain
        'domain'   => '',          // Empty = current domain only
        'secure'   => $secure,     // Only send over HTTPS if available
        'httponly' => true,        // Prevent JavaScript access (XSS protection)
        'samesite' => 'Lax'       // CSRF protection (Lax allows top-level navigation)
    ]);

    // Start the session with configured parameters
    session_start();

    // ============================================
    // DEBUG SESSION LOGGING (Development Only)
    // ============================================
    // This debug block only runs when DEBUG constant is true
    // Remove or disable in production to avoid logging sensitive data
    if (defined('DEBUG') && DEBUG === true) {
        error_log("=== BOOTSTRAP SESSION DEBUG ===");
        error_log("Session ID: " . session_id());
        error_log("Session status: " . session_status());
        error_log("Cookie params: " . print_r(session_get_cookie_params(), true));
        error_log("Session data: " . print_r($_SESSION, true));
        error_log("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
        error_log("Cookies received: " . print_r($_COOKIE, true));
    }
}

// ============================================
// SECURITY HEADERS
// ============================================
// Set HTTP security headers if not already sent
// These headers protect against common web vulnerabilities
if (!headers_sent()) {

    // Prevent clickjacking by only allowing same-origin framing
    header('X-Frame-Options: SAMEORIGIN');

    // Prevent MIME-type sniffing (force browser to respect Content-Type)
    header('X-Content-Type-Options: nosniff');

    // Control how much referrer information is sent with requests
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Disable potentially dangerous browser features
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    // Content Security Policy - restricts what resources can be loaded
    // This is a baseline policy that should be adjusted based on needs
    header(
        "Content-Security-Policy: " .
            "default-src 'self'; " .           // Only load resources from same origin by default
            "frame-src 'self'; " .             // Only allow iframes from same origin
            "child-src 'self'; " .             // Only allow web workers from same origin
            "img-src 'self' data: https:; " .  // Allow images from same origin, data URIs, and HTTPS
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .  // Allow inline styles + Google Fonts
            "font-src 'self' https://fonts.gstatic.com; " .  // Allow fonts from same origin + Google
            "script-src 'self' 'unsafe-inline';"  // Allow scripts from same origin + inline scripts
    );
}

// ============================================
// DEPENDENCY LOADING
// ============================================
// Load Composer autoloader for third-party dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// ============================================
// ENVIRONMENT VARIABLES
// ============================================
// Load environment variables from .env file (if it exists)
// This allows configuration without hardcoding sensitive values
try {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));

    // safeLoad() won't throw an exception if .env is missing
    // This is useful for production where env vars might be set at OS level
    $dotenv->safeLoad();
} catch (\Throwable $e) {
    // Silently continue if .env loading fails
    // Application will use default values or OS-level environment variables
}

// ============================================
// APPLICATION CONFIGURATION
// ============================================
// Define DEBUG constant if not already defined
if (!defined('DEBUG')) {
    define('DEBUG', false);
}

// Load application configuration (database, mail, etc.)
require_once __DIR__ . '/config/config.php';

// Load global helper functions (view(), redirect(), etc.)
require_once __DIR__ . '/helpers/functions.php';

// ============================================
// TEMPLATE ENGINE INITIALIZATION
// ============================================
// Set up paths for template files and compiled cache
$viewsPath = __DIR__ . '/views';
$cachePath = __DIR__ . '/../storage/cache/views';

// Create cache directory if it doesn't exist
// This directory stores compiled PHP templates for better performance
if (!file_exists($cachePath)) {
    mkdir($cachePath, 0755, true);
}

// Load the PhpTemplate engine class
require_once __DIR__ . '/core/PhpTemplate.php';

// Create PhpTemplate instance with caching enabled
// Parameters: (templates directory, cache directory, enable caching)
$phpTemplate = new PhpTemplate($viewsPath, $cachePath, true);

// Make instance available globally for the view() helper function
PhpTemplate::setGlobalInstance($phpTemplate);

// Also store in $GLOBALS for backward compatibility
$GLOBALS['phpTemplate'] = $phpTemplate;

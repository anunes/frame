<?php


function redirect($url, $code = 302): void
{
    if (strncmp('cli', PHP_SAPI, 3) !== 0) {
        if (headers_sent() !== true) {
            if (strlen(session_id()) > 0) {
                // Write session data and close to avoid locks
                session_write_close();
            }

            if (strncmp('cgi', PHP_SAPI, 3) === 0) {
                header(sprintf('Status: %03u', $code), true, $code);
            }
            header('Location: ' . $url, true, preg_match('~^30[1237]$~', $code) > 0 ? $code : 302);
            exit();
        } else {
            // Fallback when headers already sent
            echo '<script>window.location.href = ' . json_encode($url) . ';</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></noscript>';
            exit();
        }
    }
}

function goback(): void
{
    $target = $_SERVER['HTTP_REFERER'] ?? '/';
    if (strncmp('cli', PHP_SAPI, 3) !== 0) {
        if (!headers_sent()) {
            header('Location: ' . $target);
            exit;
        }
        // Fallback when headers already sent
        echo '<script>window.location.href = ' . json_encode($target) . ';</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"></noscript>';
        exit;
    }
}

function sanitize($data): string
{
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data);
}

function dd($value): void
{
    echo '
  <pre>';
    var_dump($value);
    echo '</pre>';
    die();
}

function date_pt($dateTime): bool|string
{
    if ($dateTime === null) {
        return false;
    }

    $dateTimeObj = new DateTime($dateTime, new DateTimeZone('Atlantic/Azores'));
    return IntlDateFormatter::formatObject(
        $dateTimeObj,
        'dd-MMMM-y',
        'pt'
    );
}
/*
function date_pt($dateTime): bool|string
{
    $dateTimeObj = new DateTime($dateTime, new DateTimeZone('Atlantic/Azores'));
    return $dateFormatted =
        IntlDateFormatter::formatObject(
            $dateTimeObj,
            'dd-MMMM-y',
            'pt'
        );
    //how to use:
    //echo date_pt('2022-11-27 02:05:05');
}*/

function isEmail($email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function match_pass($password, $repass): bool
{
    if (!$password == $repass) {
        return false;
    }
    return true;
}

function validate_password($password): bool
{
    // Simple validation: minimum 6 characters, allows all lowercase, uppercase, or numbers
    // No special characters required
    if (strlen($password) < 6) {
        return false;
    }

    // Check if it contains only alphanumeric characters (letters and numbers)
    if (!preg_match('@^[a-zA-Z0-9]+$@', $password)) {
        return false;
    }

    return true;
}
/**
 * Convenience global view helper so controllers can call view('name', [...])
 * Uses the PhpTemplate engine (custom Blade-like templating).
 */
function view($view, $data = []): void
{
    $phpTemplate = PhpTemplate::getGlobalInstance();
    if (!$phpTemplate) {
        throw new \Exception('PhpTemplate is not initialized. Check bootstrap.php');
    }
    echo $phpTemplate->render($view, $data);
}

/**
 * Global helper to generate URLs from named routes.
 */
function route(string $name, array $params = []): string
{
    if (!isset($GLOBALS['router']) || !$GLOBALS['router'] instanceof \app\core\Router) {
        throw new \Exception('Router is not initialized.');
    }
    return $GLOBALS['router']->route($name, $params);
}
/**
 * CSRF helpers
 */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['_csrf_token'])) {
        try {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            // Fallback
            $_SESSION['_csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): void
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    echo "<input type=\"hidden\" name=\"_csrf\" value=\"{$token}\">";
}
/**
 * Verify CSRF token. Returns true when valid, false otherwise.
 * Optionally rotate the token after verification to prevent replay.
 */
function csrf_verify(?string $token = null, bool $rotate = true): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if ($token === null) {
        $token = $_POST['_csrf'] ?? null;
    }

    if (empty($token) || empty($_SESSION['_csrf_token'])) {
        return false;
    }

    $valid = hash_equals($_SESSION['_csrf_token'], (string)$token);
    if ($valid && $rotate) {
        // Rotate token on successful verification
        try {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        } catch (\Exception $e) {
            $_SESSION['_csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }

    return $valid;
}
/**
 * Very small Blade-like component system.
 * Usage in views: <x-contact-form /> or <x-contact-form attr="value" />
 * Maps tag name `contact-form` to file:
 *   - app/views/components/contact-form.an.php (preferred)
 *   - app/views/components/contact-form.php (fallback)
 * Attributes are exposed to the component as variables via extract($attrs).
 */
function process_components(string $html, array $shared = []): string
{
    // Regex for self-closing component tags: <x-name .../>
    $pattern = '/<x-([a-zA-Z0-9_-]+)([^>]*)\/>/m';

    // Replace callback renders each component and substitutes markup
    $html = preg_replace_callback($pattern, function ($matches) use ($shared) {
        $name = $matches[1];
        $attrString = trim($matches[2] ?? '');
        $attrs = parse_component_attributes($attrString);
        return render_component($name, array_merge($shared, $attrs));
    }, $html);

    return $html;
}
/**
 * Parse attributes from the component tag into an associative array.
 * Supports key="value", key='value', and boolean attributes.
 */
function parse_component_attributes(string $attrString): array
{
    $attrs = [];
    if ($attrString === '') {
        return $attrs;
    }
    $regex = '/\s+([a-zA-Z_][a-zA-Z0-9_-]*)\s*(=\s*(?:\"([^\"]*)\"|\'([^\']*)\'|([^\s\"\'=<>\/`]+)))?/';
    if (preg_match_all($regex, $attrString, $m, PREG_SET_ORDER)) {
        foreach ($m as $match) {
            $key = $match[1];
            if (!empty($match[4])) {
                $val = $match[4];
            } elseif (!empty($match[5])) {
                $val = $match[5];
            } elseif (!empty($match[6])) {
                $val = $match[6];
            } else {
                // boolean attr
                $val = true;
            }
            $attrs[$key] = $val;
        }
    }
    return $attrs;
}
/**
 * Render a component file with the provided variables.
 */
function render_component(string $name, array $vars = []): string
{
    $base = BASE_PATH . '/views/components/';
    $candidates = [
        $base . $name . '.view.php',
        $base . $name . '.php',
    ];

    $file = null;
    foreach ($candidates as $path) {
        if (file_exists($path)) {
            $file = $path;
            break;
        }
    }

    if ($file === null) {
        // Component not found; return an HTML comment so it fails softly
        return '<!-- component ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ' not found -->';
    }

    // Make vars available to component
    extract($vars, EXTR_SKIP);
    ob_start();
    include $file;
    return ob_get_clean();
}

/**
 * Authentication helper functions
 */

/**
 * Check if user is logged in
 */
function is_logged_in(): bool
{
    return \app\core\Session::loggedIn();
}

/**
 * Check if user is admin
 */
function is_admin(): bool
{
    return \app\core\Session::isAdmin();
}

/**
 * Get current logged in user ID
 */
function current_user_id(): ?int
{
    return $_SESSION['id'] ?? null;
}

/**
 * Get current logged in user name
 */
function current_user_name(): ?string
{
    return $_SESSION['name'] ?? null;
}

/**
 * Get current logged in user email
 */
function current_user_email(): ?string
{
    return $_SESSION['email'] ?? null;
}

/**
 * Get current user object from database
 */
function current_user(): ?object
{
    if (!is_logged_in()) {
        return null;
    }

    $userModel = new \app\models\User();
    return $userModel->findById(current_user_id());
}

/**
 * Redirect to login page if not authenticated
 */
function require_auth(): void
{
    if (!is_logged_in()) {
        \app\core\Session::setflash('Please log in to access this page.', 'warning');
        redirect(route('login'));
        exit;
    }
}

/**
 * Redirect to home page if already authenticated
 */
function require_guest(): void
{
    if (is_logged_in()) {
        redirect(route('home'));
        exit;
    }
}

/**
 * Redirect if not admin
 */
function require_admin(): void
{
    require_auth();

    if (!is_admin()) {
        \app\core\Session::setflash('Access denied. Admin privileges required.', 'danger');
        redirect(route('home'));
        exit;
    }
}

/**
 * Get the current page/route for navigation highlighting
 */
function current_page(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);

    // Remove leading slash and get the first segment
    $segments = explode('/', trim($path, '/'));
    $page = $segments[0] ?? '';

    // Map empty, root, or index to home
    if ($page === '' || $page === 'index.php' || $path === '/') {
        return 'home';
    }

    return $page;
}
/**
 * Check if the current page matches the given page name
 */
function is_current_page(string $page): bool
{
    return current_page() === $page;
}

/**
 * Get the active CSS class if the current page matches
 */
function nav_active(string $page, string $class = 'active'): string
{
    return is_current_page($page) ? $class : '';
}

/**
 * Note: env() function is provided by Illuminate\Support (Laravel)
 * which is included with the Blade templating engine.
 * No need to redeclare it here.
 */

// -----------------------------
// Localization (i18n) helpers
// -----------------------------
if (!function_exists('detect_locale')) {
    function detect_locale(array $supported = ['en', 'pt'], string $fallback = 'en'): string
    {
        static $resolved = null;
        if ($resolved !== null) {
            return $resolved;
        }
        // Site-wide locale from settings table (admin controlled)
        try {
            $setting = new \app\models\Settings();
            $stored = strtolower(trim($setting->get('site_locale', '')));
            if ($stored && in_array($stored, $supported, true)) {
                return $resolved = $stored;
            }
        } catch (\Throwable $e) {
            // ignore & fallback
        }
        return $resolved = $fallback;
    }
}

if (!function_exists('lang_path')) {
    function lang_path(string $locale): string
    {
        return __DIR__ . '/../lang/' . $locale . '.php';
    }
}

if (!function_exists('load_locale')) {
    function load_locale(string $locale): array
    {
        static $cache = [];
        if (isset($cache[$locale])) return $cache[$locale];
        $file = lang_path($locale);
        if (is_file($file)) {
            $data = require $file;
            if (is_array($data)) {
                return $cache[$locale] = $data;
            }
        }
        return $cache[$locale] = [];
    }
}

if (!function_exists('__')) {
    /**
     * Translate a key with optional locale override and default.
     * Supports simple token replacement for :name, :code via $default (if array provided) or later manual str_replace.
     * Logs missing keys once per request to PHP error_log for visibility.
     */
    function __(string $key, ?string $locale = null, $default = null): string
    {
        static $missing = [];
        $loc = $locale ?? detect_locale();
        $lines = load_locale($loc);
        if (array_key_exists($key, $lines)) {
            return $lines[$key];
        }
        if ($loc !== 'en') {
            $fallbackLines = load_locale('en');
            if (array_key_exists($key, $fallbackLines)) {
                return $fallbackLines[$key];
            }
        }
        if (!isset($missing[$key])) {
            $missing[$key] = true;
            error_log('[i18n] Missing translation key: ' . $key . ' (locale=' . $loc . ')');
        }
        if (is_array($default)) {
            // If caller passed array with 'text' plus replacements e.g. __('x', null, ['text'=>'Hello :name','name'=>'John'])
            $text = $default['text'] ?? $key;
            foreach ($default as $k => $v) {
                if ($k === 'text') continue;
                $text = str_replace(':' . $k, (string)$v, $text);
            }
            return $text;
        }
        return $default ?? $key;
    }
}

if (!function_exists('current_locale')) {
    function current_locale(): string
    {
        return detect_locale();
    }
}

// -----------------------------
// Validation helper
// -----------------------------
if (!function_exists('validate')) {
    /**
     * Basic validator.
     * @param array $data Input array (e.g., $_POST)
     * @param array $rules ['field' => ['required','email','min:8']]
     * @return array [bool valid, array errors]
     */
    function validate(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $fieldRules) {
            $value = trim((string)($data[$field] ?? ''));
            foreach ($fieldRules as $ruleRaw) {
                $parts = explode(':', $ruleRaw, 2);
                $rule = $parts[0];
                $param = $parts[1] ?? null;
                if ($rule === 'required') {
                    if ($value === '') {
                        $errors[$field][] = __('validation.required');
                    }
                } elseif ($rule === 'email') {
                    if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = __('validation.email');
                    }
                } elseif ($rule === 'min') {
                    $len = (int)$param;
                    if ($value !== '' && mb_strlen($value) < $len) {
                        $errors[$field][] = str_replace(':min', (string)$len, __('validation.min'));
                    }
                }
            }
        }
        return [empty($errors), $errors];
    }
}

// -----------------------------
// Render View Helper (Legacy - kept for backward compatibility)
// -----------------------------
if (!function_exists('render_view')) {
    /**
     * Render a PhpTemplate view
     *
     * @param string $view View name (slash notation: 'auth/login' maps to auth/login.view.php)
     * @param array $data Data to pass to the view
     * @return string Rendered HTML
     */
    function render_view(string $view, array $data = []): string
    {
        $phpTemplate = PhpTemplate::getGlobalInstance();
        if (!$phpTemplate) {
            throw new \Exception('PhpTemplate is not initialized. Check bootstrap.php');
        }

        // Convert dot notation to slash notation if needed (for backward compatibility)
        $view = str_replace('.', '/', $view);

        return $phpTemplate->render($view, $data);
    }
}

if (!function_exists('registration_enabled')) {
    /**
     * Check if user registration is enabled
     *
     * @return bool
     */
    function registration_enabled(): bool
    {
        static $enabled = null;

        if ($enabled === null) {
            $settings = new \app\models\Settings();
            $enabled = $settings->isRegistrationEnabled();
        }

        return $enabled;
    }
}

if (!function_exists('site_logo')) {
    /**
     * Get site logo path
     *
     * @return string
     */
    function site_logo(): string
    {
        static $logo = null;

        if ($logo === null) {
            $settings = new \app\models\Settings();
            $logo = $settings->getLogo();
        }

        return $logo;
    }
}

if (!function_exists('generate_random_password')) {
    /**
     * Generate a random password with letters and numbers only
     *
     * @param int $length Password length (default 6)
     * @return string
     */
    function generate_random_password(int $length = 6): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        $max = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $max)];
        }

        return $password;
    }
}

if (!function_exists('copyright_text')) {
    /**
     * Get copyright text with {year} replaced by current year
     *
     * @return string
     */
    function copyright_text(): string
    {
        static $copyrightText = null;

        if ($copyrightText === null) {
            $settings = new \app\models\Settings();
            $copyrightText = $settings->getCopyrightText();

            // Replace {year} placeholder with current year
            $copyrightText = str_replace('{year}', date('Y'), $copyrightText);
        }

        return $copyrightText;
    }
}

if (!function_exists('navbar_items')) {
    /**
     * Get navbar items filtered by user permissions
     *
     * @param string $section Section name (main, user, guest)
     * @return array Filtered navbar items
     */
    function navbar_items(string $section = 'main'): array
    {
        static $config = null;

        // Load navbar configuration
        if ($config === null) {
            $configPath = BASE_PATH . '/app/config/navbar.php';
            $config = file_exists($configPath) ? require $configPath : [];
        }

        // Get items for the specified section
        $items = $config[$section] ?? [];
        $filtered = [];

        foreach ($items as $item) {
            // Check if item should be shown
            if (!navbar_item_visible($item)) {
                continue;
            }

            $filtered[] = $item;
        }

        return $filtered;
    }
}

if (!function_exists('navbar_item_visible')) {
    /**
     * Check if a navbar item should be visible based on current user state
     *
     * @param array $item Navbar item configuration
     * @return bool
     */
    function navbar_item_visible(array $item): bool
    {
        // Check authentication requirements
        if (isset($item['auth']) && $item['auth'] === true) {
            if (!is_logged_in()) {
                return false;
            }
        }

        // Check guest-only requirement
        if (isset($item['guest']) && $item['guest'] === true) {
            if (is_logged_in()) {
                return false;
            }
        }

        // Check admin requirement
        if (isset($item['admin']) && $item['admin'] === true) {
            if (!is_admin()) {
                return false;
            }
        }

        // Check conditional visibility
        if (isset($item['show_if'])) {
            $condition = $item['show_if'];

            // Handle function-based conditions
            if ($condition === 'registration_enabled' && !registration_enabled()) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('navbar_item_active')) {
    /**
     * Check if navbar item is active
     *
     * @param array $item Navbar item configuration
     * @param string $class CSS class to return if active (default: 'active')
     * @return string Active class or empty string
     */
    function navbar_item_active(array $item, string $class = 'active'): string
    {
        if (!isset($item['active'])) {
            return '';
        }

        return is_current_page($item['active']) ? $class : '';
    }
}

// ============================================
// DATE & TIME FORMATTING FUNCTIONS
// ============================================

if (!function_exists('format_date')) {
    /**
     * Format a date using the application's configured timezone and locale
     *
     * This function takes any date/time input and formats it according to the
     * application's APP_TIMEZONE and APP_LOCALE settings. It uses predefined
     * format presets for consistency across the application.
     *
     * @param string|int|DateTime|null $date Date to format (string, timestamp, DateTime object, or null for now)
     * @param string $format Format preset: 'short', 'medium', 'long', 'full', 'datetime', 'time', or custom format
     * @param string|null $timezone Optional timezone override (uses APP_TIMEZONE if not specified)
     * @return string Formatted date string
     *
     * @example
     * // Using presets
     * format_date('2024-12-31 15:30:00', 'short');     // "12/31/24" or "31/12/24" depending on locale
     * format_date('2024-12-31 15:30:00', 'medium');    // "Dec 31, 2024" or "31 déc. 2024"
     * format_date('2024-12-31 15:30:00', 'long');      // "December 31, 2024" or "31 décembre 2024"
     * format_date('2024-12-31 15:30:00', 'full');      // "Tuesday, December 31, 2024"
     * format_date('2024-12-31 15:30:00', 'datetime');  // "Dec 31, 2024 3:30 PM"
     * format_date('2024-12-31 15:30:00', 'time');      // "3:30 PM" or "15:30"
     *
     * // Using custom format
     * format_date('2024-12-31', 'Y-m-d H:i:s');        // "2024-12-31 00:00:00"
     * format_date('2024-12-31', 'l, F j, Y');          // "Tuesday, December 31, 2024"
     *
     * // Using current time
     * format_date(null, 'long');                       // Formats current date/time
     * format_date(time(), 'datetime');                 // Formats current timestamp
     */
    function format_date($date = null, string $format = 'medium', ?string $timezone = null): string
    {
        // Get timezone - use override, APP_TIMEZONE constant, or default to UTC
        $tz = $timezone ?? (defined('APP_TIMEZONE') ? APP_TIMEZONE : 'UTC');

        try {
            $dateTimeZone = new DateTimeZone($tz);
        } catch (Exception $e) {
            // Invalid timezone, fallback to UTC
            $dateTimeZone = new DateTimeZone('UTC');
        }

        // Handle different input types
        try {
            if ($date === null) {
                // Use current time
                $dateTime = new DateTime('now', $dateTimeZone);
            } elseif ($date instanceof DateTime) {
                // Clone and set timezone
                $dateTime = clone $date;
                $dateTime->setTimezone($dateTimeZone);
            } elseif (is_numeric($date)) {
                // Unix timestamp
                $dateTime = new DateTime('@' . $date);
                $dateTime->setTimezone($dateTimeZone);
            } else {
                // String date
                $dateTime = new DateTime($date);
                $dateTime->setTimezone($dateTimeZone);
            }
        } catch (Exception $e) {
            // Invalid date format, return error message
            return 'Invalid date';
        }

        // Get locale
        $locale = defined('APP_LOCALE') ? APP_LOCALE : 'en_US';

        // Define format presets
        $presets = [
            'short'    => 'n/j/y',              // 12/31/24
            'medium'   => 'M j, Y',             // Dec 31, 2024
            'long'     => 'F j, Y',             // December 31, 2024
            'full'     => 'l, F j, Y',          // Tuesday, December 31, 2024
            'datetime' => 'M j, Y g:i A',       // Dec 31, 2024 3:30 PM
            'time'     => 'g:i A',              // 3:30 PM
            'iso'      => 'Y-m-d\TH:i:sP',      // 2024-12-31T15:30:00-05:00
            'db'       => 'Y-m-d H:i:s',        // 2024-12-31 15:30:00
        ];

        // Use preset if available, otherwise use custom format
        $formatString = $presets[$format] ?? $format;

        return $dateTime->format($formatString);
    }
}

if (!function_exists('format_date_intl')) {
    /**
     * Format a date using PHP's Intl extension for locale-aware formatting
     *
     * This function uses the IntlDateFormatter class for more sophisticated
     * locale-aware date formatting. It respects the application's locale
     * settings and provides native language output.
     *
     * @param string|int|DateTime|null $date Date to format
     * @param string $dateFormat Date format: 'none', 'short', 'medium', 'long', 'full'
     * @param string $timeFormat Time format: 'none', 'short', 'medium', 'long', 'full'
     * @param string|null $locale Locale override (uses APP_LOCALE if not specified)
     * @param string|null $timezone Timezone override (uses APP_TIMEZONE if not specified)
     * @return string Formatted date string
     *
     * @example
     * // Date only (en_US locale)
     * format_date_intl('2024-12-31', 'long', 'none');    // "December 31, 2024"
     *
     * // Date only (pt_PT locale)
     * format_date_intl('2024-12-31', 'long', 'none');    // "31 de dezembro de 2024"
     *
     * // Date and time
     * format_date_intl('2024-12-31 15:30:00', 'medium', 'short');  // "Dec 31, 2024, 3:30 PM"
     *
     * // Full format
     * format_date_intl('2024-12-31', 'full', 'none');    // "Tuesday, December 31, 2024"
     */
    function format_date_intl($date = null, string $dateFormat = 'medium', string $timeFormat = 'none', ?string $locale = null, ?string $timezone = null): string
    {
        // Get locale and timezone
        $locale = $locale ?? (defined('APP_LOCALE') ? APP_LOCALE : 'en_US');
        $tz = $timezone ?? (defined('APP_TIMEZONE') ? APP_TIMEZONE : 'UTC');

        // Map format strings to IntlDateFormatter constants
        $formatMap = [
            'none'   => IntlDateFormatter::NONE,
            'short'  => IntlDateFormatter::SHORT,
            'medium' => IntlDateFormatter::MEDIUM,
            'long'   => IntlDateFormatter::LONG,
            'full'   => IntlDateFormatter::FULL,
        ];

        $dateStyle = $formatMap[$dateFormat] ?? IntlDateFormatter::MEDIUM;
        $timeStyle = $formatMap[$timeFormat] ?? IntlDateFormatter::NONE;

        // Create formatter
        $formatter = new IntlDateFormatter(
            $locale,
            $dateStyle,
            $timeStyle,
            $tz
        );

        // Handle different input types
        try {
            if ($date === null) {
                $timestamp = time();
            } elseif ($date instanceof DateTime) {
                $timestamp = $date->getTimestamp();
            } elseif (is_numeric($date)) {
                $timestamp = (int)$date;
            } else {
                $dateTime = new DateTime($date);
                $timestamp = $dateTime->getTimestamp();
            }
        } catch (Exception $e) {
            return 'Invalid date';
        }

        return $formatter->format($timestamp);
    }
}

if (!function_exists('format_date_relative')) {
    /**
     * Format a date as relative time (e.g., "2 hours ago", "in 3 days")
     *
     * This function calculates the difference between the given date and now,
     * and returns a human-readable relative time string.
     *
     * @param string|int|DateTime $date Date to format
     * @param bool $full Show full precision (default: false for short format)
     * @return string Relative time string
     *
     * @example
     * format_date_relative('2024-12-31 10:00:00');  // "2 hours ago" or "in 2 hours"
     * format_date_relative('-5 minutes');           // "5 minutes ago"
     * format_date_relative('+3 days');              // "in 3 days"
     * format_date_relative('yesterday', true);      // "1 day ago"
     */
    function format_date_relative($date, bool $full = false): string
    {
        // Get timezone
        $tz = defined('APP_TIMEZONE') ? APP_TIMEZONE : 'UTC';

        try {
            $dateTimeZone = new DateTimeZone($tz);
        } catch (Exception $e) {
            $dateTimeZone = new DateTimeZone('UTC');
        }

        // Parse input date
        try {
            if ($date instanceof DateTime) {
                $dateTime = clone $date;
                $dateTime->setTimezone($dateTimeZone);
            } elseif (is_numeric($date)) {
                $dateTime = new DateTime('@' . $date);
                $dateTime->setTimezone($dateTimeZone);
            } else {
                $dateTime = new DateTime($date);
                $dateTime->setTimezone($dateTimeZone);
            }
        } catch (Exception $e) {
            return 'Invalid date';
        }

        // Get current time in same timezone
        $now = new DateTime('now', $dateTimeZone);

        // Calculate difference
        $diff = $now->diff($dateTime);

        // Determine if past or future
        $isPast = $dateTime < $now;
        $suffix = $isPast ? ' ago' : '';
        $prefix = $isPast ? '' : 'in ';

        // Build relative string
        if ($diff->y > 0) {
            $value = $diff->y;
            $unit = $value === 1 ? 'year' : 'years';
        } elseif ($diff->m > 0) {
            $value = $diff->m;
            $unit = $value === 1 ? 'month' : 'months';
        } elseif ($diff->d > 0) {
            $value = $diff->d;
            $unit = $value === 1 ? 'day' : 'days';
        } elseif ($diff->h > 0) {
            $value = $diff->h;
            $unit = $value === 1 ? 'hour' : 'hours';
        } elseif ($diff->i > 0) {
            $value = $diff->i;
            $unit = $value === 1 ? 'minute' : 'minutes';
        } else {
            $value = $diff->s;
            $unit = $value === 1 ? 'second' : 'seconds';
        }

        return $prefix . $value . ' ' . $unit . $suffix;
    }
}

if (!function_exists('format_date_human')) {
    /**
     * Format a date in a human-friendly way with intelligent context
     *
     * Returns "Today", "Yesterday", "Tomorrow" for recent dates,
     * or formatted date for older dates.
     *
     * @param string|int|DateTime $date Date to format
     * @param bool $includeTime Whether to include time (default: true)
     * @return string Human-friendly date string
     *
     * @example
     * format_date_human('today');                    // "Today at 3:30 PM"
     * format_date_human('yesterday');                // "Yesterday at 10:15 AM"
     * format_date_human('2024-12-25');               // "Dec 25, 2024"
     * format_date_human('2024-12-31 15:30', false);  // "Dec 31, 2024"
     */
    function format_date_human($date, bool $includeTime = true): string
    {
        // Get timezone
        $tz = defined('APP_TIMEZONE') ? APP_TIMEZONE : 'UTC';

        try {
            $dateTimeZone = new DateTimeZone($tz);
        } catch (Exception $e) {
            $dateTimeZone = new DateTimeZone('UTC');
        }

        // Parse input date
        try {
            if ($date instanceof DateTime) {
                $dateTime = clone $date;
                $dateTime->setTimezone($dateTimeZone);
            } elseif (is_numeric($date)) {
                $dateTime = new DateTime('@' . $date);
                $dateTime->setTimezone($dateTimeZone);
            } else {
                $dateTime = new DateTime($date);
                $dateTime->setTimezone($dateTimeZone);
            }
        } catch (Exception $e) {
            return 'Invalid date';
        }

        $now = new DateTime('now', $dateTimeZone);

        // Get start of day for both dates
        $dateStart = clone $dateTime;
        $dateStart->setTime(0, 0, 0);

        $nowStart = clone $now;
        $nowStart->setTime(0, 0, 0);

        // Calculate day difference
        $diff = $nowStart->diff($dateStart);
        $daysDiff = (int)$diff->format('%r%a');

        // Determine human label
        $dateLabel = '';
        if ($daysDiff === 0) {
            $dateLabel = 'Today';
        } elseif ($daysDiff === -1) {
            $dateLabel = 'Yesterday';
        } elseif ($daysDiff === 1) {
            $dateLabel = 'Tomorrow';
        } elseif ($daysDiff > -7 && $daysDiff < 0) {
            // Within last week - show day name
            $dateLabel = $dateTime->format('l'); // "Monday", "Tuesday", etc.
        } elseif ($daysDiff < 7 && $daysDiff > 0) {
            // Within next week - show "Next Monday", etc.
            $dateLabel = 'Next ' . $dateTime->format('l');
        } else {
            // Older/future date - show formatted date
            if ($dateTime->format('Y') === $now->format('Y')) {
                // Same year - omit year
                $dateLabel = $dateTime->format('M j');
            } else {
                // Different year - include year
                $dateLabel = $dateTime->format('M j, Y');
            }
        }

        // Add time if requested
        if ($includeTime && ($daysDiff >= -7 && $daysDiff <= 7)) {
            $timeString = $dateTime->format('g:i A');
            return $dateLabel . ' at ' . $timeString;
        }

        return $dateLabel;
    }
}

if (!function_exists('format_timezone')) {
    /**
     * Get formatted timezone information
     *
     * Returns information about the application's configured timezone
     *
     * @param string|null $timezone Timezone to format (uses APP_TIMEZONE if not specified)
     * @return array Timezone information [name, abbreviation, offset, offsetHours]
     *
     * @example
     * $tz = format_timezone();
     * // ['name' => 'America/New_York', 'abbreviation' => 'EST', 'offset' => -18000, 'offsetHours' => '-05:00']
     */
    function format_timezone(?string $timezone = null): array
    {
        $tz = $timezone ?? (defined('APP_TIMEZONE') ? APP_TIMEZONE : 'UTC');

        try {
            $dateTimeZone = new DateTimeZone($tz);
            $now = new DateTime('now', $dateTimeZone);

            $offset = $dateTimeZone->getOffset($now);
            $hours = floor($offset / 3600);
            $minutes = floor(($offset % 3600) / 60);
            $offsetHours = sprintf('%+03d:%02d', $hours, abs($minutes));

            return [
                'name' => $tz,
                'abbreviation' => $now->format('T'),
                'offset' => $offset,
                'offsetHours' => $offsetHours,
            ];
        } catch (Exception $e) {
            return [
                'name' => 'UTC',
                'abbreviation' => 'UTC',
                'offset' => 0,
                'offsetHours' => '+00:00',
            ];
        }
    }
}

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
 * Uses the Blade templating engine under the hood.
 */
function view($view, $data = []): void
{
    echo blade($view, $data);
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
        $base . $name . '.an.php',
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

if (!function_exists('google_maps_embed_src')) {
    /**
     * Build a Google Maps embed URL from contact settings in database.
     * If MAP_LAT / MAP_LNG provided, prefer coordinates for precision.
     * Falls back to concatenated address query.
     */
    function google_maps_embed_src(): string
    {
        try {
            $contactModel = new \app\models\ContactSetting();
            $lat = $contactModel->getMapLat();
            $lng = $contactModel->getMapLng();
            $apiKey = $contactModel->getGoogleMapsKey();

            if ($lat && $lng) {
                $q = $lat . ',' . $lng;
            } else {
                $parts = [
                    $contactModel->getAddress(),
                    $contactModel->getCode(),
                    $contactModel->getCity(),
                ];
                $q = implode(' ', array_filter($parts));
            }
        } catch (\Exception $e) {
            // Fallback to env if database fails
            $lat = env('MAP_LAT');
            $lng = env('MAP_LNG');
            $apiKey = env('GOOGLE_MAPS_EMBED_KEY');
            if ($lat && $lng) {
                $q = $lat . ',' . $lng;
            } else {
                $parts = [
                    env('CONTACT_ADDRESS', ''),
                    env('CONTACT_CODE', ''),
                    env('CONTACT_CITY', ''),
                ];
                $q = implode(' ', array_filter($parts));
            }
        }
        $encoded = rawurlencode($q);
        // Basic no-key embed variant; if key present (for certain premium features) append &key=...
        $base = 'https://www.google.com/maps/embed/v1/place?zoom=15&q=' . $encoded;
        if ($apiKey) {
            $base .= '&key=' . urlencode($apiKey);
        } else {
            // Fallback to generic iframe if no API key; we can still attempt using the older pb param style.
            // For privacy and simplicity, we'll fallback to a generic search-based embed if v1 endpoint needs a key.
            // This fallback uses the public /maps embed (pb parameter) which does not require an API key.
            $base = 'https://www.google.com/maps/embed?&q=' . $encoded;
        }
        return $base;
    }
}

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
            $setting = new \app\models\Setting();
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
// Blade View Helper
// -----------------------------
if (!function_exists('blade')) {
    /**
     * Render a Blade view
     *
     * @param string $view View name (dot notation: 'auth.login' maps to auth/login.blade.php)
     * @param array $data Data to pass to the view
     * @return string Rendered HTML
     */
    function blade(string $view, array $data = []): string
    {
        if (!isset($GLOBALS['blade'])) {
            throw new \Exception('Blade is not initialized. Check bootstrap.php');
        }

        return $GLOBALS['blade']->render($view, $data);
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

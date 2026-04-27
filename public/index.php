<?php

$uriPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if (str_starts_with($uriPath, '/assets/')) {
    serve_app_asset($uriPath);
}

require_once __DIR__ . '/../core/app/bootstrap.php';
$router = require __DIR__ . '/../core/app/routes.php';

if (!$router instanceof \app\core\Router) {
    throw new RuntimeException('Router bootstrap failed in public/index.php.');
}

// Make router globally available for route() helper
$GLOBALS['router'] = $router;

// Dispatch the request
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);

function serve_app_asset(string $uriPath): void
{
    $assetsRoot = realpath(__DIR__ . '/../core/app/assets');
    if ($assetsRoot === false) {
        log_asset_request_issue($uriPath, 'assets root not found');
        http_response_code(404);
        exit;
    }

    $relativePath = ltrim(substr($uriPath, strlen('/assets/')), '/');
    if ($relativePath === '' || preg_match('#(^|/)\.\.(/|$)#', $relativePath)) {
        log_asset_request_issue($uriPath, 'invalid asset path');
        http_response_code(404);
        exit;
    }

    $candidate = realpath($assetsRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    $assetsRootPrefix = rtrim($assetsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if ($candidate === false || (!str_starts_with($candidate, $assetsRootPrefix) && $candidate !== $assetsRoot) || !is_file($candidate)) {
        log_asset_request_issue($uriPath, 'asset file not found');
        http_response_code(404);
        exit;
    }

    $extension = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'map' => 'application/json; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'eot' => 'application/vnd.ms-fontobject',
        'txt' => 'text/plain; charset=UTF-8',
    ];

    $mimeType = $mimeTypes[$extension] ?? null;

    if ($mimeType === null) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $candidate) : 'application/octet-stream';
    }

    if (!headers_sent()) {
        header('Content-Type: ' . ($mimeType ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($candidate));
        header('Cache-Control: public, max-age=31536000');
        header('X-Content-Type-Options: nosniff');
    }

    readfile($candidate);
    exit;
}

function log_asset_request_issue(string $uriPath, string $reason): void
{
    $logsDir = dirname(__DIR__) . '/core/storage/logs';
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }

    $logFile = $logsDir . '/asset_requests.log';
    $referer = $_SERVER['HTTP_REFERER'] ?? '-';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '-';
    $entry = sprintf(
        "[%s] %s | %s | referer=%s | ua=%s\n",
        date('Y-m-d H:i:s'),
        $reason,
        $uriPath,
        $referer,
        $userAgent
    );

    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

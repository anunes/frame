<?php

namespace app\core;

class Router
{
    protected $routes = [];
    protected $namedRoutes = [];
    protected $middlewares = [];
    protected $prefix = '';
    protected $currentGroup = [];
    protected $middlewareAliases = [];

    public function get(string $uri, callable|array $action)
    {
        return $this->registerRoute('GET', $uri, $action);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function post(string $uri, callable|array $action)
    {
        return $this->registerRoute('POST', $uri, $action);
    }

    public function put(string $uri, callable|array $action)
    {
        return $this->registerRoute('PUT', $uri, $action);
    }

    public function delete(string $uri, callable|array $action)
    {
        return $this->registerRoute('DELETE', $uri, $action);
    }

    public function group(array $options, callable $callback): void
    {
        // Save current routing context so we can fully restore after group
        $previousGroup = $this->currentGroup;
        $previousPrefix = $this->prefix;
        $previousMiddlewares = $this->middlewares;

        // Apply group options
        $this->currentGroup = $options;

        if (isset($options['prefix'])) {
            $groupPrefix = trim($options['prefix'], '/');
            // Build new prefix based on previous prefix, avoiding duplicate slashes
            $this->prefix = trim($previousPrefix . '/' . $groupPrefix, '/');
        }

        if (isset($options['middleware'])) {
            $middleware = (array) $options['middleware'];
            foreach ($middleware as &$m) {
                if (is_string($m) && function_exists($m)) {
                    $m = $GLOBALS[$m];
                }
            }
            $this->middlewares = array_merge($this->middlewares, $middleware);
        }

        // Execute group callback to register routes within this context
        $callback($this);

        // Restore previous routing context to prevent leakage to subsequent routes
        $this->currentGroup = $previousGroup;
        $this->prefix = $previousPrefix;
        $this->middlewares = $previousMiddlewares;
    }

    public function registerRoute(string $method, string $uri, callable|array $action)
    {
        // Build full URI with current prefix, ensuring a single leading slash
        $full = ($this->prefix !== '' ? $this->prefix . '/' : '') . trim($uri, '/');
        $uri = '/' . ltrim($full, '/');
        $route = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
            'middleware' => $this->middlewares,
            'name' => null,
        ];

        $this->routes[] = $route;
        $routeIndex = array_key_last($this->routes);

        return new class($routeIndex, $this) {
            private int $routeIndex;
            private Router $router;

            public function __construct(int $routeIndex, Router $router)
            {
                $this->routeIndex = $routeIndex;
                $this->router = $router;
            }

            public function name(string $name): self
            {
                $this->router->setRouteName($this->routeIndex, $name);
                return $this;
            }

            public function middleware(string ...$middleware): self
            {
                $this->router->addRouteMiddleware($this->routeIndex, $middleware);
                return $this;
            }
        };
    }

    public function setRouteName(int $index, string $name): void
    {
        $this->routes[$index]['name'] = $name;
        $this->namedRoutes[$name] = &$this->routes[$index];
    }

    public function addRouteMiddleware(int $index, array $middleware): void
    {
        $this->routes[$index]['middleware'] = array_merge(
            $this->routes[$index]['middleware'],
            $middleware
        );
    }

    public function loadMiddlewareAliases(): void
    {
        $configPath = APP_PATH . '/config/middleware.php';
        if (file_exists($configPath)) {
            $this->middlewareAliases = require $configPath;
        }
    }

    protected function resolveMiddleware(string $middleware): callable
    {
        // Load middleware aliases if not already loaded
        if (empty($this->middlewareAliases)) {
            $this->loadMiddlewareAliases();
        }

        // Check if it's an alias
        if (isset($this->middlewareAliases[$middleware])) {
            $className = $this->middlewareAliases[$middleware];
            if (class_exists($className)) {
                return new $className();
            }
        }

        // Check if it's a class name
        if (class_exists($middleware)) {
            return new $middleware();
        }

        // Check if it's a function
        if (function_exists($middleware)) {
            return $middleware;
        }

        throw new \Exception("Middleware '{$middleware}' not found");
    }

    public function route(string $name, array $parameters = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("Route '{$name}' not found");
        }

        $uri = $this->namedRoutes[$name]['uri'];

        // Replace route parameters
        foreach ($parameters as $key => $value) {
            $uri = preg_replace('#\{' . preg_quote($key) . '\}#', $value, $uri);
        }

        return $uri;
    }

    public function dispatch(string $method, string $uri)
    {
        // Strip query string so matching works
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        foreach ($this->routes as $route) {
            if ($this->match($method, $uri, $route)) {
                $this->handleMiddleware($route['middleware']);
                return $this->callAction($route['action'], $this->extractParameters($route['uri'], $uri));
            }
        }

        if ($method === 'GET') {
            $pageFile = $this->resolveStaticPageFile($uri);
            if ($pageFile !== null) {
                $pageName = trim($uri, '/');
                if ($pageName === '') {
                    $pageName = 'home';
                }
                $title = ucfirst(str_replace(['-', '_', '/'], ' ', $pageName));
                ob_start();
                try {
                    include $pageFile;
                    $content = ob_get_clean();
                } catch (\Throwable $e) {
                    ob_end_clean();
                    http_response_code(500);
                    echo 'Page error.';
                    return;
                }
                // If developer used view() inside page, let that output stand
                if (str_contains($content, '<html')) { // crude detection of full layout
                    echo $content;
                    return;
                }
                if (function_exists('view')) {
                    $layoutPathHeader = __DIR__ . '/../views/layouts/header.an.php';
                    $layoutPathFooter = __DIR__ . '/../views/layouts/footer.an.php';
                    if (is_file($layoutPathHeader)) include $layoutPathHeader;
                    echo '<main class="container py-5">' . $content . '</main>';
                    if (is_file($layoutPathFooter)) include $layoutPathFooter;
                } else {
                    echo $content;
                }
                return;
            }
        }
        http_response_code(404);
        echo "404 Not Found";
    }

    protected function match(string $method, string $uri, array $route): bool
    {
        return $method === $route['method'] && preg_match($this->convertToRegex($route['uri']), $uri);
    }

    protected function convertToRegex(string $uri): string
    {
        return '#^' . preg_replace('#\{[\w]+\}#', '([\w\-\.]+)', $uri) . '$#';
    }

    protected function extractParameters(string $routeUri, string $uri): array
    {
        $pattern = $this->convertToRegex($routeUri);
        preg_match($pattern, $uri, $matches);
        array_shift($matches); // Remove the full match
        return $matches;
    }

    protected function resolveStaticPageFile(string $uri): ?string
    {
        $pagesDir = realpath(__DIR__ . '/../pages');
        if ($pagesDir === false) {
            return null;
        }

        $trimmed = trim($uri, '/');
        if ($trimmed === '') {
            $trimmed = 'home';
        }

        $segments = explode('/', $trimmed);
        foreach ($segments as $segment) {
            if ($segment === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $segment)) {
                return null;
            }
        }

        $candidate = $pagesDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments) . '.php';
        if (!is_file($candidate)) {
            return null;
        }

        $resolved = realpath($candidate);
        if ($resolved === false) {
            return null;
        }

        $pagesRoot = rtrim($pagesDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($resolved, $pagesRoot)) {
            return null;
        }

        return $resolved;
    }

    protected function handleMiddleware(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            // If it's already callable (closure or object), execute it
            if (is_callable($middleware)) {
                $result = $middleware();
                if ($result === false) {
                    exit; // Stop execution if middleware returns false
                }
            }
            // If it's a string, resolve it to a middleware class or function
            elseif (is_string($middleware)) {
                $resolved = $this->resolveMiddleware($middleware);
                $result = $resolved();
                if ($result === false) {
                    exit; // Stop execution if middleware returns false
                }
            }
        }
    }

    protected function callAction(callable|array $action, array $parameters)
    {
        if (is_callable($action)) {
            return call_user_func_array($action, $parameters);
        }

        [$controller, $method] = $action;
        if (class_exists($controller) && method_exists($controller, $method)) {
            $controllerInstance = new $controller();
            return call_user_func_array([$controllerInstance, $method], $parameters);
        }

        throw new \Exception("Controller or method not found");
    }
}

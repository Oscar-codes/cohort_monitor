<?php

namespace App\Core;

/**
 * Router
 *
 * Lightweight routing system that maps URI patterns to controller actions.
 * Supports GET and POST methods with named route parameters.
 */
class Router
{
    /** @var array<string, array> Registered routes grouped by HTTP method */
    private array $routes = [];

    /** @var array<string, string> Named routes for URL generation */
    private array $namedRoutes = [];

    /**
     * Register a GET route.
     */
    public function get(string $uri, array $action, ?string $name = null): self
    {
        return $this->addRoute('GET', $uri, $action, $name);
    }

    /**
     * Register a POST route.
     */
    public function post(string $uri, array $action, ?string $name = null): self
    {
        return $this->addRoute('POST', $uri, $action, $name);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $uri, array $action, ?string $name = null): self
    {
        return $this->addRoute('PUT', $uri, $action, $name);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $uri, array $action, ?string $name = null): self
    {
        return $this->addRoute('DELETE', $uri, $action, $name);
    }

    /**
     * Add a route to the internal registry.
     */
    private function addRoute(string $method, string $uri, array $action, ?string $name = null): self
    {
        $uri = '/' . trim($uri, '/');

        $this->routes[$method][] = [
            'uri'        => $uri,
            'controller' => $action[0],
            'method'     => $action[1] ?? 'index',
            'pattern'    => $this->compilePattern($uri),
        ];

        if ($name) {
            $this->namedRoutes[$name] = $uri;
        }

        return $this;
    }

    /**
     * Compile a URI pattern into a regex for matching.
     * Converts {param} segments into named capture groups.
     */
    private function compilePattern(string $uri): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    /**
     * Resolve the current request and dispatch to the appropriate controller.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = $this->getUri();

        // Support method override via hidden form field (_method)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        if (!isset($this->routes[$method])) {
            $this->sendNotFound();
            return;
        }

        foreach ($this->routes[$method] as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->callAction($route['controller'], $route['method'], $params);
                return;
            }
        }

        $this->sendNotFound();
    }

    /**
     * Instantiate the controller and call the action method.
     */
    private function callAction(string $controllerClass, string $method, array $params): void
    {
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller [{$controllerClass}] not found.");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Method [{$method}] not found in [{$controllerClass}].");
        }

        // Use array_values to pass positional parameters instead of named ones.
        // This ensures compatibility regardless of controller parameter names.
        call_user_func_array([$controller, $method], array_values($params));
    }

    /**
     * Extract and normalize the request URI.
     */
    private function getUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return '/' . trim($uri, '/');
    }

    /**
     * Send a 404 response.
     */
    private function sendNotFound(): void
    {
        http_response_code(404);
        require_once APP_ROOT . '/app/Views/errors/404.php';
    }

    /**
     * Generate a URL for a named route.
     */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException("Route [{$name}] not defined.");
        }

        $url = $this->namedRoutes[$name];

        foreach ($params as $key => $value) {
            $url = str_replace("{{$key}}", $value, $url);
        }

        return $url;
    }
}

<?php

declare(strict_types=1);

namespace CFK\Utils;

/**
 * Simple router for handling HTTP requests
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];

    /**
     * Add a GET route
     */
    public function get(string $pattern, callable $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    /**
     * Add a POST route
     */
    public function post(string $pattern, callable $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    /**
     * Add a route for any HTTP method
     */
    public function addRoute(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    /**
     * Add middleware
     */
    public function middleware(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Route the current request
     */
    public function route(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        // Run middlewares
        foreach ($this->middlewares as $middleware) {
            $result = $middleware($method, $uri);
            if ($result === false) {
                return; // Middleware blocked the request
            }
        }

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchRoute($route['pattern'], $uri);
            if ($params !== false) {
                // Route matched, call handler
                call_user_func($route['handler'], ...$params);
                return;
            }
        }

        // No route matched
        $this->notFound();
    }

    /**
     * Match route pattern against URI
     */
    private function matchRoute(string $pattern, string $uri): array|false
    {
        // Convert pattern to regex
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches); // Remove full match
            return $matches;
        }

        return false;
    }

    /**
     * Handle 404 Not Found
     */
    private function notFound(): void
    {
        http_response_code(404);
        echo '404 - Page Not Found';
    }
}
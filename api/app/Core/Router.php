<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Add a route
     */
    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $this->middlewares
        ];
    }

    /**
     * Add GET route
     */
    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /**
     * Add POST route
     */
    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * Add PUT route
     */
    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    /**
     * Add DELETE route
     */
    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    /**
     * Add middleware to subsequent routes
     */
    public function middleware(callable $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Group routes with middleware
     */
    public function group(callable $callback): void
    {
        $currentMiddlewares = $this->middlewares;
        $callback($this);
        $this->middlewares = $currentMiddlewares;
    }

    /**
     * Match and execute route
     */
    public function dispatch(): void
    {
        $method = $this->request->getMethod();
        $uri = $this->request->getUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if ($this->matchPath($route['path'], $uri, $params)) {
                // Execute middlewares
                foreach ($route['middlewares'] as $middleware) {
                    $middleware($this->request);
                }

                // Execute route handler
                $route['handler']($this->request, $params);
                return;
            }
        }

        // No route matched - 404
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Route not found',
            'path' => $uri
        ]);
    }

    /**
     * Match route path with URI
     */
    private function matchPath(string $routePath, string $uri, &$params): bool
    {
        $params = [];

        // Convert route path to regex
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }
}

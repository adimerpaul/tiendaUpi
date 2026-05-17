<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    /**
     * @var array<int, array{method: string, pattern: string, handler: callable}>
     */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $paramNames = [];
            $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function (array $matches) use (&$paramNames): string {
                $paramNames[] = $matches[1];
                return '([^\/]+)';
            }, $route['pattern']);

            if ($regex === null) {
                continue;
            }

            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $uri, $matches) !== 1) {
                continue;
            }

            array_shift($matches);
            $params = [];

            foreach ($paramNames as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }

            call_user_func_array($route['handler'], $params);
            return;
        }

        http_response_code(404);
        echo json_encode(['error' => 'Ruta no encontrada'], JSON_UNESCAPED_UNICODE);
    }
}

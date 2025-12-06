<?php
namespace App;

class Router
{
    private array $routes = [];
    private string $baseUrl;

    public function __construct(string $baseUrl = '')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function get(string $path, callable $callback): void
    {
        $this->addRoute('GET', $path, $callback);
    }

    public function post(string $path, callable $callback): void
    {
        $this->addRoute('POST', $path, $callback);
    }

    private function addRoute(string $method, string $path, callable $callback): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'callback' => $callback,
        ];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Retirer le base URL
        if ($this->baseUrl && strpos($uri, $this->baseUrl) === 0) {
            $uri = substr($uri, strlen($this->baseUrl));
        }
        
        $uri = '/' . trim($uri, '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                call_user_func_array($route['callback'], $matches);
                return;
            }
        }

        http_response_code(404);
        echo '404 - Page non trouvée';
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . APP_URL . $path);
        exit;
    }
}

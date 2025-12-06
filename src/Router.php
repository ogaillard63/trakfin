<?php
namespace App;

class Router
{
    private array $routes = [];
    private string $baseUrl;

    public function __construct(string $baseUrl = '')
    {
        // Enregistrer seulement le chemin (path) de l'URL de base pour le routing
        $path = parse_url($baseUrl, PHP_URL_PATH) ?? '';
        $this->baseUrl = rtrim($path, '/');
    }

    public function get(string $path, callable $callback): void
    {
        $this->addRoute('GET', $path, $callback);
    }

    public function post(string $path, callable $callback): void
    {
        $this->addRoute('POST', $path, $callback);
    }

    public function put(string $path, callable $callback): void
    {
        $this->addRoute('PUT', $path, $callback);
    }

    public function delete(string $path, callable $callback): void
    {
        $this->addRoute('DELETE', $path, $callback);
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
        // Redirection relative à la racine pour préserver le HTTPS
        $basePath = parse_url(APP_URL, PHP_URL_PATH) ?? '';
        $basePath = rtrim($basePath, '/');
        header('Location: ' . $basePath . $path);
        exit;
    }
}

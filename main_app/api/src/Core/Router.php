<?php
namespace Core;

class Router {
    private $routes = [];

    public function add($method, $path, $callback) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'callback' => $callback
        ];
    }

    public function dispatch($method, $uri) {
        // Strip query string
        $uri = explode('?', $uri)[0];
        // Normalize URI: remove /main_app/api/v1 prefix if exists, or handle based on entry point
        // For simplicity, we assume index.php is in v1/ and we match relative to that
        $uri = preg_replace('/^.*?\/api\/v1/', '', $uri);
        if (empty($uri)) $uri = '/';

        foreach ($this->routes as $route) {
            $pattern = $this->getPathPattern($route['path']);
            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove full match
                return $this->executeCallback($route['callback'], $matches);
            }
        }

        Response::error('Not Found', 404);
    }

    private function getPathPattern($path) {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $path);
        return "#^" . $pattern . "$#";
    }

    private function executeCallback($callback, $params) {
        if (is_callable($callback)) {
            return call_user_func_array($callback, $params);
        }

        if (is_string($callback) && strpos($callback, '@') !== false) {
            list($controller, $method) = explode('@', $callback);
            $controllerClass = "Controllers\\" . $controller;
            if (class_exists($controllerClass)) {
                $instance = new $controllerClass();
                return call_user_func_array([$instance, $method], $params);
            }
        }

        Response::error('Server Error: Controller not found', 500);
    }
}

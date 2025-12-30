<?php
// Unified API Entry Point (v1)
require_once dirname(__DIR__) . '/api_bootstrap.php';

// Autoloader for src/
spl_autoload_register(function ($class) {
    $prefix = '';
    $base_dir = dirname(__DIR__) . '/src/';
    $file = $base_dir . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use Core\Router;
use Core\Response;
use Middleware\AuthMiddleware;

// Global Middleware (Conditional)
$uri = $_SERVER['REQUEST_URI'];
if (strpos($uri, '/auth/login') === false) {
    AuthMiddleware::handle();
}

$router = new Router();

// --- ROUTE DEFINITIONS ---

// Auth
$router->add('POST', '/auth/login', 'AuthController@login');
$router->add('POST', '/auth/logout', 'AuthController@logout');
$router->add('GET', '/auth/me', 'AuthController@me');

// Orders
$router->add('GET', '/orders', 'OrdersController@index');
$router->add('GET', '/orders/{id}', 'OrdersController@show');
$router->add('PUT', '/orders/{id}/status', 'OrdersController@updateStatus');
$router->add('POST', '/orders/update-status', 'OrdersController@updateStatusLegacy');

// Products
$router->add('GET', '/products', 'ProductsController@index');
$router->add('GET', '/products/{id}', 'ProductsController@show');

// AI Chat
$router->add('POST', '/chat', 'RAGController@chat');

// --- DISPATCH ---
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);

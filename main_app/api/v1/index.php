<?php
// Unified API Entry Point (v1)
require_once dirname(__DIR__) . '/api_bootstrap.php';
include_once dirname(__DIR__) . '/tools/super_debug_include.php';
file_put_contents(dirname(__DIR__) . '/debug_request.log', date('Y-m-d H:i:s') . " - " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . "\n", FILE_APPEND);

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
$router->add('POST', '/products', 'ProductsController@store');
$router->add('PUT', '/products/{id}', 'ProductsController@update');
$router->add('DELETE', '/products/{id}', 'ProductsController@destroy');

// Inventory
$router->add('GET', '/inventory', 'InventoryController@getInventory');
$router->add('GET', '/inventory/inbound', 'InventoryController@inbound');
$router->add('POST', '/inventory/inbound', 'InventoryController@inbound');
$router->add('POST', '/inventory/trigger-restock', 'InventoryController@triggerRestock');

// AI Chat
$router->add('POST', '/chat', 'RAGController@chat');

// --- DISPATCH ---
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);

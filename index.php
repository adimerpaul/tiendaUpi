<?php

declare(strict_types=1);

require_once __DIR__ . '/app/Database/Database.php';
require_once __DIR__ . '/app/Core/Router.php';
require_once __DIR__ . '/app/Models/Product.php';
require_once __DIR__ . '/app/Controllers/ProductController.php';

use App\Controllers\ProductController;
use App\Core\Router;
use App\Models\Product;

header('Content-Type: application/json; charset=utf-8');

$router = new Router();
$productController = new ProductController();

$router->add('GET', '/api/productos', [$productController, 'index']);
$router->add('GET', '/api/productos/{id}', [$productController, 'show']);
$router->add('POST', '/api/productos', [$productController, 'store']);
$router->add('PUT', '/api/productos/{id}', [$productController, 'update']);
$router->add('DELETE', '/api/productos/{id}', [$productController, 'destroy']);

$router->add('GET', '/', function (): void {
    $productModel = new Product();
    $products = $productModel->all();

    http_response_code(200);
    echo json_encode([
        'mensaje' => 'API MVC de productos activa (version simple)',
        'prueba_productos' => [
            'total' => count($products),
            'primer_producto' => $products[0] ?? null,
        ],
        'endpoints' => [
            'GET /api/productos',
            'GET /api/productos/{id}',
            'POST /api/productos',
            'PUT /api/productos/{id}',
            'DELETE /api/productos/{id}',
        ],
    ], JSON_UNESCAPED_UNICODE);
});

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

if ($basePath !== '' && $basePath !== '/' && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}

$uri = $uri === '' ? '/' : $uri;
$router->dispatch($method, $uri);
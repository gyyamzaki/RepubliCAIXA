<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Session;

Session::start();

$routes = require dirname(__DIR__) . '/routes/web.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$routeKey = $method . ' ' . $uri;

if (isset($routes[$routeKey])) {
    $routes[$routeKey]();
    exit;
}

http_response_code(404);
require dirname(__DIR__) . '/app/Views/errors/404.php';

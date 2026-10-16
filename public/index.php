<?php
declare(strict_types=1);

// Single entry point. Everything outside /public stays unreachable from the web.
require dirname(__DIR__) . '/app/bootstrap.php';

$router = require APP_PATH . '/routes.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = request_path();

// One canonical address per page: no trailing slash.
if ($method === 'GET' && $path !== '/' && str_ends_with((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/')) {
    redirect(url($path), 301);
}

$router->dispatch($method, $path);

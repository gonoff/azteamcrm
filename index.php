<?php

require_once 'bootstrap.php';

use App\Core\Router;

// Get the route from URL
$route = $_GET['route'] ?? '';

// Clean the route
$route = trim($route, '/');

// Default to login if no route
if (empty($route)) {
    $route = 'login';
}

// Add leading slash for router
$route = '/' . $route;

// Create router and dispatch
$router = new Router();
$router->dispatch($route);
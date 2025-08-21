<?php

namespace App\Core;

class Router
{
    private $routes = [];
    private $params = [];
    
    public function __construct()
    {
        $this->routes = require dirname(__DIR__, 2) . '/config/routes.php';
    }
    
    public function dispatch($url)
    {
        $url = $this->removeQueryString($url);
        
        if ($route = $this->matchRoute($url)) {
            $this->executeRoute($route);
        } else {
            $this->sendNotFound();
        }
    }
    
    private function matchRoute($url)
    {
        foreach ($this->routes as $pattern => $handler) {
            $pattern = $this->convertToRegex($pattern);
            
            if (preg_match($pattern, $url, $matches)) {
                array_shift($matches);
                $this->params = $matches;
                return $handler;
            }
        }
        return false;
    }
    
    private function convertToRegex($pattern)
    {
        $pattern = preg_replace('/\//', '\\/', $pattern);
        $pattern = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[^\/]+)', $pattern);
        $pattern = '/^' . $pattern . '$/i';
        return $pattern;
    }
    
    private function executeRoute($handler)
    {
        if (strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);
            $controllerClass = "App\\Controllers\\{$controller}";
            
            if (class_exists($controllerClass)) {
                $controllerInstance = new $controllerClass();
                
                if (method_exists($controllerInstance, $method)) {
                    call_user_func_array([$controllerInstance, $method], array_values($this->params));
                } else {
                    $this->sendNotFound("Method {$method} not found in {$controller}");
                }
            } else {
                $this->sendNotFound("Controller {$controller} not found");
            }
        } else {
            $this->sendNotFound("Invalid route handler format");
        }
    }
    
    private function removeQueryString($url)
    {
        if ($url != '') {
            $parts = explode('?', $url, 2);
            return rtrim($parts[0], '/');
        }
        return '';
    }
    
    private function sendNotFound($message = "Page not found")
    {
        http_response_code(404);
        echo "<h1>404 - {$message}</h1>";
        exit;
    }
}
<?php

namespace App\Core;

class Controller
{
    protected function view($view, $data = [])
    {
        extract($data);
        
        $viewFile = dirname(__DIR__) . "/Views/{$view}.php";
        
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("View {$view} not found");
        }
    }
    
    protected function redirect($url)
    {
        // Simple redirect - just add base path
        if (strpos($url, 'http') !== 0) {
            $url = '/azteamcrm/' . ltrim($url, '/');
        }
        header("Location: {$url}");
        exit;
    }
    
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    protected function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    protected function requireAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }
    
    protected function requireRole($role)
    {
        $this->requireAuth();
        if ($_SESSION['user_role'] !== $role && $_SESSION['user_role'] !== 'administrator') {
            http_response_code(403);
            $this->view('errors/403');
            exit;
        }
    }
    
    protected function csrf()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    protected function verifyCsrf()
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            die("CSRF token validation failed");
        }
    }
    
    protected function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    protected function validate($data, $rules)
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? '';
            
            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field] = ucfirst($field) . " is required";
            }
            
            if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "Invalid email format";
            }
            
            if (preg_match('/min:(\d+)/', $rule, $matches) && strlen($value) < $matches[1]) {
                $errors[$field] = ucfirst($field) . " must be at least {$matches[1]} characters";
            }
        }
        
        return $errors;
    }
}
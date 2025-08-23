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
        // Don't modify absolute URLs
        if (strpos($url, 'http') === 0) {
            header("Location: {$url}");
            exit;
        }
        
        // Check if URL already contains the base path
        if (strpos($url, '/azteamcrm/') === 0) {
            // URL already has the base path, use as-is
            header("Location: {$url}");
            exit;
        }
        
        // Add base path for relative URLs
        $url = '/azteamcrm/' . ltrim($url, '/');
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
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        
        // Check session timeout (30 minutes of inactivity)
        $timeout = 1800; // 30 minutes in seconds
        $now = time();
        
        if (isset($_SESSION['last_activity'])) {
            $elapsed = $now - $_SESSION['last_activity'];
            
            if ($elapsed > $timeout) {
                // Session has timed out
                $_SESSION = [];
                if (isset($_COOKIE[session_name()])) {
                    setcookie(session_name(), '', time() - 3600, '/');
                }
                session_destroy();
                
                // Start new session for message
                session_start();
                $_SESSION['login_error'] = 'Your session has expired. Please login again.';
                $this->redirect('/login');
            }
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = $now;
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
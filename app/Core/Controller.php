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
    
    protected function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
               (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
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
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Session expired', 'redirect' => '/azteamcrm/login'], 401);
            } else {
                $this->redirect('/login');
            }
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
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'CSRF token validation failed'], 403);
            } else {
                http_response_code(403);
                die("CSRF token validation failed");
            }
        }
    }
    
    protected function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        
        // Only trim and clean data for processing - don't HTML encode here
        // HTML encoding should only happen at output time in views
        $cleaned = trim($input);
        
        // Remove null bytes and control characters for security
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cleaned);
        
        return $cleaned;
    }
    
    protected function toTitleCase($string)
    {
        if (empty($string)) {
            return $string;
        }
        
        // Convert to lowercase first to handle ALL CAPS input
        $string = mb_strtolower($string, 'UTF-8');
        
        // List of words that should remain lowercase (except at start)
        $exceptions = ['de', 'da', 'do', 'dos', 'das', 'e', 'and', 'or', 'of', 'the', 'van', 'von'];
        
        // Handle special cases like McDonald, O'Brien, etc.
        $patterns = [
            // McDonald, MacDonald, etc.
            "/(^|\\s)(mc|mac)([a-z])/i" => function($matches) {
                return $matches[1] . ucfirst(strtolower($matches[2])) . ucfirst($matches[3]);
            },
            // O'Brien, O'Connor, etc.
            "/(^|\\s)(o')([a-z])/i" => function($matches) {
                return $matches[1] . $matches[2] . ucfirst($matches[3]);
            }
        ];
        
        // Split by spaces and hyphens while keeping delimiters
        $words = preg_split('/(\s+|-)/u', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = [];
        $isFirst = true;
        
        foreach ($words as $word) {
            if ($word === ' ' || $word === '-' || trim($word) === '') {
                $result[] = $word;
                continue;
            }
            
            $lowerWord = mb_strtolower($word, 'UTF-8');
            
            // Always capitalize first word, otherwise check exceptions
            if ($isFirst || !in_array($lowerWord, $exceptions)) {
                // Apply special patterns
                $formatted = $word;
                foreach ($patterns as $pattern => $callback) {
                    $formatted = preg_replace_callback($pattern, $callback, $formatted);
                }
                
                // If no special pattern matched, use standard title case
                if ($formatted === $word) {
                    $formatted = mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
                }
                
                $result[] = $formatted;
            } else {
                $result[] = $lowerWord;
            }
            
            if (!empty(trim($word))) {
                $isFirst = false;
            }
        }
        
        return implode('', $result);
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
    
    /**
     * Standardized error handling methods
     */
    protected function handleDatabaseOperation($operation, $successMessage = null, $failureMessage = null)
    {
        try {
            $result = $operation();
            
            if ($result) {
                if ($successMessage) {
                    $this->setSuccess($successMessage);
                }
                return $result;
            } else {
                $this->setError($failureMessage ?: 'Operation failed. Please try again.');
                return false;
            }
        } catch (\Exception $e) {
            return $this->handleException($e, $failureMessage);
        }
    }
    
    protected function setError($message)
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['error'] = $message;
    }
    
    protected function setSuccess($message)
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['success'] = $message;
    }
    
    protected function handleException(\Exception $e, $userMessage = null)
    {
        // Log the actual error for debugging (in a real app, use proper logging)
        error_log("Database Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        
        // Set user-friendly error message
        $message = $userMessage ?: 'An unexpected error occurred. Please try again or contact support if the problem persists.';
        $this->setError($message);
        
        return false;
    }
    
    protected function validateAndSanitize($data, $rules)
    {
        // Sanitize the data first
        $sanitizedData = $this->sanitize($data);
        
        // Then validate
        $errors = $this->validate($sanitizedData, $rules);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $sanitizedData;
            return false;
        }
        
        return $sanitizedData;
    }
    
    /**
     * Pagination Helper Methods
     */
    protected function renderPagination($pagination, $baseUrl, $searchParams = [])
    {
        if ($pagination['total_pages'] <= 1) {
            return '';
        }
        
        $html = '<nav aria-label="Page navigation">';
        $html .= '<ul class="pagination justify-content-center">';
        
        // Previous button
        if ($pagination['has_previous']) {
            $prevUrl = $this->buildPaginationUrl($baseUrl, $pagination['previous_page'], $searchParams);
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $prevUrl . '" aria-label="Previous">';
            $html .= '<span aria-hidden="true">&laquo;</span>';
            $html .= '</a>';
            $html .= '</li>';
        } else {
            $html .= '<li class="page-item disabled">';
            $html .= '<span class="page-link" aria-label="Previous">';
            $html .= '<span aria-hidden="true">&laquo;</span>';
            $html .= '</span>';
            $html .= '</li>';
        }
        
        // Page numbers
        $start = max(1, $pagination['current_page'] - 2);
        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
        
        // Always show first page if not in range
        if ($start > 1) {
            $firstUrl = $this->buildPaginationUrl($baseUrl, 1, $searchParams);
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $firstUrl . '">1</a>';
            $html .= '</li>';
            if ($start > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        // Page number links
        for ($i = $start; $i <= $end; $i++) {
            $pageUrl = $this->buildPaginationUrl($baseUrl, $i, $searchParams);
            if ($i == $pagination['current_page']) {
                $html .= '<li class="page-item active" aria-current="page">';
                $html .= '<span class="page-link">' . $i . '</span>';
                $html .= '</li>';
            } else {
                $html .= '<li class="page-item">';
                $html .= '<a class="page-link" href="' . $pageUrl . '">' . $i . '</a>';
                $html .= '</li>';
            }
        }
        
        // Always show last page if not in range
        if ($end < $pagination['total_pages']) {
            if ($end < $pagination['total_pages'] - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $lastUrl = $this->buildPaginationUrl($baseUrl, $pagination['total_pages'], $searchParams);
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $lastUrl . '">' . $pagination['total_pages'] . '</a>';
            $html .= '</li>';
        }
        
        // Next button
        if ($pagination['has_next']) {
            $nextUrl = $this->buildPaginationUrl($baseUrl, $pagination['next_page'], $searchParams);
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $nextUrl . '" aria-label="Next">';
            $html .= '<span aria-hidden="true">&raquo;</span>';
            $html .= '</a>';
            $html .= '</li>';
        } else {
            $html .= '<li class="page-item disabled">';
            $html .= '<span class="page-link" aria-label="Next">';
            $html .= '<span aria-hidden="true">&raquo;</span>';
            $html .= '</span>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</nav>';
        
        return $html;
    }
    
    protected function buildPaginationUrl($baseUrl, $page, $searchParams = [])
    {
        $params = array_merge($searchParams, ['page' => $page]);
        $queryString = http_build_query($params);
        
        return $baseUrl . '?' . $queryString;
    }
    
    protected function renderPaginationInfo($pagination)
    {
        if ($pagination['total_items'] == 0) {
            return '<div class="pagination-info text-muted">No records found</div>';
        }
        
        return sprintf(
            '<div class="pagination-info text-muted">Showing %d to %d of %d results</div>',
            $pagination['start_item'],
            $pagination['end_item'],
            $pagination['total_items']
        );
    }
}
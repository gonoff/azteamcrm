# Routing System Documentation

## Table of Contents
1. [Routing Overview](#routing-overview)
2. [URL Structure](#url-structure)
3. [Route Definitions](#route-definitions)
4. [URL Rewriting](#url-rewriting)
5. [Route Matching](#route-matching)
6. [Parameter Extraction](#parameter-extraction)
7. [Controller Mapping](#controller-mapping)
8. [Complete Route Registry](#complete-route-registry)
9. [Error Handling](#error-handling)

---

## Routing Overview

The routing system is responsible for mapping incoming HTTP requests to appropriate controllers and methods. It uses a pattern-based approach with parameter extraction capabilities.

### Key Components
1. **Router Class** (`/app/Core/Router.php`): Core routing logic
2. **Routes Configuration** (`/config/routes.php`): Route definitions
3. **Apache .htaccess**: URL rewriting rules
4. **Entry Point** (`index.php`): Request dispatcher

## URL Structure

### URL Format
```
http://localhost/azteamcrm/[route]/[parameters]
```

### URL Components
- **Base URL**: `http://localhost/azteamcrm/`
- **Route**: Path after base URL (e.g., `orders`, `customers/create`)
- **Parameters**: Dynamic segments (e.g., `{id}`, `{order_id}`)
- **Query String**: Optional parameters (e.g., `?page=2&search=term`)

### Clean URLs
Apache mod_rewrite converts:
- From: `/azteamcrm/orders/123`
- To: `/azteamcrm/index.php?route=orders/123`

## Route Definitions

### Route Configuration File
Location: `/config/routes.php`

```php
return [
    // Static routes
    '/' => 'DashboardController@index',
    '/login' => 'AuthController@showLogin',
    
    // Parameterized routes
    '/orders/{id}' => 'OrderController@show',
    '/customers/{id}/edit' => 'CustomerController@edit',
    
    // Multi-parameter routes
    '/orders/{order_id}/order-items' => 'OrderItemController@index',
];
```

### Route Pattern Syntax
- **Static**: `/orders` - Exact match
- **Single Parameter**: `/orders/{id}` - One dynamic segment
- **Multiple Parameters**: `/orders/{order_id}/items/{item_id}`
- **Action Suffix**: `/orders/create` - Specific action

## URL Rewriting

### Apache .htaccess Configuration
```apache
RewriteEngine On

# Prevent direct access to app folders
RewriteRule ^(app|config|storage)/ - [F,L]

# Don't rewrite for existing files and directories
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Rewrite everything else to index.php
RewriteRule ^(.*)$ index.php?route=$1 [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

### Rewrite Process
1. Check if request is for protected directory → Forbidden
2. Check if file/directory exists → Serve directly
3. Otherwise → Rewrite to index.php with route parameter

## Route Matching

### Matching Algorithm
```php
private function matchRoute($url) {
    foreach ($this->routes as $pattern => $handler) {
        // Convert pattern to regex
        $pattern = $this->convertToRegex($pattern);
        
        // Attempt match
        if (preg_match($pattern, $url, $matches)) {
            array_shift($matches); // Remove full match
            $this->params = $matches;
            return $handler;
        }
    }
    return false;
}
```

### Pattern Conversion
```php
private function convertToRegex($pattern) {
    // Escape forward slashes
    $pattern = preg_replace('/\//', '\\/', $pattern);
    
    // Convert {param} to named capture group
    $pattern = preg_replace('/\{([a-z_]+)\}/', '(?P<\1>[^\/]+)', $pattern);
    
    // Add start/end anchors
    $pattern = '/^' . $pattern . '$/i';
    
    return $pattern;
}
```

### Matching Examples
| URL | Pattern | Match | Parameters |
|-----|---------|-------|------------|
| `/orders` | `/orders` | ✓ | None |
| `/orders/123` | `/orders/{id}` | ✓ | id=123 |
| `/orders/abc` | `/orders/{id}` | ✓ | id=abc |
| `/orders/123/edit` | `/orders/{id}/edit` | ✓ | id=123 |
| `/orders/123/items/456` | `/orders/{order_id}/items/{item_id}` | ✓ | order_id=123, item_id=456 |

## Parameter Extraction

### Extraction Process
1. **Pattern Matching**: Named capture groups in regex
2. **Parameter Storage**: Stored in `$this->params` array
3. **Parameter Passing**: Passed to controller method

### Parameter Types
```php
// Single parameter
'/orders/{id}' → function show($id)

// Multiple parameters
'/orders/{order_id}/items/{item_id}' → function showItem($order_id, $item_id)

// Optional parameters (handled in controller)
'/orders' → function index($page = 1)
```

### Parameter Validation
Parameters are validated in controllers:
```php
public function show($id) {
    if (!is_numeric($id)) {
        $this->sendNotFound();
    }
    // Continue processing
}
```

## Controller Mapping

### Mapping Format
```
'ControllerName@methodName'
```

### Controller Resolution
```php
private function executeRoute($handler) {
    // Split controller and method
    list($controller, $method) = explode('@', $handler);
    
    // Build full class name
    $controllerClass = "App\\Controllers\\{$controller}";
    
    // Instantiate controller
    $controllerInstance = new $controllerClass();
    
    // Call method with parameters
    call_user_func_array(
        [$controllerInstance, $method], 
        array_values($this->params)
    );
}
```

### Namespace Resolution
- Route: `'OrderController@index'`
- Class: `App\Controllers\OrderController`
- File: `/app/Controllers/OrderController.php`

## Complete Route Registry

### Authentication Routes
```php
'/login' => 'AuthController@showLogin',          // GET: Login form
'/login/submit' => 'AuthController@login',       // POST: Process login
'/logout' => 'AuthController@logout',            // GET/POST: Logout
```

### Dashboard Routes
```php
'/' => 'DashboardController@index',              // Root redirects to dashboard
'/dashboard' => 'DashboardController@index',     // Dashboard view
```

### Customer Routes
```php
'/customers' => 'CustomerController@index',                    // List all
'/customers/search' => 'CustomerController@search',            // AJAX search
'/customers/create' => 'CustomerController@create',            // Create form
'/customers/store' => 'CustomerController@store',              // Save new
'/customers/{id}' => 'CustomerController@show',                // View details
'/customers/{id}/edit' => 'CustomerController@edit',           // Edit form
'/customers/{id}/update' => 'CustomerController@update',       // Save changes
'/customers/{id}/delete' => 'CustomerController@delete',       // Delete
'/customers/{id}/toggle-status' => 'CustomerController@toggleStatus', // Toggle active
```

### Order Routes
```php
'/orders' => 'OrderController@index',                          // List all
'/orders/create' => 'OrderController@create',                  // Create form
'/orders/store' => 'OrderController@store',                    // Save new
'/orders/{id}' => 'OrderController@show',                      // View details
'/orders/{id}/edit' => 'OrderController@edit',                 // Edit form
'/orders/{id}/update' => 'OrderController@update',             // Save changes
'/orders/{id}/delete' => 'OrderController@delete',             // Delete
'/orders/{id}/update-status' => 'OrderController@updateStatus', // Update payment
'/orders/{id}/cancel' => 'OrderController@cancelOrder',        // Cancel order
'/orders/{id}/process-payment' => 'OrderController@processPayment', // Payment
```

### Order Item Routes
```php
'/orders/{order_id}/order-items' => 'OrderItemController@index',           // List items
'/orders/{order_id}/order-items/create' => 'OrderItemController@create',   // Add form
'/orders/{order_id}/order-items/store' => 'OrderItemController@store',     // Save new
'/order-items/{id}/edit' => 'OrderItemController@edit',                    // Edit form
'/order-items/{id}/update' => 'OrderItemController@update',                // Save changes
'/order-items/{id}/delete' => 'OrderItemController@delete',                // Delete
'/order-items/{id}/update-status' => 'OrderItemController@updateStatus',   // AJAX status
'/order-items/{id}/update-inline' => 'OrderItemController@updateInline',   // Inline edit
```

### User Management Routes
```php
'/users' => 'UserController@index',                            // List all
'/users/create' => 'UserController@create',                    // Create form
'/users/store' => 'UserController@store',                      // Save new
'/users/{id}/edit' => 'UserController@edit',                   // Edit form
'/users/{id}/update' => 'UserController@update',               // Save changes
'/users/{id}/delete' => 'UserController@delete',               // Delete
'/users/{id}/toggle-status' => 'UserController@toggleStatus',  // Toggle active
```

### Profile Routes
```php
'/profile' => 'UserController@profile',                        // View profile
'/profile/update-password' => 'UserController@updatePassword', // Change password
```

### Production Routes
```php
'/production' => 'ProductionController@index',                 // Main dashboard
'/production/pending' => 'ProductionController@pending',       // Pending items
'/production/today' => 'ProductionController@today',           // Today's schedule
'/production/materials' => 'ProductionController@materials',   // Materials report
'/production/bulk-update' => 'ProductionController@updateBulkStatus', // Bulk update
```

### Planned Routes (Commented)
```php
// '/reports' => 'ReportController@index',
// '/reports/financial' => 'ReportController@financial',
// '/reports/production' => 'ReportController@production',
```

## Error Handling

### 404 Not Found
```php
private function sendNotFound($message = "Page not found") {
    http_response_code(404);
    echo "<h1>404 - {$message}</h1>";
    exit;
}
```

### Error Scenarios
1. **No matching route**: Returns 404
2. **Controller not found**: Returns 404 with message
3. **Method not found**: Returns 404 with message
4. **Invalid parameters**: Handled by controller

### Custom Error Pages
Controllers can render custom error views:
```php
if (!$order) {
    http_response_code(404);
    $this->view('errors/404');
    return;
}
```

## Route Best Practices

### RESTful Conventions
```php
'/resources' => 'index',           // GET: List
'/resources/create' => 'create',   // GET: Form
'/resources/store' => 'store',     // POST: Save
'/resources/{id}' => 'show',       // GET: View
'/resources/{id}/edit' => 'edit',  // GET: Form
'/resources/{id}/update' => 'update', // POST: Save
'/resources/{id}/delete' => 'delete', // POST: Delete
```

### Naming Conventions
1. Use lowercase URLs
2. Use hyphens for multi-word routes
3. Keep URLs descriptive and concise
4. Group related routes together
5. Use consistent patterns

### Security Considerations
1. All routes require authentication (except login)
2. CSRF tokens required for state-changing operations
3. Role-based access control in controllers
4. Parameter validation in controllers
5. SQL injection prevention via prepared statements

## Route Debugging

### Debug Mode
Enable in `.env`:
```env
APP_DEBUG=true
```

### Logging Route Matches
```php
// In Router::matchRoute()
error_log("Attempting to match: $url");
error_log("Against pattern: $pattern");
error_log("Match result: " . ($matches ? 'true' : 'false'));
```

### Common Issues
1. **Trailing slashes**: Routes don't match with trailing slash
2. **Case sensitivity**: Routes are case-insensitive
3. **Parameter format**: Only alphanumeric and underscore allowed
4. **Route order**: First match wins, order matters

---

*Last Updated: August 2025*
*Router Version: 2.0*
*Total Routes: 55 active, 3 planned*
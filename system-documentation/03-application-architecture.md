# Application Architecture

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Directory Structure](#directory-structure)
3. [MVC Pattern Implementation](#mvc-pattern-implementation)
4. [Request Lifecycle](#request-lifecycle)
5. [Core Framework Components](#core-framework-components)
6. [Autoloading System](#autoloading-system)
7. [Bootstrap Process](#bootstrap-process)
8. [Configuration Management](#configuration-management)
9. [Design Patterns Used](#design-patterns-used)

---

## Architecture Overview

The AZTEAM CRM/ERP system implements a custom Model-View-Controller (MVC) architecture without external framework dependencies. This lightweight approach provides full control over the application behavior while maintaining clean separation of concerns.

### Key Architectural Decisions
1. **Custom Framework**: No external dependencies (Laravel, Symfony, etc.)
2. **Single Entry Point**: All requests route through index.php
3. **PSR-4 Autoloading**: Namespace-based class loading
4. **File-based Sessions**: Simple session storage without database overhead
5. **CDN Dependencies**: Frontend libraries loaded from CDNs
6. **No Build Process**: Direct file editing without compilation

## Directory Structure

```
/opt/lampp/htdocs/azteamcrm/
│
├── app/                        # Application core
│   ├── Controllers/           # Request handlers
│   │   ├── AuthController.php
│   │   ├── CustomerController.php
│   │   ├── DashboardController.php
│   │   ├── OrderController.php
│   │   ├── OrderItemController.php
│   │   ├── ProductionController.php
│   │   └── UserController.php
│   │
│   ├── Core/                  # Framework classes
│   │   ├── Controller.php    # Base controller
│   │   ├── Database.php      # Database singleton
│   │   ├── Model.php         # Active Record base
│   │   └── Router.php        # URL routing
│   │
│   ├── Models/               # Data models
│   │   ├── Customer.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── User.php
│   │   └── [Legacy: LineItem.php, OrderPayment.php]
│   │
│   └── Views/                # Presentation layer
│       ├── auth/            # Authentication views
│       ├── customers/       # Customer views
│       ├── dashboard/       # Dashboard views
│       ├── errors/          # Error pages
│       ├── layouts/         # Shared layouts
│       ├── order-items/     # Order item views
│       ├── orders/          # Order views
│       ├── production/      # Production views
│       └── users/           # User management views
│
├── assets/                    # Static assets
│   ├── css/
│   │   ├── app.css          # Main styles
│   │   └── login.css        # Login page styles
│   └── js/
│       └── app.js           # Main JavaScript
│
├── config/                    # Configuration files
│   ├── config.php           # Application config
│   ├── database.php         # Database config
│   └── routes.php           # Route definitions
│
├── public/                    # Public assets
│   └── [Currently empty]
│
├── storage/                   # Writable storage
│   ├── logs/                # Application logs
│   ├── sessions/            # PHP session files
│   └── uploads/             # File uploads
│
├── system-documentation/      # System documentation
│   └── [Documentation files]
│
├── .env                      # Environment variables
├── .htaccess                 # Apache configuration
├── bootstrap.php             # Application bootstrap
├── index.php                 # Entry point
└── azteamerp.sql            # Database schema
```

### Directory Responsibilities

**`/app`**: Core application logic
- Controllers handle HTTP requests
- Models manage data and business logic
- Views render HTML output
- Core contains framework components

**`/assets`**: Frontend resources
- CSS for styling
- JavaScript for interactivity
- No build process required

**`/config`**: Configuration files
- Centralized application settings
- Route definitions
- Database configuration

**`/storage`**: Writable directories
- Session storage
- Log files
- User uploads

## MVC Pattern Implementation

### Model Layer
```php
namespace App\Models;
use App\Core\Model;

class Order extends Model {
    protected $table = 'orders';
    protected $primaryKey = 'order_id';
    protected $fillable = [...];
    
    // Business logic methods
    public function calculateTotal() { }
    public function syncStatusFromItems() { }
}
```

### View Layer
```php
// Views are PHP templates with embedded logic
// Located in /app/Views/{module}/{action}.php
<?php include __DIR__ . '/../layouts/header.php'; ?>
<div class="content">
    <?php foreach($orders as $order): ?>
        <!-- Display logic -->
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
```

### Controller Layer
```php
namespace App\Controllers;
use App\Core\Controller;

class OrderController extends Controller {
    public function index() {
        $this->requireAuth();
        $orders = $this->getOrders();
        $this->view('orders/index', ['orders' => $orders]);
    }
}
```

## Request Lifecycle

```
1. HTTP Request
   ↓
2. Apache .htaccess
   - Rewrites URL to index.php?route=...
   ↓
3. index.php (Entry Point)
   - Includes bootstrap.php
   - Creates Router instance
   - Dispatches request
   ↓
4. bootstrap.php
   - Sets up environment
   - Configures autoloading
   - Initializes settings
   ↓
5. Router
   - Matches URL to route pattern
   - Extracts parameters
   - Instantiates controller
   ↓
6. Controller
   - Checks authentication
   - Validates CSRF token
   - Processes request
   - Loads models
   ↓
7. Model
   - Queries database
   - Applies business logic
   - Returns data
   ↓
8. Controller
   - Prepares view data
   - Renders view
   ↓
9. View
   - Generates HTML
   - Includes layouts
   ↓
10. HTTP Response
```

## Core Framework Components

### 1. Database Class (`/app/Core/Database.php`)
Singleton pattern for database connections.

```php
class Database {
    private static $instance = null;
    private $connection;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // PDO connection setup
        $this->connection = new PDO(...);
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
```

**Features:**
- Singleton pattern ensures single connection
- PDO for database abstraction
- Prepared statements for security
- Transaction support
- Connection pooling

### 2. Model Class (`/app/Core/Model.php`)
Active Record base class for data models.

```php
abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $attributes = [];
    
    public function find($id) { }
    public function findAll($conditions = []) { }
    public function create($data) { }
    public function update() { }
    public function delete() { }
    public function save() { }
}
```

**Features:**
- Active Record pattern
- CRUD operations
- Mass assignment protection
- Query builder methods
- Relationship support
- Automatic timestamps

### 3. Controller Class (`/app/Core/Controller.php`)
Base controller with common functionality.

```php
class Controller {
    protected function view($view, $data = []) { }
    protected function redirect($url) { }
    protected function json($data, $statusCode = 200) { }
    protected function requireAuth() { }
    protected function requireRole($role) { }
    protected function csrf() { }
    protected function verifyCsrf() { }
    protected function validate($data, $rules) { }
    protected function sanitize($input) { }
}
```

**Features:**
- View rendering
- URL redirection
- JSON responses
- Authentication checks
- CSRF protection
- Input validation
- Data sanitization

### 4. Router Class (`/app/Core/Router.php`)
URL routing and dispatching.

```php
class Router {
    private $routes = [];
    private $params = [];
    
    public function dispatch($url) {
        $route = $this->matchRoute($url);
        $this->executeRoute($route);
    }
    
    private function matchRoute($url) {
        // Pattern matching with parameter extraction
        // Converts /orders/{id} to regex pattern
    }
}
```

**Features:**
- Pattern-based routing
- Parameter extraction
- Controller instantiation
- Method invocation
- 404 handling

## Autoloading System

### PSR-4 Implementation
```php
// bootstrap.php
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = APP_PATH . '/';
    
    // Check if class uses our namespace
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    
    // Convert namespace to file path
    $relative_class = substr($class, strlen($prefix));
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // Include file if it exists
    if (file_exists($file)) {
        require $file;
    }
});
```

### Namespace Structure
- `App\Controllers\*` → `/app/Controllers/*.php`
- `App\Models\*` → `/app/Models/*.php`
- `App\Core\*` → `/app/Core/*.php`

## Bootstrap Process

### Initialization Sequence (`bootstrap.php`)

1. **Define Constants**
```php
define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('CONFIG_PATH', ROOT_PATH . '/config');
```

2. **Register Autoloader**
```php
spl_autoload_register(function ($class) { ... });
```

3. **Load Environment Variables**
```php
if (file_exists(ROOT_PATH . '/.env')) {
    // Parse .env file
    // Set $_ENV and $_SERVER variables
}
```

4. **Configure Error Reporting**
```php
if (getenv('APP_ENV') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('log_errors', 1);
}
```

5. **Set Timezone**
```php
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/New_York');
```

6. **Configure Sessions**
```php
ini_set('session.save_path', STORAGE_PATH . '/sessions');
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_httponly', 1);
```

7. **Create Storage Directories**
```php
foreach ($storage_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
```

## Configuration Management

### Environment Configuration (`.env`)
```env
APP_NAME="AZTEAM CRM"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/azteamcrm
APP_TIMEZONE=America/New_York

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=azteamerp
DB_USERNAME=root
DB_PASSWORD=

SESSION_LIFETIME=30
```

### Route Configuration (`/config/routes.php`)
```php
return [
    '/' => 'DashboardController@index',
    '/login' => 'AuthController@showLogin',
    '/orders' => 'OrderController@index',
    '/orders/{id}' => 'OrderController@show',
    '/orders/{id}/edit' => 'OrderController@edit',
    // ... more routes
];
```

### Database Configuration (`/config/database.php`)
```php
return [
    'driver' => env('DB_CONNECTION', 'mysql'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'azteamerp'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
```

## Design Patterns Used

### 1. MVC (Model-View-Controller)
- **Purpose**: Separation of concerns
- **Implementation**: Separate directories for M, V, C

### 2. Singleton Pattern
- **Purpose**: Single database connection
- **Implementation**: Database::getInstance()

### 3. Active Record Pattern
- **Purpose**: Simplified database operations
- **Implementation**: Base Model class

### 4. Front Controller Pattern
- **Purpose**: Single entry point
- **Implementation**: index.php handles all requests

### 5. Template Method Pattern
- **Purpose**: Common controller functionality
- **Implementation**: Base Controller class

### 6. Registry Pattern
- **Purpose**: Global configuration access
- **Implementation**: $_ENV and $_SERVER superglobals

### 7. Factory Pattern
- **Purpose**: Object creation
- **Implementation**: Router creates controllers

### 8. Strategy Pattern
- **Purpose**: Different authentication strategies
- **Implementation**: Role-based access control

## Performance Considerations

### Optimization Strategies
1. **Database Connection Pooling**: Singleton pattern
2. **Lazy Loading**: Load models only when needed
3. **View Caching**: Future implementation
4. **Query Optimization**: Indexed columns
5. **Autoloader Caching**: Opcache for production

### Scalability Features
1. **Stateless Design**: Ready for horizontal scaling
2. **Database Abstraction**: Easy to switch databases
3. **Modular Architecture**: Add features without affecting core
4. **CDN Assets**: Offload static resources
5. **Session Storage**: Can move to Redis/Memcached

---

*Last Updated: August 2025*
*Architecture Version: 2.0*
*Framework: Custom MVC*
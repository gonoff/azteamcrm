<?php

define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('CONFIG_PATH', ROOT_PATH . '/config');

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = APP_PATH . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load environment variables
if (file_exists(ROOT_PATH . '/.env')) {
    $lines = file(ROOT_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Error reporting
if (getenv('APP_ENV') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', STORAGE_PATH . '/logs/error.log');
}

// Set timezone
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/New_York');

// Session configuration
$sessionPath = STORAGE_PATH . '/sessions';

// Check if session directory is writable, if not use system default
if (is_writable($sessionPath)) {
    ini_set('session.save_path', $sessionPath);
} else {
    // Try to make it writable
    @chmod($sessionPath, 0777);
    if (is_writable($sessionPath)) {
        ini_set('session.save_path', $sessionPath);
    }
    // If still not writable, PHP will use system default (/tmp)
}

ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', getenv('APP_ENV') === 'production' ? 1 : 0);

// Create storage directories if they don't exist
$storage_dirs = [
    STORAGE_PATH . '/logs',
    STORAGE_PATH . '/sessions',
    STORAGE_PATH . '/uploads'
];

foreach ($storage_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Helper function to get environment variables
function env($key, $default = null)
{
    $value = getenv($key);
    
    if ($value === false) {
        return $default;
    }
    
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'empty':
        case '(empty)':
            return '';
        case 'null':
        case '(null)':
            return null;
    }
    
    return $value;
}
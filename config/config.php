<?php

return [
    'app' => [
        'name' => env('APP_NAME', 'AZTEAM CRM'),
        'url' => env('APP_URL', 'http://localhost/azteamcrm'),
        'env' => env('APP_ENV', 'development'),
        'debug' => env('APP_DEBUG', true),
        'timezone' => env('APP_TIMEZONE', 'America/New_York'),
    ],
    
    'session' => [
        'lifetime' => 120,
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => STORAGE_PATH . '/sessions',
    ],
    
    'upload' => [
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        'path' => STORAGE_PATH . '/uploads',
    ],
    
    'pagination' => [
        'per_page' => 20,
    ],
    
    'company' => [
        'name' => 'AZTEAM',
        'address' => '',
        'phone' => '',
        'email' => 'hanieldesigner@gmail.com',
    ],
];
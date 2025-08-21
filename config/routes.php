<?php

return [
    // Authentication routes
    '/login' => 'AuthController@showLogin',
    '/login/submit' => 'AuthController@login',
    '/logout' => 'AuthController@logout',
    
    // Dashboard
    '/' => 'DashboardController@index',
    '/dashboard' => 'DashboardController@index',
    
    // Orders - COMMENTED: Missing views (orders/index.php, orders/show.php, orders/form.php)
    // '/orders' => 'OrderController@index',
    // '/orders/create' => 'OrderController@create',
    // '/orders/store' => 'OrderController@store',
    // '/orders/{id}' => 'OrderController@show',
    // '/orders/{id}/edit' => 'OrderController@edit',
    // '/orders/{id}/update' => 'OrderController@update',
    // '/orders/{id}/delete' => 'OrderController@delete',
    // '/orders/{id}/update-status' => 'OrderController@updateStatus',
    
    // Line Items - COMMENTED: Controller doesn't exist
    // '/orders/{order_id}/line-items' => 'LineItemController@index',
    // '/orders/{order_id}/line-items/create' => 'LineItemController@create',
    // '/orders/{order_id}/line-items/store' => 'LineItemController@store',
    // '/line-items/{id}/edit' => 'LineItemController@edit',
    // '/line-items/{id}/update' => 'LineItemController@update',
    // '/line-items/{id}/delete' => 'LineItemController@delete',
    // '/line-items/{id}/update-status' => 'LineItemController@updateStatus',
    
    // Users Management
    '/users' => 'UserController@index',
    '/users/create' => 'UserController@create',
    '/users/store' => 'UserController@store',
    '/users/{id}/edit' => 'UserController@edit',
    '/users/{id}/update' => 'UserController@update',
    '/users/{id}/delete' => 'UserController@delete',
    '/users/{id}/toggle-status' => 'UserController@toggleStatus',
    
    // User Profile
    '/profile' => 'UserController@profile',
    '/profile/update-password' => 'UserController@updatePassword',
    
    // Production - COMMENTED: Controller doesn't exist
    // '/production' => 'ProductionController@index',
    // '/production/pending' => 'ProductionController@pending',
    
    // Reports - COMMENTED: Controller doesn't exist
    // '/reports' => 'ReportController@index',
    // '/reports/financial' => 'ReportController@financial',
    // '/reports/production' => 'ReportController@production',
];
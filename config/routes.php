<?php

return [
    // Authentication routes
    '/login' => 'AuthController@showLogin',
    '/login/submit' => 'AuthController@login',
    '/logout' => 'AuthController@logout',
    
    // Dashboard
    '/' => 'DashboardController@index',
    '/dashboard' => 'DashboardController@index',
    
    // Customers
    '/customers' => 'CustomerController@index',
    '/customers/search' => 'CustomerController@search',
    '/customers/create' => 'CustomerController@create',
    '/customers/store' => 'CustomerController@store',
    '/customers/{id}' => 'CustomerController@show',
    '/customers/{id}/edit' => 'CustomerController@edit',
    '/customers/{id}/update' => 'CustomerController@update',
    '/customers/{id}/delete' => 'CustomerController@delete',
    '/customers/{id}/toggle-status' => 'CustomerController@toggleStatus',
    
    // Orders
    '/orders' => 'OrderController@index',
    '/orders/create' => 'OrderController@create',
    '/orders/store' => 'OrderController@store',
    '/orders/{id}' => 'OrderController@show',
    '/orders/{id}/edit' => 'OrderController@edit',
    '/orders/{id}/update' => 'OrderController@update',
    '/orders/{id}/delete' => 'OrderController@delete',
    '/orders/{id}/update-status' => 'OrderController@updateStatus',
    '/orders/{id}/update-shipping' => 'OrderController@updateShipping',
    '/orders/{id}/update-discount' => 'OrderController@updateDiscount',
    '/orders/{id}/toggle-tax' => 'OrderController@toggleTax',
    '/orders/{id}/cancel' => 'OrderController@cancelOrder',
    '/orders/{id}/process-payment' => 'OrderController@processPayment',
    
    // Order Items (managed via modals in order show page)
    '/orders/{order_id}/order-items/create' => 'OrderItemController@create',
    '/orders/{order_id}/order-items/store' => 'OrderItemController@store',
    '/order-items/{id}/edit' => 'OrderItemController@edit',
    '/order-items/{id}/update' => 'OrderItemController@update',
    '/order-items/{id}/delete' => 'OrderItemController@delete',
    '/order-items/{id}/update-status' => 'OrderItemController@updateStatus',
    '/order-items/{id}/update-inline' => 'OrderItemController@updateInline',
    
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
    
    // Production
    '/production' => 'ProductionController@index',
    '/production/pending' => 'ProductionController@pending',
    '/production/today' => 'ProductionController@today',
    '/production/materials' => 'ProductionController@materials',
    '/production/bulk-update' => 'ProductionController@updateBulkStatus',
    
    // Reports - COMMENTED: Controller doesn't exist
    // '/reports' => 'ReportController@index',
    // '/reports/financial' => 'ReportController@financial',
    // '/reports/production' => 'ReportController@production',
];
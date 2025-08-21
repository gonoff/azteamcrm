<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\LineItem;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        
        $order = new Order();
        $lineItem = new LineItem();
        
        // Get statistics
        $stats = [
            'total_orders' => $order->count(),
            'pending_orders' => $order->count(['payment_status' => 'unpaid']),
            'rush_orders' => $order->count(['is_rush_order' => 1]),
            'total_revenue' => $order->getTotalRevenue(),
            'outstanding_balance' => $order->getTotalOutstanding(),
            'orders_due_today' => $order->countDueToday(),
            'orders_overdue' => $order->countOverdue(),
            'items_in_production' => $lineItem->countInProduction()
        ];
        
        // Get recent orders
        $recentOrders = $order->findAll([], 'created_at DESC', 10);
        
        // Get urgent orders (rush or due soon)
        $urgentOrders = $order->getUrgentOrders();
        
        $this->view('dashboard/index', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'urgentOrders' => $urgentOrders,
            'user' => $_SESSION
        ]);
    }
}
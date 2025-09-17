<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $this->requireFeature('dashboard');
        
        $order = new Order();
        $orderItem = new OrderItem();
        
        // Get statistics with corrected logic
        $stats = [
            'total_orders' => $order->count() - $order->count(['order_status' => 'cancelled']),
            'pending_orders' => $order->countPendingOrders(), // Production status, not payment status
            'unpaid_orders' => $order->countUnpaidOrders(), // Payment status
            'rush_orders' => $order->countRushOrders(), // Efficient SQL-based calculation
            'total_revenue' => $order->getTotalRevenue(), // Now includes tax, shipping, minus discounts
            'outstanding_balance' => $order->getTotalOutstanding(), // Now calculates actual remaining balance
            'orders_due_today' => $order->countDueToday(),
            'orders_overdue' => $order->countOverdue(),
            'items_in_production' => $orderItem->countInProduction() // Now only counts items actually in production
        ];
        
        // Get recent orders (excluding cancelled)
        $recentOrders = $order->getRecentActiveOrders(10);
        
        // Load customer data for recent orders
        foreach ($recentOrders as $ord) {
            $ord->customer = $ord->getCustomer();
        }
        
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

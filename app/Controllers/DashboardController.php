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
        $this->requireAuth();
        
        $order = new Order();
        $orderItem = new OrderItem();
        
        // Get statistics
        $stats = [
            'total_orders' => $order->count(),
            'pending_orders' => $order->count(['payment_status' => 'unpaid']),
            'rush_orders' => 0, // Will be calculated based on date_due
            'total_revenue' => $order->getTotalRevenue(),
            'outstanding_balance' => $order->getTotalOutstanding(),
            'orders_due_today' => $order->countDueToday(),
            'orders_overdue' => $order->countOverdue(),
            'items_in_production' => $orderItem->countInProduction()
        ];
        
        // Get recent orders
        $recentOrders = $order->findAll([], 'date_created DESC', 10);
        
        // Load customer data for recent orders
        foreach ($recentOrders as $ord) {
            $ord->customer = $ord->getCustomer();
        }
        
        // Get urgent orders (rush or due soon)
        $urgentOrders = $order->getUrgentOrders();
        
        // Count rush orders (due within 7 days)
        $rushCount = 0;
        $sevenDaysFromNow = strtotime('+7 days');
        foreach ($order->findAll([], 'date_due ASC') as $ord) {
            if (strtotime($ord->date_due) <= $sevenDaysFromNow && $ord->payment_status !== 'paid') {
                $rushCount++;
            }
        }
        $stats['rush_orders'] = $rushCount;
        
        $this->view('dashboard/index', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'urgentOrders' => $urgentOrders,
            'user' => $_SESSION
        ]);
    }
}
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;

class ProductionController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }
    
    public function index()
    {
        $orderItem = new OrderItem();
        
        // Get all active production items using model method
        $productionItems = $orderItem->getProductionItems();
        
        // Calculate statistics from the retrieved items
        $stats = [
            'total_pending' => 0,
            'in_production' => 0,
            'completed_today' => 0,
            'rush_items' => 0,
            'overdue_items' => 0,
            'awaiting_supplier' => 0
        ];
        
        // Calculate stats from production items
        foreach ($productionItems as $item) {
            // Update statistics
            if ($item->order_item_status === 'pending') {
                $stats['total_pending']++;
            } elseif ($item->order_item_status === 'in_production') {
                $stats['in_production']++;
            }
            
            if ($item->urgency_level === 'rush' || $item->urgency_level === 'due_soon') {
                $stats['rush_items']++;
            }
            
            if ($item->urgency_level === 'overdue') {
                $stats['overdue_items']++;
            }
            
            if ($item->supplier_status === 'awaiting_order' || $item->supplier_status === 'order_made') {
                $stats['awaiting_supplier']++;
            }
        }
        
        // Get items completed today using model method
        $stats['completed_today'] = $orderItem->getCompletedTodayCount();
        
        // Filter items due today
        $itemsDueToday = array_filter($productionItems, function($item) {
            return $item->urgency_level === 'due_today';
        });
        
        $this->view('production/index', [
            'title' => 'Production Dashboard',
            'stats' => $stats,
            'productionItems' => $productionItems,
            'itemsDueToday' => $itemsDueToday,
            'csrf_token' => $this->csrf()
        ]);
    }
    
    public function pending()
    {
        $orderItem = new OrderItem();
        
        // Get all pending items using model method
        $pendingItems = $orderItem->getPendingProductionItems();
        
        $this->view('production/pending', [
            'title' => 'Pending Production Items',
            'pendingItems' => $pendingItems,
            'csrf_token' => $this->csrf()
        ]);
    }
    
    public function today()
    {
        $orderItem = new OrderItem();
        
        // Get items for today's schedule using model method
        $todayItems = $orderItem->getItemsDueToday();
        
        $this->view('production/today', [
            'title' => "Today's Production Schedule",
            'todayItems' => $todayItems,
            'csrf_token' => $this->csrf()
        ]);
    }
    
    public function materials()
    {
        $orderItem = new OrderItem();
        
        // Get materials summary using model method
        $materials = $orderItem->getMaterialsSummary();
        
        $this->view('production/materials', [
            'title' => 'Materials Report',
            'materials' => $materials,
            'csrf_token' => $this->csrf()
        ]);
    }
    
    public function updateBulkStatus()
    {
        $this->requireAuth();
        $this->verifyCsrf();
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $itemIds = $_POST['item_ids'] ?? [];
        $statusType = $_POST['status_type'] ?? '';
        $statusValue = $_POST['status_value'] ?? '';
        
        if (empty($itemIds) || empty($statusType) || empty($statusValue)) {
            $this->json(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }
        
        $orderItem = new OrderItem();
        $successCount = 0;
        $failCount = 0;
        
        foreach ($itemIds as $itemId) {
            $item = $orderItem->find($itemId);
            if ($item) {
                if ($statusType === 'order_item_status') {
                    if ($item->updateStatus($statusValue)) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                } elseif ($statusType === 'supplier_status') {
                    if ($item->updateSupplierStatus($statusValue)) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                }
            } else {
                $failCount++;
            }
        }
        
        // Sync order statuses for affected orders (only if updating item status, not supplier status)
        if ($statusType === 'order_item_status' && $successCount > 0) {
            $affectedOrders = [];
            foreach ($itemIds as $itemId) {
                $item = $orderItem->find($itemId);
                if ($item && !in_array($item->order_id, $affectedOrders)) {
                    $affectedOrders[] = $item->order_id;
                }
            }
            
            $order = new Order();
            foreach ($affectedOrders as $orderId) {
                $orderData = $order->find($orderId);
                if ($orderData) {
                    $orderData->syncStatusFromItems();
                }
            }
        }
        
        if ($successCount > 0 && $failCount === 0) {
            $this->json([
                'success' => true,
                'message' => "Successfully updated {$successCount} items"
            ]);
        } elseif ($successCount > 0) {
            $this->json([
                'success' => true,
                'message' => "Updated {$successCount} items, {$failCount} failed"
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'Failed to update items'
            ]);
        }
    }
}
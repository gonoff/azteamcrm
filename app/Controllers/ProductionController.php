<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Services\SettingsService;

class ProductionController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }
    
    public function index()
    {
        $orderItem = new OrderItem();
        
        // Get pagination parameters
        $page = intval($_GET['page'] ?? 1);
        $perPage = SettingsService::getProductionPageSize(); // Show more items on production dashboard
        $search = trim($_GET['search'] ?? '');
        $allowedSortColumns = ['date_due', 'order_id', 'order_item_id'];

        // Get paginated production items using efficient SQL pagination
        if (!empty($search)) {
            $result = $orderItem->searchAndPaginate(
                $search,
                ['product_type', 'product_description', 'note_item'],
                $page,
                $perPage,
                ['order_item_status' => ['pending', 'artwork_sent_for_approval', 'artwork_approved', 'nesting_digitalization_done']], // Active items only
                'date_due ASC',
                $allowedSortColumns
            );
        } else {
            // Use efficient SQL-based pagination instead of loading all items into memory
            $result = $orderItem->getProductionItemsPaginated($page, $perPage, 'date_due ASC');
        }
        
        $productionItems = $result['data'];
        
        // Get accurate statistics from complete dataset (not just paginated results)
        $stats = $orderItem->getProductionStatistics();
        
        // Filter items due today
        $itemsDueToday = array_filter($productionItems, function($item) {
            return $item->urgency_level === 'due_today';
        });
        
        $this->view('production/index', [
            'title' => 'Production Dashboard',
            'stats' => $stats,
            'productionItems' => $productionItems,
            'itemsDueToday' => $itemsDueToday,
            'pagination' => $result['pagination'],
            'search_term' => $search,
            'pagination_html' => $this->renderPagination($result['pagination'], '/azteamcrm/production', ['search' => $search]),
            'pagination_info' => $this->renderPaginationInfo($result['pagination']),
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
        
        // Create OrderItem instance to use its methods for consistent labeling
        $orderItemInstance = new OrderItem();
        
        $this->view('production/materials', [
            'title' => 'Materials Report',
            'materials' => $materials,
            'orderItemHelper' => $orderItemInstance,
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
    
    public function supplierTracking()
    {
        $tab = $_GET['tab'] ?? 'active'; // Default to active orders tab
        $sort = $_GET['sort'] ?? null; // Get sort parameter
        
        // Validate and set default sort based on tab
        $validSorts = ['urgency', 'due_date_asc', 'due_date_desc', 'order_date_asc', 'order_date_desc', 'customer_name'];
        
        if (!in_array($sort, $validSorts)) {
            // Set appropriate defaults for each tab
            $sort = ($tab === 'completed') ? 'due_date_desc' : 'urgency';
        }
        
        $orderItem = new OrderItem();
        
        if ($tab === 'completed') {
            $ordersData = $orderItem->getCompletedOrdersData($sort);
        } else {
            $ordersData = $orderItem->getSupplierTrackingData($sort);
        }
        
        $this->view('production/supplier-tracking', [
            'orders' => $ordersData,
            'activeTab' => $tab,
            'currentSort' => $sort,
            'refreshInterval' => SettingsService::getSupplierTrackingRefreshInterval(),
            'csrf_token' => $this->csrf()
        ]);
    }
    
    public function updateMaterialPrepared()
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $this->verifyCsrf();
        
        $itemId = intval($_POST['item_id'] ?? 0);
        $prepared = isset($_POST['prepared']) && $_POST['prepared'] === '1';
        
        if (!$itemId) {
            $this->json(['success' => false, 'message' => 'Invalid item ID']);
            return;
        }
        
        $orderItem = new OrderItem();
        $item = $orderItem->find($itemId);
        
        if (!$item) {
            $this->json(['success' => false, 'message' => 'Item not found']);
            return;
        }
        
        if ($item->updateMaterialPrepared($prepared)) {
            $this->json(['success' => true, 'message' => 'Material preparation status updated successfully']);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to update material preparation status']);
        }
    }
}

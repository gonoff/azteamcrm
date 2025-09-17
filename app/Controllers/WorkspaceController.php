<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Customer;

class WorkspaceController extends Controller
{
    public function __construct()
    {
        $this->requireFeature('workspace');
    }
    
    /**
     * Main workspace view with tabs
     */
    public function index()
    {
        $this->view('workspace/index', [
            'title' => 'Personal Workspace',
            'csrf_token' => $this->csrf()
        ]);
    }
    
    
    
    
    /**
     * AJAX endpoint to get tab data
     */
    public function getTabData($tab = 'available')
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $orderItem = new OrderItem();
        $userId = $_SESSION['user_id'];
        $items = [];
        
        switch ($tab) {
            case 'available':
                $items = $orderItem->getAllPendingItems();
                break;
            case 'artwork-sent':
                $items = $orderItem->getAllArtworkSent();
                break;
            case 'artwork-approved':
                $items = $orderItem->getAllArtworkApproved();
                break;
            case 'nesting-done':
                $items = $orderItem->getAllNestingDone();
                break;
            case 'completed':
                $items = $orderItem->getUserCompleted($userId);
                break;
            default:
                $this->json(['success' => false, 'message' => 'Invalid tab']);
                return;
        }
        
        // Format items for display (data already loaded via JOIN - no N+1 queries)
        $formattedItems = [];
        foreach ($items as $item) {
            $formattedItems[] = [
                'order_item_id' => $item->order_item_id,
                'order_id' => $item->order_id,
                'order_number' => 'Order #' . str_pad($item->order_id, 3, '0', STR_PAD_LEFT),
                'customer_name' => $item->customer_name ?? 'Unknown Customer',
                'company_name' => $item->company_name ?? null,
                'quantity' => $item->quantity,
                'product_type' => $item->getProductTypeLabel(),
                'product_size' => $item->getSizeLabel(),
                'product_description' => $item->product_description,
                'custom_method' => $item->getCustomMethodLabel(),
                'custom_area' => $item->custom_area,
                'due_date' => $item->date_due ?? null,
                'is_overdue' => ($item->urgency_level ?? '') === 'overdue',
                'is_rush' => ($item->urgency_level ?? '') === 'rush',
                'is_due_soon' => ($item->urgency_level ?? '') === 'due_soon',
                'status' => $item->order_item_status,
                'completed_at' => $item->completed_at,
                'note_item' => $item->note_item
            ];
        }
        
        $this->json([
            'success' => true,
            'tab' => $tab,
            'items' => $formattedItems,
            'count' => count($formattedItems)
        ]);
    }
    
    /**
     * AJAX endpoint to advance order item to next status in workflow
     */
    public function advanceItem($id)
    {
        $this->verifyCsrf();
        
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        $orderItem = new OrderItem();
        $item = $orderItem->find($id);
        
        if (!$item) {
            $this->json(['success' => false, 'message' => 'Order item not found']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        if (!$item->canAdvanceStatus()) {
            $this->json(['success' => false, 'message' => 'Item cannot be advanced further']);
            return;
        }
        
        $nextStatus = $item->getNextStatus();
        if (!$nextStatus) {
            $this->json(['success' => false, 'message' => 'No next status available']);
            return;
        }
        
        // Advance the item
        $success = $item->advanceStatus($userId);
        
        if ($success) {
            // Sync order status if item was completed
            if ($item->order_item_status === OrderItem::STATUS_COMPLETED) {
                $order = new Order();
                $order = $order->find($item->order_id);
                if ($order) {
                    $order->syncStatusFromItems();
                }
            }
            
            $statusLabels = OrderItem::getStatusLabels();
            $nextStatusLabel = $statusLabels[$nextStatus] ?? ucfirst(str_replace('_', ' ', $nextStatus));
            
            $this->json([
                'success' => true,
                'message' => 'Item advanced to: ' . $nextStatusLabel,
                'item_id' => $id,
                'status' => $nextStatus,
                'status_label' => $nextStatusLabel
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to advance item']);
        }
    }
}

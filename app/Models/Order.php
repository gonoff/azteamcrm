<?php

namespace App\Models;

use App\Core\Model;
use App\Services\SettingsService;

class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'order_id';
    protected $fillable = [
        'order_status', 'payment_status', 'customer_id', 'user_id',
        'order_total', 'amount_paid', 'discount_amount', 'tax_amount', 
        'shipping_amount', 'apply_ct_tax', 'date_created', 'date_due', 'order_notes'
    ];
    
    public function getOrderItems()
    {
        $orderItem = new OrderItem();
        return $orderItem->where('order_id', '=', $this->order_id);
    }
    
    public function getUser()
    {
        $user = new User();
        return $user->find($this->user_id);
    }
    
    public function getCustomer()
    {
        $customer = new Customer();
        return $customer->find($this->customer_id);
    }
    
    public function updatePaymentStatus($status, $amountPaid = null)
    {
        $this->payment_status = $status;
        $this->attributes['payment_status'] = $status;
        
        if ($amountPaid !== null) {
            $this->amount_paid = floatval($amountPaid);
            $this->attributes['amount_paid'] = floatval($amountPaid);
        }
        
        // Auto-determine payment status based on amounts
        $totalAmount = $this->getTotalAmount();
        if ($this->amount_paid >= $totalAmount) {
            $this->payment_status = 'paid';
            $this->attributes['payment_status'] = 'paid';
        } elseif ($this->amount_paid > 0) {
            $this->payment_status = 'partial';
            $this->attributes['payment_status'] = 'partial';
        } else {
            $this->payment_status = 'unpaid';
            $this->attributes['payment_status'] = 'unpaid';
        }
        
        return $this->update();
    }
    
    public function updateOrderStatus($status)
    {
        $this->order_status = $status;
        return $this->update();
    }
    
    public function syncStatusFromItems()
    {
        // Don't auto-sync if order is manually overridden
        if (in_array($this->order_status, ['cancelled', 'on_hold'])) {
            return true;
        }
        
        // Get all order items
        $orderItems = $this->getOrderItems();
        
        if (empty($orderItems)) {
            $this->order_status = 'pending';
            $this->attributes['order_status'] = 'pending';
            return $this->update();
        }
        
        $allCompleted = true;
        $anyInProduction = false;
        $allCancelled = true;
        
        foreach ($orderItems as $item) {
            if ($item->order_item_status !== 'cancelled') {
                $allCancelled = false;
            }
            
            if ($item->order_item_status !== 'completed' && $item->order_item_status !== 'cancelled') {
                $allCompleted = false;
                if ($item->order_item_status === 'in_production') {
                    $anyInProduction = true;
                }
            }
        }
        
        // Determine order status based on items
        if ($allCancelled) {
            $newStatus = 'cancelled';
        } elseif ($allCompleted) {
            $newStatus = 'completed';
        } elseif ($anyInProduction) {
            $newStatus = 'in_production';
        } else {
            $newStatus = 'pending';
        }
        
        // Update if changed
        if ($this->order_status !== $newStatus) {
            $this->order_status = $newStatus;
            $this->attributes['order_status'] = $newStatus;
            return $this->update();
        }
        
        return true;
    }
    
    public function calculateTotal()
    {
        $sql = "SELECT SUM(total_price) as total FROM order_items WHERE order_id = :order_id";
        $stmt = $this->db->query($sql, ['order_id' => $this->order_id]);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $this->order_total = $result['total'] ?? 0;
            return $this->update();
        }
        return false;
    }
    
    public function getTotalRevenue()
    {
        // Calculate total revenue including all charges (tax, shipping) minus discounts
        $sql = "SELECT SUM(
                    COALESCE(order_total, 0) + 
                    COALESCE(tax_amount, 0) + 
                    COALESCE(shipping_amount, 0) - 
                    COALESCE(discount_amount, 0)
                ) as total 
                FROM {$this->table} 
                WHERE order_status != 'cancelled'";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        }
        return 0;
    }
    
    public function getTotalOutstanding()
    {
        // Calculate actual remaining balance: (order_total + tax_amount + shipping_amount - discount_amount - amount_paid) for unpaid/partial orders
        $sql = "SELECT SUM(
                    GREATEST(0, 
                        COALESCE(order_total, 0) + 
                        COALESCE(tax_amount, 0) + 
                        COALESCE(shipping_amount, 0) - 
                        COALESCE(discount_amount, 0) - 
                        COALESCE(amount_paid, 0)
                    )
                ) as total 
                FROM {$this->table} 
                WHERE payment_status != 'paid' 
                AND order_status != 'cancelled'";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        }
        return 0;
    }
    
    public function getSubtotal()
    {
        // Calculate from order items (currently just order_total)
        return floatval($this->order_total ?? 0);
    }
    
    public function getTotalAmount()
    {
        // Subtotal + tax + shipping - discount
        $subtotal = $this->getSubtotal();
        $total = $subtotal + floatval($this->tax_amount ?? 0) + floatval($this->shipping_amount ?? 0) - floatval($this->discount_amount ?? 0);
        return max(0, $total);
    }
    
    public function getBalanceDue()
    {
        // Total amount - amount paid
        return max(0, $this->getTotalAmount() - floatval($this->amount_paid ?? 0));
    }
    
    public function getOutstandingBalance()
    {
        // Keep for backward compatibility - now uses getBalanceDue
        return $this->getBalanceDue();
    }
    
    public function calculateConnecticutTax()
    {
        // Calculate Connecticut tax if enabled
        if ($this->apply_ct_tax) {
            $subtotal = $this->getSubtotal();
            $taxRate = SettingsService::getCtTaxRate();
            return round($subtotal * $taxRate, 2);
        }
        return 0.00;
    }
    
    public function updateTax()
    {
        // Update tax amount based on Connecticut tax setting
        $newTaxAmount = $this->calculateConnecticutTax();
        $this->tax_amount = $newTaxAmount;
        $this->attributes['tax_amount'] = $newTaxAmount;
        return $this->update();
    }
    
    public function addPayment($amount, $method = null, $notes = null, $userId = null)
    {
        // Record payment in history table
        $sql = "INSERT INTO order_payments (order_id, payment_amount, payment_method, payment_notes, recorded_by) 
                VALUES (:order_id, :amount, :method, :notes, :user_id)";
        
        $params = [
            'order_id' => $this->order_id,
            'amount' => $amount,
            'method' => $method,
            'notes' => $notes,
            'user_id' => $userId ?? $_SESSION['user_id']
        ];
        
        $stmt = $this->db->query($sql, $params);
        
        if ($stmt) {
            // Update order's amount_paid
            $this->amount_paid = floatval($this->amount_paid ?? 0) + floatval($amount);
            $this->attributes['amount_paid'] = $this->amount_paid;
            
            // Update payment status
            $this->updatePaymentStatus($this->payment_status, $this->amount_paid);
            
            return true;
        }
        
        return false;
    }
    
    public function getPaymentHistory()
    {
        $sql = "SELECT p.*, u.full_name as recorded_by_name 
                FROM order_payments p 
                LEFT JOIN users u ON p.recorded_by = u.id 
                WHERE p.order_id = :order_id 
                ORDER BY p.payment_date DESC";
        
        $stmt = $this->db->query($sql, ['order_id' => $this->order_id]);
        
        if ($stmt) {
            return $stmt->fetchAll(\PDO::FETCH_OBJ);
        }
        
        return [];
    }
    
    public function countDueToday()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE date_due = CURDATE()";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        }
        return 0;
    }
    
    public function countOverdue()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE date_due < CURDATE() AND payment_status != 'paid'";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        }
        return 0;
    }
    
    public function getUrgentOrders($limit = null)
    {
        // Use default urgent orders limit if not specified
        if ($limit === null) {
            $limit = \App\Services\SettingsService::getUrgentOrdersLimit();
        }
        
        $sql = "SELECT o.*, c.full_name, c.company_name 
                FROM {$this->table} o
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE date_due <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                AND payment_status != 'paid'
                ORDER BY date_due ASC
                LIMIT :limit";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
    }
    
    public function countRushOrders()
    {
        // Count orders due within configured rush threshold that aren't paid yet
        $rushThreshold = SettingsService::getRushOrderThreshold();
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table} 
                WHERE date_due <= DATE_ADD(CURDATE(), INTERVAL {$rushThreshold} DAY) 
                AND date_due >= CURDATE()
                AND payment_status != 'paid' 
                AND order_status != 'cancelled'";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        }
        return 0;
    }
    
    public function countPendingOrders()
    {
        // Count orders with pending order_status (production status)
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table} 
                WHERE order_status = 'pending'";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        }
        return 0;
    }
    
    public function countUnpaidOrders()
    {
        // Count orders with unpaid payment_status
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table} 
                WHERE payment_status = 'unpaid' 
                AND order_status != 'cancelled'";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        }
        return 0;
    }
    
    public function getRecentActiveOrders($limit = null)
    {
        // Use default recent orders limit if not specified
        if ($limit === null) {
            $limit = \App\Services\SettingsService::getDashboardRecentOrdersLimit();
        }
        
        // Get recent orders excluding cancelled ones
        $sql = "SELECT * FROM {$this->table} 
                WHERE order_status != 'cancelled' 
                ORDER BY date_created DESC 
                LIMIT :limit";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
    }
    
    public function isOverdue()
    {
        return strtotime($this->date_due) < strtotime('today');
    }
    
    public function isDueSoon()
    {
        $dueDate = strtotime($this->date_due);
        $dueSoonThreshold = SettingsService::getDueSoonThreshold();
        $thresholdTime = strtotime("+{$dueSoonThreshold} days");
        return $dueDate <= $thresholdTime && $dueDate >= strtotime('today');
    }
    
    public function isRushOrder()
    {
        $dueDate = strtotime($this->date_due);
        $rushThreshold = SettingsService::getRushOrderThreshold();
        $rushTime = strtotime("+{$rushThreshold} days");
        return $dueDate <= $rushTime;
    }
    
    public function getStatusBadge()
    {
        if ($this->payment_status === 'paid') {
            return '<span class="badge bg-success">Paid</span>';
        } elseif ($this->payment_status === 'partial') {
            return '<span class="badge bg-warning text-dark">Partial</span>';
        } else {
            return '<span class="badge bg-danger">Unpaid</span>';
        }
    }
    
    public function getUrgencyBadge()
    {
        if ($this->isOverdue()) {
            return '<span class="badge bg-danger">Overdue</span>';
        } elseif ($this->isRushOrder()) {
            return '<span class="badge bg-danger">RUSH</span>';
        } elseif ($this->isDueSoon()) {
            return '<span class="badge bg-warning text-dark">Due Soon</span>';
        }
        return '';
    }
    
    public function getOrderStatusBadge()
    {
        $badges = [
            'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
            'in_production' => '<span class="badge bg-info text-dark">In Production</span>',
            'completed' => '<span class="badge bg-success">Completed</span>',
            'cancelled' => '<span class="badge bg-secondary">Cancelled</span>'
        ];
        
        return $badges[$this->order_status] ?? '<span class="badge bg-secondary">' . ucfirst($this->order_status) . '</span>';
    }
    
    public function getPaymentStatusBadge()
    {
        // Alias for getStatusBadge to maintain compatibility
        return $this->getStatusBadge();
    }
}
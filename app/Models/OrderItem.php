<?php

namespace App\Models;

use App\Core\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'order_item_id';
    protected $fillable = [
        'order_item_status', 'order_id', 'user_id', 'quantity',
        'unit_price', 'product_type', 'product_description',
        'product_size', 'custom_method', 'custom_area',
        'supplier_status', 'note_item'
    ];
    
    public function getOrder()
    {
        $order = new Order();
        return $order->find($this->order_id);
    }
    
    public function getUser()
    {
        $user = new User();
        return $user->find($this->user_id);
    }
    
    public function updateStatus($status)
    {
        $this->attributes['order_item_status'] = $status;
        $this->order_item_status = $status;
        return $this->update();
    }
    
    public function updateSupplierStatus($status)
    {
        $this->attributes['supplier_status'] = $status;
        $this->supplier_status = $status;
        return $this->update();
    }
    
    public function calculateTotalPrice()
    {
        // Note: total_price is a generated column in the database
        // This method is for reference if needed in PHP
        return $this->quantity * $this->unit_price;
    }
    
    public function countInProduction()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE order_item_status IN ('pending', 'in_production')";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        }
        return 0;
    }
    
    public function getPendingItems()
    {
        $sql = "SELECT oi.*, o.date_due, c.full_name, c.company_name 
                FROM {$this->table} oi
                JOIN orders o ON oi.order_id = o.order_id
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE oi.order_item_status != 'completed'
                ORDER BY o.date_due ASC";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
        }
        return [];
    }
    
    public function getStatusBadge()
    {
        // Item status is now limited to: pending, in_production, completed
        // 'cancelled' is kept for legacy data but not available in forms
        $badges = [
            'pending' => '<span class="badge badge-warning">Pending</span>',
            'in_production' => '<span class="badge badge-info">In Production</span>',
            'completed' => '<span class="badge badge-success">Completed</span>',
            'cancelled' => '<span class="badge badge-secondary">Cancelled</span>' // Legacy support
        ];
        
        return $badges[$this->order_item_status] ?? '<span class="badge badge-secondary">' . ucfirst($this->order_item_status) . '</span>';
    }
    
    public function getSupplierStatusBadge()
    {
        if (!$this->supplier_status) {
            return '<span class="badge badge-light">N/A</span>';
        }
        
        $badges = [
            'awaiting_order' => '<span class="badge badge-secondary">Awaiting Order</span>',
            'order_made' => '<span class="badge badge-info">Order Made</span>',
            'order_arrived' => '<span class="badge badge-primary">Arrived</span>',
            'order_delivered' => '<span class="badge badge-success">Delivered</span>'
        ];
        
        return $badges[$this->supplier_status] ?? '<span class="badge badge-secondary">' . ucfirst(str_replace('_', ' ', $this->supplier_status)) . '</span>';
    }
    
    public function getSizeLabel()
    {
        if (!$this->product_size) {
            return 'N/A';
        }
        
        $sizes = [
            'child_xs' => 'Child XS',
            'child_s' => 'Child S',
            'child_m' => 'Child M',
            'child_l' => 'Child L',
            'child_xl' => 'Child XL',
            'xs' => 'XS',
            's' => 'S',
            'm' => 'M',
            'l' => 'L',
            'xl' => 'XL',
            'xxl' => 'XXL',
            'xxxl' => 'XXXL',
            'xxxxl' => 'XXXXL'
        ];
        
        return $sizes[strtolower($this->product_size)] ?? $this->product_size;
    }
    
    public function getCustomMethodLabel()
    {
        if (!$this->custom_method) {
            return 'N/A';
        }
        
        $methods = [
            'htv' => 'HTV',
            'dft' => 'DFT',
            'embroidery' => 'Embroidery',
            'sublimation' => 'Sublimation',
            'printing' => 'Printing Services'
        ];
        
        return $methods[strtolower($this->custom_method)] ?? ucwords(str_replace('_', ' ', $this->custom_method));
    }
    
    public function getProductTypeLabel()
    {
        if (!$this->product_type) {
            return 'N/A';
        }
        
        return ucwords(str_replace('_', ' ', $this->product_type));
    }
    
    public function getCustomAreaLabel()
    {
        if (!$this->custom_area) {
            return 'N/A';
        }
        
        return ucwords(str_replace('_', ' ', $this->custom_area));
    }
    
    public function getUrgencyBadge()
    {
        if (!isset($this->urgency_level)) {
            return '';
        }
        
        $badges = [
            'overdue' => '<span class="badge badge-danger">OVERDUE</span>',
            'due_today' => '<span class="badge badge-warning">DUE TODAY</span>',
            'due_soon' => '<span class="badge badge-info">DUE SOON</span>',
            'rush' => '<span class="badge badge-danger">RUSH</span>',
            'normal' => ''
        ];
        
        return $badges[$this->urgency_level] ?? '';
    }
    
    public function getRowClass()
    {
        if (!isset($this->urgency_level)) {
            return '';
        }
        
        $classes = [
            'overdue' => 'table-danger',
            'due_today' => 'table-warning',
            'due_soon' => 'table-info',
            'rush' => 'table-warning',
            'normal' => ''
        ];
        
        return $classes[$this->urgency_level] ?? '';
    }
    
    public function getProductionItems()
    {
        $sql = "SELECT 
                    oi.*, 
                    o.order_id,
                    o.date_due,
                    o.order_status,
                    o.payment_status,
                    c.full_name as customer_name,
                    c.company_name,
                    c.phone_number,
                    CASE 
                        WHEN o.date_due < CURDATE() THEN 'overdue'
                        WHEN o.date_due = CURDATE() THEN 'due_today'
                        WHEN o.date_due <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 'due_soon'
                        WHEN o.date_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'rush'
                        ELSE 'normal'
                    END as urgency_level
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE oi.order_item_status != 'completed'
                    AND o.order_status NOT IN ('cancelled', 'on_hold')
                ORDER BY 
                    FIELD(urgency_level, 'overdue', 'due_today', 'due_soon', 'rush', 'normal'),
                    o.date_due ASC";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $results = $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
            // Add additional properties to each item
            foreach ($results as $item) {
                $item->customer_name = $item->customer_name ?? 'Unknown';
                $item->company_name = $item->company_name ?? '';
                $item->phone_number = $item->phone_number ?? '';
                $item->urgency_level = $item->urgency_level ?? 'normal';
            }
            return $results;
        }
        return [];
    }
    
    public function getCompletedTodayCount()
    {
        // Note: Without an updated_at column, we cannot track when items were completed
        // This would require adding timestamp tracking to the order_items table
        // For now, returning 0 as we cannot determine completion time
        return 0;
        
        /* Future implementation when updated_at column is added:
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table} 
                WHERE order_item_status = 'completed' 
                AND DATE(updated_at) = CURDATE()";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        }
        return 0;
        */
    }
    
    public function getItemsDueToday()
    {
        $sql = "SELECT 
                    oi.*, 
                    o.order_id,
                    o.date_due,
                    o.order_status,
                    c.full_name as customer_name,
                    c.company_name,
                    CASE 
                        WHEN o.date_due = CURDATE() THEN 'due_today'
                        WHEN o.date_due = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'due_tomorrow'
                        ELSE 'start_today'
                    END as priority
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE oi.order_item_status != 'completed'
                    AND o.order_status NOT IN ('cancelled', 'on_hold')
                    AND o.date_due <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)
                ORDER BY o.date_due ASC, priority ASC";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
        }
        return [];
    }
    
    public function getPendingProductionItems()
    {
        $sql = "SELECT 
                    oi.*, 
                    o.order_id,
                    o.date_due,
                    o.order_status,
                    c.full_name as customer_name,
                    c.company_name
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                JOIN customers c ON o.customer_id = c.customer_id
                WHERE oi.order_item_status = 'pending'
                    AND o.order_status NOT IN ('cancelled', 'on_hold')
                ORDER BY o.date_due ASC";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
        }
        return [];
    }
    
    public function getMaterialsSummary()
    {
        $sql = "SELECT 
                    product_type,
                    product_size,
                    custom_method,
                    COUNT(*) as item_count,
                    SUM(quantity) as total_quantity
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                WHERE oi.order_item_status != 'completed'
                    AND o.order_status NOT IN ('cancelled', 'on_hold')
                GROUP BY product_type, product_size, custom_method
                ORDER BY product_type, product_size";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        return [];
    }
}
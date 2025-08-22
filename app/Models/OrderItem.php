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
        $this->order_item_status = $status;
        return $this->update();
    }
    
    public function updateSupplierStatus($status)
    {
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
        $badges = [
            'pending' => '<span class="badge badge-warning">Pending</span>',
            'in_production' => '<span class="badge badge-info">In Production</span>',
            'completed' => '<span class="badge badge-success">Completed</span>',
            'cancelled' => '<span class="badge badge-secondary">Cancelled</span>'
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
}
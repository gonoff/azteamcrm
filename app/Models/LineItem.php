<?php

namespace App\Models;

use App\Core\Model;

class LineItem extends Model
{
    protected $table = 'line_items';
    protected $fillable = [
        'order_id', 'product_description', 'size', 'customization_method',
        'customization_areas', 'quantity', 'supplier_status', 'completion_status',
        'product_type', 'color_specification', 'line_item_notes'
    ];
    
    public function getOrder()
    {
        $order = new Order();
        return $order->find($this->order_id);
    }
    
    public function updateSupplierStatus($status)
    {
        $this->supplier_status = $status;
        return $this->update();
    }
    
    public function updateCompletionStatus($status)
    {
        $this->completion_status = $status;
        return $this->update();
    }
    
    public function countInProduction()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE completion_status IN ('waiting_approval', 'artwork_approved', 'material_prepared')";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        }
        return 0;
    }
    
    public function getPendingItems()
    {
        $sql = "SELECT li.*, o.client_name, o.due_date, o.is_rush_order 
                FROM {$this->table} li
                JOIN orders o ON li.order_id = o.id
                WHERE li.completion_status != 'work_completed'
                ORDER BY o.is_rush_order DESC, o.due_date ASC";
        
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
        }
        return [];
    }
    
    public function getSupplierStatusBadge()
    {
        $badges = [
            'awaiting_to_order' => '<span class="badge badge-secondary">Awaiting Order</span>',
            'order_made' => '<span class="badge badge-info">Order Made</span>',
            'order_arrived' => '<span class="badge badge-primary">Arrived</span>',
            'order_delivered' => '<span class="badge badge-success">Delivered</span>'
        ];
        
        return $badges[$this->supplier_status] ?? '';
    }
    
    public function getCompletionStatusBadge()
    {
        $badges = [
            'waiting_approval' => '<span class="badge badge-warning">Waiting Approval</span>',
            'artwork_approved' => '<span class="badge badge-info">Artwork Approved</span>',
            'material_prepared' => '<span class="badge badge-primary">Material Prepared</span>',
            'work_completed' => '<span class="badge badge-success">Completed</span>'
        ];
        
        return $badges[$this->completion_status] ?? '';
    }
    
    public function getSizeLabel()
    {
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
        
        return $sizes[$this->size] ?? $this->size;
    }
    
    public function getCustomizationMethodLabel()
    {
        $methods = [
            'htv' => 'HTV',
            'dft' => 'DFT',
            'embroidery' => 'Embroidery',
            'sublimation' => 'Sublimation',
            'printing_services' => 'Printing Services'
        ];
        
        return $methods[$this->customization_method] ?? $this->customization_method;
    }
    
    public function getProductTypeLabel()
    {
        return ucwords(str_replace('_', ' ', $this->product_type));
    }
    
    public function getCustomizationAreasArray()
    {
        // Convert SET field to array
        return explode(',', $this->customization_areas);
    }
}
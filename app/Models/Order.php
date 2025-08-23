<?php

namespace App\Models;

use App\Core\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'order_id';
    protected $fillable = [
        'order_status', 'payment_status', 'customer_id', 'user_id',
        'order_total', 'date_created', 'date_due', 'order_notes'
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
    
    public function updatePaymentStatus($status)
    {
        $this->payment_status = $status;
        return $this->update();
    }
    
    public function updateOrderStatus($status)
    {
        $this->order_status = $status;
        return $this->update();
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
        $sql = "SELECT SUM(order_total) as total FROM {$this->table}";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        }
        return 0;
    }
    
    public function getTotalOutstanding()
    {
        $sql = "SELECT SUM(order_total) as total FROM {$this->table} WHERE payment_status != 'paid'";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        }
        return 0;
    }
    
    public function getOutstandingBalance()
    {
        // Calculate outstanding balance for this specific order
        if ($this->payment_status === 'paid') {
            return 0;
        }
        // For unpaid or partial, return the full order total
        // In future, when payments table is added, subtract payments from total
        return floatval($this->order_total ?? 0);
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
    
    public function getUrgentOrders($limit = 10)
    {
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
    
    public function isOverdue()
    {
        return strtotime($this->date_due) < strtotime('today');
    }
    
    public function isDueSoon()
    {
        $dueDate = strtotime($this->date_due);
        $threeDaysFromNow = strtotime('+3 days');
        return $dueDate <= $threeDaysFromNow && $dueDate >= strtotime('today');
    }
    
    public function isRushOrder()
    {
        $dueDate = strtotime($this->date_due);
        $sevenDaysFromNow = strtotime('+7 days');
        return $dueDate <= $sevenDaysFromNow;
    }
    
    public function getStatusBadge()
    {
        if ($this->payment_status === 'paid') {
            return '<span class="badge badge-success">Paid</span>';
        } elseif ($this->payment_status === 'partial') {
            return '<span class="badge badge-warning">Partial</span>';
        } else {
            return '<span class="badge badge-danger">Unpaid</span>';
        }
    }
    
    public function getUrgencyBadge()
    {
        if ($this->isOverdue()) {
            return '<span class="badge badge-danger">Overdue</span>';
        } elseif ($this->isRushOrder()) {
            return '<span class="badge badge-danger">RUSH</span>';
        } elseif ($this->isDueSoon()) {
            return '<span class="badge badge-warning">Due Soon</span>';
        }
        return '';
    }
    
    public function getOrderStatusBadge()
    {
        $badges = [
            'pending' => '<span class="badge badge-warning">Pending</span>',
            'in_production' => '<span class="badge badge-info">In Production</span>',
            'completed' => '<span class="badge badge-success">Completed</span>',
            'cancelled' => '<span class="badge badge-secondary">Cancelled</span>'
        ];
        
        return $badges[$this->order_status] ?? '<span class="badge badge-secondary">' . ucfirst($this->order_status) . '</span>';
    }
}
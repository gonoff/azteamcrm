<?php

namespace App\Models;

use App\Core\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $fillable = [
        'client_name', 'client_phone', 'date_received', 'due_date',
        'total_value', 'outstanding_balance', 'payment_status',
        'captured_by_user_id', 'is_rush_order', 'order_notes'
    ];
    
    public function getLineItems()
    {
        $lineItem = new LineItem();
        return $lineItem->where('order_id', '=', $this->id);
    }
    
    public function getCapturedByUser()
    {
        $user = new User();
        return $user->find($this->captured_by_user_id);
    }
    
    public function updatePaymentStatus($status, $paidAmount = 0)
    {
        $this->payment_status = $status;
        
        if ($status === 'paid') {
            $this->outstanding_balance = 0;
        } elseif ($status === 'partial' && $paidAmount > 0) {
            $this->outstanding_balance = $this->total_value - $paidAmount;
        } else {
            $this->outstanding_balance = $this->total_value;
        }
        
        return $this->update();
    }
    
    public function getTotalRevenue()
    {
        $sql = "SELECT SUM(total_value) as total FROM {$this->table}";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        }
        return 0;
    }
    
    public function getTotalOutstanding()
    {
        $sql = "SELECT SUM(outstanding_balance) as total FROM {$this->table} WHERE payment_status != 'paid'";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        }
        return 0;
    }
    
    public function countDueToday()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE due_date = CURDATE()";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        }
        return 0;
    }
    
    public function countOverdue()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE due_date < CURDATE() AND payment_status != 'paid'";
        $stmt = $this->db->query($sql);
        
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        }
        return 0;
    }
    
    public function getUrgentOrders($limit = 10)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (is_rush_order = 1 OR due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY))
                AND payment_status != 'paid'
                ORDER BY due_date ASC, is_rush_order DESC
                LIMIT :limit";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_CLASS, static::class);
    }
    
    public function isOverdue()
    {
        return strtotime($this->due_date) < strtotime('today');
    }
    
    public function isDueSoon()
    {
        $dueDate = strtotime($this->due_date);
        $threeDaysFromNow = strtotime('+3 days');
        return $dueDate <= $threeDaysFromNow && $dueDate >= strtotime('today');
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
        if ($this->is_rush_order) {
            return '<span class="badge badge-danger">RUSH</span>';
        } elseif ($this->isOverdue()) {
            return '<span class="badge badge-danger">Overdue</span>';
        } elseif ($this->isDueSoon()) {
            return '<span class="badge badge-warning">Due Soon</span>';
        }
        return '';
    }
}
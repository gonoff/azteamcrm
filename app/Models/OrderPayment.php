<?php

namespace App\Models;

use App\Core\Model;

class OrderPayment extends Model
{
    protected $table = 'order_payments';
    protected $primaryKey = 'payment_id';
    protected $fillable = [
        'order_id', 'payment_amount', 'payment_method', 
        'payment_notes', 'recorded_by'
    ];
    
    public function getOrder()
    {
        $order = new Order();
        return $order->find($this->order_id);
    }
    
    public function getUser()
    {
        $user = new User();
        return $user->find($this->recorded_by);
    }
}
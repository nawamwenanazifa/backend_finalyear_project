<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'order_number',
        'subtotal',
        'tax',
        'delivery_fee',
        'total',
        'payment_method',
        'payment_status',
        'order_status',
        'shipping_address',
        'notes',
        'delivered_at',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
    ];

    // Generate unique order number
    public static function generateOrderNumber()
    {
        $prefix = 'FB';
        $year = date('Y');
        $month = date('m');
        $random = strtoupper(substr(uniqid(), -6));
        return $prefix . $year . $month . $random;
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Calculate total
    public function calculateTotal()
    {
        $this->total = $this->subtotal + $this->tax + $this->delivery_fee;
        return $this->total;
    }

    // Status helpers
    public function isPending()
    {
        return $this->order_status === 'pending';
    }

    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    public function isDelivered()
    {
        return $this->order_status === 'delivered';
    }
}
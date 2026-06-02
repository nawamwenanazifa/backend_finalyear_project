<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'session_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function getTotalAttribute()
    {
        // Check if items relationship exists and has items
        if (!$this->relationLoaded('items')) {
            return 0;
        }
        
        return $this->items->sum(function ($item) {
            // Check if product exists
            if (!$item->relationLoaded('product')) {
                return 0;
            }
            return ($item->product ? (float)$item->product->price : 0) * $item->quantity;
        });
    }

    public function getItemCountAttribute()
    {
        if (!$this->relationLoaded('items')) {
            return 0;
        }
        return $this->items->sum('quantity');
    }
}
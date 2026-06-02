<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Product extends Model
{
    use HasFactory, Auditable;
    
    protected $fillable = [
        'category_id', 
        'name', 
        'price', 
        'description', 
        'color', 
        'image', 
        'rating', 
        'in_stock', 
        'is_featured',
        'stock_quantity',
        'low_stock_threshold',
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'rating' => 'decimal:1',
        'in_stock' => 'boolean',
        'is_featured' => 'boolean',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
    ];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
    
    // Scope for active products
    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }
    
    // Scope for featured products
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
    
    // Scope for low stock products
    public function scopeLowStock($query)
    {
        return $query->where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }
    
    // Scope for out of stock products
    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', '<=', 0);
    }
    
    // Accessor for formatted price
    public function getFormattedPriceAttribute()
    {
        return 'UGX ' . number_format($this->price, 0);
    }
    
    // Check if product is in stock
    public function getIsInStockAttribute(): bool
    {
        return $this->stock_quantity > 0;
    }
    
    // Check if product is low on stock
    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold && $this->stock_quantity > 0;
    }
    
    // Get stock status label
    public function getStockStatusAttribute(): string
    {
        if ($this->stock_quantity <= 0) {
            return 'Out of Stock';
        }
        if ($this->stock_quantity <= $this->low_stock_threshold) {
            return 'Low Stock';
        }
        return 'In Stock';
    }
    
    // Get stock status color
    public function getStockStatusColorAttribute(): string
    {
        if ($this->stock_quantity <= 0) {
            return 'danger';
        }
        if ($this->stock_quantity <= $this->low_stock_threshold) {
            return 'warning';
        }
        return 'success';
    }
    
    // Reduce stock when order is placed
    public function reduceStock(int $quantity): void
    {
        $this->stock_quantity -= $quantity;
        $this->in_stock = $this->stock_quantity > 0;
        $this->save();
    }
    
    // Increase stock when order is cancelled or restocked
    public function increaseStock(int $quantity): void
    {
        $this->stock_quantity += $quantity;
        $this->in_stock = true;
        $this->save();
    }
    
    // Check if product has discount (for future use)
    public function getHasDiscountAttribute()
    {
        return isset($this->discount_percentage) && $this->discount_percentage > 0;
    }
}
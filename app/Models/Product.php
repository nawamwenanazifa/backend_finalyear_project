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
        'is_featured'
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'rating' => 'decimal:1',
        'in_stock' => 'boolean',
        'is_featured' => 'boolean',
    ];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function bookings()
    {
        return $this->hasMany(Booking::class);
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
    
    // Accessor for formatted price
    public function getFormattedPriceAttribute()
    {
        return 'UGX ' . number_format($this->price, 0);
    }
    
    // Check if product has discount (for future use)
    public function getHasDiscountAttribute()
    {
        return isset($this->discount_percentage) && $this->discount_percentage > 0;
    }
}
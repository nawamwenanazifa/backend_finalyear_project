<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Support\Facades\Storage;

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
        'price'               => 'decimal:2',
        'rating'              => 'decimal:1',
        'in_stock'            => 'boolean',
        'is_featured'         => 'boolean',
        'stock_quantity'      => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    // ── Image URL accessor ────────────────────────────────────────────────────
    // Always returns a full URL using APP_URL from .env so it works on
    // web (localhost), emulator (10.0.2.2), and production without any
    // hardcoded IPs or ports.
    // Set APP_URL=http://localhost:8000 in your .env file.
    public function getImageUrlAttribute(): string
    {
        if (empty($this->image)) {
            return '';
        }

        // If already a full URL, return as-is
        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }

        // Build URL from APP_URL in .env — no hardcoded 127.0.0.1
        $appUrl = rtrim(config('app.url'), '/');
        return $appUrl . '/storage/' . ltrim($this->image, '/');
    }

    // ── Relationships ─────────────────────────────────────────────────────────

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

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeLowStock($query)
    {
        return $query->where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', '<=', 0);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getFormattedPriceAttribute()
    {
        return 'UGX ' . number_format($this->price, 0);
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold && $this->stock_quantity > 0;
    }

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

    // ── Stock management ──────────────────────────────────────────────────────

    public function reduceStock(int $quantity): void
    {
        $this->stock_quantity -= $quantity;
        $this->in_stock = $this->stock_quantity > 0;
        $this->save();
    }

    public function increaseStock(int $quantity): void
    {
        $this->stock_quantity += $quantity;
        $this->in_stock = true;
        $this->save();
    }

    public function getHasDiscountAttribute()
    {
        return isset($this->discount_percentage) && $this->discount_percentage > 0;
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Category extends Model
{
    use HasFactory, Auditable;
    
    protected $fillable = [
        'name', 
        'icon', 
        'description',
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    
    public function activeProducts()
    {
        return $this->products()->where('in_stock', true);
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
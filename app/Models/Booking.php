<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Booking extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'service_type',
        'booking_date',
        'status',
        'phone',
        'email',
        'notes',
        'booking_reference',
    ];
    
    protected $casts = [
        'booking_date' => 'datetime',
    ];

    // Auto-generate booking reference on creation
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($booking) {
            if (empty($booking->booking_reference)) {
                $booking->booking_reference = 'BK-' . strtoupper(uniqid());
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>', now())->where('status', '!=', 'cancelled');
    }
    
    public function scopePast($query)
    {
        return $query->where('booking_date', '<', now());
    }
    
    public function getIsCancellableAttribute()
    {
        return $this->booking_date > now() && $this->status !== 'cancelled';
    }
    
    // Accessor for booking reference (fallback if null)
    public function getBookingReferenceAttribute($value)
    {
        return $value ?? '#BK-' . $this->id;
    }
}